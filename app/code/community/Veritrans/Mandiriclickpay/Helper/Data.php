<?php
class Veritrans_Mandiriclickpay_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function sentReqVtrans($comidity)
    {
        $json       = json_encode($comidity);
        $server_key = Mage::getStoreConfig('payment/mandiriclickpay/serverkey');
        $server_key = base64_encode($server_key);

        $enviroment = Mage::getStoreConfig('payment/mandiriclickpay/environment');
        if($environment == 'production')
        {
            $url = "https://api.veritrans.co.id/v2/charge";
        }
        else
        {
            $url = "https://api.sandbox.veritrans.co.id/v2/charge";
        }
        
        $ch         = curl_init($url);
        //curl_setopt($ch, CURLOPT_USERPWD, $server_key.':');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . $server_key
        ));

        $result = curl_exec($ch);

        //Mage::log($json,null,'VTjson.log',true);
        return json_decode($result);
    }
	
}
	 