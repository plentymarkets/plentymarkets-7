<?php

namespace Payone\Providers\Api\Request;

use Plenty\Modules\Order\Models\Order;

/**
 * Class DebitDataProvider
 */
class DebitDataProvider extends DataProviderAbstract implements DataProviderOrder
{
    /**
     * @param string $paymentCode
     * @param Order $order
     * @param string|null $requestReference
     * @param int|null $clientId
     * @param int|null $pluginSetId
     * @return array
     */
    public function getDataFromOrder(string $paymentCode, Order $order, string $preAuthUniqueId, int $clientId = null, int $pluginSetId = null): array
    {
        $requestParams = $this->getDefaultRequestData($paymentCode, $clientId, $pluginSetId);
        $requestParams['context']['sequencenumber'] = $this->getSequenceNumber($order);
        $requestParams['basket'] = $this->getBasketDataFromOrder($order);
        $requestParams['basketItems'] = $this->getOrderItemData($order);
        $requestParams['order'] = $this->getOrderData($order);
        $requestParams['referenceId'] = $preAuthUniqueId;

        return $requestParams;
    }

    /**
     * @param $paymentCode
     * @param Order $order
     * @param Order $refund
     * @param $preAuthUniqueId
     * @param int|null $clientId
     * @param int|null $pluginSetId
     *
     * @return array
     */
    public function getPartialRefundData($paymentCode, Order $order, Order $refund, $preAuthUniqueId, int $clientId = null, int $pluginSetId = null): array
    {
        $requestParams = $this->getDefaultRequestData($paymentCode, $clientId, $pluginSetId);
        $requestParams['context']['sequencenumber'] = $this->getSequenceNumber($order);
        $requestParams['basket'] = $this->getBasketDataFromOrder($refund);
        $requestParams['basketItems'] = $this->getOrderItemData($order);
        $requestParams['order'] = $this->getOrderData($refund);
        $requestParams['referenceId'] = $preAuthUniqueId;

        $this->validator->validate($requestParams);

        return $requestParams;
    }
}
