<?php

class PensoPay_Payment_Block_Adminhtml_VirtualTerminal_Edit_Renderer_Operations extends \Maho\Data\Form\Element\AbstractElement
{
    #[\Override]
    public function getElementHtml(): string
    {
        /** @var PensoPay_Payment_Helper_Data $helper */
        $helper = Mage::helper('pensopay');

        $value = $this->getValue();
        $operationsArray = Mage::helper('core')->jsonDecode($value);

        if (!empty($operationsArray)) {
            $html = '<table class="operations">';
            $html .= sprintf('<tr><th>%s</th><th>%s</th><th>%s</th></tr>', $helper->__('Type'), $helper->__('Result'), $helper->__('Time'));
            foreach ($operationsArray as $operation) {
                $timestamp = strtotime((string) $operation['created_at']);
                $createdAt = $timestamp !== false ? date('d-m-Y H:i:s', $timestamp) : '';
                $html .= sprintf('<tr class="%s"><td>%s</td><td>%s: %s</td><td>%s</td></tr>', $helper->getStatusColorCode($operation['qp_status_code']), $operation['type'], $operation['qp_status_code'], $operation['qp_status_msg'], $createdAt);
            }
            $html .= '</table>';
            return $html;
        }
        return $helper->__('Error during operations rendering.');
    }
}
