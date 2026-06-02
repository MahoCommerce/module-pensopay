<?php

class PensoPay_Payment_Helper_Checkout extends Mage_Core_Helper_Abstract
{
    /**
     * Restore last active quote based on checkout session
     *
     * @return bool True if quote restored successfully, false otherwise
     */
    public function restoreQuote(): bool
    {
        $order = $this->getCheckoutSession()->getLastRealOrder();

        if ($order && $order->getId()) {
            $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
            if ($quote->getId()) {
                $quote->setIsActive(1)
                    // setReservedOrderId() magic setter rejects null per its docblock signature
                    ->setData('reserved_order_id', null)
                    ->save();
                $this->getCheckoutSession()
                    ->replaceQuote($quote)
                    ->unsLastRealOrderId();

                return true;
            }
        }

        return false;
    }

    public function getPaymentConfig(string $value): mixed
    {
        return Mage::getStoreConfig('payment/pensopay/' . $value, Mage::app()->getStore());
    }

    public function isCheckoutIframe(): bool
    {
        $checkoutMethod = Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_CHECKOUT_METHOD);
        return $checkoutMethod === PensoPay_Payment_Model_System_Config_Source_CheckoutMethods::METHOD_IFRAME;
    }

    public function isCheckoutEmbedded(): bool
    {
        $checkoutMethod = Mage::getStoreConfig(PensoPay_Payment_Model_Config::XML_PATH_CHECKOUT_METHOD);
        return $checkoutMethod === PensoPay_Payment_Model_System_Config_Source_CheckoutMethods::METHOD_EMBEDDED;
    }

    public function getCheckoutSession(): Mage_Checkout_Model_Session
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getCoreSession(): Mage_Core_Model_Session
    {
        return Mage::getSingleton('core/session');
    }
}
