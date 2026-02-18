<?php

class PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Renderer_Grid_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(\Maho\DataObject $row)
    {
        /** @var PensoPay_Payment_Helper_Data $helper */
        $helper = Mage::helper('pensopay');

        /** @var PensoPay_Payment_Model_Payment $payment */
        $payment = Mage::getModel('pensopay/payment')->load($row->getData('id'));

        $extraClass = $helper->getStatusColorCode($payment->getLastCode());
        $html = "
            <div class='payment-status {$extraClass}'>
                {$payment->getDisplayStatus()}
            </div>
        ";

        return $html;
    }
}