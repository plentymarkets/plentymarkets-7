<?php

namespace Payone\Services;

use Payone\Adapter\Logger;
use Payone\Adapter\SessionStorage;
use Payone\Helpers\LoginHelper;
use Payone\Methods\PayoneAmazonPayPaymentMethod;
use Payone\Models\Api\GenericPayment\ConfirmOrderReferenceResponse;
use Payone\Models\Api\GenericPayment\GetOrderReferenceDetailsResponse;
use Payone\Models\Api\GenericPayment\SetOrderReferenceDetailsResponse;
use Payone\PluginConstants;
use Payone\Providers\Api\Request\GenericPaymentDataProvider;
use Payone\Providers\Api\Request\Models\GenericPayment;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Account\Address\Models\AddressOption;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Order\Shipping\Countries\Models\Country;
use Plenty\Modules\Order\Shipping\Countries\Models\CountryState;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Modules\Order\Models\Order;

class AmazonPayService
{
    /** @var Api */
    private $api;

    /** @var GenericPaymentDataProvider */
    private $dataProvider;

    /** @var Logger */
    private $logger;
    private LoginHelper $loginHelper;

    /**
     * AmazonPayService constructor.
     * @param Api $api
     * @param GenericPaymentDataProvider $dataProvider
     * @param Logger $logger
     * @param LoginHelper $logger
     */
    public function __construct(
        Api $api,
        GenericPaymentDataProvider $dataProvider,
        Logger $logger,
        LoginHelper $loginHelper
    ) {
        $this->api = $api;
        $this->dataProvider = $dataProvider;
        $this->logger = $logger;
        $this->loginHelper = $loginHelper;
    }

    /**
     * @param GetOrderReferenceDetailsResponse $orderRefDetails
     * @param bool $billingAddress
     * @return mixed
     */
    public function registerCustomerFromAmazonPay(
        GetOrderReferenceDetailsResponse $orderRefDetails,
        $billingAddress = false
    ) {
        $this->logger
            ->setIdentifier(__METHOD__)
            ->debug('AmazonPay.registerCustomer', (array)$orderRefDetails);

        $addressData = [];
        $addressData['email'] = $orderRefDetails->getEmail() ?? "";

        if ($billingAddress) {
            $addressData['company'] = $orderRefDetails->getBillingCompany() ?? "";
            $addressData['firstName'] = $orderRefDetails->getBillingFirstname() ?? "";
            $addressData['lastName'] = $orderRefDetails->getBillingLastname() ?? "";
            $addressData['streetAndNumber'] = $orderRefDetails->getBillingStreet() ?? "";
            $addressData['postalCode'] = $orderRefDetails->getBillingZip() ?? "";
            $addressData['city'] = $orderRefDetails->getBillingCity() ?? "";
            $addressData['state'] = $orderRefDetails->getBillingState() ?? "";
            $addressData['countryId'] = $orderRefDetails->getBillingCountry() ?? "";
            $addressData['telNo'] = $orderRefDetails->getBillingTelephonenumber() ?? "";
        } else {
            $addressData['company'] = $orderRefDetails->getShippingCompany() ?? "";
            $addressData['firstName'] = $orderRefDetails->getShippingFirstname() ?? "";
            $addressData['lastName'] = $orderRefDetails->getShippingLastname() ?? "";
            $addressData['streetAndNumber'] = $orderRefDetails->getShippingStreet() ?? "";
            $addressData['postalCode'] = $orderRefDetails->getShippingZip() ?? "";
            $addressData['city'] = $orderRefDetails->getShippingCity() ?? "";
            $addressData['state'] = $orderRefDetails->getShippingState() ?? "";
            $addressData['countryId'] = $orderRefDetails->getShippingCountry() ?? "";
            $addressData['telNo'] = $orderRefDetails->getShippingTelephonenumber() ?? "";
        }

        $newAddress = $this->mapAmazonAddressToAddress($addressData);

        /** @var AddressRepositoryContract $contactAddressRepo */
        $addressRepo = pluginApp(AddressRepositoryContract::class);
        $createdAddress = $addressRepo->createAddress($newAddress->toArray());

        return $createdAddress;
    }


    /**
     * @param array $amazonAddress
     * @return Address
     */
    private function mapAmazonAddressToAddress(array $amazonAddress)
    {
        /** @var Address $address */
        $address = pluginApp(Address::class);

        if (strlen($amazonAddress['company'])) {
            $address->name1 = $amazonAddress['company'];
            /** @var AddressOption $addressOption */
            $addressOption = pluginApp(AddressOption::class);

            $addressOption->typeId = AddressOption::TYPE_CONTACT_PERSON;
            $addressOption->value = $amazonAddress['firstName'] . " " . $amazonAddress['lastName'];

            $address->options->push($addressOption->toArray());
        }

        $address->name2 = $amazonAddress['firstName'];
        $address->name3 = $amazonAddress['lastName'];

        /** @var CountryRepositoryContract $countryContract */
        $countryContract = pluginApp(CountryRepositoryContract::class);

        /** @var Country $country */
        $country = $countryContract->getCountryByIso($amazonAddress['countryId'], 'isoCode2');

        /** @var LibraryCallContract $libCall */
        $libCall = pluginApp(LibraryCallContract::class);
        $parsedAddress = $libCall->call(PluginConstants::NAME . '::addressParser', [
            'address' => $amazonAddress['streetAndNumber']
        ]);

        $address->address1 = $parsedAddress['address1'];
        $address->address2 = $parsedAddress['address2'];
        if (strlen($parsedAddress['address3'])) {
            $address->address3 = $parsedAddress['address3'];
        }
        $address->town = $amazonAddress['city'];
        $address->postalCode = $amazonAddress['postalCode'];
        $address->countryId = $country->id;

        if (strlen($amazonAddress['state'])) {
            /** @var CountryState $state */
            $state = $countryContract->getCountryStateByIso($country->id, $amazonAddress['state']);
            $address->state = $state;
            $address->stateId = $state->id;
        }

        if (strlen($amazonAddress['telNo'])) {
            /** @var AddressOption $addressOption */
            $addressOption = pluginApp(AddressOption::class);

            $addressOption->typeId = AddressOption::TYPE_TELEPHONE;
            $addressOption->value = $amazonAddress['telNo'];

            $address->options->push($addressOption->toArray());
        }
        if (strlen($amazonAddress['email'])) {
            /** @var AddressOption $addressOption */
            $addressOption = pluginApp(AddressOption::class);

            $addressOption->typeId = AddressOption::TYPE_EMAIL;
            $addressOption->value = $amazonAddress['email'];

            $address->options->push($addressOption->toArray());
        }

        return $address;
    }

    /**
     * @param Basket $basket
     * @return mixed
     */
    public function setOrderReference(Basket $basket)
    {
        /** @var SessionStorage $sessionStorage */
        $sessionStorage = pluginApp(SessionStorage::class);
        $workOrderId = $sessionStorage->getSessionValue('workOrderId');
        $amazonReferenceId = $sessionStorage->getSessionValue('amazonReferenceId');

        $requestParams = $this->dataProvider->getSetOrderReferenceDetailsRequestData(
            PayoneAmazonPayPaymentMethod::PAYMENT_CODE,
            $workOrderId,
            $amazonReferenceId,
            $basket->basketAmount,
            $basket->currency
        );

        /** @var SetOrderReferenceDetailsResponse $orderReferenceResponse */
        $orderReferenceResponse = $this->api->doGenericPayment(
            GenericPayment::ACTIONTYPE_SETORDERREFERENCEDETAILS,
            $requestParams
        );

        $this->logger
            ->setIdentifier(__METHOD__)
            ->debug('AmazonPay.setOrderReference', [
                "workOrderId" => $workOrderId,
                "amazonReferenceId" => $amazonReferenceId,
                "requestParams" => $this->loginHelper->cleanupLogs($requestParams),
                "setOrderReferenceResponse" => (array)$orderReferenceResponse
            ]);

        return $orderReferenceResponse;
    }

    /**
     * @param Order $order
     * @return SetOrderReferenceDetailsResponse
     * @throws \Exception
     */
    public function setOrderReferenceFromOrder(Order $order)
    {
        /** @var SessionStorage $sessionStorage */
        $sessionStorage = pluginApp(SessionStorage::class);
        $workOrderId = $sessionStorage->getSessionValue('workOrderId');
        $amazonReferenceId = $sessionStorage->getSessionValue('amazonReferenceId');

        if (!empty($order->amount->giftCardAmount)) {
            $amount = $order->amount->invoiceTotal - $order->amount->giftCardAmount;
        } else {
            $amount = $order->amount->invoiceTotal;
        }
        $requestParams = $this->dataProvider->getSetOrderReferenceDetailsRequestData(
            PayoneAmazonPayPaymentMethod::PAYMENT_CODE,
            $workOrderId,
            $amazonReferenceId,
            $amount,
            $order->amount->currency
        );

        /** @var SetOrderReferenceDetailsResponse $orderReferenceResponse */
        $orderReferenceResponse = $this->api->doGenericPayment(
            GenericPayment::ACTIONTYPE_SETORDERREFERENCEDETAILS,
            $requestParams
        );

        $this->logger
            ->setIdentifier(__METHOD__)
            ->debug('AmazonPay.setOrderReference', [
                "workOrderId" => $workOrderId,
                "amazonReferenceId" => $amazonReferenceId,
                "requestParams" => $this->loginHelper->cleanupLogs($requestParams),
                "setOrderReferenceResponse" => (array)$orderReferenceResponse
            ]);

        return $orderReferenceResponse;
    }


    /**
     * @param Basket $basket
     * @return mixed
     */
    public function confirmOrderReference(Basket $basket)
    {
        try {
            /** @var SessionStorage $sessionStorage */
            $sessionStorage = pluginApp(SessionStorage::class);

            $workOrderId = $sessionStorage->getSessionValue('workOrderId');
            $amazonReferenceId = $sessionStorage->getSessionValue('amazonReferenceId');

            $requestParams = $this->dataProvider->getConfirmOrderReferenceRequestData(
                PayoneAmazonPayPaymentMethod::PAYMENT_CODE,
                $workOrderId,
                $basket->id,
                $amazonReferenceId,
                $basket->basketAmount,
                $basket->currency,
                $basket->id
            );

            /** @var ConfirmOrderReferenceResponse $confirmOrderReferenceResponse */
            $confirmOrderReferenceResponse = $this->api->doGenericPayment(
                GenericPayment::ACTIONTYPE_CONFIRMORDERREFERENCE,
                $requestParams
            );

            $this->logger
                ->setIdentifier(__METHOD__)
                ->debug('AmazonPay.confirmOrderReference', [
                    "workOrderId" => $workOrderId,
                    "amazonReferenceId" => $amazonReferenceId,
                    "requestParams" => $this->loginHelper->cleanupLogs($requestParams),
                    "confirmOrderReferenceResponse" => (array)$confirmOrderReferenceResponse
                ]);

            return $confirmOrderReferenceResponse;
        } catch (\Exception $exception) {
            $this->logger
                ->setIdentifier(__METHOD__)
                ->error('AmazonPay.confirmOrderReference', $exception);

            return $exception;
        }
    }

    /**
     * @param Order $order
     * @return \Exception|ConfirmOrderReferenceResponse
     */
    public function confirmOrderReferenceFromOrder(Order $order)
    {
        try {
            /** @var SessionStorage $sessionStorage */
            $sessionStorage = pluginApp(SessionStorage::class);

            $workOrderId = $sessionStorage->getSessionValue('workOrderId');
            $amazonReferenceId = $sessionStorage->getSessionValue('amazonReferenceId');

            if (!empty($order->amount->giftCardAmount)) {
                $amount = $order->amount->invoiceTotal - $order->amount->giftCardAmount;
            } else {
                $amount = $order->amount->invoiceTotal;
            }
            $requestParams = $this->dataProvider->getConfirmOrderReferenceRequestDataForReinit(
                PayoneAmazonPayPaymentMethod::PAYMENT_CODE,
                $workOrderId,
                $order->id,
                $amazonReferenceId,
                $amount,
                $order->amount->currency,
                $order->id
            );

            /** @var ConfirmOrderReferenceResponse $confirmOrderReferenceResponse */
            $confirmOrderReferenceResponse = $this->api->doGenericPayment(
                GenericPayment::ACTIONTYPE_CONFIRMORDERREFERENCE,
                $requestParams
            );

            $this->logger
                ->setIdentifier(__METHOD__)
                ->debug('AmazonPay.confirmOrderReference', [
                    "workOrderId" => $workOrderId,
                    "amazonReferenceId" => $amazonReferenceId,
                    "requestParams" => $this->loginHelper->cleanupLogs($requestParams),
                    "confirmOrderReferenceResponse" => (array)$confirmOrderReferenceResponse
                ]);

            return $confirmOrderReferenceResponse;
        } catch (\Exception $exception) {
            $this->logger
                ->setIdentifier(__METHOD__)
                ->error('AmazonPay.confirmOrderReference', $exception);

            return $exception;
        }
    }
}
