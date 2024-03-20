<?php

namespace Payone\Assistants\DataSources;

use Payone\Repositories\LoginRepository;
use Payone\Services\SettingsService;
use Plenty\Modules\Plugin\PluginSet\Contracts\PluginSetRepositoryContract;
use Plenty\Modules\Wizard\Models\WizardData;
use Plenty\Modules\Wizard\Services\DataSources\BaseWizardDataSource;

class AssistantDataSource extends BaseWizardDataSource
{
    /**
     * @var SettingsService
     */
    protected $settingsService;

    /**
     * @var PluginSetRepositoryContract
     */
    protected $pluginSetRepositoryContract;

    /**
     * AssistantDataSource constructor.
     * @param SettingsService $settingsService
     * @param PluginSetRepositoryContract $pluginSetRepositoryContract
     */
    public function __construct(
        SettingsService $settingsService,
        PluginSetRepositoryContract $pluginSetRepositoryContract
    ) {
        $this->settingsService = $settingsService;
        $this->pluginSetRepositoryContract = $pluginSetRepositoryContract;
    }

    /**
     * @return WizardData WizardData
     */
    public function findData(): WizardData
    {
        /** @var WizardData $wizardData */
        $wizardData = pluginApp(WizardData::class);
        $wizardData->data = ['default' => false];

        return $wizardData;
    }

    /**
     * @return array
     */
    public function getIdentifiers(): array
    {
        $optionIdentifiers = [];

        $settings = $this->settingsService->getAllAccountSettings();

        foreach ($settings as $pid => $value) {
            $optionIdentifiers[] = $pid;
        }

        return $optionIdentifiers;
    }

    /**
     * @param array $steps
     * @return array
     */
    public function create(array $steps = []): array
    {
        return $this->dataStructure;
    }

    /**
     * @param array $properties
     * @return array
     */
    public function update(array $properties = []): array
    {
        return $properties;
    }

    public function delete()
    {
    }

    /**
     * @return array
     */
    public function get(): array
    {
        $wizardData = $this->dataStructure;

        //Must be passed otherwise the tiles have no data.
        $tileConfig = [];

        $settings = $this->settingsService->getAllAccountSettings();

        if (is_array($settings)) {
            foreach ($settings as $pid => $setting) {
                $tileConfig[$pid] = [
                    'clientId' => $pid
                ];
            }
        }
        $wizardData['data'] = $tileConfig;

        return $wizardData;
    }


    /**
     * @param string $optionId
     * @return array
     * @throws \Exception
     */
    public function getByOptionId(string $optionId = 'default'): array
    {
        $data = $this->dataStructure;
        $assistant = [];

        if (!is_numeric($optionId)) {
            $data['data'] = $assistant;
            return $data;
        }

        /** @var SettingsService $settingsService */
        $settingsService = pluginApp(SettingsService::class);
        $accountSettings = $settingsService->getSettings(
            (int)$optionId,
            (int)$this->pluginSetRepositoryContract->getCurrentPluginSetId()
        );

        $assistant['clientId'] = $optionId;
        if (!is_null($accountSettings)) {
            $assistant['loginId'] = $accountSettings->value['loginId'] ?? '';
            $assistant['mid'] = $accountSettings->value['mid'] ?? "";
            $assistant['portalId'] = $accountSettings->value['portalId'] ?? "";
            $assistant['aid'] = $accountSettings->value['aid'] ?? "";
            $assistant['key'] = "";
            $assistant['mode'] = $accountSettings->value['mode'] ?? 1;
            $assistant['authType'] = $accountSettings->value['authType'] ?? 1;
            $assistant['userId'] = $accountSettings->value['userId'] ?? 0;

            //Payone Payment Methods
            if ($accountSettings->value['payoneMethods']) {
                foreach ($accountSettings->value['payoneMethods'] as $paymentCode => $value) {
                    $assistant[$paymentCode . 'Toggle'] = (bool)($value['active'] ?? false);
                    $assistant[$paymentCode . 'MinimumAmount'] = $value['MinimumAmount'] ?? 0;
                    $assistant[$paymentCode . 'MaximumAmount'] = $value['MaximumAmount'] ?? 2000;
                    $assistant[$paymentCode . 'paymentIcon'] = $value['paymentIcon'];
                    $assistant[$paymentCode . 'AllowedDeliveryCountries'] = is_array(
                        $value['AllowedDeliveryCountries']
                    ) ? $value['AllowedDeliveryCountries'] : [];
                    $assistant[$paymentCode . 'AuthType'] = $value['AuthType'] ?? -1;

                    switch ($paymentCode) {
                        case 'PAYONE_PAYONE_INVOICE_SECURE':
                            $assistant[$paymentCode . 'portalId'] = $value['portalId'] ?? '';
                            $assistant[$paymentCode . 'key'] = '';
                            break;
                        case 'PAYONE_PAYONE_CREDIT_CARD':
                            $assistant[$paymentCode . 'minExpireTime'] = (int)($value['minExpireTime'] ?? 30);
                            $assistant[$paymentCode . 'defaultStyle'] = $value['defaultStyle'] ?? 'font-family: Helvetica; padding: 10.5px 21px; color: #7a7f7f; font-size: 17.5px; height:100%';
                            $assistant[$paymentCode . 'defaultHeightInPx'] = (int)($value['defaultHeightInPx'] ?? 44);
                            $assistant[$paymentCode . 'defaultWidthInPx'] = (int)($value['defaultWidthInPx'] ?? 644);
                            $assistant[$paymentCode . 'AllowedCardTypes'] = is_array(
                                $value['AllowedCardTypes']
                            ) ? $value['AllowedCardTypes'] : [];
                            break;
                        case 'PAYONE_PAYONE_AMAZON_PAY':
                            $assistant[$paymentCode . 'Sandbox'] = (int)($value['Sandbox'] ?? 0);
                            break;
                    }
                }
            }
        }

        $data['data'] = $assistant;
        return $data;
    }

    /**
     * @param string $optionId
     * @throws \Exception
     */
    public function deleteDataOption(string $optionId): void
    {
        $this->settingsService->deleteSettings($optionId, $this->pluginSetRepositoryContract->getCurrentPluginSetId());
    }
}
