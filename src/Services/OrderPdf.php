<?php

namespace Payone\Services;

use Payone\Adapter\Translator;
use Payone\Helpers\PaymentHelper;
use Plenty\Modules\Order\Pdf\Models\OrderPdfGeneration;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Models\PaymentProperty;

class OrderPdf
{
    const PDF_LINEBREAK = PHP_EOL;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * OrderPdf constructor.
     *
     * @param PaymentHelper $paymentHelper
     * @param Translator $translator
     */
    public function __construct(PaymentHelper $paymentHelper, Translator $translator)
    {
        $this->paymentHelper = $paymentHelper;
        $this->translator = $translator;
    }

    /**
     * @param Payment $payment
     * @param string $lang
     * @return OrderPdfGeneration|void
     */
    public function createPdfNote(Payment $payment, $lang = 'de')
    {
        /** @var OrderPdfGeneration $orderPdfGenerationModel */
        $orderPdfGenerationModel = pluginApp(OrderPdfGeneration::class);
        $orderPdfGenerationModel->language = $lang;

        if (!$this->paymentHelper->isPayonePayment($payment->mopId)) {
            return;
        }

        $adviceData = [
            (string)$this->getPayoneBankAccount($payment, $lang),
            $this->getPaymentReferenceText($payment, $lang),
        ];

        $orderPdfGenerationModel->advice = implode(self::PDF_LINEBREAK . self::PDF_LINEBREAK, $adviceData);

        return $orderPdfGenerationModel;
    }

    /**
     * @param Payment $payment
     * @param String $lang
     * @return string
     */
    private function getPayoneBankAccount(Payment $payment, String $lang): string
    {

        $iban = $this->paymentHelper->getPaymentPropertyValue($payment, PaymentProperty::TYPE_IBAN_OF_RECEIVER);;
        $bic = $this->paymentHelper->getPaymentPropertyValue($payment, PaymentProperty::TYPE_BIC_OF_RECEIVER);;
        $accountHolder = $this->paymentHelper->getPaymentPropertyValue($payment,
            PaymentProperty::TYPE_NAME_OF_RECEIVER);;

        if (!$iban || !$bic || !$accountHolder) {
            return '';
        }

        return $this->translator->trans('Invoice.holder', [], $lang) . ': ' . $accountHolder . self::PDF_LINEBREAK .
            'IBAN: ' . $iban . self::PDF_LINEBREAK .
            'BIC: ' . $bic;
    }


    /**
     * @param Payment $payment
     * @param String $lang
     * @return string
     */
    private function getPaymentReferenceText(Payment $payment, String $lang): string
    {
        $referenceNumber = $this->paymentHelper->getPaymentPropertyValue(
            $payment,
            PaymentProperty::TYPE_TRANSACTION_ID
        );
        return $this->translator->trans('Invoice.paymentReference', [], $lang) . ': ' . $referenceNumber;
    }
}
