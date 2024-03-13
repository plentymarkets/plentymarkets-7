<?php

namespace Payone\Repositories;

use Carbon\Carbon;
use Exception;
use Payone\Helpers\PayoneHelper;
use Payone\Models\Logins;
use Payone\Models\Settings;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Market\Credentials\Contracts\CredentialsRepositoryContract;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Plugin\Log\Loggable;
use Plenty\Repositories\Models\PaginatedResult;
use Throwable;

class LoginRepository
{
    use Loggable;

    /** @var AuthHelper $authHelper */
    private $authHelper;

    /** @var CredentialsRepositoryContract */
    private $credentialRepository;

    public function __construct(AuthHelper $authHelper, CredentialsRepositoryContract $credentialsRepository)
    {
        $this->authHelper = $authHelper;
        $this->credentialRepository = $credentialsRepository;
    }

    /**
    * @param array $data
    * @return false|mixed
    * @throws Throwable
     */
    public function create(array $data)
    {
        $credentialsData['data'] = $data;
        $credentialsData['status'] = 'active';
        $credentialsData['environment'] = 'production';
        $credentialsData['market'] = PayoneHelper::PLUGIN_NAME;
        try {
            return $this->authHelper->processUnguarded(function () use ($data) {
                return $this->credentialRepository->create($credentialsData);
            });
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)
                ->error('Payone::General.saveLoginError', $e->getMessage());
            return false;
        }
    }

    /**
     * @param $id
     * @param string $settingKey
     * @return mixed|void|null
     * @throws Throwable
     */
    public function getValue($id, string $settingKey = '')
    {
        try {
            /** @var PaginatedResult $result */
            $result = $this->authHelper->processUnguarded(function () use ($id) {
                return $this->credentialRepository->search(
                    [
                        'market' => 'Payone',
                        'id' => $id
                    ]
                );
            });

            $item = $result->getResult();
            if (!empty($item[0])) {
                $loginData = $item[0]->data;
                if (!empty($settingKey)) {
                    return $loginData[$settingKey] ?? null;
                }
                $login = pluginApp(Logins::class);
                foreach ($loginData as $key => $value) {
                    $login->{$key} = $value ?? "";
                }

                return $login;
            }
        } catch (Exception $ex){
            $this->getLogger(__METHOD__)
                ->error(PayoneHelper::PLUGIN_NAME . "::General.getCredentialsById::$id", $ex->getMessage());
        }
        return null;
    }

    /**
     * @param array $data
     * @return Model
     */
    public function updateValues($loginId, array $data): Model
    {
        if (isset($data['mid'])) {
            $credentialsData['data']['mid'] = $data['mid'];
        }
        if (isset($data['portalId'])) {
            $credentialsData['data']['portalId'] = $data['portalId'];
        }
        if (isset($data['aid'])) {
            $credentialsData['data']['aid'] = $data['aid'];
        }
        if (isset($data['key'])) {
            $credentialsData['data']['key'] = $data['key'];
        }
        if (isset($data['mode'])) {
            $credentialsData['data']['mode'] = $data['mode'];
        }
        if (isset($data['authType'])) {
            $credentialsData['data']['authType'] = $data['authType'];
        }
        if (isset($data['userId'])) {
            $credentialsData['data']['userId'] = $data['userId'];
        }
        if (isset($data['PAYONE_PAYONE_INVOICE'])) {
            $credentialsData['data']['PAYONE_PAYONE_INVOICE'] = $data['PAYONE_PAYONE_INVOICE'];
        }
        if (isset($data['PAYONE_PAYONE_PAYDIREKT'])) {
            $credentialsData['data']['PAYONE_PAYONE_PAYDIREKT'] = $data['PAYONE_PAYONE_PAYDIREKT'];
        }
        if (isset($data['PAYONE_PAYONE_PAYOLUTION_INSTALLMENT'])) {
            $credentialsData['data']['PAYONE_PAYONE_PAYOLUTION_INSTALLMENT'] = $data['PAYONE_PAYONE_PAYOLUTION_INSTALLMENT'];
        }
        if (isset($data['PAYONE_PAYONE_PAY_PAL'])) {
            $credentialsData['data']['PAYONE_PAYONE_PAY_PAL'] = $data['PAYONE_PAYONE_PAY_PAL'];
        }
        if (isset($data['PAYONE_PAYONE_RATEPAY_INSTALLMENT'])) {
            $credentialsData['data']['PAYONE_PAYONE_RATEPAY_INSTALLMENT'] = $data['PAYONE_PAYONE_RATEPAY_INSTALLMENT'];
        }
        if (isset($data['PAYONE_PAYONE_SOFORT'])) {
            $credentialsData['data']['PAYONE_PAYONE_SOFORT'] = $data['PAYONE_PAYONE_SOFORT'];
        }
        if (isset($data['PAYONE_PAYONE_CASH_ON_DELIVERY'])) {
            $credentialsData['data']['PAYONE_PAYONE_CASH_ON_DELIVERY'] = $data['PAYONE_PAYONE_CASH_ON_DELIVERY'];
        }
        if (isset($data['PAYONE_PAYONE_PRE_PAYMENT'])) {
            $credentialsData['data']['PAYONE_PAYONE_PRE_PAYMENT'] = $data['PAYONE_PAYONE_PRE_PAYMENT'];
        }
        if (isset($data['PAYONE_PAYONE_CREDIT_CARD'])) {
            $credentialsData['data']['PAYONE_PAYONE_CREDIT_CARD'] = $data['PAYONE_PAYONE_CREDIT_CARD'];
        }
        if (isset($data['PAYONE_PAYONE_DIRECT_DEBIT'])) {
            $credentialsData['data']['PAYONE_PAYONE_DIRECT_DEBIT'] = $data['PAYONE_PAYONE_DIRECT_DEBIT'];
        }
        if (isset($data['PAYONE_PAYONE_INVOICE_SECURE'])) {
            $credentialsData['data']['PAYONE_PAYONE_INVOICE_SECURE'] = $data['PAYONE_PAYONE_INVOICE_SECURE'];
        }
        if (isset($data['PAYONE_PAYONE_AMAZON_PAY'])) {
            $credentialsData['data']['PAYONE_PAYONE_AMAZON_PAY'] = $data['PAYONE_PAYONE_AMAZON_PAY'];
        }
        if (isset($data['PAYONE_PAYONE_KLARNA_DIRECT_BANK'])) {
            $credentialsData['data']['PAYONE_PAYONE_KLARNA_DIRECT_BANK'] = $data['PAYONE_PAYONE_KLARNA_DIRECT_BANK'];
        }
        if (isset($data['PAYONE_PAYONE_KLARNA_DIRECT_DEBIT'])) {
            $credentialsData['data']['PAYONE_PAYONE_KLARNA_DIRECT_DEBIT'] = $data['PAYONE_PAYONE_KLARNA_DIRECT_DEBIT'];
        }
        if (isset($data['PAYONE_PAYONE_KLARNA_INSTALLMENTS'])) {
            $credentialsData['data']['PAYONE_PAYONE_KLARNA_INSTALLMENTS'] = $data['PAYONE_PAYONE_KLARNA_INSTALLMENTS'];
        }
        if (isset($data['PAYONE_PAYONE_KLARNA_INVOICE'])) {
            $credentialsData['data']['PAYONE_PAYONE_KLARNA_INVOICE'] = $data['PAYONE_PAYONE_KLARNA_INVOICE'];
        }
        if (isset($data['payoneMethods'])) {
            $credentialsData['data']['payoneMethods'] = $data['payoneMethods'];
        }

        try {
            $result = $this->authHelper->processUnguarded(function () use ($loginId, $credentialsData) {
                return $this->credentialRepository->update($loginId, $credentialsData);
            });
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)
                ->error(PayoneHelper::PLUGIN_NAME . "::General.updateLoginError::{$loginId}", $e->getMessage());
            return false;
        }
        $login = pluginApp(Logins::class);
        foreach ($result->data as $key => $value) {
            $login->{$key} = $value ?? "";
        }
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
    * @param $id
    * @return bool
    * @throws Throwable
     */
    public function delete($id)
    {
        try {
            $this->authHelper->processUnguarded(function () use ($id) {
                return $this->credentialRepository->delete($id);
            });
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)
                ->error("Payone::General.deleteLoginError", $e->getMessage());
            return false;
        }

        return true;
    }
}
