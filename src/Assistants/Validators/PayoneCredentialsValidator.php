<?php

namespace Payone\Assistants\Validators;

use Illuminate\Support\MessageBag;
use Payone\Helpers\PayoneHelper;
use Payone\PluginConstants;
use Payone\Services\SettingsService;
use Plenty\Exceptions\ValidationException;
use Plenty\Plugin\Translation\Translator;
use Plenty\Validation\Validator;

class PayoneCredentialsValidator extends Validator
{
    /**
     * @param array $data
     * @throws ValidationException
     */
    public static function validateOrFail(array $data): void
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);

        $loginMid = $data['mid'];
        $loginPortalId = $data['portalId'];
        $loginAid = $data['aid'];
        $loginKey = $data['key'];

        $validationMessage = '';

        if ($data['loginId'] && empty($loginKey)) {
            /** @var SettingsService $settingsService */
            $settingsService = pluginApp(SettingsService::class);
            $accountSettings = $settingsService->getSettings();

            $mid = $accountSettings->value['mid'];
            $portalId = $accountSettings->value['portalId'];
            $aid = $accountSettings->value['aid'];
            if ($loginMid != $mid || $loginPortalId != $portalId || $loginAid != $aid) {
                $key = PluginConstants::NAME . "::Assistant.usernameWithEmptyPasswordError";
                $validationMessage .= $translator->trans($key, [
                    'mid' => $mid,
                    'portalId' => $portalId,
                    'aid' => $aid
                ]);
                self::returnMessage($validationMessage);
            }
        }
        //for new login, check the password to be filled-in
        if (empty($data['loginId']) && empty($loginKey)) {
            $key = PluginConstants::NAME . "::Assistant.usernameWithEmptyPasswordError";
            $validationMessage .= $translator->trans($key, [
                'mid' => $mid,
                'portalId' => $portalId,
                'aid' => $aid
            ]);
            self::returnMessage($key, $validationMessage);
        }

        parent::validateOrFail($data);
    }

    /**
     * @throws ValidationException
     */
    public static function returnMessage($key, $message = null)
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);
        $messageText = $message ?? $translator->trans($key);
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
