<?php

namespace Payone\Models;


use Carbon\Carbon;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * Class Settings
 *
 * @property int $id
 * @property int $clientId
 * @property int $pluginSetId
 * @property array $value
 * @property string $createdAt
 * @property string $updatedAt
 *
 *
 * @package Payone\Models
 */
class Settings extends Model
{
    public $id;
    public $clientId;
    public $pluginSetId;
    public $value = [];
    public $createdAt = '';
    public $updatedAt = '';

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return 'Payone::settings';
    }

    /**
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        $this->clientId = $data['clientId'];
        $this->pluginSetId = $data['pluginSetId'];
        $this->createdAt = (string)Carbon::now();

        $this->value = [
            'loginId' => $data['loginId'] ? ''
//            'mid' => $data['mid'],
//            'portalId' => $data['portalId'],
//            'aid' => $data['aid'],
//            'key' => $data['key'],
//            'mode' => $data['mode'],
//            'authType' => $data['authType'],
//            'userId' => $data['userId'],
//            'PAYONE_PAYONE_INVOICE' => $data['PAYONE_PAYONE_INVOICE'],
//            'PAYONE_PAYONE_PAYDIREKT' => $data['PAYONE_PAYONE_PAYDIREKT'],
//            'PAYONE_PAYONE_PAYOLUTION_INSTALLMENT' => $data['PAYONE_PAYONE_PAYOLUTION_INSTALLMENT'],
//            'PAYONE_PAYONE_PAY_PAL' => $data['PAYONE_PAYONE_PAY_PAL'],
//            'PAYONE_PAYONE_RATEPAY_INSTALLMENT' => $data['PAYONE_PAYONE_RATEPAY_INSTALLMENT'],
//            'PAYONE_PAYONE_SOFORT' => $data['PAYONE_PAYONE_SOFORT'],
//            'PAYONE_PAYONE_CASH_ON_DELIVERY' => $data['PAYONE_PAYONE_CASH_ON_DELIVERY'],
//            'PAYONE_PAYONE_PRE_PAYMENT' => $data['PAYONE_PAYONE_PRE_PAYMENT'],
//            'PAYONE_PAYONE_CREDIT_CARD' => $data['PAYONE_PAYONE_CREDIT_CARD'],
//            'PAYONE_PAYONE_DIRECT_DEBIT' => $data['PAYONE_PAYONE_DIRECT_DEBIT'],
//            'PAYONE_PAYONE_INVOICE_SECURE' => $data['PAYONE_PAYONE_INVOICE_SECURE'],
//            'PAYONE_PAYONE_KLARNA_DIRECT_BANK' => $data['PAYONE_PAYONE_KLARNA_DIRECT_BANK'],
//            'PAYONE_PAYONE_KLARNA_DIRECT_DEBIT' => $data['PAYONE_PAYONE_KLARNA_DIRECT_DEBIT'],
//            'PAYONE_PAYONE_KLARNA_INSTALLMENTS' => $data['PAYONE_PAYONE_KLARNA_INSTALLMENTS'],
//            'PAYONE_PAYONE_KLARNA_INVOICE' => $data['PAYONE_PAYONE_KLARNA_INVOICE']
        ];

        return $this->save();
    }

    /**
     * @param string $settingKey
     * @return array|mixed|null
     */
    public function getValue(string $settingKey = "")
    {
        if (!empty($settingKey)) {
            return $this->value[$settingKey] ?? null;
        }

        return $this->value;
    }

    /**
     * @param array $data
     * @return Model
     */
    public function updateValues(array $data): Model
    {
        if (isset($data['loginId'])) {
            $this->value['loginId'] = $data['loginId'];
        }
        return $this->save();
    }

    /**
     * @param Settings $newModel
     * @return Model
     */
    public function save(): Model
    {
        /** @var DataBase $database */
        $database = pluginApp(DataBase::class);
        $this->updatedAt = (string)Carbon::now();

        return $database->save($this);
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        /** @var DataBase $database */
        $database = pluginApp(DataBase::class);
        return $database->delete($this);
    }
}
