<?php

namespace Payone\Providers\Api\Request;

use Plenty\Modules\Order\Models\Order;

interface DataProviderOrder
{
    /**
     * @param string $paymentCode
     * @param Order $order
     * @param string|null $requestReference
     * @param int|null $clientId
     * @param int|null $pluginSetId
     * @return array
     */
    public function getDataFromOrder(string $paymentCode, Order $order, string $preAuthUniqueId, int $clientId = null, int $pluginSetId = null);
}
