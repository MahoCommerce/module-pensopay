<?php

/**
 * Shipping method selection block for the MobilePay Checkout flow.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

class PensoPay_Payment_Block_Mpo_ShippingMethod extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    /**
     * Get save shipping url
     */
    public function getPostActionUrl(): string
    {
        return $this->getUrl('*/*/shippingPost');
    }

    /**
     * Get Cart URL
     */
    public function getBackUrl(): string
    {
        return Mage::helper('checkout/cart')->getCartUrl();
    }
}
