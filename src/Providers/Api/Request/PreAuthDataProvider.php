<?php

namespace Payone\Providers\Api\Request;

use Payone\Methods\PayoneAmazonPayPaymentMethod;
use Payone\Methods\PayoneCCPaymentMethod;
use Payone\Methods\PayoneDirectDebitPaymentMethod;
use Payone\Methods\PayoneSofortPaymentMethod;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Order\Models\Order;
use Payone\Methods\PayoneKlarnaDirectBankTransferPaymentMethod;
use Payone\Methods\PayoneKlarnaDirectDebitPaymentMethod;
use Payone\Methods\PayoneKlarnaInstallmentsPaymentMethod;
use Payone\Methods\PayoneKlarnaInvoicePaymentMethod;
use Payone\Adapter\SessionStorage;

/**
 * Class PreAuthDataProvider
 */
class PreAuthDataProvider extends DataProviderAbstract implements DataProviderOrder, DataProviderBasket
{
    /**
     * @param string $paymentCode
     * @param Basket $basket
     * @param string|null $requestReference
     * @param int|null $clientId
     * @param int|null $pluginSetId
     * @return array
     * @throws \Exception
     */
    public function getDataFromBasket(string $paymentCode, Basket $basket, string $requestReference = null, int $clientId = null, int $pluginSetId = null): array
    {
        $requestParams = $this->getDefaultRequestData($paymentCode, $clientId, $pluginSetId);

        $requestParams['basket'] = $this->getBasketData($basket);

        $requestParams['basketItems'] = $this->getCartItemData($basket);
        $requestParams['shippingAddress'] = $this->addressHelper->getAddressData(
            $this->addressHelper->getBasketShippingAddress($basket)
        );
        $billingAddress = $this->addressHelper->getBasketBillingAddress($basket);
        $requestParams['billingAddress'] = $this->addressHelper->getAddressData(
            $billingAddress
        );
        $requestParams['customer'] = $this->getCustomerData($billingAddress, $basket->customerId);

        if ($paymentCode == PayoneSofortPaymentMethod::PAYMENT_CODE) {
            $requestParams['bankAccount'] = $this->getBankAccount();
            $requestParams['bankAccount']['country'] = $requestParams['billingAddress']['country'];
        }
        if ($paymentCode == PayoneDirectDebitPaymentMethod::PAYMENT_CODE) {
            $requestParams['bankAccount'] = $this->getBankAccount();
        }
        if ($this->paymentHasRedirect($paymentCode)) {
            $urls = $this->getRedirectUrls($basket->id);
            $requestParams['redirect'] = $urls;
            $requestParams['successurl'] = $urls['success'];
            $requestParams['errorurl'] = $urls['error'];
        }
        if ($paymentCode == PayoneCCPaymentMethod::PAYMENT_CODE) {
            $requestParams['ccCheck'] = $this->getCreditCardData()->jsonSerialize();
            $requestParams['pseudocardpan'] = $requestParams['ccCheck']['pseudocardpan'];
        }
        if ($paymentCode == PayoneDirectDebitPaymentMethod::PAYMENT_CODE) {
            $requestParams['sepaMandate'] = $this->getSepaMandateData();
        }
        if ($paymentCode == PayoneAmazonPayPaymentMethod::PAYMENT_CODE) {
            $requestParams['amazonPayAuth'] = $this->getAmazonPayData($basket->id, $basket->basketAmount, $basket->currency);
        }

        if ($paymentCode == PayoneKlarnaDirectDebitPaymentMethod::PAYMENT_CODE ||
            $paymentCode == PayoneKlarnaDirectBankTransferPaymentMethod::PAYMENT_CODE ||
            $paymentCode == PayoneKlarnaInstallmentsPaymentMethod::PAYMENT_CODE ||
            $paymentCode == PayoneKlarnaInvoicePaymentMethod::PAYMENT_CODE) {

            /** @var SessionStorage $sessionStorage */
            $sessionStorage = pluginApp(SessionStorage::class);


            $requestParams['klarnaAuthToken'] =$sessionStorage->getSessionValue('klarnaAuthToken');
            $requestParams['klarnaWorkOrderId'] =$sessionStorage->getSessionValue('klarnaWorkOrderId');
        }

        $requestParams['referenceId'] = $requestReference;
        $requestParams['shippingProvider'] = $this->getShippingProvider($basket->shippingProfileId);
        $this->validator->validate($requestParams);

        return $requestParams;
    }

    /**
     * @param string $paymentCode
     * @param Order $order
     * @param string|null $requestReference
     * @param int|null $clientId
     * @param int|null $pluginSetId
     * @return array
     * @throws \Exception
     */
    public function getDataFromOrder(string $paymentCode, Order $order, string $requestReference = null, int $clientId = null, int $pluginSetId = null): array
    {
        $requestParams = $this->getDefaultRequestData($paymentCode, $clientId, $pluginSetId);

        $requestParams['basket'] = $this->getBasketDataFromOrder($order);

        $requestParams['basketItems'] = $this->getOrderItemData($order);
        $requestParams['shippingAddress'] = $this->addressHelper->getAddressData(
            $this->addressHelper->getOrderShippingAddress($order)
        );
        $billingAddress = $this->addressHelper->getOrderBillingAddress($order);
        $requestParams['billingAddress'] = $this->addressHelper->getAddressData(
            $billingAddress
        );
        $requestParams['customer'] = $this->getCustomerData($billingAddress, $order->ownerId);

        if ($paymentCode == PayoneSofortPaymentMethod::PAYMENT_CODE) {
            $requestParams['bankAccount'] = $this->getBankAccount();
            $requestParams['bankAccount']['country'] = $requestParams['billingAddress']['country'];
        }
        if ($paymentCode == PayoneDirectDebitPaymentMethod::PAYMENT_CODE) {
            $requestParams['bankAccount'] = $this->getBankAccount();
        }
        if ($this->paymentHasRedirect($paymentCode)) {
            $requestParams['redirect'] = $this->getRedirectUrls();
        }
        if ($paymentCode == PayoneCCPaymentMethod::PAYMENT_CODE) {
            $requestParams['ccCheck'] = $this->getCreditCardData()->jsonSerialize();
            $requestParams['pseudocardpan'] = $requestParams['ccCheck']['pseudocardpan'];
        }
        if ($paymentCode == PayoneDirectDebitPaymentMethod::PAYMENT_CODE) {
            $requestParams['sepaMandate'] = $this->getSepaMandateData();
        }
        $requestParams['referenceId'] = $requestReference;
        $requestParams['shippingProvider'] = $this->getShippingProvider($order->shippingProfileId);

        $this->validator->validate($requestParams);

        return $requestParams;
    }
}
