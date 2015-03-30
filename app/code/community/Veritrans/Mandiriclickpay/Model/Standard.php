<?php
class Veritrans_Mandiriclickpay_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	
	protected $_code = 'mandiriclickpay';

	protected $_canUseCheckout          = true;
	protected $_canUseInternal          = true;


	protected $_isInitializeNeeded      = true;
	
	protected $_canUseForMultishipping  = false;
	
	protected $_canAuthorize            = true;
	protected $_formBlockType = 'mandiriclickpay/mandiriclickpay';
	//protected $_infoBlockType = 'vtdirect/info';

	public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);

        }
        $session    = Mage::getSingleton('core/session');
        $info = $this->getInfoInstance();
        $info->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear());
        $session->setVeritransQuoteId($this->_getOrderId());
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
