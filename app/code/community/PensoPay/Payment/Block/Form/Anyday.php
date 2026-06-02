<?php

class PensoPay_Payment_Block_Form_Anyday extends Mage_Payment_Block_Form
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
        return sprintf('<img src="%s" height="%s" alt="%s"/>', $this->getSkinUrl('images/pensopaypayment/anyday.png'), Mage::getStoreConfig('payment/pensopay/cardlogos_size'), 'Anyday');
    }
}
