<?php
class ControllerPaymentMCashIpg extends Controller {
    public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');

		$data['text_loading'] = $this->language->get('text_loading');

		$data['continue'] = $this->url->link('payment/mcash_ipg/processpay');
		$this->load->model('payment/mcash_ipg');
		
		return $this->load->view('payment/mcash_ipg', $data);
    }

	public function processpay() {
		$this->load->model('checkout/order');
		$this->load->model('payment/mcash_ipg');
		
		$merchantID = $this->config->get('mcash_ipg_merchant_id');
		$ipgMode = $this->config->get('mcash_ipg_mode');
		
		
		$mobileNumber = $this->config->get('mcash_ipg_mobile_number');
		$tokenPassword = $this->config->get('mcash_ipg_token_password');
		$orderID = $this->session->data['order_id'];
		$order_info = $this->model_checkout_order->getOrder($orderID);
		$invoice = $this->model_payment_mcash_ipg->getMcashInvoice($orderID);

		$data['orderID'] = $this->session->data['order_id'];

		$redirect_url = "https://www.mcash.lk/ipg/payment.html";
		if ($ipgMode == "Live") {
			$redirect_url = "https://ipg.mobitel.lk/mcash/payment.html";
		}
		// Create map with request parameters
		$params = array (
			'merchant_id' => $merchantID,
			'merchant_invoice_id' => $invoice,
			'merchant_mobile' => $mobileNumber,
			'token_pwd' => $tokenPassword,
			'customer_mobile' => $invoice,
			'amount' => $order_info['total']);
		// Build Http query using params
		$query = http_build_query ($params);
		// Create Http context details
		$contextData = array ('method' => 'POST','header' => "Content-Type: application/x-www-form-urlencoded\r\n"."Connection: close\r\n"."Content-Length: ".strlen($query)."\r\n",'content'=> $query );
		// Create context resource for our request
		$context = stream_context_create (array ( 'http' => $contextData ));
		// Read page rendered as result of your POST request
		$token_url = "https://www.mcash.lk/ipg/auth/tokens.html";
		
		if ($ipgMode == "Live") {
			$token_url = "https://ipg.mobitel.lk/mcash/auth/tokens.html";
		}
		//$token_url = "https://www.mcash.lk/ipg/auth/tokens.html?merchant_id=BUEA00-5999&merchant_mobile=0710460174&token_pwd=BUEY0174&customer_mobile=estr&merchant_invoice_id=ydrssd&amount=90.00";

		$token = file_get_contents ($token_url ,false,$context);
		//print_r($context);
		//echo $merchantID."</br>";
		//echo $mobileNumber ."</br>";
		//echo $tokenPassword ."</br>";
		//echo $orderID ."</br>";
		//echo $invoice ."</br>";
		//echo $token ."</br>";
		$this->response->redirect($redirect_url.'?t_id='.$token);
		//echo '<script>';
		//echo 'window.location = "'.$redirect_url.'?t_id='.$token.'";';
		//echo '</script>';
	}

	public function callback() {
		$this->load->model('payment/mcash_ipg');
		
		//$this->model_payment_mcash_ipg->insertPassword('xxxx', '100', 'yyyy', 'zzzz', '400', 'rwew', 'asa');
		
		$amount = $this->request->post['amount'];
		$invoice_id = $this->request->post['invoice_id'];
		$mcashReferenceID = $this->request->post['mcash_reference_id'];
		$customerMobile = $this->request->post['customer_mobile'];
		$statusCode = $this->request->post['status_code'];
		$encrypted_verification_password = $this->request->post['encrypted_verification_password'];
		$sha256_checksum = $this->request->post['sha256_checksum'];


		$merchantID = $this->config->get('mcash_ipg_merchant_id');
		$mobileNumber = $this->config->get('mcash_ipg_mobile_number');
		$tokenPassword = $this->config->get('mcash_ipg_token_password');

		$s = $tokenPassword . $merchantID;
		// $s should be : 5dH3vaCNxuLEmgnQLT/pK4V04n+OpubVOxCAFAA7x9U=
		//echo "s is : " . $s;
		$signature = base64_encode ( hash ( "sha256", $s, True ) );
		// password 1 should be : 5dH3vaCNxuLEmgnQLT/pK4V04n+OpubV
		$password1 = substr ( $signature, 0, 32 );
		$iv = '071cafabd6453219'; // initialization vector
		// now at callback you have encrypted_verification_password,

		// Decryption AES-256
		$cipher = mcrypt_module_open ( MCRYPT_RIJNDAEL_128, '', 'cbc', '' );
		mcrypt_generic_init ( $cipher, $password1, $iv );
		// should be BZVs8ygfCUyZHbYWKLcg3IC6v3oR5U0t
		$plain_verification_password = mdecrypt_generic($cipher, base64_decode ($encrypted_verification_password));
		// since you are at callback.php store your plain_verification_password together with invoice_id as it is required later in success page or failure page
		$this->model_payment_mcash_ipg->insertPassword($invoice_id, $amount, $mcashReferenceID, $customerMobile, $statusCode, $plain_verification_password, $sha256_checksum);
		mcrypt_generic_deinit ( $cipher );
		$data['test'] = "test";
		return $this->load->view('payment/mcash_ipg_response', $data);
		
	}

	public function paymentsuccess() {
		// ******************
		// 2 - at redirection
		// ******************
		// now after customer redirection (either in your failure or success url )you have payment and invoice_id parameters

		$this->load->model('payment/mcash_ipg');
		$this->load->model('checkout/order');
		$this->load->language('payment/mcash_ipg');

		$payment = $this->request->get['payment'];
		$invoiceID = $this->request->get['invoice_id'];
		$pass = $this->model_payment_mcash_ipg->getPassword($invoiceID);
		$orderID = $this->model_payment_mcash_ipg->getOrderIDByInvoice($invoiceID);

		$merchantID = $this->config->get('mcash_ipg_merchant_id');
		$mobileNumber = $this->config->get('mcash_ipg_mobile_number');
		$tokenPassword = $this->config->get('mcash_ipg_token_password');

		$s = $tokenPassword . $merchantID;
		// $s should be : 5dH3vaCNxuLEmgnQLT/pK4V04n+OpubVOxCAFAA7x9U=
		//echo "s is : " . $s;
		$signature = base64_encode ( hash ( "sha256", $s, True ) );
		// password 1 should be : 5dH3vaCNxuLEmgnQLT/pK4V04n+OpubV
		$password1 = substr ( $signature, 0, 32 );
		$iv = '071cafabd6453219'; // initialization vector
		// now at callback you have encrypted_verification_password,

		// Decryption AES-256
		$cipher = mcrypt_module_open ( MCRYPT_RIJNDAEL_128, '', 'cbc', '' );
		//mcrypt_generic_init ( $cipher, $password1, $iv );



		if ($pass == null) {
			$this->session->data['failure_text'] = sprintf($this->language->get('text_failure_message'), "Service Error", $this->url->link('information/contact'));
			$this->response->redirect($this->url->link('checkout/failure', '', true));
		} else {
			// obtain plain_verification_password coupled with invoice_id (in the callback)
			mcrypt_generic_init ( $cipher, substr (trim($pass['password']), 0, 32), $iv );
			$payment_info = mdecrypt_generic ( $cipher, base64_decode ( $payment ) );
			// you have now decrypted payment information, compare it with callback that you have received
			//echo "Payment Info : " . $payment_info;
			mcrypt_generic_deinit ( $cipher );
			$info = explode("|", $payment_info);
			
			if ($info[0] == $pass["invoice_id"] && $info[1] == $pass["amount"] &&  $info[2] == $pass["customer_mobile"]  &&  $info[3] == $pass["status_code"] &&  substr($info[4], 0, 20) == substr($pass["mcash_reference_id"], 0, 20)) {
				$comment = "Payment done via mCash internet payment gateway with mCash reference number ".$pass["mcash_reference_id"]." and the mobile number ".$pass["customer_mobile"];
				$this->model_checkout_order->addOrderHistory($orderID, $this->config->get('mcash_ipg_order_status_id'), $comment,true);
				$this->response->redirect($this->url->link('checkout/success', '', true));
			} else {
				$this->session->data['failure_text'] = sprintf($this->language->get('text_failure_message'), "Internal Service Error", $this->url->link('information/contact'));
				$this->response->redirect($this->url->link('checkout/failure', '', true));
			}

		}
	}

	public function paymentfailure() {
		$this->load->model('payment/mcash_ipg');
		$this->load->language('payment/mcash_ipg');

		//$payment = $this->request->get['payment'];
		$invoiceID = $this->request->get['invoice_id'];
		$pass = $this->model_payment_mcash_ipg->getPassword($invoiceID);
		
		$reason[1000] = "Transaction Successful";
		$reason[1001] = "Duplicate Request";
		$reason[1002] = "Exceeds Maximum Transaction Limit per day";
		$reason[1003] = "Less than Minimum transaction value";
		$reason[1004] = "Customer wallet is not active";
		$reason[1005] = "Wallet is not customer type";
		$reason[1006] = "Customer Wallet not found";
		$reason[1010] = "Merchant wallet is not active";
		$reason[1011] = "Wallet is not merchant type";
		$reason[1012] = "Merchant wallet not found";
		$reason[1014] = "Invalid Token";
		$reason[1048] = "Maximum Account limit Exceeds";
		$reason[1049] = "Customer does not have enough credits";
		$reason[1052] = "Invalid Amount";
		$reason[1053] = "Customers Maximum per day transaction limit exceeds";
		$reason[1065] = "This will exceed maximum transaction limit";
		$reason[1998] = "Request Parameter validation failed";
		$reason[1999] = "Platform failure";
		
		if (array_key_exists($pass['status_code'], $reason)) {
			$this->session->data['failure_text'] = sprintf($this->language->get('text_failure_message'), $reason[$pass['status_code']], $this->url->link('information/contact'));
		}
		$this->response->redirect($this->url->link('checkout/failure', '', true));
	}

	public function paymentcancel() {
		$this->response->redirect($this->url->link('checkout/failure', '', true));
	}



//    public function index() {
//        $this->load->language('payment/ez_cash');
//        $data['button_confirm'] = $this->language->get('button_confirm');
//        $data['action'] = 'https://yourpaymentgatewayurl';
//
//        $this->load->model('checkout/order');
//        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
//
//        if ($order_info) {
//            $data['text_config_one'] = trim($this->config->get('text_config_one'));
//            $data['text_config_two'] = trim($this->config->get('text_config_two'));
//            $data['orderid'] = date('His') . $this->session->data['order_id'];
//            $data['callbackurl'] = $this->url->link('payment/custom/callback');
//            $data['orderdate'] = date('YmdHis');
//            $data['currency'] = $order_info['currency_code'];
//            $data['orderamount'] = $this->currency->format($order_info['total'], $data['currency'] , false, false);
//            $data['billemail'] = $order_info['email'];
//            $data['billphone'] = html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
//            $data['billaddress'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
//            $data['billcountry'] = html_entity_decode($order_info['payment_iso_code_2'], ENT_QUOTES, 'UTF-8');
//            $data['billprovince'] = html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8');;
//            $data['billcity'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');
//            $data['billpost'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
//            $data['deliveryname'] = html_entity_decode($order_info['shipping_firstname'] . $order_info['shipping_lastname'], ENT_QUOTES, 'UTF-8');
//            $data['deliveryaddress'] = html_entity_decode($order_info['shipping_address_1'], ENT_QUOTES, 'UTF-8');
//            $data['deliverycity'] = html_entity_decode($order_info['shipping_city'], ENT_QUOTES, 'UTF-8');
//            $data['deliverycountry'] = html_entity_decode($order_info['shipping_iso_code_2'], ENT_QUOTES, 'UTF-8');
//            $data['deliveryprovince'] = html_entity_decode($order_info['shipping_zone'], ENT_QUOTES, 'UTF-8');
//            $data['deliveryemail'] = $order_info['email'];
//            $data['deliveryphone'] = html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
//            $data['deliverypost'] = html_entity_decode($order_info['shipping_postcode'], ENT_QUOTES, 'UTF-8');
//
////            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/custom.tpl')){
////                $this->template = $this->config->get('config_template') . '/template/payment/custom.tpl';
////            } else {
////                $this->template = 'default/template/payment/custom.tpl';
////            }
//        }
//        return $this->load->view('payment/custom', $data);
//    }
//
//    public function callback() {
//        if (isset($this->request->post['orderid'])) {
//            $order_id = trim(substr(($this->request->post['orderid']), 6));
//        } else {
//            die('Illegal Access');
//        }
//
//        $this->load->model('checkout/order');
//        $order_info = $this->model_checkout_order->getOrder($order_id);
//
//        if ($order_info) {
//            $data = array_merge($this->request->post,$this->request->get);
//
//            //payment was made successfully
//            if ($data['status'] == 'Y' || $data['status'] == 'y') {
//                // update the order status accordingly
//            }
//        }
//
//    }
//

}
?>