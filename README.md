# Vt-Direct-magento

This is veritrans Vt-Direct plugin/module/extension for magento.
The information about Vt-Direct can be found here http://docs.veritrans.co.id/vtdirect/introduction.html

Tested with:
* Magento 1.8 Default themes & Onepage checkout
* Magento 1.9 RWD themes & Onepage checkout

Payment Channel developed: Credit card, Mandiri Clickpay, CImbClicks, Permata Virtual Account.

How to install:

* download zip 
* Merge App & Skin folder
* go to your magento's backoffice Click 'Save config' button. Go to system->configuration->payment mehtod, then veritrans' payment channels group should be appear on the top of the group
* Please enable the payment channel you wish to use.
* Set the name of payment method
* input server key & client key which can be found in my.sandbox.veritrans.co.id/my.veritrans.co.id, and make sure the environment and the keys match.
* To choose multiple credit card types, hold Ctrl button (for windows & linux) or Command button(for mac) and click
* Set the conversion rate if you don't use IDR currency on your site
* enable 3Dsecure 
* Set the order status into 'processing' for Credit Card & Mandiri clickpay, and Pending for CIMB Clicks
* http://[your website]/vtdirect/payment/notification
