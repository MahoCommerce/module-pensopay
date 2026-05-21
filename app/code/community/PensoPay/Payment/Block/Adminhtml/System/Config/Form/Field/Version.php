<?php

class PensoPay_Payment_Block_Adminhtml_System_Config_Form_Field_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Render field
     *
     * @return string
     */
    #[\Override]
    public function render(\Maho\Data\Form\Element\AbstractElement $element)
    {
        //Hide scope checkbox and label
        $element->setCanUseWebsiteValue(0);
        $element->setCanUseDefaultValue(0);
        $element->setScope(0);

        return parent::render($element);
    }

    /**
     * Get extension version
     *
     * @return string
     */
    #[\Override]
    protected function _getElementHtml(\Maho\Data\Form\Element\AbstractElement $element)
    {
        $node = Mage::getConfig()?->getNode();
        $installedVersion = $node ? $node->modules->PensoPay_Payment->version : '';

        return '<strong>' . $installedVersion . '</strong>';
    }
}
