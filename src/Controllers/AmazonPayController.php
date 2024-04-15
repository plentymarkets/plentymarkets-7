<?php

namespace Payone\Controllers;

use IO\Services\BasketService;
use IO\Services\CheckoutService;
use Payone\Adapter\Logger;
use Payone\Adapter\SessionStorage;
use Payone\Helpers\LoginHelper;
use Payone\Helpers\PaymentHelper;
use Payone\Helpers\ShopHelper;
use Payone\Methods\PayoneAmazonPayPaymentMethod;
use Payone\Models\Api\GenericPayment\GetConfigurationResponse;
use Payone\Models\Api\GenericPayment\GetOrderReferenceDetailsResponse;
use Payone\PluginConstants;
use Payone\Providers\Api\Request\GenericPaymentDataProvider;
use Payone\Providers\Api\Request\Models\GenericPayment;
use Payone\Services\AmazonPayService;
use Payone\Services\Api;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Frontend\Contracts\Checkout;
use Plenty\Modules\Webshop\Contracts\ContactRepositoryContract;
use Plenty\Modules\Webshop\Contracts\LocalizationRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Templates\Twig;


class AmazonPayController extends Controller
{

    /** @var Api */
    private $api;

    /** @var GenericPaymentDataProvider */
    private $dataProvider;

    /** @var Logger */
    private $logger;

    /**
     * AmazonPayController constructor.
     * @param Api $api
     * @param GenericPaymentDataProvider $dataProvider
     * @param Logger $logger
     */
    public function __construct(
        Api $api,
        GenericPaymentDataProvider $dataProvider,
        Logger $logger
    ) {
        $this->api = $api;
        $this->dataProvider = $dataProvider;
        $this->logger = $logger;
    }

    /**
     * Renders login button for the frontend.
     *
     * @param Twig $twig
     * @param SessionStorage $sessionStorage
     * @param BasketRepositoryContract $basketRepository
     * @param PaymentHelper $paymentHelper
     * @return string|Response
     * @throws \Twig_Error_Loasder
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function getAmazonPayLoginWidget(
        Twig $twig,
        Response $response,
        SessionStorage $sessionStorage,
        BasketRepositoryContract $basketRepository,
        PaymentHelper $paymentHelper
    ) {
        $basket = $basketRepository->load();
        $selectedPaymentId = $basket->methodOfPaymentId;
        $amazonPayMopId = $paymentHelper->getMopId(PayoneAmazonPayPaymentMethod::PAYMENT_CODE);

        $requestParams = $this->dataProvider->getGetConfigRequestData(
            PayoneAmazonPayPaymentMethod::PAYMENT_CODE,
            $basket->currency
        );

        $clientId = $sessionStorage->getSessionValue('clientId');
        $sellerId = $sessionStorage->getSessionValue('sellerId');
        $workOrderId = $sessionStorage->getSessionValue('workOrderId');

        if (strlen($clientId) <= 0 || strlen($sellerId) <= 0 || strlen($workOrderId) <= 0) {
            /** Only load the configuration data if not already stored within the session */
            /** @var GetConfigurationResponse $configResponse */
            $configResponse = $this->api->doGenericPayment(GenericPayment::ACTIONTYPE_GETCONFIGURATION, $requestParams);

            $this->logger
                ->setIdentifier(__METHOD__)
                ->debug('AmazonPay.configLoginButton', [
                    'configResponse' => $configResponse
                ]);

            if (!$configResponse->getSuccess()) {
                return $response->json([
                    'error' => [
                        'message' => $configResponse->getErrorMessage()
                    ]
                ], 200);
            }

            $clientId = $configResponse->getClientId();
            $sellerId = $configResponse->getSellerId();
            $workOrderId = $configResponse->getWorkOrderId();

            $sessionStorage->setSessionValue('clientId', $clientId);
            $sessionStorage->setSessionValue('sellerId', $sellerId);
            $sessionStorage->setSessionValue('workOrderId', $workOrderId);
        }

        $this->logger
            ->setIdentifier(__METHOD__)
            ->debug('AmazonPay.configLoginButton', [
                'configResponse' => $configResponse
            ]);

        /** @var LocalizationRepositoryContract $localizationRepositoryContract */
        $localizationRepositoryContract = pluginApp(LocalizationRepositoryContract::class);
        $lang = $this->getLanguageCode($localizationRepositoryContract->getLanguage());

        $content = [
            'clientId' => $clientId,
            'sellerId' => $sellerId,
            'type' => "LwA",
            'color' => "Gold",
            'size' => "medium",
            'language' => $lang,
            'scopes' => "profile payments:widget payments:shipping_address payments:billing_address",
            'popup' => "true",
            'workOrderId' => $workOrderId
        ];

        $this->logger
            ->setIdentifier(__METHOD__)
            ->debug('AmazonPay.renderLoginWidget', [
                "content" => (array)$content
            ]);

        return $twig->render(PluginConstants::NAME . '::Checkout.AmazonPayLogin', [
            'selectedPaymentId' => $selectedPaymentId,
            'amazonPayMopId' => $amazonPayMopId,
            'content' => $content,
            'trailingSlash' => ShopHelper::getTrailingSlash()
        ]);
    }

    /**
     * Maps our language key into the specified language key from Amazon
     *
     * @param string $lang
     * @return string
     */
    public function getLanguageCode(string $lang): string
    {
        switch ($lang) {
            case 'de':
                $lang = 'de-DE';
                break;
            case 'en':
                $lang = 'en-GB';
                break;
            case 'es':
                $lang = 'es-ES';
                break;
            case 'fr':
                $lang = 'fr-FR';
                break;
            case 'it':
                $lang = 'it-IT';
                break;
            default:
                $lang = "en-GB";
        }
        return $lang;
    }

    /**
     * Renders the address book and wallet widget for the frontend.
     *
     * @param Twig $twig
     * @param PaymentHelper $paymentHelper
     * @param BasketRepositoryContract $basketRepository
     * @param Request $request
     * @param SessionStorage $sessionStorage
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderWidgets(
        Twig $twig,
        PaymentHelper $paymentHelper,
        BasketRepositoryContract $basketRepository,
        Request $request,
        SessionStorage $sessionStorage
    ): string {
        $basket = $basketRepository->load();

        // AccessToken in Request
        $accessToken = $request->get('accessToken');
        $workdOrderId = $request->get('workOrderId');

        $clientId = $sessionStorage->getSessionValue('clientId');
        $sellerId = $sessionStorage->getSessionValue('sellerId');

        $sessionStorage->setSessionValue('accessToken', $accessToken);
        $sessionStorage->setSessionValue('workOrderId', $workdOrderId);

        // SWAP containers here
        $content = [
            'clientId' => $clientId,
            'sellerId' => $sellerId,
            'addressBookScope' => "profile payments:widget payments:shipping_address payments:billing_address",
            'walletScope' => "profile payments:widget payments:shipping_address payments:billing_address",
            'currency' => $basket->currency
        ];
        $amazonPayMopId = $paymentHelper->getMopId(PayoneAmazonPayPaymentMethod::PAYMENT_CODE);

        $this->logger
            ->setIdentifier(__METHOD__)
            ->debug('AmazonPay.renderWidgets', [
                "content" => (array)$content
            ]);

        return $twig->render(PluginConstants::NAME . '::Checkout.AmazonPayWidgets', [
            'content' => $content,
            'accessToken' => $accessToken,
            'workOrderId' => $workdOrderId,
            'amazonPayMopId' => $amazonPayMopId,
            'trailingSlash' => ShopHelper::getTrailingSlash()
        ]);
    }

    /**
     * Loads the contact data via the selected address in the widget,
     * maps it to our address structure and sets it in the checkout.
     *
     * @param Request $request
     * @param Response $response
     * @param BasketRepositoryContract $basketRepositoryContract
     * @param Checkout $checkout
     * @param SessionStorage $sessionStorage
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getOrderReference(
        Request $request,
        Response $response,
        BasketRepositoryContract $basketRepositoryContract,
        Checkout $checkout,
        SessionStorage $sessionStorage
    ): \Symfony\Component\HttpFoundation\Response {
        try {
            $amazonReferenceId = $request->get('amazonReferenceId');
            $sessionStorage->setSessionValue('amazonReferenceId', $amazonReferenceId);

            $workOrderId = $sessionStorage->getSessionValue('workOrderId');
            $accessToken = $sessionStorage->getSessionValue('accessToken');

            $basket = $basketRepositoryContract->load();

            /** @var GenericPaymentDataProvider $genericPaymentDataProvider */
            $genericPaymentDataProvider = pluginApp(GenericPaymentDataProvider::class);
            $requestParams = $genericPaymentDataProvider->getGetOrderReferenceDetailsRequestData(
                PayoneAmazonPayPaymentMethod::PAYMENT_CODE,
                $workOrderId,
                $accessToken,
                $amazonReferenceId,
                $basket->currency,
                $basket->basketAmount
            );

            /** @var GetOrderReferenceDetailsResponse $orderReferenceResponse */
            $orderReferenceResponse = $this->api->doGenericPayment(
                GenericPayment::ACTIONTYPE_GETORDERREFERENCEDETAILS,
                $requestParams
            );
            /** @var LoginHelper $loginHelper */
            $loginHelper = pluginApp(LoginHelper::class);
            $this->logger
                ->setIdentifier(__METHOD__)
                ->debug('AmazonPay.getOrderReference', [
                    "workOrderId" => $workOrderId,
                    "amazonReferenceId" => $amazonReferenceId,
                    "accessToken" => $accessToken,
                    "requestParams" => $loginHelper->cleanupLogs($requestParams),
                    "orderReferenceResponse" => (array)$orderReferenceResponse
                ]);

            if (!$orderReferenceResponse->getSuccess()) {
                return $response->json([
                    'error' => [
                        'message' => $orderReferenceResponse->getErrorMessage()
                    ]
                ], 200);
            }

            /** @var AmazonPayService $amazonPayService */
            $amazonPayService = pluginApp(AmazonPayService::class);
            $shippingAddress = $amazonPayService->registerCustomerFromAmazonPay($orderReferenceResponse);
            $billingAddress = $amazonPayService->registerCustomerFromAmazonPay($orderReferenceResponse, true);

            $checkout->setCustomerShippingAddressId($shippingAddress->id);
            $checkout->setCustomerInvoiceAddressId($billingAddress->id);

            /** @var BasketService $basketService */
            $basketService = pluginApp(BasketService::class);

            /** @var ContactRepositoryContract $contactRepository */
            $contactRepository = pluginApp(ContactRepositoryContract::class);

            /** @var CheckoutService $checkoutService */
            $checkoutService = pluginApp(CheckoutService::class);

            $responseData['events']['AfterBasketChanged']['basket'] = $basketService->getBasketForTemplate();
            $responseData['events']['AfterBasketChanged']['showNetPrices'] = $contactRepository->showNetPrices();
            $responseData['events']['AfterBasketChanged']['basketItems'] = $basketService->getBasketItemsForTemplate(
                '',
                false
            );
            $responseData['events']['CheckoutChanged']['checkout'] = $checkoutService->getCheckout();

            $responseData['events']['CheckoutChanged']['AmazonPayAddress']['changed'] = true;
            $responseData['events']['CheckoutChanged']['AmazonPayAddress']['shippingAddress'] = $shippingAddress;
            $responseData['events']['CheckoutChanged']['AmazonPayAddress']['billingAddress'] = $billingAddress;

            $this->logger
                ->setIdentifier(__METHOD__)
                ->debug('AmazonPay.getOrderReference', [
                    "shippingAddress" => (array)$shippingAddress,
                    "billingAddress" => (array)$billingAddress,
                    "checkout" => (array)$checkout,
                    "checkoutViaService" => (array)$checkoutService->getCheckout()
                ]);

            return $response->json($responseData, 200);
        } catch (\Exception $exception) {
            $this->logger
                ->setIdentifier(__METHOD__)
                ->error('AmazonPay.getOrderReference', $exception);
        }
    }
}
