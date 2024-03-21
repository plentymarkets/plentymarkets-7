<?php

namespace Payone\Migrations;

use Payone\Models\Logins;
use Payone\Models\Settings;
use Payone\Repositories\LoginRepository;
use Payone\Services\SettingsService;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Plugin\CachingRepository;
use Plenty\Plugin\Log\Loggable;

class MigrateKeyToCredentialsTable
{
    use Loggable;

    public function run()
    {
        $cachingRepository = pluginApp(CachingRepository::class);
        $database = pluginApp(DataBase::class);

        /** @var Settings[] $allSettings */
        $allSettings = $database->query(Settings::class)->get();
        $this->getLogger(__METHOD__)->debug('Payone::General.objectData', $allSettings);
        /** @var LoginRepository $loginRepository */
        $loginRepository = pluginApp(LoginRepository::class);

        foreach ($allSettings as $setting) {
            try {
                $credentialData = pluginApp(Logins::class);
                $credentialData->key = $setting->value['key'];
                $credentialData->invoiceSecureKey = $setting->value['PAYONE_PAYONE_INVOICE_SECURE']['key'];
                $credentialsSettings = $loginRepository->save($credentialData);

                unset($setting->value['key'], $setting->value['PAYONE_PAYONE_INVOICE_SECURE']['key']);

                $setting->value['loginId'] = $credentialsSettings->id;
                $setting->save();
            } catch (\Exception $ex) {
                $this->getLogger(__METHOD__)->debug('Payone::General.objectData', $ex->getTrace());
            }
        }
    }
}
