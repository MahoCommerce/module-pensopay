<?php

declare(strict_types=1);

class PensoPay_Payment_Model_Method_Paypal extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_paypal';
    protected $_formBlockType = 'pensopay/form_paypal';

    /**
     * Get payment methods
     *
     * @return mixed
     */
    #[\Override]
    public function getPaymentMethods()
    {
        return 'paypal';
    }
}
