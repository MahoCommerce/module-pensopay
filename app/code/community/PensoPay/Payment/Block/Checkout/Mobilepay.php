<?php

/**
 * Provides MobilePay Checkout configuration and shipping methods to the checkout template.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

class PensoPay_Payment_Block_Checkout_Mobilepay extends Mage_Core_Block_Template
{
    public const MOBILEPAY_ACTICE_XML_PATH      = 'payment/pensopay_mobilepay_checkout/active';
    public const MOBILEPAY_TITLE_XML_PATH      = 'payment/pensopay_mobilepay_checkout/title';
    public const MOBILEPAY_DESCRIPTION_XML_PATH  = 'payment/pensopay_mobilepay_checkout/instructions';
    public const MOBILEPAY_POPUP_DESCRIPTION_XML_PATH  = 'payment/pensopay_mobilepay_checkout/popup_description';

    public function getTitle(): mixed
    {
        return Mage::getStoreConfig(self::MOBILEPAY_TITLE_XML_PATH, Mage::app()->getStore());
    }

    public function getDescription(): mixed
    {
        return Mage::getStoreConfig(self::MOBILEPAY_DESCRIPTION_XML_PATH, Mage::app()->getStore());
    }

    public function getPopupDescription(): mixed
    {
        return Mage::getStoreConfig(self::MOBILEPAY_POPUP_DESCRIPTION_XML_PATH, Mage::app()->getStore());
    }

    public function isActive(): mixed
    {
        return Mage::getStoreConfig(self::MOBILEPAY_ACTICE_XML_PATH, Mage::app()->getStore());
    }

    public function getRedirectUrl(): string
    {
        return $this->getUrl('pensopay/mobilepay/redirect');
    }

    /**
     * @return array<string, array{title: string, price: string}>
     */
    public function getShippingMethods(): array
    {
        return Mage::getModel('pensopay/carrier_shipping')->getMobilePayMethods();
    }
}
