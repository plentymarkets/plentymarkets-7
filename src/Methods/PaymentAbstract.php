<?php

namespace Payone\Methods;

use Payone\Helpers\PaymentHelper;
use Payone\PluginConstants;
use Payone\Services\SettingsService;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Payment\Method\Services\PaymentMethodBaseService;
use Plenty\Plugin\Application;
use Plenty\Plugin\Translation\Translator;
use Plenty\Modules\Payment\Models\Payment;

abstract class PaymentAbstract extends PaymentMethodBaseService
{
    const PAYMENT_CODE = 'Payone';

    /**
     * @var SettingsService
     */
    protected $settingsService;

    /**
     * @var PaymentValidator
     */
    private $paymentValidator;

    /**
     * @var Application
     */
    private $app;

    /**
     * PaymentAbstract constructor.
     *
     * @param Application $application
     * @param PaymentValidator $paymentValidator
     * @param SettingsService $settingsService
     */
    public function __construct(
        Application      $application,
        PaymentValidator $paymentValidator,
        SettingsService  $settingsService
    )
    {
        $this->paymentValidator = $paymentValidator;
        $this->app = $app = $application;
        $this->settingsService = $settingsService;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool)$this->settingsService->getPaymentSettingsValue('active', $this::PAYMENT_CODE)
            && $this->paymentValidator->validate($this, $this->settingsService);
    }

    /**
     * Get shown name
     *
     * @param string $lang
     * @return string
     */
    public function getName(string $lang = 'de'): string
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);
        return $translator->trans('Payone::PaymentMethods.' . $this::PAYMENT_CODE, [], $lang);
    }

    /**
     * @return float
     */
    public function getFee(): float
    {
        return 0.;
    }

    /**
     * @param string $lang
     * @return string
     */
    public function getIcon(string $lang = 'de'): string
    {
        $paymentIcon = $this->settingsService->getPaymentSettingsValue('paymentIcon', $this::PAYMENT_CODE);

        if (empty($paymentIcon)) {
            $pluginPath = $this->app->getUrlPath(PluginConstants::NAME);
            $paymentIcon = $pluginPath . '/images/logos/' . $this::PAYMENT_CODE . '.png';
        }

        return $paymentIcon;
    }

    /**
     * @param string $lang
     * @return string
     */
    public function getDescription(string $lang = 'de'): string
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);
        return $translator->trans('Payone::PaymentMethods.' . $this::PAYMENT_CODE . '_DESCRIPTION', [], $lang);
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this::PAYMENT_CODE;
    }

    /**
     * @return float
     */
    public function getMaxCartAmount(): float
    {
        $amount = $this->settingsService->getPaymentSettingsValue('MaximumAmount', $this::PAYMENT_CODE);

        return $amount ? (float)$amount : 0.;
    }

    /**
     * @return float
     */
    public function getMinCartAmount(): float
    {
        $amount = $this->settingsService->getPaymentSettingsValue('MinimumAmount', $this::PAYMENT_CODE);

        return $amount ? (float)$amount : 0.;
    }

    /**
     * @return array
     */
    public function getAllowedCountries(): array
    {
        return (array)$this->settingsService->getPaymentSettingsValue('AllowedDeliveryCountries', $this::PAYMENT_CODE);
    }

    /**
     * Check if this payment method should be searchable in the backend
     *
     * @return bool
     */
    public function isBackendSearchable(): bool
    {
        return true;
    }

    /**
     * Check if this payment method should be active in the backend
     *
     * @return bool
     */
    public function isBackendActive(): bool
    {
        return false;
    }

    /**
     * Get name for the backend
     *
     * @param string $lang
     * @return string
     */
    public function getBackendName(string $lang = 'de'): string
    {
        return $this->getName();
    }

    /**
     * Check if this payment method can handle subscriptions
     *
     * @return bool
     */
    public function canHandleSubscriptions(): bool
    {
        return false;
    }

    /**
     * Get the url for the backend icon
     *
     * @return string
     */
    public function getBackendIcon(): string
    {
        $app = pluginApp(Application::class);
        $icon = $app->getUrlPath(PluginConstants::NAME) . '/images/logos/' . strtolower($this::PAYMENT_CODE) . '_backend_icon.svg';
        return $icon;
    }

    /**
     * Can the delivery address be different from the invoice address?
     *
     * @return bool
     */
    public function canHandleDifferingDeliveryAddress(): bool
    {
        return true;
    }

    /**
     * Check if all settings for the payment method are set.
     *
     * @param SettingsService $settingsService
     * @return bool
     */
    public function validateSettings(SettingsService $settingsService): bool
    {
        return true;
    }

    /**
     * Is the payment method active for the given currency?
     *
     * @param $currency
     * @return bool
     */
    public function isActiveForCurrency($currency): bool
    {
        return true;
    }

    /**
     * @param int|null $orderId
     * @return bool
     */
    public function isSwitchableTo($orderId = null): bool
    {
        if ($orderId > 0) {
            /** @var PaymentOrderValidator $paymentOrderValidator */
            $paymentOrderValidator = pluginApp(PaymentOrderValidator::class);

            return (bool)$this->settingsService->getPaymentSettingsValue('active', $this::PAYMENT_CODE)
                && $paymentOrderValidator->validate($this, $this->settingsService, $orderId);
        }

        return false;
    }

    /**
     * Check if it is allowed to switch from this payment method
     * @return bool
     */
    public function isSwitchableFrom($orderId = null): bool
    {
        if ($orderId > 0) {
            /** @var OrderRepositoryContract $orderRepo */
            $orderRepo = pluginApp(OrderRepositoryContract::class);
            try {
                $order = $orderRepo->findById($orderId, ['payments'], true);
                /** @var PaymentHelper $paymentHelper */
                $paymentHelper = pluginApp(PaymentHelper::class);
                foreach ($order->payments as $paymentData) {
                    if (in_array($paymentData->mopId, $paymentHelper->getMops()) &&
                        $paymentData->status == Payment::STATUS_AWAITING_APPROVAL
                    ) {
                        return false;
                    }
                }
                return true;
            } catch (\Exception $e) {
                return true;
            }
        } else {
            return true;
        }
    }
}
