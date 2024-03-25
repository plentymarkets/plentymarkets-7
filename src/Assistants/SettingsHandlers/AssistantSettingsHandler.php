<?php

namespace Payone\Assistants\SettingsHandlers;

use Exception;
use Payone\Helpers\PaymentHelper;
use Payone\Helpers\PayoneHelper;
use Payone\Models\Logins;
use Payone\Repositories\LoginRepository;
use Payone\Services\SettingsService;
use Plenty\Modules\Plugin\PluginSet\Contracts\PluginSetRepositoryContract;
use Plenty\Modules\Wizard\Contracts\WizardSettingsHandler;
use Plenty\Plugin\Log\Loggable;
use Throwable;

class AssistantSettingsHandler implements WizardSettingsHandler
{
    use Loggable;

    /**
     * @param array $parameters
     * @return bool
     * @throws Exception
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

        /** @var Logins $loginModel */
        $loginModel = pluginApp(Logins::class);

        /** @var LoginRepository $loginRepository */
        $loginRepository = pluginApp(LoginRepository::class);
        if (!empty($data['loginId'])) {
            $oldLoginModel = $loginRepository->getById($data['loginId']);
            if ($oldLoginModel !== null) {
                $loginModel = $oldLoginModel;
            }
        }
        if ($data['key'] != '') {
            $loginModel->key = $data['key'];
        }

        $settings = [
            'mid' => $data['mid'] ?? '',
            'portalId' => $data['portalId'] ?? '',
            'aid' => $data['aid'] ?? '',
            'mode' => $data['mode'] ?? 1,
            'authType' => $data['authType'] ?? 1,
            'userId' => $data['userId'] ?? null
        ];

        $payoneMethods = [];
        foreach ($paymentHelper->getPaymentCodes() as $paymentCode) {
            $payoneMethods[$paymentCode]['active'] = false;
            if (isset($data[$paymentCode . 'Toggle'])) {
                $payoneMethods[$paymentCode]['active'] = (int)$data[$paymentCode . 'Toggle'];

                switch ($paymentCode) {
                    case 'PAYONE_PAYONE_INVOICE_SECURE':
                        $payoneMethods[$paymentCode]['portalId'] = $data[$paymentCode . 'portalId'] ?? '';

                        if ($data[$paymentCode . 'key'] != '') {
                            $loginModel->invoiceSecureKey = $data[$paymentCode . 'key'];
                        }

                        break;
                    case 'PAYONE_PAYONE_CREDIT_CARD':
                        $payoneMethods[$paymentCode]['minExpireTime'] = (int)($data[$paymentCode . 'minExpireTime'] ?? 30);
                        $payoneMethods[$paymentCode]['defaultStyle'] = $data[$paymentCode . 'defaultStyle'] ?? 'font-family: Helvetica; padding: 10.5px 21px; color: #7a7f7f; font-size: 17.5px; height:100%';
                        $payoneMethods[$paymentCode]['defaultHeightInPx'] = (int)($data[$paymentCode . 'defaultHeightInPx'] ?? 44);
                        $payoneMethods[$paymentCode]['defaultWidthInPx'] = (int)($data[$paymentCode . 'defaultWidthInPx'] ?? 644);
                        $payoneMethods[$paymentCode]['AllowedCardTypes'] = is_array(
                            $data[$paymentCode . 'AllowedCardTypes']
                        ) ? $data[$paymentCode . 'AllowedCardTypes'] : [];
                        break;
                    case 'PAYONE_PAYONE_AMAZON_PAY':
                        $payoneMethods[$paymentCode]['Sandbox'] = (int)($data[$paymentCode . 'Sandbox'] ?? 0);
                        break;
                }

                $payoneMethods[$paymentCode]['MinimumAmount'] = (int)($data[$paymentCode . 'MinimumAmount'] ?? 0);
                $payoneMethods[$paymentCode]['MaximumAmount'] = (int)($data[$paymentCode . 'MaximumAmount'] ?? 0);
                $payoneMethods[$paymentCode]['AllowedDeliveryCountries'] = is_array(
                    $data[$paymentCode . 'AllowedDeliveryCountries']
                ) ? $data[$paymentCode . 'AllowedDeliveryCountries'] : [];
                $payoneMethods[$paymentCode]['AuthType'] = (int)($data[$paymentCode . 'AuthType'] ?? -1);
                $payoneMethods[$paymentCode]['paymentIcon'] = $data[$paymentCode . 'paymentIcon'] ?? '';
            }
        }

        $settings['payoneMethods'] = $payoneMethods;
        try {
            $updatedLogin = $loginRepository->save($loginModel);
            $settings['loginId'] = $updatedLogin->id ?? '';
            /** @var SettingsService $settingsService */
            $settingsService = pluginApp(SettingsService::class);
            $settingsService->updateOrCreateSettings($settings, $clientId, $pluginSetId);
        } catch (Throwable $ex) {
            $this->getLogger(__METHOD__)->debug(PayoneHelper::PLUGIN_NAME . '::General.saveSettingsError', [
                'error' => $ex->getMessage(),
                'trace' => $ex->getTrace(),
                'settings' => $settings
            ]);
            return false;
        }
        return true;
    }
}
