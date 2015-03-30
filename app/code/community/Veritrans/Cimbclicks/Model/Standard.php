<?php
class Veritrans_Cimbclicks_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	
	protected $_code = 'cimbclicks';

	protected $_canUseCheckout          = true;
	protected $_canUseInternal          = true;


	protected $_isInitializeNeeded      = true;
	
	protected $_canUseForMultishipping  = false;
	
	protected $_canAuthorize            = true;
	protected $_formBlockType = 'cimbclicks/cimbclicks';

	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('vtdirect/payment/redirect', array('_secure' => true));
	}
	
}
?>
