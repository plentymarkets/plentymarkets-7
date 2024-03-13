<?php

namespace Payone\Migrations;

use Payone\Models\Settings;
use Payone\Repositories\LoginRepository;
use Plenty\Modules\Market\Credentials\Models\Credentials;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class MigrateKeyToCredentialsTable
{
    public function run()
    {
        $database = pluginApp(DataBase::class);

        /** @var Settings[] $allSettings */
        $allSettings = $database->query(Settings::class)->get();
        /** @var LoginRepository $loginRepository */
        $loginRepository = pluginApp(LoginRepository::class);

        foreach ($allSettings as $setting) {
            $newLogin = $loginRepository->create($setting->value);
                unset($setting->value);
                $setting->value['loginId'] = $newLogin->id;
                $setting->save();
        }
    }
}
