<?php

// strict

namespace Payone\Methods;

use Payone\Services\SettingsService;

/**
 * Class PayoneInvoiceSecurePaymentMethod
 */
class PayoneInvoiceSecurePaymentMethod extends PaymentAbstract
{
    const PAYMENT_CODE = 'PAYONE_PAYONE_INVOICE_SECURE';

    /**
     * Can the delivery address be different from the invoice address?
     *
     * @return bool
     */
    public function canHandleDifferingDeliveryAddress(): bool
    {
        return false;
    }

    /**
     * Check if all settings for the payment method are set.
     *
     * @param SettingsService $settingsService
     * @return bool
     */
    public function validateSettings(SettingsService $settingsService): bool
    {
        $portalId = $settingsService->getPaymentSettingsValue('portalId', self::PAYMENT_CODE);
        $key = $settingsService->getPaymentSettingsValue('key', self::PAYMENT_CODE);

        // A separate portal ID and key must be set for this payment method
        return (!empty($portalId) && !empty($key));
    }

    /**
     * Is the payment method active for the given currency?
     *
     * @param $currency
     * @return bool
     */
    public function isActiveForCurrency($currency): bool
    {
        return $currency == 'EUR';
    }
}
