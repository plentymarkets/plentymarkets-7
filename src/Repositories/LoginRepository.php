<?php

namespace Payone\Repositories;

use Exception;
use Payone\Models\Logins;
use Payone\PluginConstants;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Market\Credentials\Contracts\CredentialsRepositoryContract;
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
     * @param $id
     * @return Logins|null
     * @throws Throwable
     */
    public function getById($id)
    {
        /** @var AuthHelper $authHelper */
        $authHelper = pluginApp(AuthHelper::class);
        try {
            /** @var PaginatedResult $result */
            $result = $authHelper->processUnguarded(function () use ($id) {
                return $this->credentialRepository->search(
                    [
                        'market' => PluginConstants::NAME,
                        'id' => $id
                    ]
                );
            });

            $item = $result->getResult();
            if (!empty($item[0])) {
                $loginData = $item[0]->data;
                /** @var Logins $login */
                $login = pluginApp(Logins::class);
                $login->id = $item[0]->id;
                $login->key = $loginData['key'] ?? "";
                $login->invoiceSecureKey = $loginData['invoiceSecureKey'] ?? "";

                return $login;
            }
        } catch (Exception $ex) {
            $this->getLogger(__METHOD__)
                ->error(PluginConstants::NAME . "::General.getCredentialsById::$id", $ex->getMessage());
        }

        return null;
    }

    /**
     * Update or save a Login model.
     * If the id in the Login model is set than it will update the entry
     *
     * @param Logins $login
     * @return bool|Logins
     * @throws Throwable
     */
    public function save(Logins $login)
    {
        /** @var AuthHelper $authHelper */
        $authHelper = pluginApp(AuthHelper::class);

        $data['data'] = [
            'key' => $login->key,
            'invoiceSecureKey' => $login->invoiceSecureKey
        ];

        $data['status'] = 'active';
        $data['environment'] = 'production';
        $data['market'] = PluginConstants::NAME;

        if ($login->id) {
            try {
                $result = $authHelper->processUnguarded(function () use ($login, $data) {
                    return $this->credentialRepository->update($login->id, $data);
                });
            } catch (Exception $e) {
                $this->getLogger(__METHOD__)
                    ->error(PluginConstants::NAME . "::General.updateLoginError::{$login->id}", $e->getMessage());
                return false;
            }
        } else {
            try {
                $result = $authHelper->processUnguarded(function () use ($data) {
                    return $this->credentialRepository->create($data);
                });
            } catch (Exception $e) {
                $this->getLogger(__METHOD__)
                    ->error(PluginConstants::NAME . "::General.saveLoginError", $e->getMessage());
                return false;
            }
        }

        /** @var Logins $login */
        $login = pluginApp(Logins::class);
        $login->id = $result->id;
        $login->key = $result->data['key'] ?? "";
        $login->invoiceSecureKey = $result->data['invoiceSecureKey'] ?? "";

        return $login;
    }

    /**
     * @param $id
     * @return bool|mixed
     * @throws Throwable
     */
    public function delete($id)
    {
        try {
            /** @var AuthHelper $authHelper */
            $authHelper = pluginApp(AuthHelper::class);

            $authHelper->processUnguarded(function () use ($id) {
                return $this->credentialRepository->delete($id);
            });
        } catch (Exception $e) {
            $this->getLogger(__METHOD__)
                ->error(PluginConstants::NAME . "::General.deleteLoginError", $e->getMessage());
            return false;
        }

        return true;
    }
}
