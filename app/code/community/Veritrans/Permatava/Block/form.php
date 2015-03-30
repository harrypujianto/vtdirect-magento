<?php
class Veritrans_Permatava_Block_Form extends Mage_Payment_Block_Form
{
   
    protected function _construct()
    {
        parent::_construct();
	//$this->setFormMessage(Mage::helper('vtdirect/data')->_getFormMessage());
        $this->setTemplate('permatava/form.phtml');
    }

}
?>