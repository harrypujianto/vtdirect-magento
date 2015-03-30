# Vt-Direct-magento

This is veritrans Vt-Direct plugin/module/extension for magento.
The information about Vt-Direct can be found here http://docs.veritrans.co.id/vtdirect/introduction.html

Tested with maegnto 1.8 & 1.9 with base package & default themes

Payment Channel developed: Credit card, Mandiri Clickpay, CImbClicks, Permata Virtual Account.

How to install:
1. download zip
2. Merge App & Skin folder
3. go to your magento's backoffice Click 'Save config' button. Go to system->configuration->payment mehtod, then veritrans' payment channels group should be appear on the top of the group
4. Please enable the payment channel you wish to use.
5. Set the name of payment method
6. input server key & client key which can be found in my.sandbox.veritrans.co.id/my.veritrans.co.id, and make sure the environment and the keys match.
7. To choose multiple credit card types, hold Ctrl button (for windows & linux) or Command button(for mac) and click
8. Set the conversion rate if you don't use IDR currency on your site
9. enable 3Dsecure 
10. Set the order status into 'processing' for Credit Card & Mandiri clickpay, and Pending for CIMB Clicks
11. http://[your website]/vtdirect/payment/notification

This plugin might have bug(s) which haven't been discovered yet.
If you wish to use the other package and themes please move the layout and template folder into your desired package & themes

