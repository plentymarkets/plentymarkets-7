<?php

namespace Payone\Migrations;

use Payone\Methods\PayoneInvoiceSecurePaymentMethod;
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
        $database = pluginApp(DataBase::class);
        $cachingRepository = pluginApp(CachingRepository::class);

        /** @var Settings[] $allSettings */
        $allSettings = $database->query(Settings::class)->get();
        /** @var LoginRepository $loginRepository */
        $loginRepository = pluginApp(LoginRepository::class);

        foreach ($allSettings as $setting) {
            try {
                $credentialData = pluginApp(
                    Logins::class,
                    [
                        null,
                        $setting->value['key'],
                        $setting->value['payoneMethods'][PayoneInvoiceSecurePaymentMethod::PAYMENT_CODE]['key']
                    ]
                );

                $credentialsSettings = $loginRepository->save($credentialData);

                unset($setting->value['key'], $setting->value['payoneMethods'][PayoneInvoiceSecurePaymentMethod::PAYMENT_CODE]['key']);

                $setting->value['loginId'] = $credentialsSettings->id;
                $setting->save();

                $cachingRepository->forget(
                    SettingsService::CACHING_KEY_SETTINGS . '_' . $setting->clientId . '_' . $setting->pluginSetId
                );
            } catch (\Throwable $ex) {
                $this->getLogger(__METHOD__)->debug('Payone::General.objectData', $ex->getTrace());
            }
        }
    }
}
