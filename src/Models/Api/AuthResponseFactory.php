<?php

namespace Payone\Models\Api;

use Payone\Models\Api\Clearing\ClearingFactory;

/**
 * Class AuthResponseFactory
 */
class AuthResponseFactory
{
    /**
     * @param array $responseData
     *
     * @return AuthResponse
     */
    public static function create(array $responseData)
    {
        $clearing = ClearingFactory::create($responseData);
        /** @var AuthResponse $response */
        $response = pluginApp(AuthResponse::class);

        return $response->init(
            $responseData['success'] ?? false,
            $responseData['errorMessage'] ?? '',
            $responseData['transactionID'] ?? '',
            $clearing,
            $responseData['redirecturl'] ?? ''
        );
    }
}
