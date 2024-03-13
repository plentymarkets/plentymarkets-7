<?php

namespace Payone\Migrations;

use Exception;
use Payone\Models\Settings;
use Payone\Services\SettingsService;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\PluginSet\Contracts\PluginSetRepositoryContract;
use Plenty\Plugin\CachingRepository;

class CleanupOfOrphansRows
{

    public function run()
    {
        $database = pluginApp(DataBase::class);

        /** @var Settings[] $allSettings */
        $allSettings = $database->query(Settings::class)->get();

        /** @var CachingRepository $cachingRepository */
        $cachingRepository = pluginApp(CachingRepository::class);

        /** @var PluginSetRepositoryContract $pluginSetRepo */
        $pluginSetRepo = pluginApp(PluginSetRepositoryContract::class);

        foreach ($allSettings as $setting) {
            $toDelete = false;
            // check if the plugin set assigned to the entry still exists in the system
            try {
                $pluginSetRepo->get($setting->pluginSetId);
            } catch (Exception) {
                $toDelete = true;
            }
            // delete current row and clear the cache
            if ($toDelete) {
                $pluginSetId = $setting->pluginSetId;
                $clientId = $setting->clientId;
                $setting->delete();
                $cachingRepository->forget(
                    SettingsService::CACHING_KEY_SETTINGS . '_' . $clientId . '_' . $pluginSetId
                );
            }
        }
    }
}
