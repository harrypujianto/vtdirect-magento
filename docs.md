#Magento Vt-Direct 

Dokumentasi teknikal untuk Module VT-Direct Magento

* Payment module dibuat untuk onepage checkout (Versi 1.8 & 1.9)
* Menggunakan default themes
* Diperlukan untuk merubah 2 File

## File 1 - Payment.phtml (App/design/frontend/template/your_package/your_themes/template/checkout/onepage/payment.phtml)
Jika menggunakan Base package / RWD package step ini tidak pelu dilakukan, hanya perlu replace file

(bagian 1)

Old: (baris 50)
```<button type="button" class="button" onclick="payment.save()"><span><span><?php echo $this->__('Continue') ?></span></span></button>```

di gantikan dengan

New		
```<button type="button" class="button" onclick="savePayment()"><span><span><?php echo $this->__('Continue') ?></span></span></button>```

(end of bagian 1)
## File 2 - onepage.phtml (App/design/frontend/template/base/default/template/checkout/onepage.phtml)
Jika menggunakan Base package / RWD package step ini tidak perlu dilakukan,hanya perlu replace file.

Tambahkan code ini di awal code
(bagian 2)
```
<?php
$order=Mage::helper('checkout')->getQuote()->getData();
$shipping = Mage::helper('checkout')->getQuote()->getShippingAddress()->getShipping_amount();
$grandTotal=round($order['grand_total']);
$grandTotal = ($current_currency != 'IDR') ? $grandTotal * $curr_rate : $grandTotal;
$mode = Mage::getStoreConfig('payment/vtdirect/environment'); //value = sandbox/production
$curr_rate = Mage::getStoreConfig('payment/vtdirect/conversion_rate');
$current_currency = Mage::app()->getStore()->getCurrentCurrencyCode();
$shipping = ($current_currency != 'IDR') ? round($shipping) * $curr_rate : $shipping;
?>
```
Code diatas digunakan untuk populate data-data yang diperlukan untuk tokenisasi kartu kredit.

(end of bagian 2)


(bagian 3)
```
<script type="text/javascript" src="<?php echo $this->getSkinUrl('js/fancybox/jquery.fancybox.js') ?>"></script>
<script type="text/javascript" src="<?php echo $this->getSkinUrl('js/no-conflict.js') ?>"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $this->getSkinUrl('js/fancybox/jquery.fancybox.css') ?>">
<?php if($mode == 'production') { ?>
    <script type="text/javascript" src="https://api.veritrans.co.id/v2/assets/js/veritrans.min.js"> </script>
<?php } else { ?>
    <script type="text/javascript" src="https://api.sandbox.veritrans.co.id/v2/assets/js/veritrans.min.js"></script>
<?php } ?>
```

Code diatas digunakan untuk menabhankan script fancybox yang akan digunakan untuk halaman 3ds, dan juga script untuk tokenisasi.

(end of bagian 3)

Tambahkan code ini di akhir baris code

(bagian 4)
```
<script type="text/javascript">

    function savePayment()
    { 
        var curr =  "<?php echo $current_currency;?>" ; 
        var total = Math.round($("price").value);
        var grand_total =  (curr.toString() != 'IDR') ? Math.round(total) * <?php echo $curr_rate;?> : total;
        console.log(grand_total);

            <?php if($mode == 'production')
            {?>
                Veritrans.url = "https://api.veritrans.co.id/v2/token";
            <?php
            }
            else
            {
            ?>
                Veritrans.url = "https://api.sandbox.veritrans.co.id/v2/token";
            <?php
            }
            ?>
            
            Veritrans.client_key = "<?php echo Mage::getStoreConfig('payment/vtdirect/clientkey');?>"; //add client-key from veritrans
            var secure = false;
            if("<?php echo Mage::getStoreConfig('payment/vtdirect/enable_3d_secure'); ?>" == '1') {
                secure = true;
            }

            function card(){
                return {
                    "card_number" : $("vtdirect_cc_number").value,
                    "card_exp_month": $("vtdirect_expiration").value,
                    "card_exp_year" : $("vtdirect_expiration_yr").value,
                    "card_cvv" : $("vtdirect_cc_cid").value,
                    "secure"        : secure,
                    "gross_amount"  : grand_total
                }
                
            };

            function callback(response){
               // alert('5:'+JSON.stringify(response));
                console.log('5:'+JSON.stringify(response));
            if (response.redirect_url) {
                // 3dsecure transaction, please open this popup
                if(secure == true){
                openDialog(response.redirect_url);    
                }

            } else if (response.status_code == '200') {

                // success 3d secure or success normal
                //if(secure == true){
                closeDialog();    
                //}

                //$("#token_id").val(response.token_id);
                $('token_id').value = response.token_id; // store token data in input #token_id
                console.log('tokenid='+$('token_id').value);

                var timeout = setTimeout(function() {
                        if(response.status_code=='200'){
                            payment.save();
                        }else{
                            _error(response);
                        }
                    }, 2000);


            } else {
                // failed request token
                 _error(response);
                console.log('Close Dialog - failed');
                closeDialog();
                $('token_id').value = response.token_id;
                payment.save();
            }
        }

        function _error(response){//alert('12:'+JSON.stringify(response));
                mytext=response.status_message.replace('[','');
                mytext=mytext.replace(']','');
        }

        function openDialog(url) {
            jQuery.fancybox.open({
                href: url,
                type: 'iframe',
                autoSize: false,
                width: 700,
                height: 500,
                closeBtn: false,
                modal: true
            });
        }

        function closeDialog() {
            jQuery.fancybox.close();
        }   

            if(payment.currentMethod=='vtdirect'){
            Veritrans.token(card, callback);
            }
            else{
                payment.save();
            }
    }

    
    function mandiri()
    {       
        var curr =  "<?php echo $current_currency;?>" ; 
        var total = Math.round($("price").value);
        var grand_total =  (curr.toString() != 'IDR') ? Math.round(total) * <?php echo $curr_rate;?> : total;
        var cut;
        document.getElementById("mandiriclickpay_input2").value = grand_total;
        document.getElementById("mandiriclickpay_input3").value = random5digit();
        document.getElementById("mandiriclickpay_input1").value = updateInput1();

        function updateInput1(){
            var ccNumber = document.getElementById('mandiriclickpay_card_number').value;
            if(ccNumber.length > 9) {
               var cut = ccNumber.substring(ccNumber.length, ccNumber.length-10);
            }
            return cut;
        }

        function paddy(n, p, c) {
            var pad_char = typeof c !== 'undefined' ? c : '0';
            var pad = new Array(1 + p).join(pad_char);
            return (pad + n).slice(-pad.length);
        }

        function random5digit(){
            return paddy(Math.floor(Math.random() * 99999), 5); 
        }
     
    }    
</script>
```

Snippet code diatas digunakan untuk proses tokenisasi kartu kredit, dan populate data untuk mandiri clickpay

(end of bagian 4)


#Error-error yang mungkin terjadi
1. Jika payment.phtml tidak diubah menurut(bagian 1), maka akan terdapat error 411 (token id is missing, invalid or timed out).
2. Jika code pada bagian 2 tidak diimplementasikan maka akan terdapat error:” Uncaught SyntaxError: Unexpected token :” pada console, jika di-inspect emelent di browser.
3. Jika code pada bagian 3 tidak diimplementasikan maka akan terdapat error “Uncaught ReferenceError: Veritrans is not defined”  pada console, jika di-inspect emelent di browser
4. Jika code pada bagian 4 tidak diimplementasikan maka akan terjadi error ketika klik tombol continue pada step 4 di onepageChekcout. pesan error yang terdapat di console log tampak seperti ini “Uncaught ReferenceError: savePayment is not defined”
5. Jika payment method yang dipilih adalah mandiri clickpay, dan code pada bagian 4 tidak diimplementasikan, maka terdapat pesan error “Uncaught ReferenceError: mandiri is not defined” di console.

# Model/type
pada folder app/code/community/veritrans/vtdirect/model/type/onepage.php
Jika menggunakan versi magento 1.8, silahkan rename file onepage 1.8.php menjadi onepage.php. Step ini tidak perlu dilakukan jika menggunakan versi 1.9

