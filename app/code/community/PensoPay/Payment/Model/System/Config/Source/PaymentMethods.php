<?php

class PensoPay_Payment_Model_System_Config_Source_PaymentMethods
{
    public const METHOD_SPECIFIED = 'specified';
    public const METHOD_CREDITCARDS = 'creditcard';

    /**
     * Get available payment methods
     *
     * @return array<array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => '',
                'label' => Mage::helper('pensopay')->__('All Payment Methods'),
            ],
            [
                'value' => self::METHOD_CREDITCARDS,
                'label' => Mage::helper('pensopay')->__('All Credit Cards'),
            ],
            [
                'value' => self::METHOD_SPECIFIED,
                'label' => Mage::helper('pensopay')->__('As Specified'),
            ],
        ];
    }
}
