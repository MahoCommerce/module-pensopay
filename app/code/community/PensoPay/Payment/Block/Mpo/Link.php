<?php

/**
 * Renders the MobilePay Checkout link on the cart page.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

declare(strict_types=1);

class PensoPay_Payment_Block_Mpo_Link extends Mage_Core_Block_Template
{
    public function isMobilePayCheckoutEnabled(): bool
    {
        return false;
    }

    /**
     * Get MobilePay Checkout URL
     */
    public function getCheckoutUrl(): string
    {
        return $this->getUrl('payment/checkout/mobilepay', ['_secure' => true]);
    }
}
