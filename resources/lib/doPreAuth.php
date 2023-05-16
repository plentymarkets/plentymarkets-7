<?php
require_once (__DIR__.'/vendor/autoload.php');

use PayoneApi\Api\Client;
use PayoneApi\Api\PostApi;
use PayoneApi\Lib\Version;
use PayoneApi\Request\ArraySerializer;
use PayoneApi\Request\PreAuthorization\RequestFactory;
use PayoneApi\Response\ClientErrorResponse;

try {
    if (class_exists('Payone\Tests\Integration\Mock\SdkRestApi')) {
        $sdkRestApi = Payone\Tests\Integration\Mock\SdkRestApi::class;
    } else {
        $sdkRestApi = \SdkRestApi::class;
    }
    $data = [];
    $data['basket'] = $sdkRestApi::getParam('basket');
    $data['basketItems'] = $sdkRestApi::getParam('basketItems');
    $data['shippingAddress'] = $sdkRestApi::getParam('shippingAddress');
    $data['context'] = $sdkRestApi::getParam('context');
    $data['order'] = $sdkRestApi::getParam('order');
    $data['customer'] = $sdkRestApi::getParam('customer');
    $data['shippingProvider'] = $sdkRestApi::getParam('shippingProvider');
    $data['systemInfo'] = $sdkRestApi::getParam('systemInfo');
    $data['redirect'] = $sdkRestApi::getParam('redirect');
    $data['pseudocardpan'] = $sdkRestApi::getParam('pseudocardpan');
    $data['sepaMandate'] = $sdkRestApi::getParam('sepaMandate');
    $data['bankAccount'] = $sdkRestApi::getParam('bankAccount');
    $data['amazonPayAuth'] = $sdkRestApi::getParam('amazonPayAuth');
    $data['klarnaAuthToken'] = $sdkRestApi::getParam('klarnaAuthToken');
    $data['klarnaWorkOrderId'] = $sdkRestApi::getParam('klarnaWorkOrderId');
    $data['successurl'] = $sdkRestApi::getParam('successurl');
    $data['errorurl'] = $sdkRestApi::getParam('errorurl');

    $paymentMethod = $sdkRestApi::getParam('paymentMethod');

    $request = RequestFactory::create($paymentMethod, $data);
    $serializer = new ArraySerializer();
    $client = new PostApi(new Client(), $serializer);
    $response = $client->doRequest($request);
} catch (Exception $e) {
    $errorResponse = new ClientErrorResponse(
        'SdkRestApi error: ' . $e->getMessage() . PHP_EOL .
        'Lib version: ' . Version::getVersion() . PHP_EOL .
        $e->getTraceAsString()
    );

    return $errorResponse->jsonSerialize();
}

if (!$response->getSuccess()) {
    $errorResponse = new ClientErrorResponse(
        'Request successful but response invalid. ' . PHP_EOL .
        'Lib version: ' . Version::getVersion() . PHP_EOL .
        'Message: ' . $response->getErrorMessage() . PHP_EOL .
        'Request was : ' . json_encode($serializer->serialize($request), JSON_PRETTY_PRINT) . PHP_EOL .
        'Response was: ' . json_encode($serializer->serialize($response), JSON_PRETTY_PRINT)
    );

    return $errorResponse->jsonSerialize();
}

return $response->jsonSerialize();
