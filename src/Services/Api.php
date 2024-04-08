<?php

namespace Payone\Services;

use Payone\Adapter\Logger;
use Payone\Helpers\LoginHelper;
use Payone\Models\Api\AuthResponse;
use Payone\Models\Api\AuthResponseFactory;
use Payone\Models\Api\GenericPayment\GenericPaymentResponseFactory;
use Payone\Models\Api\GenericPayment\GetConfigurationResponse;
use Payone\Models\Api\GetInvoiceResponse;
use Payone\Models\Api\GetInvoiceResponseFactory;
use Payone\Models\Api\ManagemandateResponse;
use Payone\Models\Api\ManagemandateResponseFactory;
use Payone\Models\Api\PreAuthResponse;
use Payone\Models\Api\PreAuthResponseFactory;
use Payone\Models\Api\Response;
use Payone\Models\Api\ResponseFactory;
use Payone\PluginConstants;
use Payone\Providers\Api\Request\Models\GenericPayment;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;

/**
 * Class Api
 */
class Api
{
    const REQUEST_MODE_TEST = 'test';
    const REQUEST_MODE_LIVE = 'live';

    const REQUEST_TYPE_AUTH = 'Auth';
    const REQUEST_TYPE_PRE_AUTH = 'PreAuth';
    const REQUEST_TYPE_RE_AUTH = 'ReAuth';
    const REQUEST_TYPE_CAPTURE = 'Capture';
    const REQUEST_TYPE_REVERSAL = 'Reversal';
    const REQUEST_TYPE_REFUND = 'Refund';
    const REQUEST_TYPE_CALCULATION = 'Calculation';
    const REQUEST_TYPE_DEBIT = 'Debit';
    const REQUEST_TYPE_MANAGEMANDATE = 'Managemandate';
    const REQUEST_TYPE_INVOICE = 'GetDocument';
    const REQUEST_TYPE_GENERIC_PAYMENT = 'GenericPayment';

    /**
     * @var LibraryCallContract
     */
    private $libCall;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Api constructor.
     *
     * @param LibraryCallContract $libCall
     * @param Logger $logger
     */
    public function __construct(
        LibraryCallContract $libCall,
        Logger $logger
    ) {
        $this->libCall = $libCall;
        $this->logger = $logger;
    }

    /**
     * @param $requestParams
     *
     * @return AuthResponse
     * @throws \Exception
     *
     */
    public function doAuth($requestParams): AuthResponse
    {
        $this->logger->setIdentifier(__METHOD__);
        $response = $this->doLibCall((self::REQUEST_TYPE_AUTH), $requestParams);

        $responseObject = AuthResponseFactory::create($response);

        $this->logger->addReference(Logger::PAYONE_REQUEST_REFERENCE, $responseObject->getTransactionID());
        $this->logger->debug('Api.' . $this->getCallAction(self::REQUEST_TYPE_AUTH), $response);

        return $responseObject;
    }

    /**
     * @param string $call request type
     * @param $requestParams
     *
     * @return array
     */
    public function doLibCall($call, $requestParams): array
    {
        $this->logger->debug('Api.' . $this->getCallAction($call), $requestParams);

        try {
            $response = $this->libCall->call(
                PluginConstants::NAME . '::' . $this->getCallAction($call),
                $requestParams
            );
        } catch (\Exception $e) {
            // something unexpected happened
            $response = ['errorMessage' => $e->getMessage()];
        }
        if (isset($response['error'])) {
            //sdk error
            $response = ['errorMessage' => json_encode($response)];
        }

        $success = $response['success'] ?? false;
        if (!$success) {// log all errors including successful but invalid requests
            $this->logger->error('Api.' . $this->getCallAction($call), $response);
        }

        return $response;
    }

    /**
     * @param string $requestType
     *
     * @return string
     */
    private function getCallAction($requestType): string
    {
        return 'do' . $requestType;
    }

    /**
     * @param $requestParams
     *
     * @return PreAuthResponse
     * @throws \Exception
     *
     */
    public function doPreAuth($requestParams): PreAuthResponse
    {
        $this->logger->setIdentifier(__METHOD__);
        $response = $this->doLibCall((self::REQUEST_TYPE_PRE_AUTH), $requestParams);
        $responseObject = PreAuthResponseFactory::create($response);

        $this->logger->addReference(Logger::PAYONE_REQUEST_REFERENCE, $responseObject->getTransactionID());
        $this->logger->debug('Api.' . $this->getCallAction(self::REQUEST_TYPE_PRE_AUTH), $response);

        return $responseObject;
    }

    /**
     * @param $requestParams
     *
     * @return Response
     * @throws \Exception
     *
     */
    public function doReversal($requestParams): Response
    {
        $this->logger->setIdentifier(__METHOD__);
        $response = $this->doLibCall((self::REQUEST_TYPE_REVERSAL), $requestParams);

        $responseObject = ResponseFactory::create($response);

        $this->logger->addReference(Logger::PAYONE_REQUEST_REFERENCE, $responseObject->getTransactionID());
        $this->logger->debug('Api.' . $this->getCallAction(self::REQUEST_TYPE_REVERSAL), $response);

        return $responseObject;
    }

    /**
     * @param $requestParams
     *
     * @return Response
     * @throws \Exception
     *
     */
    public function doCapture($requestParams): Response
    {
        $this->logger->setIdentifier(__METHOD__);
        $response = $this->doLibCall((self::REQUEST_TYPE_CAPTURE), $requestParams);

        $responseObject = ResponseFactory::create($response);

        $this->logger->addReference(Logger::PAYONE_REQUEST_REFERENCE, $responseObject->getTransactionID());
        $this->logger->debug('Api.' . $this->getCallAction(self::REQUEST_TYPE_CAPTURE), $response);

        return $responseObject;
    }

    /**
     * @param $requestParams
     *
     * @return Response
     * @throws \Exception
     *
     */
    public function doRefund($requestParams): Response
    {
        $this->logger->setIdentifier(__METHOD__);
        $response = $this->doLibCall((self::REQUEST_TYPE_REFUND), $requestParams);

        $responseObject = ResponseFactory::create($response);

        $this->logger->addReference(Logger::PAYONE_REQUEST_REFERENCE, $responseObject->getTransactionID());
        $this->logger->debug('Api.' . $this->getCallAction(self::REQUEST_TYPE_AUTH), $response);

        return $responseObject;
    }

    /**
     * @param $requestParams
     *
     * @return Response
     */
    public function doReAuth($requestParams): Response
    {
        $this->logger->setIdentifier(__METHOD__);
        $response = $this->doLibCall((self::REQUEST_TYPE_RE_AUTH), $requestParams);

        $responseObject = ResponseFactory::create($response);

        $this->logger->addReference(Logger::PAYONE_REQUEST_REFERENCE, $responseObject->getTransactionID());
        $this->logger->debug('Api.' . $this->getCallAction(self::REQUEST_TYPE_AUTH), $response);

        return $responseObject;
    }

    /**
     * @param $requestParams
     *
     * @return Response
     */
    public function doDebit($requestParams): Response
    {
        $this->logger->setIdentifier(__METHOD__);
        $response = $this->doLibCall((self::REQUEST_TYPE_DEBIT), $requestParams);

        $responseObject = ResponseFactory::create($response);

        $this->logger->addReference(Logger::PAYONE_REQUEST_REFERENCE, $responseObject->getTransactionID());
        $this->logger->debug('Api.' . $this->getCallAction(self::REQUEST_TYPE_AUTH), $response);

        return $responseObject;
    }

    /**
     * @param $requestParams
     *
     * @return Response
     */
    public function doManagemandate($requestParams): ManagemandateResponse
    {
        $this->logger->setIdentifier(__METHOD__);
        $response = $this->doLibCall((self::REQUEST_TYPE_MANAGEMANDATE), $requestParams);

        $responseObject = ManagemandateResponseFactory::create($response);

        $this->logger->addReference(Logger::PAYONE_REQUEST_REFERENCE, $responseObject->getTransactionID());
        $this->logger->debug('Api.' . $this->getCallAction(self::REQUEST_TYPE_MANAGEMANDATE), $response);

        return $responseObject;
    }

    /**
     * @param $requestParams
     *
     * @return Response
     */
    public function doGetInvoice($requestParams): GetInvoiceResponse
    {
        $this->logger->setIdentifier(__METHOD__);
        $response = $this->doLibCall((self::REQUEST_TYPE_INVOICE), $requestParams);
        $responseObject = GetInvoiceResponseFactory::create($response);
        $this->logger->addReference(Logger::PAYONE_REQUEST_REFERENCE, $responseObject->getTransactionID());
        $this->logger->debug('Api.' . $this->getCallAction(self::REQUEST_TYPE_INVOICE), $response);
        return $responseObject;
    }

    /**
     * @param string $actionType
     * @param array $requestParams
     * @return mixed
     */
    public function doGenericPayment(string $actionType, array $requestParams)
    {
        $response = $this->doLibCall(self::REQUEST_TYPE_GENERIC_PAYMENT, $requestParams);
        $responseObject = GenericPaymentResponseFactory::create($actionType, $response);
        /** @var LoginHelper $loginHelper */
        $loginHelper = pluginApp(LoginHelper::class);
        $this->logger
            ->setIdentifier(__METHOD__)
            ->addReference('requestType', self::REQUEST_TYPE_GENERIC_PAYMENT)
            ->debug('AmazonPay.apiCall', [
                'actionType' => $actionType,
                'requestParams' => $loginHelper->cleanupLogs($requestParams),
                'response' => $response,
                'responseObject' => $responseObject
            ]);

        return $responseObject;
    }
}
