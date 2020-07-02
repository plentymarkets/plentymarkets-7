<?php

namespace Payone\Providers\Api\Request;


class GetInvoiceDataProvider extends DataProviderAbstract
{
    /**
     * {@inheritdoc}
     */
    public function getRequestData(string $paymentCode,
                                   string $requestReference = null,
                                   string $sequenceNumber = null,
                                   string $documentNumber = null)
    {
        $requestParams = $this->getDefaultRequestData($paymentCode);
        $requestParams['context']['documentNumber']   = $documentNumber     ;
        $this->validator->validate($requestParams);
        return $requestParams;
    }
}
