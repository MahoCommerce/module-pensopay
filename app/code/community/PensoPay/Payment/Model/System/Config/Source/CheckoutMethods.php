<?php

/**
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

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
