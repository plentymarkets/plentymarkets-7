<?php

namespace PayoneApi\Request\Authorization;

use Faker\Provider\Payment;
use PayoneApi\Request\AuthorizationRequestAbstract;
use PayoneApi\Request\GenericAuthRequestFactory;
use PayoneApi\Request\Parts\BankAccount;
use PayoneApi\Request\Parts\CartFactory;
use PayoneApi\Request\Parts\RedirectUrls;
use PayoneApi\Request\Parts\SepaMandate;
use PayoneApi\Request\Parts\ShippingAddress;
use PayoneApi\Request\PaymentTypes;
use PayoneApi\Request\RequestFactoryContract;
use PayoneApi\Request\Types;

class RequestFactory implements RequestFactoryContract
{
    protected static $requestType = Types::AUTHORIZATION;

    /**
     * @param string $paymentMethod
     * @param array $data
     * @param string|null $referenceId
     *
     * @throws \Exception
     *
     * @return AuthorizationRequestAbstract
     */
    public static function create(string $paymentMethod, array $data, string $referenceId = null)
    {
        $genericAuthRequest = GenericAuthRequestFactory::create(static::$requestType, $data);

        switch ($paymentMethod) {
            case PaymentTypes::PAYONE_INVOICE:
                return new Invoice($genericAuthRequest);
            case PaymentTypes::PAYONE_INVOICE_SECURE:
                return new InvoiceSecure($genericAuthRequest, CartFactory::create($data));
            case PaymentTypes::PAYONE_PRE_PAYMENT:
                return new PrePayment($genericAuthRequest);
            case PaymentTypes::PAYONE_CASH_ON_DELIVERY:
                return new CashOnDelivery(
                    $genericAuthRequest,
                    $data['shippingProvider']['name']
                );
            case PaymentTypes::PAYONE_CREDIT_CARD:

                return  new Creditcard(
                    $genericAuthRequest,
                    self::createUrls($data['redirect']),
                    $data['pseudocardpan'],
                    $data['successurl'] ?? '',
                    $data['errorurl'] ?? ''
                );

            case PaymentTypes::PAYONE_DIRECT_DEBIT:
                $sepaMandateData = $data['sepaMandate'];
                $sepaMandate = new SepaMandate(
                    $sepaMandateData['identification'],
                    $sepaMandateData['dateofsignature'],
                    $sepaMandateData['iban'],
                    $sepaMandateData['bic'],
                    $sepaMandateData['bankcountry']
                );

                return new DirectDebit(
                    $genericAuthRequest,
                    $sepaMandate
                );
            case PaymentTypes::PAYONE_PAY_PAL:

                return new PayPal(
                    $genericAuthRequest,
                    self::createUrls($data['redirect'])
                );
            case PaymentTypes::PAYONE_SOFORT:
                $bankAccountData = $data['bankAccount'];
                $bankAccount = new BankAccount(
                    $bankAccountData['country'],
                    $bankAccountData['holder'],
                    $bankAccountData['iban'],
                    $bankAccountData['bic']
                );

                return new Sofort(
                    $genericAuthRequest,
                    self::createUrls($data['redirect']),
                    $bankAccount
                );
            case PaymentTypes::PAYONE_ON_LINE_BANK_TRANSFER:
                $bankAccountData = $data['bankAccount'];
                $bankAccount = new BankAccount(
                    $bankAccountData['country'],
                    $bankAccountData['holder'],
                    $bankAccountData['iban'],
                    $bankAccountData['bic']
                );

                return new OnlineBankTransfer(
                    $genericAuthRequest,
                    self::createUrls($data['redirect']),
                    $bankAccount
                );

            case PaymentTypes::PAYONE_PAYDIREKT:
                $customerAddressData = $data['shippingAddress'];
                $shippingAddress = new ShippingAddress(
                    $customerAddressData['firstname'],
                    $customerAddressData['lastname'],
                    $customerAddressData['street'],
                    $customerAddressData['addressaddition'],
                    $customerAddressData['postalCode'],
                    $customerAddressData['town'],
                    $customerAddressData['country']
                );

                return new Paydirekt(
                    $genericAuthRequest,
                    self::createUrls($data['redirect']),
                    $shippingAddress
                );
            case PaymentTypes::PAYONE_AMAZON_PAY:
                $amazonPayAuth = $data['amazonPayAuth'];
                return new AmazonPay(
                    $genericAuthRequest,
                    self::createUrls($data['redirect']),
                    $amazonPayAuth['amount'],
                    $amazonPayAuth['workOrderId'],
                    $amazonPayAuth['reference'],
                    $amazonPayAuth['currency'],
                    $amazonPayAuth['amazonReferenceId']
                );
            case PaymentTypes::PAYONE_KLARNA_INVOICE || PaymentTypes::PAYONE_KLARNA_INSTALLMENTS
                || PaymentTypes::PAYONE_KLARNA_DIRECT_DEBIT || PaymentTypes::PAYONE_KLARNA_DIRECT_BANK :
                $klarnaAuthToken= $data['klarnaAuthToken'];
                $klarnaWorkOrderId = $data['klarnaWorkOrderId'];
                $cart = null;
                $cart = CartFactory::create($data);
                $customerAddressData = $data['shippingAddress'];
                $shippingAddress = new ShippingAddress(
                    $customerAddressData['firstname'],
                    $customerAddressData['lastname'],
                    $customerAddressData['street'],
                    $customerAddressData['addressaddition'],
                    $customerAddressData['postalCode'],
                    $customerAddressData['town'],
                    $customerAddressData['country']
                );
                return new Klarna(
                    $genericAuthRequest,
                    self::createUrls($data['redirect']),
                    $klarnaWorkOrderId,
                    $klarnaAuthToken,
                    $paymentMethod,
                    $cart,
                    $shippingAddress,
                    $data['customer']['email'],
                    $data['customer']['title'],
                    $data['customer']['telephonenumber']

                );
        }
        throw new \Exception('Unimplemented payment method ' . $paymentMethod);
    }

    /**
     * @param $redirectData
     *
     * @return RedirectUrls
     */
    private static function createUrls($redirectData)
    {
        return new RedirectUrls($redirectData['success'], $redirectData['error'], $redirectData['back']);
    }
}
