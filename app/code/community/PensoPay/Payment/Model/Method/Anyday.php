<?php

declare(strict_types=1);

class PensoPay_Payment_Model_Method_Anyday extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_anyday';
    protected $_formBlockType = 'pensopay/form_anyday';

    /**
     * Get payment methods
     *
     * @return mixed
     */
    #[\Override]
    public function getPaymentMethods()
    {
        return 'anyday-split';
    }

    #[\Override]
    public function canUseForCurrency($currencyCode)
    {
        return $currencyCode === 'DKK'; //Anyday-split currently only has DKK available
    }
}
