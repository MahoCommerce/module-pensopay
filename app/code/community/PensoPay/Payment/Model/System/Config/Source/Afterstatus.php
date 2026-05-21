<?php

class PensoPay_Payment_Model_System_Config_Source_Afterstatus
{
    /** @var array<string> */
    protected array $_stateStatuses = [
        Mage_Sales_Model_Order::STATE_NEW,
        Mage_Sales_Model_Order::STATE_PROCESSING,
        Mage_Sales_Model_Order::STATE_HOLDED,
    ];

    /**
     * @return array<array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        if ($this->_stateStatuses) {
            $statuses = Mage::getSingleton('sales/order_config')->getStateStatuses($this->_stateStatuses);
        } else {
            $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
        }
        $options = [];
        $options[] = [
            'value' => '',
            'label' => Mage::helper('adminhtml')->__('-- Please Select --'),
        ];
        foreach ($statuses as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => $label,
            ];
        }
        return $options;
    }
}
