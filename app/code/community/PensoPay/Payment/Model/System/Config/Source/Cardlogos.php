<?php

class PensoPay_Payment_Model_System_Config_Source_Cardlogos
{
    /**
     * @return array<array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'dankort',
                'label' => Mage::helper('pensopay')->__('Dankort'),
            ],
            [
                'value' => 'forbrugsforeningen',
                'label' => Mage::helper('pensopay')->__('Forbrugsforeningen'),
            ],
            [
                'value' => 'visa',
                'label' => Mage::helper('pensopay')->__('VISA'),
            ],
            [
                'value' => 'visaelectron',
                'label' => Mage::helper('pensopay')->__('VISA Electron'),
            ],
            [
                'value' => 'mastercard',
                'label' => Mage::helper('pensopay')->__('MasterCard'),
            ],
            [
                'value' => 'maestro',
                'label' => Mage::helper('pensopay')->__('Maestro'),
            ],
            [
                'value' => 'jcb',
                'label' => Mage::helper('pensopay')->__('JCB'),
            ],
            [
                'value' => 'diners',
                'label' => Mage::helper('pensopay')->__('Diners Club'),
            ],
            [
                'value' => 'amex',
                'label' => Mage::helper('pensopay')->__('AMEX'),
            ],
            [
                'value' => 'sofort',
                'label' => Mage::helper('pensopay')->__('Sofort'),
            ],
            [
                'value' => 'viabill',
                'label' => Mage::helper('pensopay')->__('ViaBill'),
            ],
            [
                'value' => 'mobilepay',
                'label' => Mage::helper('pensopay')->__('MobilePay'),
            ],
            [
                'value' => 'applepay',
                'label' => Mage::helper('pensopay')->__('ApplePay'),
            ],
        ];
    }

    /**
     * Get label for card
     */
    public function getFrontendLabel(string $value): string
    {
        foreach ($this->toOptionArray() as $option) {
            if ($value === $option['value']) {
                return $option['label'];
            }
        }

        return '';
    }
}
