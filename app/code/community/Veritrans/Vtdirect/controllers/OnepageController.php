<?php
require_once 'Mage/Checkout/controllers/OnepageController.php';
class Veritrans_Vtdirect_OnepageController extends Mage_Checkout_OnepageController
{
	public function saveOrderAction()
	{
      
     $paymentMethod = Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethodInstance()->getCode(); 
    
            $result = array();
            $session = Mage::getSingleton('core/session');
            $checkout    = Mage::getSingleton('checkout/session');
            $email       = $this->getEmailCust($checkout);
            $quote_items = $this->getOnepage()->getQuote()->getAllItems();
            $server_key  = base64_encode($this->getServerKey());
        
            $items       = $checkout->getQuote()->getAllItems();
            
            $countItem   = Mage::helper('checkout/cart')->getItemsCount();
            $order_id=$checkout->getQuote()->getReservedOrderId();

            $curr_rate = Mage::getStoreConfig('payment/vtdirect/conversion_rate');
            $current_currency = Mage::app()->getStore()->getCurrentCurrencyCode(); 
            $shipRates   = round($checkout->getQuote()->getShippingAddress()->collectShippingRates()->getShippingAmount());
            $shipRates = ($current_currency != 'IDR') ? $shipRates * $curr_rate : $shipRates; 

            $i           = 1;
            $gross       = null;
            $disc        = null;
            $arrDscn     = null;
            $minDisc     = 0;
            $discAvg     = 0;
            $disc        = $checkout->getQuote()->getData();
            $gt          = (int) $disc['grand_total'];
            $st          = (int) $disc['subtotal'];

//start calculating data for json body

            foreach ($items as $item) {

                $base_price  = intval(round($item->getPrice()));
                $price = ($current_currency != 'IDR') ? $base_price * $curr_rate : $base_price;
                $qty=$item->getQty();
                $calcPrice=(int)$price;
                $calcDisc=(int)$discount;
                if($price!=0){
                    $discAvg=0;
                    if($discount!=0)$discAvg=round(($calcPrice*$calcDisc)/$st);
                    $price=round($price-$discAvg);
                    $cart[] = array(
                        'id' => $item->getSku(),
                        'price' => $price,
                        'quantity' => $item->getQty(),
                        'name' => $this->repString($this->getName($item->getName()))
                    );
                };
                $gross  = $gross + (number_format($price, 0, '.', '') * $item->getQty());
                $i++;
            };
            
            $merge[]  = array(
                'id' => 1,
                'price' => number_format($shipRates, 0, '.', ''),
                'quantity' => 1,
                'name' => 'Shipping'
            );

            $token = $this->getToken();

            $checkout->getQuote()->setReservedOrderId($order_id);
            $arry     = array_merge($cart, $merge);
            $gross    = $gross + $shipRates;
            $ship_data = $checkout->getQuote()->getShippingAddress()->getData();
            $firstname = $ship_data['firstname'];
            $lastname = $ship_data['lastname'];
            $phone = $ship_data['telephone'];
// end of calculating data

            $customer_details = array();
            $customer_details['first_name'] = $firstname;
            $customer_details['last_name'] = $lastname;
            $customer_details['email'] = $email;
            $customer_details['phone'] = $phone;
            $customer_details['billing_address'] = $this->getBillAddr($checkout);
            $customer_details['shipping_address'] = $this->getShippingAddr($checkout);

            $transaction_details = array();
            $transaction_details['order_id'] = $order_id;
            $transaction_details['gross_amount'] = intval(round($gross));


            if($paymentMethod == 'vtdirect')
            {
                $cc_arr = array(
                    'token_id' => $token
                );

                $comidity = array(
                    'payment_type' => 'credit_card',
                    'credit_card' => $cc_arr,
                    "transaction_details" => $transaction_details,
                    "item_details" => $arry,
                    "customer_details" => $customer_details
                );
            }
             else if($paymentMethod == 'mandiriclickpay')
            {

             $mandiri = $this->getRequest()->getPost('payment', array());
            
                $comidity = array(
                    'payment_type' => 'mandiri_clickpay',
                    'mandiri_clickpay' => array(
                          'card_number' => $mandiri['cc_number'],
                          'input1' => $mandiri['input1'],
                          'input2' => $mandiri['total_amount'],
                          'input3' => $mandiri['input3'],
                          'token' => $mandiri['challenge_token']
                        ),
                    "transaction_details" => $transaction_details,
                    "item_details" => $arry,
                    "customer_details" => $customer_details
                );   
            }
            else if($paymentMethod == 'permatava')
            {

                    $comidity = array(
                        'payment_type' => 'bank_transfer',
                        'bank_transfer' => array(
                              'bank' => 'permata',
                            ),
                        "transaction_details" => $transaction_details,
                        "item_details" => $arry,
                        "customer_details" => $customer_details
                    );
            }
        
        if($paymentMethod == 'permatava' ||$paymentMethod == 'vtdirect' || $paymentMethod == 'mandiriclickpay')
        {

            $json2    = json_encode($comidity);
            Mage::log('$json2:'.print_r($json2,true),null,'$json2.log',true);
            $sentReq  = Mage::helper('vtdirect')->sentReqVtrans($comidity);
            Mage::log('sentReq:'.print_r($sentReq,true),null,'sentReq.log',true);
            $codeResp = $sentReq->status_code;
            $trx_order_id = $sentReq->order_id;
            $transaction_id = $sentReq->transaction_id;
            $status_message = $sentReq->status_message;
            $va_number = $sentReq->permata_va_number;
            $expire = $sentReq->transaction_time;
            $total = $sentReq->gross_amount;

             try {
                if ($requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds()) {
                    $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                    if ($diff = array_diff($requiredAgreements, $postedAgreements)) {
                        $result['success']        = false;
                        $result['error']          = true;
                        $result['error_messages'] = $this->__('Please agree to all the terms and conditions before placing the order.');
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        return;
                    }
                }

                switch($codeResp)
                {
                    case '200':
                    error_log('200');
                   //credit card

                        $this->resultSave($quote_items,$transaction_id,$paymentMethod);
                        $result['success'] = true;
                        $result['error']   = false;
                        
                        break;
                    case '201':    
                        error_log('201');
                        $this->resultSave($quote_items,$transaction_id,$paymentMethod);
                        $new_order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
                        $new_order->setStatus(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
                        $new_order->save();

                        $result['success'] = true;
                        $result['error']   = false;
                        
                        if($paymentMethod == 'permatava')
                        {
                        $result['redirect'] = Mage::getUrl('vtdirect/payment/permatava', array('_secure' => true, 'status'=>$status_message , 'order_id'=>$trx_order_id , 'va_number'=>$va_number, 'expire'=>$expire, 'total'=>$total));
                        }
                        break;

                       case '202':    
                        error_log('202');
                        $this->resultSave($quote_items,$transaction_id,$paymentMethod);
                        
                        $new_order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
                        $new_order->setStatus(Mage_Sales_Model_Order::STATE_CLOSED);
                        $new_order->save();

                        $result['success'] = true;
                        $result['error']   = false;
                        $result['redirect'] = Mage::getUrl('vtdirect/payment/test', array('_secure' => true, 'status'=>$codeResp , 'order_id'=>$trx_order_id));
                        break;

                        default:
                        # code...
                        $vt_message = $sentReq->status_message;
                        error_log($vt_message);
                        $result['success']        = false;
                        $result['error']          = true;
                        
                        $result['error_messages'] = $this->__($vt_message).' ('.$codeResp.','.$order_id.')';
                        $result['goto_section']   = 'payment';
                        break;   
                }
            }
            catch (Mage_Core_Exception $e) {
                Mage::logException($e);
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                $result['success']        = false;
                $result['error']          = true;
                $result['error_messages'] = $e->getMessage();
                
                if ($gotoSection = $this->getOnepage()->getCheckout()->getGotoSection()) {
                    $result['goto_section'] = $gotoSection;
                    $this->getOnepage()->getCheckout()->setGotoSection(null);
                }
                
                if ($updateSection = $this->getOnepage()->getCheckout()->getUpdateSection()) {
                    if (isset($this->_sectionUpdateFunctions[$updateSection])) {
                        $updateSectionFunction    = $this->_sectionUpdateFunctions[$updateSection];
                        $result['update_section'] = array(
                            'name' => $updateSection,
                            'html' => $this->$updateSectionFunction()
                        );
                    }
                    $this->getOnepage()->getCheckout()->setUpdateSection(null);
                }
            }
            catch (Exception $e) {
                Mage::logException($e);
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                $result['success']        = false;
                $result['error']          = true;
                $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
            }
            $this->getOnepage()->getQuote()->save();
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

        }    
        else
        {
            parent::saveOrderAction();
        }

	}

    private function resultSave($quote, $transaction_id,$paymentMethod)
    {
        
        foreach ($quote as $item) {
            $item->save();
        }
        $transaction_id = $sentReq->transaction_id;
        $this->getOnepage()->getQuote()->setPayType($paymentMethod)->save();
        if ($data = $this->getRequest()->getPost('payment', false)) {
        $this->getOnepage()->getQuote()->getPayment()->importData($data);
        }
        $this->getOnepage()->saveOrder();

    }

	private function getServerKey()
    {
        return Mage::getStoreConfig('payment/vtdirect/serverkey');
    }
    
    private function getToken()
    {
        return Mage::getSingleton('core/session')->getTokenBrowser();
    }

    private function repString($str){
        return preg_replace("/[^a-zA-Z0-9]+/", " ", $str);
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

    private function getName($s)
    {
        $max_length = 20;
        if (strlen($s) > $max_length) {
            $offset = ($max_length - 3) - strlen($s);
            $s      = substr($s, 0, strrpos($s, ' ', $offset));
        }
        return $s;
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

}
?>
