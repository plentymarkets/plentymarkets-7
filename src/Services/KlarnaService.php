<?php

namespace Payone\Services;

use Payone\Adapter\Logger;
use Payone\Helpers\AddressHelper;
use Payone\Helpers\LoginHelper;
use Payone\Providers\Api\Request\GenericPaymentDataProvider;
use Payone\Providers\Api\Request\Models\GenericPayment;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Webshop\Contracts\LocalizationRepositoryContract;
use Plenty\Modules\Order\Models\Order;

/**
 * Class KlarnaService
 *
 * @package Payone\Services
 */
class KlarnaService
{
    /** @var Api */
    private $api;

    /** @var GenericPaymentDataProvider */
    private $dataProvider;

    /** @var Logger */
    private $logger;

    /**
     * KlarnaService constructor.
     * @param Api $api
     * @param GenericPaymentDataProvider $dataProvider
     * @param Logger $logger
     */
    public function __construct(
        Api $api,
        GenericPaymentDataProvider $dataProvider,
        Logger $logger
    ) {
        $this->api = $api;
        $this->dataProvider = $dataProvider;
        $this->logger = $logger;
    }

    /**
     * @param string $paymentCode
     * @param Order $order
     * @return mixed
     */
    public function startSessionFromOrder(string $paymentCode, Order $order)
    {
        /** @var AddressHelper $addressHelper */
        $addressHelper = pluginApp(AddressHelper::class);
        $billingAddress = $addressHelper->getOrderBillingAddress($order);
        // If shippingAddress is empty, then it's filled with the billingAddress data
        $shippingAddress = $addressHelper->getOrderShippingAddress($order);

        $requestParams = $this->dataProvider->getStartSessionRequestDataFromOrder(
            $paymentCode,
            $order
        );

        $requestParams['address'] = $this->createAddressData($billingAddress, $shippingAddress);

        $startSessionResponse = $this->api->doGenericPayment(GenericPayment::ACTIONTYPE_STARTSESSION, $requestParams);
        /** @var LoginHelper $loginHelper */
        $loginHelper = pluginApp(LoginHelper::class);
        $this->logger
            ->setIdentifier(__METHOD__)
            ->debug('Klarna.confirmOrderReference', [
                "requestParams" => $loginHelper->cleanupLogs($requestParams),
                "response" => $startSessionResponse
            ]);

        return $startSessionResponse;
    }

    /**
     * @param Address $billingAddress
     * @param Address $shippingAddress
     * @return array
     */
    private function createAddressData(Address $billingAddress, Address $shippingAddress)
    {
        /** @var LocalizationRepositoryContract $localizationRepository */
        $localizationRepository = pluginApp(LocalizationRepositoryContract::class);

        $addressData = [
            'country' => $billingAddress->country->isoCode2,
            'firstname' => $billingAddress->firstName,
            'lastname' => $billingAddress->lastName,
            'street' => $billingAddress->street . ' ' . $billingAddress->houseNumber,
            'zip' => $billingAddress->postalCode,
            'city' => $billingAddress->town,
            'email' => $billingAddress->email,
            'language' => $localizationRepository->getLanguage()
        ];

        // Adding optional fields
        if (empty($billingAddress->companyName)) {
            $addressData['b2b'] = false;
        } else {
            $addressData['b2b'] = true;
            $addressData['company'] = $billingAddress->companyName;
        }
        if (!empty($billingAddress->address3)) {
            $addressData['addressaddition'] = $billingAddress->address3;
        }
        if (!empty($billingAddress->state)) {
            $addressData['state'] = $billingAddress->state->isoCode3166;
        }
        if (!empty($billingAddress->gender)) {
            switch ($billingAddress->gender) {
                case  "male":
                    $addressData['gender'] = 'm';
                    break;
                case  "female":
                    $addressData['gender'] = 'f';
                    break;
                case  "diverse":
                    $addressData['gender'] = 'd';
                    break;
            }
        }//1992-02-21
        if (!empty($billingAddress->phone)) {
            $addressData['telephonenumber'] = $billingAddress->phone;
        }
        // todo: handling in serviceProvider vorab und hier basierend auf land setzen
        if (!empty($billingAddress->birthday)) {
            $addressData['birthday'] = date('Ymd', strtotime($billingAddress->birthday));
        }
        if (!empty($billingAddress->title)) {
            $addressData['title'] = $billingAddress->title;
        }
        if (!empty($billingAddress->phone)) {
            $addressData['phone'] = $billingAddress->phone;
        }

        // shippingAddress contains as fallback the billingAddress data
        if ($shippingAddress->id != $billingAddress->id) {
            if (!empty($shippingAddress->firstName)) {
                $addressData['shipping_firstname'] = $shippingAddress->firstName;
            }
            if (!empty($shippingAddress->lastName)) {
                $addressData['shipping_lastname'] = $shippingAddress->lastName;
            }
            if (!empty($shippingAddress->title)) {
                $addressData['shipping_title'] = $shippingAddress->title;
            }
            if (!empty($shippingAddress->companyName)) {
                $addressData['shipping_company'] = $shippingAddress->companyName;
            }
            if (!empty($shippingAddress->street) && !empty($shippingAddress->houseNumber) &&
                !empty($shippingAddress->postalCode) && !empty($shippingAddress->town)) {
                $addressData['shipping_street'] = $shippingAddress->street . ' ' . $shippingAddress->houseNumber;
                $addressData['shipping_zip'] = $shippingAddress->postalCode;
                $addressData['shipping_city'] = $shippingAddress->town;
                $addressData['shipping_country'] = $shippingAddress->country->isoCode2;
                if (!empty($shippingAddress->state)) {
                    $addressData['shipping_state'] = $shippingAddress->state->isoCode3166;
                }
            }
            if (!empty($shippingAddress->phone)) {
                $addressData['shipping_telephonenumber'] = $shippingAddress->phone;
            }
            if (!empty($shippingAddress->email)) {
                $addressData['shipping_email'] = $shippingAddress->email;
            }
        }

        return $addressData;
    }

    /**
     * @param string $paymentCode
     * @param Basket $basket
     * @return mixed
     */
    public function startSession(string $paymentCode, Basket $basket)
    {
        /** @var AddressHelper $addressHelper */
        $addressHelper = pluginApp(AddressHelper::class);
        $billingAddress = $addressHelper->getBasketBillingAddress($basket);
        // If shippingAddress is empty, then it's filled with the billingAddress data
        $shippingAddress = $addressHelper->getBasketShippingAddress($basket);

        $requestParams = $this->dataProvider->getStartSessionRequestData(
            $paymentCode,
            $basket
        );

        $requestParams['address'] = $this->createAddressData($billingAddress, $shippingAddress);

        $startSessionResponse = $this->api->doGenericPayment(GenericPayment::ACTIONTYPE_STARTSESSION, $requestParams);
        /** @var LoginHelper $loginHelper */
        $loginHelper = pluginApp(LoginHelper::class);
        $this->logger
            ->setIdentifier(__METHOD__)
            ->debug('AmazonPay.confirmOrderReference', [
                "requestParams" => $loginHelper->cleanupLogs($requestParams),
                "response" => $startSessionResponse
            ]);

        return $startSessionResponse;
    }
}
