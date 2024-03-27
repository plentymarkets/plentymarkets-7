<?php

namespace Payone\Assistants\Validators;

use Illuminate\Support\MessageBag;
use Payone\Methods\PayoneInvoiceSecurePaymentMethod;
use Payone\PluginConstants;
use Payone\Services\SettingsService;
use Plenty\Exceptions\ValidationException;
use Plenty\Plugin\Translation\Translator;
use Plenty\Validation\Validator;

class PayoneInvoiceSecureCredentialsValidator extends Validator
{
    /**
     * @param array $data
     * @throws ValidationException
     */
    public static function validateOrFail(array $data): void
    {
        $paymentCode = PayoneInvoiceSecurePaymentMethod::PAYMENT_CODE;
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);

        $loginKey = $data[$paymentCode . 'key'];
        $loginPortalId = $data[$paymentCode . 'portalId'];
        $validationMessage = '';

        if ($data['loginId'] && empty($loginKey)) {
            /** @var SettingsService $settingsService */
            $settingsService = pluginApp(SettingsService::class);
            $accountSettings = $settingsService->getSettings();

            $portalId = $accountSettings->value[$paymentCode]['portalId'];
            if ($loginPortalId != $portalId) {
                $key = PluginConstants::NAME . "::General.existingUsernameInvoiceSecureWithEmptyPasswordError";
                self::returnMessage($key, [
                    'portalId' => $loginPortalId,
                ]);
            }
        }
        //for new login, check the password to be filled-in
        if (empty($data['loginId']) && empty($loginKey)) {
            $key = PluginConstants::NAME . "::General.existingUsernameInvoiceSecureWithEmptyPasswordError";

            self::returnMessage($key, [
                'portalId' => $portalId,
            ] );
        }

        parent::validateOrFail($data);
    }

    /**
     * @throws ValidationException
     */
    public static function returnMessage($key, $data = [])
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);
        $messageText = $translator->trans($key, $data);
        /** @var MessageBag $messageBag */
        $messageBag = pluginApp(
            MessageBag::class,
            [
                [
                    'key' => $messageText
                ]
            ]
        );

        /** @var ValidationException $exception */
        $exception = pluginApp(ValidationException::class, [$messageText]);
        $exception->setMessageBag($messageBag);
        throw $exception;
    }

    protected function defineAttributes()
    {
    }
}
