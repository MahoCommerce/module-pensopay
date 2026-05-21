<?php

declare(strict_types=1);

class PensoPay_Payment_Model_Resource_Payment_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('pensopay/payment');
    }
}
