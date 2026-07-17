<?php

/**
 * Grid container for the Virtual Terminal payments listing.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

class PensoPay_Payment_Block_Adminhtml_Virtualterminal extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_removeButton('add');

        $this->_addButtonLabel = $this->__('Create Payment');
        $this->_addButton('edit', [
            'label'     => $this->getAddButtonLabel(),
            'onclick'   => 'setLocation(\'' . $this->getCreateUrl() . '\')',
            'class'     => 'add',
        ]);
    }

    #[\Override]
    public function getCreateUrl(): string
    {
        return $this->getUrl('*/*/edit');
    }

    #[\Override]
    protected function _toHtml(): string
    {
        $html = '';
        $session = Mage::getSingleton('adminhtml/session');
        if ($session->getPaymentLink()) {
            $paymentBlock = $this->getChild('payment_additional');
            $paymentBlock->setPaymentLink($session->getData('payment_link', true)); //Avoid repeat popups
            $paymentBlock->setPaymentLinkAutovisit($session->getData('payment_link_autovisit', true)); // ^
            $html = $this->getChildHtml('payment_additional');
        }
        $html .= parent::_toHtml();
        return $html;
    }

    #[\Override]
    protected function _construct(): void
    {
        $this->_blockGroup = 'pensopay';
        $this->_controller = 'adminhtml_virtualTerminal';
        $this->_headerText = $this->__('PensoPay Payments');
        parent::_construct();
    }
}
