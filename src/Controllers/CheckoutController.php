<?php

namespace Payone\Controllers;

use IO\Services\NotificationService;
use Payone\Adapter\Logger;
use Payone\Helpers\OrderHelper;
use Payone\Helpers\PaymentHelper;
use Payone\Helpers\SessionHelper;
use Payone\Helpers\ShopHelper;
use Payone\Methods\PayoneAmazonPayPaymentMethod;
use Payone\Models\BankAccount;
use Payone\Models\BankAccountCache;
use Payone\Models\CreditCardCheckResponse;
use Payone\Models\CreditCardCheckResponseRepository;
use Payone\Models\PaymentCache;
use Payone\Models\SepaMandateCache;
use Payone\PluginConstants;
use Payone\Services\AmazonPayService;
use Payone\Services\PaymentService;
use Payone\Services\SepaMandate;
use Payone\Validator\CardExpireDate;
use Payone\Views\ErrorMessageRenderer;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Webshop\Contracts\LocalizationRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Templates\Twig;
use Payone\Adapter\SessionStorage;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Payone\Methods\PaymentMethodServiceFactory;
use Payone\Methods\PayoneInvoiceSecurePaymentMethod;
use Payone\Adapter\Translator;
use Payone\Models\PaymentMethodContent;
use Payone\Views\PaymentRenderer;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Payone\Methods\PaymentAbstract;
use Payone\Methods\PayoneKlarnaDirectDebitPaymentMethod;
use Payone\Methods\PayoneKlarnaDirectBankTransferPaymentMethod;
use Payone\Methods\PayoneKlarnaInstallmentsPaymentMethod;
use Payone\Methods\PayoneKlarnaInvoicePaymentMethod;
use Payone\Services\KlarnaService;
use Payone\Models\Api\GenericPayment\StartSessionResponse;
use Payone\Models\Api\GenericPayment\ConfirmOrderReferenceResponse;
use Payone\Models\Api\GenericPayment\SetOrderReferenceDetailsResponse;


/**
 * Class CheckoutController
 */
class CheckoutController extends Controller
{
    /** @var SessionHelper */
    private $sessionHelper;

    /** @var ErrorMessageRenderer */
    private $renderer;
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var Response
     */
    private $response;

    /**
     * @var LocalizationRepositoryContract
     */
    private $localizationRepositoryContract;

    /**
     * CheckoutController constructor.
     *
     * @param SessionHelper $sessionHelper
     * @param ErrorMessageRenderer $renderer
     * @param Request $request
     * @param Logger $logger
     * @param Response $response
     * @param LocalizationRepositoryContract $localizationRepositoryContract
     */
    public function __construct(
        SessionHelper        $sessionHelper,
        ErrorMessageRenderer $renderer,
        Request              $request,
        Logger               $logger,
        Response             $response,
        LocalizationRepositoryContract $localizationRepositoryContract
    ) {
        $this->sessionHelper = $sessionHelper;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->logger = $logger;
        $this->response = $response;
        $this->localizationRepositoryContract = $localizationRepositoryContract;
    }

    /**
     * @param int $orderId
     * @param Response $response
     * @return \Symfony\Component\HttpFoundation\Response|void
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function reinitPayment(
        int $orderId,
        Response $response
    )
    {
        /** @var OrderHelper $orderHelper */
        $orderHelper = pluginApp(OrderHelper::class);

        $order = $orderHelper->getOrderByOrderId($orderId);

        $mopId = $order->methodOfPaymentId;
        /** @var Logger $logger */
        $logger = pluginApp(Logger::class);
        /** @var PaymentHelper $paymentHelper */
        $paymentHelper = pluginApp(PaymentHelper::class);

        /** @var PaymentService $paymentService */
        $paymentService = pluginApp(PaymentService::class);
        $paymentCode = $paymentHelper->getPaymentCodeByMop($mopId);

        /** @var PaymentAbstract $payment */
        $payment = PaymentMethodServiceFactory::create($paymentCode);

        $billingAddress = $order->billingAddress;

        if ($paymentCode == PayoneInvoiceSecurePaymentMethod::PAYMENT_CODE &&
            (!isset($billingAddress->birthday) || !strlen($billingAddress->birthday))) {

            /** @var Translator $translator */
            $translator = pluginApp(Translator::class);
            /** @var ShopHelper $shopHelper */
            $shopHelper = pluginApp(ShopHelper::class);
            $lang = $shopHelper->getCurrentLanguage();

            $dateOfBirthMissingMessage = $translator->trans('Payone::Template.missingDateOfBirth', [], $lang);
            throw new \Exception($dateOfBirthMissingMessage);
        }

        if ($paymentCode == PayoneAmazonPayPaymentMethod::PAYMENT_CODE) {
            /** @var AmazonPayService $amazonPayService */
            $amazonPayService = pluginApp(AmazonPayService::class);

            /** @var SetOrderReferenceDetailsResponse $setOrderRefResponse */
            $setOrderRefResponse = $amazonPayService->setOrderReferenceFromOrder($order);

            /** @var ConfirmOrderReferenceResponse $confirmOrderRefResponse */
            $confirmOrderRefResponse = $amazonPayService->confirmOrderReferenceFromOrder($order);

            $logger
                ->setIdentifier(__METHOD__)
                ->debug('AmazonPay.paymentMethodContent', [
                    "setOrderRefResponse" => (array)$setOrderRefResponse,
                    "confirmOrderRefResponse" => (array)$confirmOrderRefResponse
                ]);

            /** @var SessionStorage $sessionStorage */
            $sessionStorage = pluginApp(SessionStorage::class);

            /** @var Twig $twig */
            $twig = pluginApp(Twig::class);

            $html = $twig->render(
                PluginConstants::NAME . '::MyAccount.ConfirmationReinit',
                [
                    'success' => $confirmOrderRefResponse->getSuccess(),
                    'sellerId' => $sessionStorage->getSessionValue('sellerId'),
                    'amazonReferenceId' => $sessionStorage->getSessionValue('amazonReferenceId'),
                    'orderId' => $orderId,
                    'trailingSlash' => ShopHelper::getTrailingSlash()
                ]
            );

            return $response->json([
                'data' => $html,
                'paymentCode' => $paymentCode
            ], 200);
        }
        if (
            $paymentCode == PayoneKlarnaDirectDebitPaymentMethod::PAYMENT_CODE ||
            $paymentCode == PayoneKlarnaInvoicePaymentMethod::PAYMENT_CODE ||
            $paymentCode == PayoneKlarnaInstallmentsPaymentMethod::PAYMENT_CODE ||
            $paymentCode == PayoneKlarnaDirectBankTransferPaymentMethod::PAYMENT_CODE
        ) {
            /** @var KlarnaService $klarnaService */
            $klarnaService = pluginApp(KlarnaService::class);


            /** @var StartSessionResponse $sessionResponse */
            $sessionResponse = $klarnaService->startSessionFromOrder($paymentCode, $order);

            /** @var SessionStorage $sessionStorage */
            $sessionStorage = pluginApp(SessionStorage::class);

            $sessionStorage->setSessionValue('klarnaWorkOrderId', $sessionResponse->getKlarnaWorkOrderId());

            /** @var Twig $twig */
            $twig = pluginApp(Twig::class);
            $html = $twig->render(
                PluginConstants::NAME . '::MyAccount.KlarnaWidgetReinit',
                [
                    'client_token' => $sessionResponse->getKlarnaClientToken(),
                    'payment_method' => $sessionResponse->getKlarnaMethodIdentifier(),
                    'order' => $orderId,
                    'trailingSlash' => ShopHelper::getTrailingSlash()
                ]
            );

            return $response->json([
                'data' => $html,
                'paymentCode' => $paymentCode
            ], 200);
        }

        try {
            /** @var PaymentMethodContent $content */
            $content = pluginApp(PaymentMethodContent::class);
            $renderingType = $content->getPaymentContentType($paymentCode);

            switch ($renderingType) {
                case GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL:
                    $auth = $paymentService->openTransactionFromOrder($order);
                    return $response->json([
                        'data' => $auth->getRedirecturl(),
                        'paymentCode' => $paymentCode
                    ], 200);

                case GetPaymentMethodContent::RETURN_TYPE_CONTINUE:
                    $paymentService->openTransactionFromOrder($order);

                    /** @var ShopHelper $shopHelper */
                    $shopHelper = pluginApp(ShopHelper::class);
                    return $response->json([
                        'data' => $shopHelper->getPlentyDomain() . '/'. $this->localizationRepositoryContract->getLanguage() .'/place-order',
                        'paymentCode' => $paymentCode
                    ], 200);


                case  GetPaymentMethodContent::RETURN_TYPE_HTML:
                    /** @var PaymentRenderer $paymentRenderer */
                    $paymentRenderer = pluginApp(PaymentRenderer::class);
                    $html = $paymentRenderer->render($payment, '', $orderId);

                    return $response->json([
                        'data' => $html,
                        'paymentCode' => $paymentCode
                    ], 200);
            }
        } catch (\Exception $e) {
            $logger->logException($e);

        }
    }

    /**
     * @param PaymentService $paymentService
     * @param BasketRepositoryContract $basket
     *
     * @return string
     */
    public function doAuth(
        PaymentService           $paymentService,
        BasketRepositoryContract $basket
    )
    {
        $this->logger->setIdentifier(__METHOD__)
            ->debug('Controller.Checkout', $this->request->all());
        if (!$this->sessionHelper->isLoggedIn()) {
            return $this->getJsonErrors([
                'message' => 'Your session expired. Please login and start a new purchase.',
            ]);
        }
        try {
            $auth = $paymentService->openTransaction($basket->load());
            $this->logger->setIdentifier(__METHOD__)
                ->debug('Controller.Checkout', ['authResponse' => $auth]);
        } catch (\Exception $e) {
            return $this->getJsonErrors(['message' => $e->getMessage()]);
        }

        return $this->getJsonSuccess($auth);
    }

    /**
     * @param PaymentService $paymentService
     * @param int $orderId
     * @return string
     * @throws \Throwable
     */
    public function doAuthFromOrder(
        PaymentService $paymentService,
        int            $orderId
    )
    {

        /** @var OrderHelper $orderHelper */
        $orderHelper = pluginApp(OrderHelper::class);
        $order = $orderHelper->getOrderByOrderId($orderId);
        /** @var Logger $logger */
        $logger = pluginApp(Logger::class);
        $logger
            ->setIdentifier(__METHOD__)
            ->debug('AmazonPay.doAuthFromOrder', [
                "order" => $order,

            ]);
        try {
            $auth = $paymentService->openTransactionFromOrder($order);

            $mopId = $order->methodOfPaymentId;
            /** @var PaymentHelper $paymentHelper */
            $paymentHelper = pluginApp(PaymentHelper::class);
            $amazonPayMopId = $paymentHelper->getMopId(PayoneAmazonPayPaymentMethod::PAYMENT_CODE);

            if ($mopId == $amazonPayMopId) {
                $sessionStorage = pluginApp(SessionStorage::class);
                $sessionStorage->setSessionValue('clientId', null);
                $sessionStorage->setSessionValue('sellerId', null);
                $sessionStorage->setSessionValue('workOrderId', null);
                $sessionStorage->setSessionValue('accessToken', null);
                $sessionStorage->setSessionValue('amazonReferenceId', null);
            }
        } catch (\Exception $e) {
            return $this->getJsonErrors(['message' => $e->getMessage()]);
        }

        return $this->getJsonSuccess($auth);
    }

    /**
     * @param PaymentService $paymentService
     * @param BasketRepositoryContract $basket
     *
     * @return string
     */
    public function doKlarnaAuth(
        PaymentService           $paymentService,
        BasketRepositoryContract $basket
    )
    {
        $klarnaAuthToken = $this->request->get('authorization_token');
        /** @var SessionStorage $sessionStorage */
        $sessionStorage = pluginApp(SessionStorage::class);
        $sessionStorage->setSessionValue('klarnaAuthToken', $klarnaAuthToken);

        $this->logger->setIdentifier(__METHOD__)
            ->debug('Controller.Checkout', $this->request->all());
        if (!$this->sessionHelper->isLoggedIn()) {
            return $this->getJsonErrors([
                'message' => 'Your session expired. Please login and start a new purchase.',
            ]);
        }
        try {
            $auth = $paymentService->openTransaction($basket->load());
        } catch (\Exception $e) {
            return $this->getJsonErrors(['message' => $e->getMessage()]);
        }

        return $this->getJsonSuccess($auth);
    }

    /**
     * @param int $orderId
     * @param PaymentService $paymentService
     * @return string
     * @throws \Throwable
     */
    public function doKlarnaAuthForReinit(
        int $orderId,
        PaymentService $paymentService
    )
    {
        $klarnaAuthToken = $this->request->get('authorization_token');
        /** @var SessionStorage $sessionStorage */
        $sessionStorage = pluginApp(SessionStorage::class);
        $sessionStorage->setSessionValue('klarnaAuthToken', $klarnaAuthToken);

        $this->logger->setIdentifier(__METHOD__)
            ->debug('Controller.Checkout', $this->request->all());

        try {
            /** @var OrderRepositoryContract $orderContract */
            $orderContract = pluginApp(OrderRepositoryContract::class);

            /** @var \Plenty\Modules\Authorization\Services\AuthHelper $authHelper */
            $authHelper = pluginApp(AuthHelper::class);

            //guarded
            $order = $authHelper->processUnguarded(
                function () use ($orderContract, $orderId) {
                    //unguarded
                    return $orderContract->findOrderById($orderId);
                }
            );
            $auth = $paymentService->openTransactionFromOrder($order);

        } catch (\Exception $e) {
            return $this->getJsonErrors(['message' => $e->getMessage()]);
        }

        return $this->getJsonSuccess($auth);
    }

    /**
     * @param CreditCardCheckResponseRepository $repository
     * @param CreditCardCheckResponse $response
     * @param CardExpireDate $validator
     *
     * @return string
     */
    public function storeCCCheckResponse(
        CreditCardCheckResponseRepository $repository,
        CreditCardCheckResponse           $response,
        CardExpireDate                    $validator
    )
    {
        $this->logger->setIdentifier(__METHOD__)
            ->debug('Controller.Checkout', $this->request->all());
//        if (!$this->sessionHelper->isLoggedIn()) {
//            return $this->getJsonErrors(['message' => 'Your session expired. Please login and start a new purchase.']);
//        }
        $status = $this->request->get('status');
        if ($status !== 'VALID') {
            return $this->getJsonErrors(['message' => 'Credit card check failed.']);
        }
        try {
            $response->init(
                $this->request->get('status'),
                $this->request->get('pseudocardpan'),
                $this->request->get('truncatedcardpan'),
                $this->request->get('cardtype'),
                $this->request->get('cardexpiredate')
            );
            $validator->validate(\DateTime::createFromFormat('Y-m-d', $response->getCardexpiredate()));
            $repository->storeLastResponse($response);
        } catch (\Exception $e) {
            return $this->getJsonErrors(['message' => $e->getMessage()]);
        }

        return $this->getJsonSuccess($response);
    }

    /**
     * @param BankAccount $bankAccount
     * @param BankAccountCache $accountCache
     * @param SepaMandate $mandateService
     * @param SepaMandateCache $mandateCache
     * @param $orderId
     * @return string
     */
    public function storeAccountDataForReinit(
        $orderId,
        BankAccount $bankAccount,
        BankAccountCache $accountCache,
        SepaMandate $mandateService,
        SepaMandateCache $mandateCache
    )
    {

        /** @var OrderRepositoryContract $orderContract */
        $orderContract = pluginApp(OrderRepositoryContract::class);

        /** @var \Plenty\Modules\Authorization\Services\AuthHelper $authHelper */
        $authHelper = pluginApp(AuthHelper::class);

        //guarded
        $order = $authHelper->processUnguarded(
            function () use ($orderContract, $orderId) {
                //unguarded
                return $orderContract->findOrderById($orderId);
            }
        );

        $errors = [];


        $formData = [
            'holder' => $this->request->get('holder'),
            'iban' => $this->request->get('iban'),
            'bic' => $this->request->get('bic'),
        ];
        $this->logger->setIdentifier(__METHOD__)->debug('Controller.routeCalled', $this->request->all());

        foreach ($formData as $key => $value) {
            if (empty($formData[$key])) {
                $errors[$key] = true;
            }
        }

        if ($errors) {
            return $this->getJsonErrors($errors);
        }

        $accountCache->storeBankAccount(
            $bankAccount->init(
                $this->request->get('holder'),
                $this->request->get('iban'),
                $this->request->get('bic')
            )
        );

        try {
            $mandate = $mandateService->createMandateFromOrder($order);
        } catch (\Exception $e) {
            return $this->getJsonErrors([
                'message' => $this->renderer->render(
                    $e->getMessage()
                ),
            ]);
        }
        $sepaMandate = $mandate->getMandate();

        $mandateCache->store($sepaMandate);

        /** @var SessionStorage $sessionStorage */
        $sessionStorage = pluginApp(SessionStorage::class);
        $sessionStorage->setSessionValue('debitOrderId', $orderId);
        return $this->getJsonSuccess($sepaMandate);
    }

    /**
     * @param BankAccount $bankAccount
     * @param BankAccountCache $accountCache
     * @param SepaMandate $mandateService
     * @param SepaMandateCache $mandateCache
     * @param BasketRepositoryContract $basket
     *
     * @return string
     */
    public function storeAccountData(
        BankAccount              $bankAccount,
        BankAccountCache         $accountCache,
        SepaMandate              $mandateService,
        SepaMandateCache         $mandateCache,
        BasketRepositoryContract $basket
    )
    {
        $errors = [];

        if (!$this->sessionHelper->isLoggedIn()) {
            return $this->getJsonErrors([
                'message' => $this->renderer->render(
                    'Your session expired. Please login and start a new purchase.'
                ),
            ]);
        }

        $formData = [
            'holder' => $this->request->get('holder'),
            'iban' => $this->request->get('iban'),
            'bic' => $this->request->get('bic'),
        ];
        $this->logger->setIdentifier(__METHOD__)->debug('Controller.routeCalled', $this->request->all());

        foreach ($formData as $key => $value) {
            if (empty($formData[$key])) {
                $errors[$key] = true;
            }
        }

        if ($errors) {
            return $this->getJsonErrors($errors);
        }

        $accountCache->storeBankAccount(
            $bankAccount->init(
                $this->request->get('holder'),
                $this->request->get('iban'),
                $this->request->get('bic')
            )
        );

        try {
            $mandate = $mandateService->createMandate($basket->load());
        } catch (\Exception $e) {
            return $this->getJsonErrors([
                'message' => $this->renderer->render(
                    $e->getMessage()
                ),
            ]);
        }
        $sepaMandate = $mandate->getMandate();

        $mandateCache->store($sepaMandate);

        return $this->getJsonSuccess($sepaMandate);
    }

    /**
     * @param Twig $twig
     * @param SepaMandateCache $sepaMandateCache
     * @param ShopHelper $helper
     *
     * @return string
     */
    public function getSepaMandateStep(Twig $twig, SepaMandateCache $sepaMandateCache, ShopHelper $helper)
    {
        /** @var SessionStorage $sessionStorage */
        $sessionStorage = pluginApp(SessionStorage::class);
        $orderId = $sessionStorage->getSessionValue('debitOrderId');

        if (!$this->sessionHelper->isLoggedIn() && empty($orderId)) {
            return $this->getJsonErrors([
                'message' => $this->renderer->render(
                    'Your session expired.. Please login and start a new purchase.'
                ),
            ]);
        }

        try {
            $mandate = $sepaMandateCache->load();
            $html = $twig->render(PluginConstants::NAME . '::Partials.PaymentForm.PAYONE_PAYONE_DIRECT_DEBIT_MANDATE', [
                'mandate' => $mandate,
                'locale' => $helper->getCurrentLocale(),
                'orderId' => $orderId,
                'trailingSlash' => ShopHelper::getTrailingSlash()
            ]);
        } catch (\Exception $e) {
            return $this->getJsonErrors([
                'message' => $this->renderer->render(
                    $e->getMessage()
                ),
            ]);
        }

        return $this->getJsonSuccess(
            [
                'html' => $html,
            ]
        );
    }

    /**
     * @param BasketRepositoryContract $basketReopo
     * @param PaymentHelper $helper
     *
     * @return string
     */
    public function checkoutSuccess(BasketRepositoryContract $basketReopo, PaymentHelper $helper, PaymentCache $paymentCache)
    {
        $this->logger->setIdentifier(__METHOD__);

        $this->logger->debug('Controller.Success', $this->request->all());
        $transactionBasketId = $this->request->get('transactionBasketId');

        if (strlen($transactionBasketId)) {
            $storedBasketId = $paymentCache->getActiveBasketId();
            if ($storedBasketId === null) {

                /** @var BasketRepositoryContract $bascketRepo */
                $bascketRepo = pluginApp(BasketRepositoryContract::class);
                $basketContent = $bascketRepo->load()->toArray();
                $this->logger->debug('Controller.Success', ['bascket' => $basketContent]);

                return $this->response->redirectTo('confirmation');
            }
            if ($storedBasketId != $transactionBasketId) {
                return $this->response->redirectTo('payone/error' . ShopHelper::getTrailingSlash());
            }
        } else {
            return $this->response->redirectTo('payone/error' . ShopHelper::getTrailingSlash());
        }
        $basket = $basketReopo->load();
        if (!$helper->isPayonePayment($basket->methodOfPaymentId)) {
            return $this->response->redirectTo('payone/error' . ShopHelper::getTrailingSlash());
        }

        $paymentCache->resetActiveBasketId();
        return $this->response->redirectTo($this->localizationRepositoryContract->getLanguage().'/place-order');
    }

    /**
     * @param BasketRepositoryContract $basketReopo
     * @param PaymentHelper $helper
     * @param PaymentCache $paymentCache
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function checkoutSuccessForReinit(BasketRepositoryContract $basketReopo, PaymentHelper $helper, PaymentCache $paymentCache)
    {

        $this->logger->setIdentifier(__METHOD__);
        $this->logger->debug('Controller.Success', $this->request->all());

        return $this->response->redirectTo($this->localizationRepositoryContract->getLanguage() . '/place-order');
    }

    /**
     * @param NotificationService $notificationService
     * @param ErrorMessageRenderer $messageRenderer
     *
     * @return string
     */
    public function redirectWithNotice(
        NotificationService  $notificationService,
        ErrorMessageRenderer $messageRenderer
    )
    {
        $this->logger->setIdentifier(__METHOD__);
        $this->logger->debug('Controller.redirecting');

        //info would be enought but is not shown in frontend
        $notificationService->error($messageRenderer->render('Payone::Template.orderErrorMessage'));

        return $this->response->redirectTo('checkout');
    }

    /**
     * @param null $data
     *
     * @return string
     */
    private function getJsonSuccess($data = null)
    {
        return $this->response->json(['success' => true, 'message' => null, 'data' => $data]);
    }

    /**
     * @param $errors
     *
     * @return string
     */
    private function getJsonErrors($errors)
    {
        $data = [];
        $data['success'] = false;
        $data['errors'] = $errors;

        return $this->response->json($data, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param BasketRepositoryContract $basketReopo
     * @param PaymentHelper $helper
     *
     * @return string
     */
    public function backRedirect(BasketRepositoryContract $basketReopo, PaymentHelper $helper, PaymentCache $paymentCache)
    {
        $this->logger->setIdentifier(__METHOD__);

        $paymentCache->resetActiveBasketId();
        return $this->response->redirectTo($this->localizationRepositoryContract->getLanguage() . '/place-order');
    }
}
