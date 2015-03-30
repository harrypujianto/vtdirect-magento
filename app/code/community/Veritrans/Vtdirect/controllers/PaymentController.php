<?php

class Veritrans_Vtdirect_PaymentController extends Mage_Core_Controller_Front_Action {

	public function indexAction()
	{
		echo 'hahaha';
	}


  /**
   * @return Mage_Checkout_Model_Session
   */
  protected function _getCheckout() {
    return Mage::getSingleton('checkout/session');
  }

	public function redirectAction() {

    $orderIncrementId = $this->_getCheckout()->getLastRealOrderId();
    //error_reporting($orderIncrementId);
    error_log($orderIncrementId);
    $order = Mage::getModel('sales/order')
        ->loadByIncrementId($orderIncrementId);
    $sessionId = Mage::getSingleton('core/session');

    /* send an order email when redirecting to payment page although payment
       has not been completed. */
    $order->setState(Mage::getStoreConfig('payment/cimb_clicks/order_status'),true,
        'New order, waiting for payment.');
    $order->sendNewOrderEmail();
    $order->setEmailSent(true);

    /*Veritrans_Config::$isSanitized =
        Mage::getStoreConfig('payment/vtweb/enable_sanitized') == '1'
        ? true : false;*/

    $transaction_details = array();
    $transaction_details['order_id'] = $orderIncrementId;
    $order_billing_address = $order->getBillingAddress();
    

    $billing_address = array();
    $billing_address['first_name']   = $order_billing_address->getFirstname();
    $billing_address['last_name']    = $order_billing_address->getLastname();
    $billing_address['address']      = $order_billing_address->getStreet(1);
    $billing_address['city']         = $order_billing_address->getCity();
    $billing_address['postal_code']  = $order_billing_address->getPostcode();
    //$billing_address['country_code'] = $this->convert_country_code($order_billing_address->getCountry());
    $billing_address['phone']        = $order_billing_address->getTelephone();

    $order_shipping_address = $order->getShippingAddress();
    $shipping_address = array();
    $shipping_address['first_name']   = $order_shipping_address->getFirstname();
    $shipping_address['last_name']    = $order_shipping_address->getLastname();
    $shipping_address['address']      = $order_shipping_address->getStreet(1);
    $shipping_address['city']         = $order_shipping_address->getCity();
    $shipping_address['postal_code']  = $order_shipping_address->getPostcode();
    $shipping_address['phone']        = $order_shipping_address->getTelephone();
  //  $shipping_address['country_code'] =
  //      $this->convert_country_code($order_shipping_address->getCountry());

    $customer_details = array();
    $customer_details['billing_address']  = $billing_address;
    $customer_details['shipping_address'] = $shipping_address;
    $customer_details['first_name']       = $order_billing_address
        ->getFirstname();
    $customer_details['last_name']        = $order_billing_address
        ->getLastname();
    $customer_details['email']            = $order_billing_address->getEmail();
    $customer_details['phone']            = $order_billing_address
        ->getTelephone();
error_log($customer_details['email']);
    $items               = $order->getAllItems();
    $shipping_amount     = $order->getShippingAmount();
    $shipping_tax_amount = $order->getShippingTaxAmount();
    $tax_amount = $order->getTaxAmount();

    $item_details = array();


    foreach ($items as $each) {
      $item = array(
          'id'       => $each->getProductId(),
          'price'    => $each->getPrice(),
          'quantity' => $each->getQtyToInvoice(),
          'name'     => $each->getName()
        );
      
      if ($item['quantity'] == 0) continue;
       error_log(print_r($each->getProductOptions(), true));
      $item_details[] = $item;
    }
    
    $num_products = count($item_details);

    unset($each);

    if ($order->getDiscountAmount() != 0) {
      $couponItem = array(
          'id' => 'DISCOUNT',
          'price' => $order->getDiscountAmount(),
          'quantity' => 1,
          'name' => 'DISCOUNT'
        );
      $item_details[] = $couponItem;
    }

    if ($shipping_amount > 0) {
      $shipping_item = array(
          'id' => 'SHIPPING',
          'price' => $shipping_amount,
          'quantity' => 1,
          'name' => 'Shipping Cost'
        );
      $item_details[] =$shipping_item;
    }
    
    if ($shipping_tax_amount > 0) {
      $shipping_tax_item = array(
          'id' => 'SHIPPING_TAX',
          'price' => $shipping_tax_amount,
          'quantity' => 1,
          'name' => 'Shipping Tax'
        );
      $item_details[] = $shipping_tax_item;
    }

    if ($tax_amount > 0) {
      $tax_item = array(
          'id' => 'TAX',
          'price' => $tax_amount,
          'quantity' => 1,
          'name' => 'Tax'
        );
      $item_details[] = $tax_item;
    }

    // convert to IDR
    $current_currency = Mage::app()->getStore()->getCurrentCurrencyCode();
    if ($current_currency != 'IDR') {
      $conversion_func = function ($non_idr_price) {
          return $non_idr_price *
              Mage::getStoreConfig('payment/cimbclicks/conversion_rate');
        };
      foreach ($item_details as &$item) {
        $item['price'] =
            intval(round(call_user_func($conversion_func, $item['price'])));
      }
      unset($item);
    }
    else {
      foreach ($item_details as &$each) {
        $each['price'] = (int) $each['price'];
      }
      unset($each);
    }


    $payloads = array();
    $payloads['transaction_details'] = $transaction_details;
    $payloads['item_details']        = $item_details;
    $payloads['customer_details']    = $customer_details;
    
    $totalPrice = 0;

    foreach ($item_details as $item) {
      $totalPrice += $item['price'] * $item['quantity'];
    }

    $comidity = array(
       'payment_type' => 'cimb_clicks',
       'cimb_clicks' => array(
           'description' => 'hahaha',
         ),
        "transaction_details" => array(
          "order_id" => $orderIncrementId,
          "gross_amount" => $totalPrice
        ),
        "item_details" => $item_details,
        "customer_details" => $customer_details
        );


            $json2       = json_encode($comidity);
            Mage::log('$json2:'.print_r($json2,true),null,'$json2.log',true);
            $sentReq  = Mage::helper('vtdirect')->sentReqVtrans($comidity);
            Mage::log('sentReq:'.print_r($sentReq,true),null,'sentReq.log',true);
            $codeResp = $sentReq->status_code;

            error_log(print_r($sentReq,TRUE));

            error_log($codeResp);
            error_log($sentReq->redirect_url);
    
    
    try {
      //$redirUrl = Veritrans_VtWeb::getRedirectionUrl($payloads);
      $redirUrl = $sentReq->redirect_url;
      $this->_redirectUrl($redirUrl);
      
    }
    catch (Exception $e) {
      error_log($e->getMessage());
    }   
  }

  public function finishAction()
  {
    $raw_response = file_get_contents('php://input');
    error_log(var_dump($raw_response));
    $response = json_decode($raw_response, true);
    
    if($response[status_code] == '200' && $response[order_id] != null && $response[order_id] !=''){
      error_log('try_this');
      $session = Mage::getSingleton('checkout/session');
        
      $session->setQuoteId($session->getLastRealOrderId());
      $session->getQuote();
    }

      $url = Mage::getUrl('vtdirect/payment/success', array('_secure' => true, 'status'=>$codeResp , 'order_id'=>$trx_order_id, 'payment_type'=>$payment_type));
      $this->_redirect($url);

    // $this->loadLayout();
    // $this->renderLayout();

  }

    public function testAction()
  {

    //echo 'test action yang nantinya bakal ada layout';

    $this->loadLayout();
    $this->renderLayout();

  }

  public function notificationAction()
  {

    //echo 'hahaha';
    $response = json_decode(file_get_contents('php://input'), true);
    $order = Mage::getModel('sales/order');
    $order->loadByIncrementId($response[order_id]);

    $transaction = $response[transaction_status];
    error_log($transaction);
    $fraud = $response[fraud_status];

    $logs = '';

    if ($transaction == 'capture') {
      $logs .= 'capture ';
      if ($fraud == 'challenge') {
        $logs .= 'challenge ';
        $order->setStatus('fraud');
      }
      else if ($fraud == 'accept') {
        $logs .= 'accept ';
        $invoice = $order->prepareInvoice()
          ->setTransactionId($order->getId())
          ->addComment('Payment successfully processed by Veritrans.')
          ->register()
          ->pay();

        $transaction_save = Mage::getModel('core/resource_transaction')
          ->addObject($invoice)
          ->addObject($invoice->getOrder());

        $transaction_save->save();

        $order->setStatus('processing');
        $order->sendOrderUpdateEmail(true,
            'Thank you, your payment is successfully processed.');
      }
    }
    else if ($transaction == 'cancel') {
      $logs .= 'cancel ';
      if ($fraud == 'challenge') {
        $logs .= 'challenge ';
        $order->setStatus('canceled');
      }
      else if ($fraud == 'accept') {
        $logs .= 'accept ';
        $order->setStatus('canceled');
      }
       else {
       $order->setStatus('canceled');
      }
    }
    else if ($transaction == 'deny') {
      $logs .= 'deny ';
      $order->setStatus('canceled');
    }   
   else if ($transaction == 'settlement') {
     $logs .= 'settlement ';
     $order->setStatus('processing');
     $order->sendOrderUpdateEmail(true,
            'Thank you, your payment is successfully processed.');
    }
   else if ($transaction == 'pending') {
     $logs .= 'pending ';
     $order->setStatus('Pending Payment');
     $order->sendOrderUpdateEmail(true,
            'Thank you, your payment is successfully processed.');
    }
    else if ($transaction == 'cancel') {
     $logs .= 'canceled';
     $order->setStatus('canceled');
    }
    else {
      $logs .= "*$transaction:$fraud ";
      $order->setStatus('fraud');
    }

    $order->save();

    error_log($logs);
  }

  // The cancel action is triggered when an order is to be cancelled
  public function cancelAction() {
    if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
        $order = Mage::getModel('sales/order')->loadByIncrementId(
            Mage::getSingleton('checkout/session')->getLastRealOrderId());
        if($order->getId()) {
      // Flag the order as 'cancelled' and save it
          $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED,
              true, 'Gateway has declined the payment.')->save();
        }
    }
  }

   private function getEmailCust($checkout){
        $email=$checkout->getQuote()->getShippingAddress()->getData();
        $emailCust=$email['email'];
        if($emailCust==null){
            $emailCust= Mage::getSingleton('customer/session')->getCustomer()->getEmail();
            if($emailCust==null){
                $email=$checkout->getQuote()->getBillingAddress()->getData();
                $emailCust=$email['email'];
            }
        }
        return $emailCust;
    }	

    private function getBillAddr($checkout)
    {
        $getBill = $checkout->getQuote()->getBillingAddress()->getData();
        $bill    = array(
            'first_name' => $getBill['firstname'],
            'last_name' => $getBill['lastname'],
            'address' => substr($this->repString($getBill['street']), 0, 100),
            'city' => $getBill['city'],
            'postal_code' => $getBill['postcode'],
            'phone' => $getBill['telephone'],
            "country_code" => "IDN"
        );
        return $bill;
    }
    
    private function getShippingAddr($checkout)
    {
        $getShipping = $checkout->getQuote()->getShippingAddress()->getData();
        $ship        = array(
            'first_name' => $getShipping['firstname'],
            'last_name' => $getShipping['lastname'],
            'address' => substr($this->repString($getShipping['street']), 0, 100),
            'city' => $getShipping['city'],
            'postal_code' => $getShipping['postcode'],
            'phone' => $getShipping['telephone'],
            "country_code" => "IDN"
        );
        return $ship;
    }

    private function repString($str){
        return preg_replace("/[^a-zA-Z0-9]+/", " ", $str);
    }

    public function permatavaAction()
    {

        $this->loadLayout();
        $this->renderLayout();
    }    


    public function successAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    protected function _redirect($path, $arguments=array())
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('vtdirect/index')
                ->setRedirectUrl(Mage::getUrl($path, $arguments))
                ->toHtml()
        );
        return $this;
    }

}
?>