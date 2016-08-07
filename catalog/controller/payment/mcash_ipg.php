<?php
class ControllerPaymentMCashIpg extends Controller {
    public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');

		$data['text_loading'] = $this->language->get('text_loading');

		$data['continue'] = $this->url->link('payment/mcash_ipg/processpay');

		return $this->load->view('payment/mcash_ipg', $data);
    }

	public function processpay() {
		$this->load->model('checkout/order');
		$this->load->model('payment/mcash_ipg');
		$merchantID = $this->config->get('mcash_ipg_merchant_id');
		$mobileNumber = $this->config->get('mcash_ipg_mobile_number');
		$tokenPassword = $this->config->get('mcash_ipg_token_password');
		$orderID = $this->session->data['order_id'];
		$order_info = $this->model_checkout_order->getOrder($orderID);
		$invoice = $this->model_payment_mcash_ipg->getMcashInvoice($orderID);

		$data['orderID'] = $this->session->data['order_id'];

		$redirect_url = "https://www.mcash.lk/ipg/payment.html";
		$token_url = "https://www.mcash.lk/ipg/auth/tokens.html";
		if($this->config->get('mcash_ipg_mode') == "Live") {
			$token_url = "https://www.mcash.lk/ipg/auth/tokens.html";
			$redirect_url = "https://www.mcash.lk/ipg/payment.html";
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

		$token = file_get_contents ($token_url ,false,$context);
		$this->response->redirect($redirect_url.'?t_id='.$token);
	}

	public function callback() {
		$this->load->model('payment/mcash_ipg');
		//print_r($this->request);

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
	}

	public function paymentsuccess() {
		// ******************
		// 2 - at redirection
		// ******************
		// now after customer redirection (either in your failure or success url )you have payment and invoice_id parameters

		$this->load->model('payment/mcash_ipg');
		$this->load->model('checkout/order');

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
		mcrypt_generic_init ( $cipher, $password1, $iv );



		if ($pass == null) {
			$this->response->redirect($this->url->link('checkout/failure', '', true));
		} else {
			// obtain plain_verification_password coupled with invoice_id (in the callback)
			mcrypt_generic_init ( $cipher, $pass['password'], $iv );
			$payment_info = mdecrypt_generic ( $cipher, base64_decode ( $payment ) );
			// you have now decrypted payment information, compare it with callback that you have received
			//echo "Payment Info : " . $payment_info;
			mcrypt_generic_deinit ( $cipher );
			$info = explode("|", $payment_info);
			if ($info[0] == $pass["invoice_id"] && $info[1] == $pass["amount"] &&  $info[2] == $pass["customer_mobile"]  &&  $info[3] == $pass["status_code"] &&  $info[4] == $pass["mcash_reference_id"]) {
				$comment = "Payment done via mCash internet payment gateway with mCash reference number ".$pass["mcash_reference_id"]." and the mobile number ".$pass["customer_mobile"];
				$this->model_checkout_order->addOrderHistory($orderID, $this->config->get('mcash_ipg_order_status_id'), $comment,true);
				$this->response->redirect($this->url->link('checkout/success', '', true));
			} else {
				$this->response->redirect($this->url->link('checkout/failure', '', true));
			}

		}
	}

	public function paymentfailure() {
		$this->response->redirect($this->url->link('checkout/failure', '', true));
	}

	public function paymentcancel() {
		$this->response->redirect($this->url->link('checkout/failure', '', true));
	}
}
?>