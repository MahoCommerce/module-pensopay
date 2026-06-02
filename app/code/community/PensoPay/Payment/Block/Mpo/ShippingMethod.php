<?php

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
