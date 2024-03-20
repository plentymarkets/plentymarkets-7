<?php

namespace Payone\Assistants;

use Payone\Assistants\DataSources\AssistantDataSource;
use Payone\Assistants\SettingsHandlers\AssistantSettingsHandler;
use Payone\Assistants\Validators\PayoneCredentialsValidator;
use Payone\Assistants\Validators\PayoneInvoiceSecureCredentialsValidator;
use Payone\Helpers\PaymentHelper;
use Payone\Methods\PayoneAmazonPayPaymentMethod;
use Payone\Methods\PayoneCCPaymentMethod;
use Payone\Methods\PayoneCODPaymentMethod;
use Payone\Methods\PayoneDirectDebitPaymentMethod;
use Payone\Methods\PayoneInvoicePaymentMethod;
use Payone\Methods\PayoneInvoiceSecurePaymentMethod;
use Payone\Methods\PayonePaydirektPaymentMethod;
use Payone\Methods\PayonePayolutionInstallmentPaymentMethod;
use Payone\Methods\PayonePayPalPaymentMethod;
use Payone\Methods\PayonePrePaymentPaymentMethod;
use Payone\Methods\PayoneRatePayInstallmentPaymentMethod;
use Payone\Methods\PayoneSofortPaymentMethod;
use Payone\Models\CreditcardTypes;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Order\Shipping\Countries\Models\Country;
use Plenty\Modules\System\Contracts\SystemInformationRepositoryContract;
use Plenty\Modules\System\Contracts\WebstoreRepositoryContract;
use Plenty\Modules\System\Models\Webstore;
use Plenty\Modules\User\Contracts\UserRepositoryContract;
use Plenty\Modules\User\Models\User;
use Plenty\Modules\Wizard\Services\WizardProvider;
use Plenty\Plugin\Application;

class PayoneAssistant extends WizardProvider
{
    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var array
     */
    protected $activeCountries;

    /**
     * @var string
     */
    protected $language;

    /**
     * PayoneAssistant constructor.
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(PaymentHelper $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * @return array
     */
    protected function structure(): array
    {
        $config = [
            "title" => 'Assistant.assistantTitle',
            "shortDescription" => 'Assistant.assistantShortDescription',
            "iconPath" => $this->getIcon(),
            "settingsHandlerClass" => AssistantSettingsHandler::class,
            "dataSource" => AssistantDataSource::class,
            "translationNamespace" => "Payone",
            "key" => "payment-payone-assistant",
            "topics" => ["payment"],
            'priority' => 990,
            "options" => [
                "clientId" => [
                    "type" => 'select',
                    'defaultValue' => $this->getMainWebstore(),
                    "options" => [
                        "name" => 'Assistant.clientId',
                        'required' => true,
                        'listBoxValues' => $this->getWebstoreValues(),
                    ],
                ],
            ],
            "steps" => [
            ]
        ];

        $config = $this->createAccountStep($config);

        $config = $this->createProductsPageAndSteps($config);

        return $config;
    }

    /**
     * @return string
     */
    protected function getIcon(): string
    {
        $app = pluginApp(Application::class);
        $icon = $app->getUrlPath('Payone') . '/images/logos/PAYONE_PAYONE_CREDIT_CARD.png';

        return $icon;
    }

    /**
     * @return int
     */
    protected function getMainWebstore(): int
    {
        /** @var WebstoreRepositoryContract $webstoreRepository */
        $webstoreRepository = pluginApp(WebstoreRepositoryContract::class);
        $webstore = $webstoreRepository->findById(0);
        return $webstore->storeIdentifier;
    }

    /**
     * @return array
     */
    protected function getWebstoreValues(): array
    {
        /** @var WebstoreRepositoryContract $webstoreRepository */
        $webstoreRepository = pluginApp(WebstoreRepositoryContract::class);
        $webstores = $webstoreRepository->loadAll();

        $values = [];

        /** @var Webstore $webstore */
        foreach ($webstores as $webstore) {
            $values[] = [
                'caption' => $webstore->name,
                'value' => $webstore->storeIdentifier
            ];
        }
        return $values;
    }

    /**
     * @param array $config
     * @return array
     */
    protected function createAccountStep(array $config): array
    {
        $backendUsers = $this->getBackendUsers();

        $config['steps']['payoneAccountStep'] = [
            'title' => 'Assistant.titlePayoneAccountStep',
            'description' => 'Assistant.descriptionPayoneAccountStep',
            'validationClass' => PayoneCredentialsValidator::class,
            'sections' => [
                [
                    'title' => 'Assistant.titlePayoneAccountStepAccount',
                    'description' => 'Assistant.descriptionPayoneAccountStepAccount',
                    'form' => [
                        'mid' => [
                            'type' => 'text',
                            'options' => [
                                'name' => 'Assistant.mid',
                                'required' => true
                            ]
                        ],
                        'portalId' => [
                            'type' => 'text',
                            'options' => [
                                'name' => 'Assistant.portalId',
                                'required' => true
                            ]
                        ],
                        'aid' => [
                            'type' => 'text',
                            'options' => [
                                'name' => 'Assistant.aid',
                                'required' => true
                            ]
                        ],
                        'key' => [
                            'type' => 'text',
                            'options' => [
                                'name' => 'Assistant.key',
                                'required' => false
                            ]
                        ],
                        'mode' => [
                            'type' => 'select',
                            "defaultValue" => 1,
                            'options' => [
                                'name' => 'Assistant.mode',
                                'listBoxValues' => [
                                    [
                                        "caption" => 'Assistant.modeProductiveOption',
                                        "value" => 1
                                    ],
                                    [
                                        "caption" => 'Assistant.modeTestingOption',
                                        "value" => 0
                                    ]
                                ]
                            ]
                        ],
                        'authType' => [
                            'type' => 'select',
                            "defaultValue" => 1,
                            'options' => [
                                'name' => 'Assistant.authType',
                                'listBoxValues' => [
                                    [
                                        "caption" => 'Assistant.authTypeAuthorization',
                                        "value" => 1
                                    ],
                                    [
                                        "caption" => 'Assistant.authTypePreAuthorization',
                                        "value" => 0
                                    ]
                                ]
                            ]
                        ],
                        "userId" => [
                            'type' => 'select',
                            'defaultValue' => $backendUsers[0]['value'],
                            'options' => [
                                'name' => 'Assistant.userId',
                                'required' => true,
                                'listBoxValues' => $backendUsers
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function getBackendUsers(): array
    {
        /** @var UserRepositoryContract $app */
        $userRepo = pluginApp(UserRepositoryContract::class);

        $allBackendUsers = $userRepo->getAll();
        $users = [];
        /** @var User $backendUser */
        foreach ($allBackendUsers as $backendUser) {
            $users[] = [
                'caption' => $backendUser->realName,
                'value' => $backendUser->id
            ];
        }

        return $users;
    }

    /**
     * @param array $config
     * @return array
     */
    protected function createProductsPageAndSteps(array $config): array
    {
        $config['steps']['payoneProductsStep'] = [
            'title' => 'Assistant.titlePayoneProductsStep',
            'description' => 'Assistant.descriptionPayoneProductsStep',
            'validationClass' => PayoneInvoiceSecureCredentialsValidator::class,
            'showFullDescription' => true,
            'sections' => []
        ];

        foreach ($this->paymentHelper->getPaymentCodes() as $paymentCode) {
            $config['steps']['payoneProductsStep']['sections'][] = [
                'title' => 'Assistant.titlePayoneProductsStep' . $paymentCode,
                'description' => 'Assistant.descriptionPayoneProductsStep' . $paymentCode,
                'showFullDescription' => true,
                'form' => [
                    $paymentCode . 'Toggle' => [
                        'type' => 'toggle',
                        'defaultValue' => false,
                        'options' => [
                            'name' => 'Assistant.title' . $paymentCode . '_Toggle',
                            'required' => false
                        ]
                    ]
                ]
            ];

            if (
                in_array($paymentCode, [
                    PayoneInvoiceSecurePaymentMethod::PAYMENT_CODE,
                    PayoneCCPaymentMethod::PAYMENT_CODE,
                    PayoneAmazonPayPaymentMethod::PAYMENT_CODE
                ])
            ) {
                // We need some special configurations for this methods.
                switch ($paymentCode) {
                    case PayoneInvoiceSecurePaymentMethod::PAYMENT_CODE:
                        $config = $this->createSecureInvoiceStep($config, $paymentCode);
                        break;
                    case PayoneCCPaymentMethod::PAYMENT_CODE:
                        $config = $this->createCreditCardStep($config, $paymentCode);
                        break;
                    case PayoneAmazonPayPaymentMethod::PAYMENT_CODE:
                        $config = $this->createAmazonPayStep($config, $paymentCode);
                        break;
                }
            } else {
                $config['steps']['payone' . $paymentCode . 'Step'] = [
                    'title' => 'Assistant.titlePayoneProductsStep' . $paymentCode,
                    'description' => 'Assistant.descriptionPayoneProductsStep' . $paymentCode,
                    'showFullDescription' => true,
                    'condition' => $paymentCode . 'Toggle',
                    'sections' => [
                        [
                            'title' => 'Assistant.titlePayonePaymentSection',
                            'description' => 'Assistant.descriptionPayonePaymentSection',
                            'form' =>
                                $this->getMinMaxAmountConfig($paymentCode)
                                +
                                $this->getDeliveryCountriesConfig($paymentCode)
                                +
                                $this->getAuthorizationConfig($paymentCode)
                        ],
                        $this->getPaymentIconConfig($paymentCode)
                    ]
                ];
            }
        }

        return $config;
    }

    /**
     * @param array $config
     * @param string $paymentCode
     * @return array
     */
    protected function createSecureInvoiceStep(array $config, string $paymentCode): array
    {
        $config['steps']['payone' . $paymentCode . 'Step'] = [
            'title' => 'Assistant.titlePayoneProductsStep' . $paymentCode,
            'description' => 'Assistant.descriptionPayoneProductsStep' . $paymentCode,
            'showFullDescription' => true,
            'condition' => $paymentCode . 'Toggle',
            'sections' => [
                [
                    'title' => 'Assistant.titlePayonePaymentSection',
                    'description' => 'Assistant.descriptionPayonePaymentSectionSecureInvoice',
                    'form' =>
                        $this->getMinMaxAmountConfig($paymentCode)
                        +
                        $this->getDeliveryCountriesConfig($paymentCode)
                        +
                        $this->getAuthorizationConfig($paymentCode)
                        + [
                            $paymentCode . 'portalId' => [
                                'type' => 'text',
                                'options' => [
                                    'name' => 'Assistant.portalId',
                                    'required' => true
                                ]
                            ],
                            $paymentCode . 'key' => [
                                'type' => 'text',
                                'options' => [
                                    'name' => 'Assistant.key',
                                    'required' => true
                                ]
                            ]
                        ]
                ],
                $this->getPaymentIconConfig($paymentCode)
            ]
        ];

        return $config;
    }

    /**
     * @param string $paymentCode
     * @return array
     */
    protected function getMinMaxAmountConfig(string $paymentCode): array
    {
        return [
            $paymentCode . 'MinimumAmount' => [
                'type' => 'double',
                'defaultValue' => 0,
                'options' => [
                    'isPriceInput' => true,
                    'decimalCount' => 2,
                    'name' => 'Assistant.MinimumAmount'
                ]
            ],
            $paymentCode . 'MaximumAmount' => [
                'type' => 'double',
                'defaultValue' => 0,
                'options' => [
                    'isPriceInput' => true,
                    'decimalCount' => 2,
                    'name' => 'Assistant.MaximumAmount'
                ]
            ]
        ];
    }

    /**
     * @param string $paymentCode
     * @return array
     */
    protected function getDeliveryCountriesConfig(string $paymentCode): array
    {
        $deliveryCountries = $this->getSpecificDeliveryCountries($paymentCode);
        return [
            $paymentCode . 'AllowedDeliveryCountries' => [
                'type' => 'checkboxGroup',
                'defaultValue' => $this->getDefaultCountries($deliveryCountries),
                'options' => [
                    'name' => 'Assistant.allowedDeliveryCountries',
                    'required' => true,
                    'checkboxValues' => array_values($deliveryCountries)
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getSpecificDeliveryCountries($paymentMethod): array
    {
        $deliveryCountries = [];
        switch ($paymentMethod) {
            case PayoneInvoiceSecurePaymentMethod::PAYMENT_CODE:
            case PayoneSofortPaymentMethod::PAYMENT_CODE:
                $allowedCountries = [
                    1, // DE
                    2, // AT
                    4  // CH
                ];
                break;
            case PayoneDirectDebitPaymentMethod::PAYMENT_CODE:
                $allowedCountries = [
                    1, //'DE',
                    2, //'AT',
                    3, // 'BE',
                    5, //'CY',
                    8, //'ES',
                    9, //'EE',
                    10, //'FR',
                    11, //'FI',
                    13, //'GR',
                    15, //'IT',
                    16, //'IE',
                    17, //'LU',
                    18, //'LV',
                    19, //'MT',
                    21, //'NL',
                    22, //'PT',
                    26, //'SK',
                    27, //'SI'
                    33, //'LT',
                    35, //'MC',
                    71, //'AD',
                    131, //'GI',
                    212, //'SM',
                ];
                break;
            case PayonePaydirektPaymentMethod::PAYMENT_CODE:
                $allowedCountries = [
                    1, //DE
                ];
                break;
            case PayoneCCPaymentMethod::PAYMENT_CODE:
            case PayoneCODPaymentMethod::PAYMENT_CODE:
            case PayoneInvoicePaymentMethod::PAYMENT_CODE:
            case PayonePayolutionInstallmentPaymentMethod::PAYMENT_CODE:
            case PayonePayPalPaymentMethod::PAYMENT_CODE:
            case PayonePrePaymentPaymentMethod::PAYMENT_CODE:
            case PayoneRatePayInstallmentPaymentMethod::PAYMENT_CODE:
            case PayoneAmazonPayPaymentMethod::PAYMENT_CODE:
            default:
                $allowedCountries = [];
                break;
        }

        /** @var CountryRepositoryContract $countryRepository */
        $countryRepository = pluginApp(CountryRepositoryContract::class);
        $systemLanguage = $this->getLanguage();
        $countries = $countryRepository->getCountriesList(null, ['names']);
        /** @var Country $country */
        foreach ($countries as $country) {
            if (count($allowedCountries) <= 0 || array_search($country->id, $allowedCountries) !== false) {
                $name = $country->names->where('lang', $systemLanguage)->first()->name;
                $deliveryCountries[$country->id] = [
                    'caption' => $name ?? $country->name,
                    'value' => $country->id
                ];
            }
        }

        return $deliveryCountries;
    }

    /**
     * @return string
     */
    protected function getLanguage()
    {
        if ($this->language === null) {
            /** @var SystemInformationRepositoryContract $systemInformationRepository */
            $systemInformationRepository = pluginApp(SystemInformationRepositoryContract::class);
            // TODO: this seems not be the log-in language. Where to get it?
            $this->language = $systemInformationRepository->loadValue('systemLang');
        }

        return $this->language;
    }

    /**
     * Load the active country values
     */
    protected function getDefaultCountries($availableCountries = array())
    {
        if ($this->activeCountries === null) {
            /** @var CountryRepositoryContract $countryRepository */
            $countryRepository = pluginApp(CountryRepositoryContract::class);
            $activeCountries = $countryRepository->getActiveCountriesList();
            /** @var Country $country */
            foreach ($activeCountries as $country) {
                $this->activeCountries[$country->id] = $country->isoCode2;
            }
        }

        return array_column(array_intersect_key($availableCountries, $this->activeCountries), 'value');
    }

    /**
     * @param string $paymentCode
     * @return array
     */
    protected function getAuthorizationConfig(string $paymentCode): array
    {
        $listBoxValues = [
            [
                "caption" => 'Assistant.authTypeDefault',
                "value" => -1
            ],
            [
                "caption" => 'Assistant.authTypeAuthorization',
                "value" => 1
            ],
            [
                "caption" => 'Assistant.authTypePreAuthorization',
                "value" => 0
            ]
        ];

        if ($paymentCode == PayoneSofortPaymentMethod::PAYMENT_CODE) {
            // Only this auth method available for SOFORT
            $listBoxValues = [
                [
                    "caption" => 'Assistant.authTypeAuthorization',
                    "value" => 1
                ]
            ];
        }

        return [
            $paymentCode . 'AuthType' => [
                'type' => 'select',
                "defaultValue" => -1,
                'options' => [
                    'name' => 'Assistant.authType',
                    'listBoxValues' => $listBoxValues
                ]
            ]
        ];
    }

    /**
     * @param string $paymentCode
     * @return array
     */
    protected function getPaymentIconConfig(string $paymentCode): array
    {
        return [
            'description' => 'Assistant.paymentIcon',
            'form' => [
                $paymentCode . "paymentIcon" => [
                    'type' => 'file',
                    'options' => [
                        'name' => 'Assistant.paymentIconDescription',
                        'showPreview' => true,
                        'allowedExtensions' => [
                            'svg',
                            'png',
                            'jpg',
                            'jpeg'
                        ],
                        'allowFolders' => false
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $config
     * @param string $paymentCode
     * @return array
     */
    protected function createCreditCardStep(array $config, string $paymentCode): array
    {
        $config['steps']['payone' . $paymentCode . 'Step'] = [
            'title' => 'Assistant.titlePayoneProductsStep' . $paymentCode,
            'description' => 'Assistant.descriptionPayoneProductsStep' . $paymentCode,
            'showFullDescription' => true,
            'condition' => $paymentCode . 'Toggle',
            'sections' => [
                [
                    'title' => 'Assistant.titlePayonePaymentSection',
                    'description' => 'Assistant.descriptionPayonePaymentSection',
                    'form' =>
                        $this->getMinMaxAmountConfig($paymentCode)
                        +
                        $this->getDeliveryCountriesConfig($paymentCode)
                        +
                        $this->getAuthorizationConfig($paymentCode)
                        + [
                            $paymentCode . 'minExpireTime' => [
                                'type' => 'text',
                                'defaultValue' => 30,
                                'options' => [
                                    'name' => 'Assistant.minExpireTime',
                                    'required' => true
                                ]
                            ],
                            $paymentCode . 'defaultStyle' => [
                                'type' => 'text',
                                'defaultValue' => 'font-family: Helvetica; padding: 10.5px 21px; color: #7a7f7f; font-size: 17.5px; height:100%',
                                'options' => [
                                    'name' => 'Assistant.defaultStyle',
                                    'required' => true
                                ]
                            ],
                            $paymentCode . 'defaultHeightInPx' => [
                                'type' => 'text',
                                'defaultValue' => '44',
                                'options' => [
                                    'name' => 'Assistant.defaultHeightInPx',
                                    'required' => true
                                ]
                            ],
                            $paymentCode . 'defaultWidthInPx' => [
                                'type' => 'text',
                                'defaultValue' => '644',
                                'options' => [
                                    'name' => 'Assistant.defaultWidthInPx',
                                    'required' => true
                                ]
                            ],
                            $paymentCode . 'AllowedCardTypes' => [
                                'type' => 'checkboxGroup',
                                'defaultValue' => ['V', 'M', 'A', 'O', 'U', 'D', 'B', 'C', 'J', 'P'],
                                'options' => [
                                    'name' => 'Assistant.AllowedCardTypes',
                                    'required' => true,
                                    'checkboxValues' => $this->getAllowedCreditCardTypes()
                                ]
                            ]
                        ]
                ],
                $this->getPaymentIconConfig($paymentCode)
            ]
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function getAllowedCreditCardTypes(): array
    {
        /** @var CreditcardTypes $creditCardTypes */
        $creditCardTypes = pluginApp(CreditcardTypes::class);

        $allowedCreditCards = [];
        $cards = $creditCardTypes->getCreditCardTypes();
        foreach ($cards as $card) {
            $allowedCreditCards[] = [
                'caption' => 'Assistant.creditCardType' . $card,
                'value' => $card
            ];
        }

        return $allowedCreditCards;
    }

    /**
     * @param array $config
     * @param string $paymentCode
     * @return array
     */
    protected function createAmazonPayStep(array $config, string $paymentCode): array
    {
        $config['steps']['payone' . $paymentCode . 'Step'] = [
            'title' => 'Assistant.titlePayoneProductsStep' . $paymentCode,
            'description' => 'Assistant.descriptionPayoneProductsStep' . $paymentCode,
            'showFullDescription' => true,
            'condition' => $paymentCode . 'Toggle',
            'sections' => [
                [
                    'title' => 'Assistant.titlePayonePaymentSection',
                    'description' => 'Assistant.descriptionPayonePaymentSectionAmazonPay',
                    'form' =>
                        $this->getMinMaxAmountConfig($paymentCode)
                        +
                        $this->getDeliveryCountriesConfig($paymentCode)
                        +
                        $this->getAuthorizationConfig($paymentCode)
                        +
                        [
                            $paymentCode . 'Sandbox' => [
                                'type' => 'select',
                                "defaultValue" => 0,
                                'options' => [
                                    'name' => 'Assistant.Sandbox',
                                    'listBoxValues' => [
                                        [
                                            "caption" => 'Assistant.sandboxProductiveOption',
                                            "value" => 0
                                        ],
                                        [
                                            "caption" => 'Assistant.sandboxTestingOption',
                                            "value" => 1
                                        ]
                                    ]
                                ]
                            ]
                        ]
                ],
                $this->getPaymentIconConfig($paymentCode)
            ]
        ];

        return $config;
    }
}
