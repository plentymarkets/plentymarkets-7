<?php

namespace Payone\Assistants\SettingsHandlers;

use Payone\Helpers\PaymentHelper;
use Payone\Services\SettingsService;
use Plenty\Modules\Plugin\PluginSet\Contracts\PluginSetRepositoryContract;
use Plenty\Modules\Wizard\Contracts\WizardSettingsHandler;

class AssistantSettingsHandler implements WizardSettingsHandler
{
    /**
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters): bool
    {
        /** @var PluginSetRepositoryContract $pluginSetRepo */
        $pluginSetRepo = pluginApp(PluginSetRepositoryContract::class);

        /** @var PaymentHelper $paymentHelper */
        $paymentHelper = pluginApp(PaymentHelper::class);

        $clientId = $parameters['data']['clientId'];
        $pluginSetId = $pluginSetRepo->getCurrentPluginSetId();

        $data = $parameters['data'];

        $settings = [
            'mid' => $data['mid'] ?? '',
            'portalId' => $data['portalId'] ?? '',
            'aid' => $data['aid'] ?? '',
            'key' => $data['key'] ?? '',
            'mode' => $data['mode'] ?? 1,
            'authType' => $data['authType'] ?? 1,
            'userId' => $data['userId'] ?? null
        ];

        $payoneMethods = [];
        foreach ($paymentHelper->getPaymentCodes() as $paymentCode) {
            $payoneMethods[$paymentCode]['active'] = false;
            if(isset($data[$paymentCode . 'Toggle'])) {
                $payoneMethods[$paymentCode]['active'] = (int)$data[$paymentCode . 'Toggle'];

                switch ($paymentCode) {
                    case 'PAYONE_PAYONE_INVOICE_SECURE':
                        $payoneMethods[$paymentCode]['portalId'] = $data[$paymentCode.'portalId'] ?? '';
                        $payoneMethods[$paymentCode]['key'] = $data[$paymentCode.'key'] ?? '';
                        break;
                    case 'PAYONE_PAYONE_CREDIT_CARD':
                        $payoneMethods[$paymentCode]['minExpireTime'] = (int)($data[$paymentCode.'minExpireTime'] ?? 30);
                        $payoneMethods[$paymentCode]['defaultStyle'] = $data[$paymentCode.'defaultStyle'] ?? 'font-family: Helvetica; padding: 10.5px 21px; color: #7a7f7f; font-size: 17.5px; height:100%';
                        $payoneMethods[$paymentCode]['defaultHeightInPx'] = (int)($data[$paymentCode.'defaultHeightInPx'] ?? 44);
                        $payoneMethods[$paymentCode]['defaultWidthInPx'] = (int)($data[$paymentCode.'defaultWidthInPx'] ?? 644);
                        $payoneMethods[$paymentCode]['AllowedCardTypes'] = is_array($data[$paymentCode.'AllowedCardTypes']) ? $data[$paymentCode.'AllowedCardTypes'] : [];
                        break;
                    case 'PAYONE_PAYONE_AMAZON_PAY':
                        $payoneMethods[$paymentCode]['Sandbox'] = (int)($data[$paymentCode.'Sandbox'] ?? 0);
                        break;
                }

                $payoneMethods[$paymentCode]['MinimumAmount'] = (int)($data[$paymentCode.'MinimumAmount'] ?? 0);
                $payoneMethods[$paymentCode]['MaximumAmount'] = (int)($data[$paymentCode.'MaximumAmount'] ?? 0);
                $payoneMethods[$paymentCode]['AllowedDeliveryCountries'] = is_array($data[$paymentCode.'AllowedDeliveryCountries']) ? $data[$paymentCode.'AllowedDeliveryCountries'] : [];
                $payoneMethods[$paymentCode]['AuthType'] = (int)($data[$paymentCode.'AuthType'] ?? -1);
                $payoneMethods[$paymentCode]['paymentIcon'] = $data[$paymentCode . 'paymentIcon'] ?? '';
            }
        }

        $settings['payoneMethods'] = $payoneMethods;

        /** @var SettingsService $settingsService */
        $settingsService = pluginApp(SettingsService::class);
        $settingsService->updateOrCreateSettings($settings, $clientId, $pluginSetId);

        return true;
    }
}
