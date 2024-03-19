<?php

namespace Payone\Assistants\Validators;

use Illuminate\Support\MessageBag;
use Payone\Helpers\PayoneHelper;
use Payone\Repositories\LoginRepository;
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

        $loginKey = $data['key'];
        $merchantId = $data['mid'];
        $validationMessage = '';

        if ($data['loginId'] && empty($loginKey)) {
            /** @var LoginRepository $loginsRepository */
            $loginsRepository = pluginApp(LoginRepository::class);

            $loginValues = $loginsRepository->getValues($data['loginId']);
            $loginMerchantId = $loginValues['mid'];
            if ($loginMerchantId != $merchantId) {
                $key = PayoneHelper::PLUGIN_NAME . "::Assistant.merchantIdWithEmptyPasswordError";
                $validationMessage .= $translator->trans($key, ['merchantId' => $merchantId]);
                self::returnMessage($validationMessage);
            }
        }
        //for new login, check the password to be filled-in
        if (empty($data['loginId']) && empty($loginKey)) {
            $key = PayoneHelper::PLUGIN_NAME . "::Assistant.merchantIdWithEmptyPasswordError";
            $validationMessage .= $translator->trans($key, ['merchantId' => $merchantId]);
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
