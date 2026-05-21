<?php

declare(strict_types=1);

class PensoPay_Payment_Model_Method_Dankort extends PensoPay_Payment_Model_Method
{
    protected $_code = 'pensopay_dankort';
    protected $_formBlockType = 'pensopay/form_dankort';

    /**
     * Get payment methods
     */
    #[\Override]
    public function getPaymentMethods(): mixed
    {
        return 'dankort';
    }
}
