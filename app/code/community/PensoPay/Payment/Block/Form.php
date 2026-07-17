<?php

/**
 * Base payment form block that renders the configured card logos next to the method label.
 *
 * SPDX-FileCopyrightText: 2026 Maho <https://mahocommerce.com>
 * SPDX-FileCopyrightText: 2019-2022 PensoPay <https://pensopay.com>
 * SPDX-License-Identifier: OSL-3.0
 * @package PensoPay_Payment
 */

class PensoPay_Payment_Block_Form extends Mage_Payment_Block_Form
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
        $cardsConfig = Mage::getStoreConfig('payment/pensopay/cardlogos');
        $cards = explode(',', (string) $cardsConfig);

        $html = '';
        if (count($cards) > 0) {
            $logoConfig = Mage::getModel('pensopay/system_config_source_cardlogos');
            foreach ($cards as $card) {
                $html .= sprintf('<img src="%s" height="%s" alt="%s"/>', $this->getSkinUrl("images/pensopaypayment/{$card}.png"), Mage::getStoreConfig('payment/pensopay/cardlogos_size'), $logoConfig->getFrontendLabel($card));
            }
        }

        return $html;
    }
}
