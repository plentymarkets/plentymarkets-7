<?php

namespace Payone\Migrations;

use Payone\Models\Logins;
use Payone\Models\Settings;
use Payone\Repositories\LoginRepository;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Plugin\Log\Loggable;

class MigrateKeyToCredentialsTable
{
    use Loggable;

    public function run()
    {
        $database = pluginApp(DataBase::class);

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
                        $setting->value['PAYONE_PAYONE_INVOICE_SECURE']['key']
                    ]
                );

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
