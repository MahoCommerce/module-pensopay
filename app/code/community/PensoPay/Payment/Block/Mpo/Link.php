<?php

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
