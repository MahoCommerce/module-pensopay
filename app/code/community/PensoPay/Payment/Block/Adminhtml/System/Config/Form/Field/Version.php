<?php

class PensoPay_Payment_Block_Adminhtml_System_Config_Form_Field_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Render field
     *
     * @param \Maho\Data\Form\Element\AbstractElement $element
     * @return string
     */
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
     * @param \Maho\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Maho\Data\Form\Element\AbstractElement $element)
    {
        $installedVersion = Mage::getConfig()->getNode()->modules->PensoPay_Payment->version;

        return "<strong>" . $installedVersion . "</strong>";
    }
}
