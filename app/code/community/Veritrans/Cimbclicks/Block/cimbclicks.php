<?php   
class Veritrans_Cimbclicks_Block_Cimbclicks extends Mage_Payment_Block_Form{   

 protected function _construct()
    {
        parent::_construct();
	//$this->setFormMessage(Mage::helper('vtdirect/data')->_getFormMessage());
        $this->setTemplate('cimbclicks/form.phtml');
    }

}