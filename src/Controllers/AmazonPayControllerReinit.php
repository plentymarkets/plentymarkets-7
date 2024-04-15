<?php

namespace Payone\Controllers;


use Payone\Adapter\Logger;
use Payone\Adapter\SessionStorage;
use Payone\Helpers\LoginHelper;
use Payone\Helpers\OrderHelper;
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
use Plenty\Modules\Order\Address\Contracts\OrderAddressRepositoryContract;
use Plenty\Modules\Webshop\Contracts\LocalizationRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Templates\Twig;

class AmazonPayControllerReinit extends AmazonPayController
{

    /** @var Api */
    private $api;

    /** @var GenericPaymentDataProvider */
    private $dataProvider;

    /** @var Logger */
    private $logger;

    /** @var OrderHelper */
    private $orderHelper;

    /**
     * @param Api $api
     * @param GenericPaymentDataProvider $dataProvider
     * @param Logger $logger
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        Api $api,
        GenericPaymentDataProvider $dataProvider,
        Logger $logger,
        OrderHelper $orderHelper
    ) {
        $this->api = $api;
        $this->dataProvider = $dataProvider;
        $this->logger = $logger;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param Twig $twig
     * @param Response $response
     * @param SessionStorage $sessionStorage
     * @param int $orderId
     * @param PaymentHelper $paymentHelper
     * @return string|\Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function getAmazonPayLoginWidgetReinit(
        Twig $twig,
        Response $response,
        SessionStorage $sessionStorage,
        int $orderId,
        PaymentHelper $paymentHelper
    ) {
        $order = $this->orderHelper->getOrderByOrderId($orderId);

        $selectedPaymentId = $order->methodOfPaymentId;
        $amazonPayMopId = $paymentHelper->getMopId(PayoneAmazonPayPaymentMethod::PAYMENT_CODE);

        $requestParams = $this->dataProvider->getGetConfigRequestData(
            PayoneAmazonPayPaymentMethod::PAYMENT_CODE,
            $order->amount->currency
        );

        $clientId = $sessionStorage->getSessionValue('clientId');
        $sellerId = $sessionStorage->getSessionValue('sellerId');
        $workOrderId = $sessionStorage->getSessionValue('workOrderId');

        if (strlen($clientId) <= 0 || strlen($sellerId) <= 0 || strlen($workOrderId) <= 0) {
            /** @var LoginHelper $loginHelper */
            $loginHelper = pluginApp(LoginHelper::class);
            $this->logger
                ->setIdentifier(__METHOD__)
                ->debug('AmazonPay.configLoginButton', [
                    'configResponse' => $loginHelper->cleanupLogs($requestParams)
                ]);

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

        return $twig->render(PluginConstants::NAME . '::MyAccount.AmazonPayLoginReinit', [
            'orderId' => $orderId,
            'selectedPaymentId' => $selectedPaymentId,
            'amazonPayMopId' => $amazonPayMopId,
            'content' => $content,
            'trailingSlash' => ShopHelper::getTrailingSlash()
        ]);
    }

    /**
     * @param Twig $twig
     * @param PaymentHelper $paymentHelper
     * @param int $orderId
     * @param Request $request
     * @param SessionStorage $sessionStorage
     * @return string
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderWidgetsReinit(
        Twig $twig,
        PaymentHelper $paymentHelper,
        int $orderId,
        Request $request,
        SessionStorage $sessionStorage
    ): string {
        $order = $this->orderHelper->getOrderByOrderId($orderId);


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
            'currency' => $order->amount->currency
        ];
        $amazonPayMopId = $paymentHelper->getMopId(PayoneAmazonPayPaymentMethod::PAYMENT_CODE);

        $this->logger
            ->setIdentifier(__METHOD__)
            ->debug('AmazonPay.renderWidgetsReinit', [
                "content" => (array)$content
            ]);

        return $twig->render(PluginConstants::NAME . '::MyAccount.AmazonPayWidgetsReinit', [
            'orderId' => $orderId,
            'content' => $content,
            'accessToken' => $accessToken,
            'workOrderId' => $workdOrderId,
            'amazonPayMopId' => $amazonPayMopId,
            'trailingSlash' => ShopHelper::getTrailingSlash()
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param int $orderId
     * @param SessionStorage $sessionStorage
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     */
    public function getOrderReferenceReinit(
        Request $request,
        Response $response,
        int $orderId,
        SessionStorage $sessionStorage
    ): \Symfony\Component\HttpFoundation\Response {
        try {
            $amazonReferenceId = $request->get('amazonReferenceId');
            $sessionStorage->setSessionValue('amazonReferenceId', $amazonReferenceId);

            $workOrderId = $sessionStorage->getSessionValue('workOrderId');
            $accessToken = $sessionStorage->getSessionValue('accessToken');

            $order = $this->orderHelper->getOrderByOrderId($orderId);

            /** @var GenericPaymentDataProvider $genericPaymentDataProvider */
            $genericPaymentDataProvider = pluginApp(GenericPaymentDataProvider::class);
            $requestParams = $genericPaymentDataProvider->getGetOrderReferenceDetailsRequestData(
                PayoneAmazonPayPaymentMethod::PAYMENT_CODE,
                $workOrderId,
                $accessToken,
                $amazonReferenceId,
                $order->amount->currency,
                $order->amount->invoiceTotal
            );
            /** @var LoginHelper $loginHelper */
            $loginHelper = pluginApp(LoginHelper::class);
            $this->logger
                ->setIdentifier(__METHOD__)
                ->debug('AmazonPay.getOrderReference', [
                    "requestParams" => $loginHelper->cleanupLogs($requestParams)
                ]);
            /** @var GetOrderReferenceDetailsResponse $orderReferenceResponse */
            $orderReferenceResponse = $this->api->doGenericPayment(
                GenericPayment::ACTIONTYPE_GETORDERREFERENCEDETAILS,
                $requestParams
            );

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


            /** @var OrderAddressRepositoryContract $orderAddressRepo */
            $orderAddressRepo = pluginApp(OrderAddressRepositoryContract::class);

            $orderAddressRepo->addOrderAddress($shippingAddress->id, $orderId, 2);
            $orderAddressRepo->addOrderAddress($billingAddress->id, $orderId, 1);

            $responseData['success'] = $orderReferenceResponse->getSuccess();

            return $response->json($responseData, 200);
        } catch (\Exception $exception) {
            $this->logger
                ->setIdentifier(__METHOD__)
                ->error('AmazonPay.getOrderReference', $exception);
        }
    }
}
