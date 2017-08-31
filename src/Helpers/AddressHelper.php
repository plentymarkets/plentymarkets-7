<?php

namespace Payone\Helpers;

use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\Address as AddressModel;
use Plenty\Modules\Account\Address\Models\AddressRelationType;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Order\Models\Order;

/**
 * Class AddressHelper
 */
class AddressHelper
{
    /**
     * @var AddressRepositoryContract
     */
    private $addressRepo;

    /**
     * Address constructor.
     *
     * @param AddressRepositoryContract $addressRepo
     */
    public function __construct(AddressRepositoryContract $addressRepo)
    {
        $this->addressRepo = $addressRepo;
    }

    /**
     * @param Basket $basket
     *
     * @return AddressModel
     */
    public function getBasketBillingAddress(Basket $basket)
    {
        return $this->loadAddress($this->getBillingAddressIdFromCart($basket));
    }

    /**
     * @param Basket $basket
     *
     * @return AddressModel
     */
    public function getBasketShippingAddress(Basket $basket)
    {
        $addressId = $this->getShippingAddressIdFromCart($basket) ?
            $this->getShippingAddressIdFromCart($basket) : $this->getBillingAddressIdFromCart($basket);

        return $this->loadAddress($addressId);
    }

    /**
     * @param Order $order
     *
     * @return AddressModel
     */
    public function getOrderBillingAddress(Order $order)
    {
        return $this->loadAddress($this->getBillingAddressIdFromOrder($order));
    }

    /**
     * @param Order $order
     *
     * @return AddressModel
     */
    public function getOrderShippingAddress(Order $order)
    {
        return $this->loadAddress($this->getShippingAddressIdFromOrder($order));
    }

    /**
     * @param AddressModel $address
     *
     * @return array
     */
    public function getAddressData($address)
    {
        $data = [];

        if (!$address) {
            return $data;
        }

        $data = $address->toArray();
        $data['city'] = $address->town;
        $data['postCode'] = $address->postalCode;
        $data['countryCode'] = $address->country;
        $data['company'] = $address->companyName;
        $data['firstName'] = $address->name2;
        $data['lastName'] = $address->name3;
        $data['street'] = $address->address1;
        $data['countryCode'] = $address->country->isoCode2;
        $data['lang'] = $address->country->lang;
        $data['houseNumber'] = $address->address2;

        return $data;
    }

    /**
     * @param Basket $basket
     *
     * @return int
     */
    private function getShippingAddressIdFromCart(Basket $basket)
    {
        return $basket->customerShippingAddressId;
    }

    /**
     * @param Basket $basket
     *
     * @return int
     */
    private function getBillingAddressIdFromCart(Basket $basket)
    {
        return $basket->customerInvoiceAddressId;
    }

    /**
     * @param Order $order
     *
     * @return int|null
     */
    private function getShippingAddressIdFromOrder(Order $order)
    {
        foreach ($order->addressRelations as $relation) {
            if ($relation['typeId'] == AddressRelationType::DELIVERY_ADDRESS) {
                return $relation['addressId'];
            }
        }
    }

    /**
     * @param Order $order
     *
     * @return mixed
     */
    private function getBillingAddressIdFromOrder(Order $order)
    {
        foreach ($order->addressRelations as $relation) {
            if ($relation['typeId'] == AddressRelationType::BILLING_ADDRESS) {
                return $relation['addressId'];
            }
        }
    }

    /**
     * @param int $addressId
     *
     * @return AddressModel
     */
    private function loadAddress($addressId)
    {
        try {
            return $this->addressRepo->findAddressById($addressId);
        } catch (\Exception $e) {
            // Maybe not logged in anymore?
        }
    }
}