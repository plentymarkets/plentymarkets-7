<?php

namespace Payone\Models;

class Logins
{
    public int|null $id;
    public $mid;
    public $portalId;
    public $aid;
    public $key;
    public $mode;
    public $authType;
    public $userId;
    public $PAYONE_PAYONE_INVOICE;
    public $PAYONE_PAYONE_PAYDIREKT;
    public $PAYONE_PAYONE_PAYOLUTION_INSTALLMENT;
    public $PAYONE_PAYONE_PAY_PAL;
    public $PAYONE_PAYONE_RATEPAY_INSTALLMENT;
    public $PAYONE_PAYONE_SOFORT;
    public $PAYONE_PAYONE_CASH_ON_DELIVERY;
    public $PAYONE_PAYONE_PRE_PAYMENT;
    public $PAYONE_PAYONE_CREDIT_CARD;
    public $PAYONE_PAYONE_DIRECT_DEBIT;
    public $PAYONE_PAYONE_INVOICE_SECURE;
    public $PAYONE_PAYONE_KLARNA_DIRECT_BANK;
    public $PAYONE_PAYONE_KLARNA_DIRECT_DEBIT;
    public $PAYONE_PAYONE_KLARNA_INSTALLMENTS;
    public $PAYONE_PAYONE_KLARNA_INVOICE;

    /**
     * @param int|null $id
     * @param $mid
     * @param $portalId
     * @param $aid
     * @param $key
     * @param $mode
     * @param $authType
     * @param $userId
     * @param $PAYONE_PAYONE_INVOICE
     * @param $PAYONE_PAYONE_PAYDIREKT
     * @param $PAYONE_PAYONE_PAYOLUTION_INSTALLMENT
     * @param $PAYONE_PAYONE_PAY_PAL
     * @param $PAYONE_PAYONE_RATEPAY_INSTALLMENT
     * @param $PAYONE_PAYONE_SOFORT
     * @param $PAYONE_PAYONE_CASH_ON_DELIVERY
     * @param $PAYONE_PAYONE_PRE_PAYMENT
     * @param $PAYONE_PAYONE_CREDIT_CARD
     * @param $PAYONE_PAYONE_DIRECT_DEBIT
     * @param $PAYONE_PAYONE_INVOICE_SECURE
     * @param $PAYONE_PAYONE_KLARNA_DIRECT_BANK
     * @param $PAYONE_PAYONE_KLARNA_DIRECT_DEBIT
     * @param $PAYONE_PAYONE_KLARNA_INSTALLMENTS
     * @param $PAYONE_PAYONE_KLARNA_INVOICE
     */
    public function __construct(
        ?int $id,
        $mid,
        $portalId,
        $aid,
        $key,
        $mode,
        $authType,
        $userId,
        $PAYONE_PAYONE_INVOICE,
        $PAYONE_PAYONE_PAYDIREKT,
        $PAYONE_PAYONE_PAYOLUTION_INSTALLMENT,
        $PAYONE_PAYONE_PAY_PAL,
        $PAYONE_PAYONE_RATEPAY_INSTALLMENT,
        $PAYONE_PAYONE_SOFORT,
        $PAYONE_PAYONE_CASH_ON_DELIVERY,
        $PAYONE_PAYONE_PRE_PAYMENT,
        $PAYONE_PAYONE_CREDIT_CARD,
        $PAYONE_PAYONE_DIRECT_DEBIT,
        $PAYONE_PAYONE_INVOICE_SECURE,
        $PAYONE_PAYONE_KLARNA_DIRECT_BANK,
        $PAYONE_PAYONE_KLARNA_DIRECT_DEBIT,
        $PAYONE_PAYONE_KLARNA_INSTALLMENTS,
        $PAYONE_PAYONE_KLARNA_INVOICE
    ) {
        $this->id = $id;
        $this->mid = $mid;
        $this->portalId = $portalId;
        $this->aid = $aid;
        $this->key = $key;
        $this->mode = $mode;
        $this->authType = $authType;
        $this->userId = $userId;
        $this->PAYONE_PAYONE_INVOICE = $PAYONE_PAYONE_INVOICE;
        $this->PAYONE_PAYONE_PAYDIREKT = $PAYONE_PAYONE_PAYDIREKT;
        $this->PAYONE_PAYONE_PAYOLUTION_INSTALLMENT = $PAYONE_PAYONE_PAYOLUTION_INSTALLMENT;
        $this->PAYONE_PAYONE_PAY_PAL = $PAYONE_PAYONE_PAY_PAL;
        $this->PAYONE_PAYONE_RATEPAY_INSTALLMENT = $PAYONE_PAYONE_RATEPAY_INSTALLMENT;
        $this->PAYONE_PAYONE_SOFORT = $PAYONE_PAYONE_SOFORT;
        $this->PAYONE_PAYONE_CASH_ON_DELIVERY = $PAYONE_PAYONE_CASH_ON_DELIVERY;
        $this->PAYONE_PAYONE_PRE_PAYMENT = $PAYONE_PAYONE_PRE_PAYMENT;
        $this->PAYONE_PAYONE_CREDIT_CARD = $PAYONE_PAYONE_CREDIT_CARD;
        $this->PAYONE_PAYONE_DIRECT_DEBIT = $PAYONE_PAYONE_DIRECT_DEBIT;
        $this->PAYONE_PAYONE_INVOICE_SECURE = $PAYONE_PAYONE_INVOICE_SECURE;
        $this->PAYONE_PAYONE_KLARNA_DIRECT_BANK = $PAYONE_PAYONE_KLARNA_DIRECT_BANK;
        $this->PAYONE_PAYONE_KLARNA_DIRECT_DEBIT = $PAYONE_PAYONE_KLARNA_DIRECT_DEBIT;
        $this->PAYONE_PAYONE_KLARNA_INSTALLMENTS = $PAYONE_PAYONE_KLARNA_INSTALLMENTS;
        $this->PAYONE_PAYONE_KLARNA_INVOICE = $PAYONE_PAYONE_KLARNA_INVOICE;
    }


}
