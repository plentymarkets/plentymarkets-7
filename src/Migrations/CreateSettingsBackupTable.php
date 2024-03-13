<?php

namespace Payone\Migrations;


use Payone\Models\Settings;
use Payone\Models\SettingsBackup;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;
use Plenty\Modules\Plugin\Exceptions\MySQLMigrateException;

class CreateSettingsBackupTable
{
    /**
     * @var Migrate
     */
    private $migrate;

    /**
     * CreateMethodSettingsTable constructor.
     * @param Migrate $migrate
     */
    public function __construct(Migrate $migrate)
    {
        $this->migrate = $migrate;
    }

    /**
     * @throws MySQLMigrateException
     */
    public function run()
    {
        $this->migrate->createTable(SettingsBackup::class);

        /**
         * moving entries to the backup table
         */
        $database = pluginApp(DataBase::class);

        /** @var Settings[] $allSettings */
        $allSettings = $database->query(Settings::class)->get();

        foreach($allSettings as $setting) {
            /** @var SettingsBackup $backupSetting */
            $backupSetting = pluginApp(SettingsBackup::class);
            $backupSetting->pluginSetId = $setting->pluginSetId;
            $backupSetting->clientId = $setting->clientId;
            $backupSetting->value = $setting->value;
            $backupSetting->createdAt = $setting->createdAt;
            $backupSetting->updatedAt = $setting->updatedAt;
            $backupSetting->save($backupSetting);
        }
    }
}
