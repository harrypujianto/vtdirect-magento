<?php   
class Veritrans_Mandiriclickpay_Block_Mandiriclickpay extends Mage_Payment_Block_Form{   

 protected function _construct()
    {
        parent::_construct();
	//$this->setFormMessage(Mage::helper('vtdirect/data')->_getFormMessage());
        $this->setTemplate('mandiriclickpay/form.phtml');
    }

}