<?php
class Veritrans_Permatava_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	
	protected $_code = 'permatava';

	protected $_canUseCheckout          = true;
	protected $_canUseInternal          = true;

	protected $_isInitializeNeeded      = true;

	protected $_canUseForMultishipping  = false;

	protected $_canAuthorize            = true;
	protected $_formBlockType = 'permatava/form';


	/**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
	public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
            
        }
        $session    = Mage::getSingleton('core/session');
        $session->setVeritransQuoteId($this->_getOrderId());
        $session->setTokenBrowser($data->getTokenId());
        return $this;
    }

    private function _getOrderId()
    {
        $info = $this->getInfoInstance();

        if ($this->_isPlaceOrder()) {
            return $info->getOrder()->getIncrementId();
        } else {
            if (!$info->getQuote()->getReservedOrderId()) {
                $info->getQuote()->reserveOrderId();
            }
            return $info->getQuote()->getReservedOrderId();
        }
    }

    private function _isPlaceOrder()
    {
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Quote_Payment) {
            return false;
        } elseif ($info instanceof Mage_Sales_Model_Order_Payment) {
            return true;
        }
    }

}
?>