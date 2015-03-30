<?php   
class Veritrans_Vtdirect_Block_Index extends Mage_Core_Block_Template{   

    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('vtdirect/redirect.phtml');
    }



}