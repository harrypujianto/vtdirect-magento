<?php

/**
 * Veritrans VT Web form block
 *
 * @category   Mage
 * @package    Mage_Veritrans_VtWeb_Block_Form
 * @author     
 * when Veritrans payment method is chosen, vtweb/form.phtml template will be rendered through this class.
 */
class Veritrans_Vtdirect_Block_Form extends Mage_Payment_Block_Form
{
    
    protected function _construct()
    {
        parent::_construct();
	//$this->setFormMessage(Mage::helper('vtdirect/data')->_getFormMessage());
        $this->setTemplate('vtdirect/form.phtml');
    }

    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }

	/**
	*retrieve available CC type, and push to form on onepage checkout payment method section
	*@return type array
	*/
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }

    /**
     *retrieve available CC type, and push to form on onepage checkout payment method section
     * @return array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if (is_null($months)) {
            $months[0] =  $this->__('Month');
            $months = array_merge($months, $this->_getConfig()->getMonths());
            $this->setData('cc_months', $months);
        }
        return $months;
    }

    /**
     * Retrieve credit card expire years
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (is_null($years)) {
            $years = $this->_getConfig()->getYears();
            $years = array(0=>$this->__('Year'))+$years;
            $this->setData('cc_years', $years);
        }
        return $years;
    }

    /*
     *retrieve Veritrans Client Key
     */
    public function getStoreConfig(){
        return Mage::getStoreConfig('payment/vpayment/client');
    }


    /*
    * solo/switch card start year
    * @return array
    */
    public function getSsStartYears()
    {
        $years = array();
        $first = date("Y");

        for ($index=5; $index>=0; $index--) {
            $year = $first - $index;
            $years[$year] = $year;
        }
        $years = array(0=>$this->__('Year'))+$years;
        return $years;
    }

    /**
     * Retrive has verification configuration
     *
     * @return boolean
     */
    public function hasVerification()
    {
        if ($this->getMethod()) {
            $configData = $this->getMethod()->getConfigData('useccv');
            if(is_null($configData)){
                return true;
            }
            return (bool) $configData;
        }
        return true;
    }

    /*
    * Whether switch/solo card type available
    */
    public function hasSsCardType()
    {
        $availableTypes = explode(',', $this->getMethod()->getConfigData('cctypes'));
        $ssPresenations = array_intersect(array('SS', 'SM', 'SO'), $availableTypes);
        if ($availableTypes && count($ssPresenations) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Render block HTML
     * @return string
     */
    protected function _toHtml()
    {
        Mage::dispatchEvent('payment_form_block_to_html_before', array(
            'block'     => $this
        ));
        return parent::_toHtml();
    }
  /**
   * Get Token Browser Encryption Key
   * 
   * @return string
   */
    public function getTokenBrowser() {
        
        return Mage::getSingleton('core/session')->getTokenBrowser();
    }

}
?>