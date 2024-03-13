<?php

namespace Payone\Services;


use Carbon\Carbon;
use Payone\Models\Settings;
use Payone\Repositories\LoginRepository;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\PluginSet\Contracts\PluginSetRepositoryContract;
use Plenty\Plugin\Application;
use Plenty\Plugin\CachingRepository;

class SettingsService
{
    const CACHING_KEY_SETTINGS = 'payone_plugin_settings';

    /**
     * @var DataBase
     */
    protected $database;

    /**
     * @var CachingRepository
     */
    protected $cachingRepository;

    /**
     * SettingsService constructor.
     * @param DataBase $database
     * @param CachingRepository $cachingRepository
     */
    public function __construct(DataBase $database, CachingRepository $cachingRepository)
    {
        $this->database = $database;
        $this->cachingRepository = $cachingRepository;
    }

    /**
     * @param int|null $clientId
     * @param int|null $pluginSetId
     * @return Settings|null
     */
    public function getSettings(int $clientId = null, int $pluginSetId = null)
    {
        if (is_null($clientId)) {
            /** @var Application $application */
            $application = pluginApp(Application::class);
            $clientId = $application->getPlentyId();
        }

        if (is_null($pluginSetId)) {
            /** @var PluginSetRepositoryContract $pluginSetRepositoryContract */
            $pluginSetRepositoryContract = pluginApp(PluginSetRepositoryContract::class);
            $pluginSetId = $pluginSetRepositoryContract->getCurrentPluginSetId();
        }

        if (!$this->cachingRepository->has(self::CACHING_KEY_SETTINGS . '_' . $clientId . '_' . $pluginSetId)) {
            /** @var Settings[] $setting */
            $setting = $this->database->query(Settings::class)
                ->where('clientId', '=', $clientId)
                ->where('pluginSetId', '=', $pluginSetId)
                ->limit(1)
                ->get();
            if(is_array($setting) && $setting[0] instanceof Settings) {
                $this->cachingRepository->add(self::CACHING_KEY_SETTINGS . '_' . $clientId . '_' . $pluginSetId, $setting[0], 1440); //One day
                return $setting[0];
            }
        }

        return $this->cachingRepository->get(self::CACHING_KEY_SETTINGS . '_' . $clientId . '_' . $pluginSetId, null);
    }

    /**
     * @param string $settingsKey
     * @param int|null $clientId
     * @param int|null $pluginSetId
     * @return mixed|null
     */
    public function getSettingsValue(string $settingsKey, int $clientId = null, int $pluginSetId = null)
    {
        $settings = $this->getSettings($clientId, $pluginSetId);
        if(!is_null($settings)) {
            if(isset($settings->value[$settingsKey])) {
                return $settings->value[$settingsKey];
            }
        }
        return null;
    }

    /**
     * @param string $settingsKey
     * @param string $paymentKey
     * @param int|null $clientId
     * @param int|null $pluginSetId
     * @return mixed|null
     */
    public function getPaymentSettingsValue(string $settingsKey, string $paymentKey, int $clientId = null, int $pluginSetId = null)
    {
        $settings = $this->getSettingsValue('payoneMethods', $clientId, $pluginSetId);
        if(!is_null($settings)) {
            if(isset($settings[$paymentKey][$settingsKey])) {
                return $settings[$paymentKey][$settingsKey];
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function getAllAccountSettings(): array
    {
        /** @var Settings[] $setting */
        $settings = $this->database->query(Settings::class)->get();

        $accountSettings = [];
        /** @var Settings $setting */
        foreach ($settings as $setting) {
            $accountSettings[$setting->clientId][$setting->pluginSetId] = $setting->value;
        }

        return $accountSettings;
    }

    /**
     * @param array $data
     * @param int|null $clientId
     * @param int|null $pluginSetId
     * @return \Plenty\Modules\Plugin\DataBase\Contracts\Model|Settings
     */
    public function updateOrCreateSettings(array $data, int $clientId = null, int $pluginSetId = null)
    {
        /** @var Settings[] $settings */
        $settings = $this->getSettings($clientId, $pluginSetId);

        if (!$settings instanceof Settings) {
            /** @var Settings $settings */
            $settings = pluginApp(Settings::class);
            $settings->clientId = $clientId;
            $settings->pluginSetId = $pluginSetId;
            $settings->createdAt = (string)Carbon::now();
        }

        $settings = $settings->updateValues($data);
        $this->cachingRepository->forget(self::CACHING_KEY_SETTINGS . '_' . $clientId . '_' . $pluginSetId);
        return $settings;
    }

    /**
     * @param int $clientId
     * @param int $pluginSetId
     * @return bool
     */
    public function deleteSettings(int $clientId, int $pluginSetId): bool
    {
        $settings = $this->getSettings($clientId, $pluginSetId);

        if ($settings instanceof Settings) {
            $this->cachingRepository->forget(self::CACHING_KEY_SETTINGS . '_' . $clientId . '_' . $pluginSetId);
            $settingsDeleted = $this->database->delete($settings);
            if ($settingsDeleted) {
                /** @var LoginRepository $loginRepository */
                $loginRepository = pluginApp(LoginRepository::class);

                return $loginRepository->delete($settings->value['loginId']);
            }
        }

        return false;
    }

    /**
     * @param int $pluginSetId
     * @return Settings[]|array|\Plenty\Modules\Plugin\DataBase\Contracts\Model[]
     */
    public function getAllSettingsForPluginSetId(int $pluginSetId)
    {
        return $this->database->query(Settings::class)->where('pluginSetId', $pluginSetId)->get();
    }
}
