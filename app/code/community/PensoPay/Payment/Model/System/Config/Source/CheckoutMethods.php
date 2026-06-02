<?php

class PensoPay_Payment_Model_System_Config_Source_CheckoutMethods
{
    public const METHOD_REDIRECT = 'redirect';
    public const METHOD_IFRAME = 'iframe';
    public const METHOD_EMBEDDED = 'embedded';

    /**
     * Get available payment methods
     *
     * @return array<array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::METHOD_REDIRECT,
                'label' => Mage::helper('pensopay')->__('Redirect'),
            ],
            //            [
            //                'value' => self::METHOD_EMBEDDED,
            //                'label' => Mage::helper('pensopay')->__('Embedded')
            //            ],
            //            [
            //                'value' => self::METHOD_IFRAME,
            //                'label' => Mage::helper('pensopay')->__('Iframe')
            //            ],
        ];
    }
}
