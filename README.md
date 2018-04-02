
Sri Lanka Mobitel mCash internet payment gateway for Opencart 3
===============================================================

This is fully working solution for Sri Lanka Mobitel mCash payment gateway
integration.

 

Instructions
------------

Just upload the contents in the upload directory. And install the payment
gateway through the admin interface.


Mobitel setup
-------------
Give following URLs to Mobitel for registering your site.

Note: Replace "online.shop.url" with your url.

**Cancel URL**
http://online.shop.url/index.php?route=checkout/checkout

**Success URL**
http://online.shop.url/index.php?route=checkout/success

**Failure URL**
http://online.shop.url/index.php?route=checkout/failure

**Callback URL**
http://online.shop.url/index.php?route=extension/payment/mcash_ipg/callback

**Logo URL**
http://online.shop.url/image/catalog/logo.png
 

Test Mode
---------
You can test this with the mobitel test payment gateway with test merchant
details. Put the mode to “Test”.

 

Live Mode
---------
After successfully registration as Mobitel merchant enter relevant data.

Put the mode to “Live”

That is it...!!!

 

For detailed checkout failure messages
--------------------------------------

Upload the contents in the “extra” folder for showing error message received
from the Mobitel payment gateway when there is a transaction failure.

 

Enjoy... :)

 

### Support

Feel free to contact me for any support.

Email: induwarabas@gmail.com 

###  
