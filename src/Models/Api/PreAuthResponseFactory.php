<?php

namespace Payone\Models\Api;

use Payone\Models\Api\Clearing\ClearingFactory;

/**
 * Class PreAuthResponseFactory
 */
class PreAuthResponseFactory
{
    /**
     * @param array $responseData
     *
     * @return PreAuthResponse
     */
    public static function create(array $responseData)
    {
        $clearing = ClearingFactory::create($responseData);
        /** @var PreAuthResponse $response */
        $response = pluginApp(PreAuthResponse::class);

        return $response->init(
            $responseData['success'] ?? false,
            $responseData['errorMessage'] ?? '',
            $responseData['transactionID'] ?? '',
            $clearing,
            $responseData['redirecturl'] ?? ''
        );
    }
}
