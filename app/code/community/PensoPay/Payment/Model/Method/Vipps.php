<?php

declare(strict_types=1);

class PensoPay_Payment_Model_Method_Vipps extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_vipps';
    protected $_formBlockType = 'pensopay/form_vipps';

    /**
     * Get payment methods
     *
     * @return mixed
     */
    #[\Override]
    public function getPaymentMethods()
    {
        return 'vipps';
    }
}
