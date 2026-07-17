<?php

/**
 * ViaBill payment form block that also renders the ViaBill pricetag widget.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

class PensoPay_Payment_Block_Form_Viabill extends Mage_Payment_Block_Form
{
    /**
     * Instructions text
     */
    protected ?string $_instructions = null;

    #[\Override]
    protected function _construct(): void
    {
        $this->setTemplate('pensopay/payment/form.phtml');
        parent::_construct();
    }

    public function getConfigData(string $key): mixed
    {
        return $this->getMethod()->getConfigData($key);
    }

    /**
     * Append logo on payment selection form
     */
    public function getMethodLabelAfterHtml(): string
    {
        return
            sprintf(
                '<img src="%s" height="%s" alt="%s"/>
            <div class="viabill-pricetag" data-view="payment" data-price="%s"></div>
            <script type="text/javascript">viabillReset();</script>
            ',
                $this->getSkinUrl('images/pensopaypayment/viabill.png'),
                Mage::getStoreConfig('payment/pensopay/cardlogos_size'),
                'Viabill',
                number_format(Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal(), 2),
            );
    }
}
