<?php

namespace Payone\Migrations;

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
                $credentialsSettings = $loginRepository->create($setting->value);
                $cachingRepository->forget(
                    SettingsService::CACHING_KEY_SETTINGS . '_' . $setting->clientId . '_' . $setting->pluginSetId
                );
                unset($credentialsSettings->data['key'], $credentialsSettings->data['PAYONE_PAYONE_INVOICE_SECURE']);
                $cachingRepository->add(
                    SettingsService::CACHING_KEY_SETTINGS . '_' . $setting->clientId . '_' . $setting->pluginSetId,
                    $credentialsSettings,
                    1440
                ); //One day

                $cachingSettings = $cachingRepository->get( SettingsService::CACHING_KEY_SETTINGS . '_' . $setting->clientId . '_' . $setting->pluginSetId);
                $this->getLogger(__METHOD__)->debug('Payone::General.objectData', $cachingSettings);
                $setting->value = null;
                $setting->value['loginId'] = $credentialsSettings->id;
                $setting->save();
            } catch (\Exception $ex) {
                $this->getLogger(__METHOD__)->debug('Payone::General.objectData', $ex->getTrace());
            }
        }
    }
}
