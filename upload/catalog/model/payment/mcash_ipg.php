<?php
class ModelPaymentMCashIpg extends Model {
	public function getMethod($address, $total) {
		$this->load->language('payment/mcash_ipg');
		$terms = '';
		if ($this->config->get('mcash_ipg_mode') == "Test") {
			$terms = 'Test';
		}
		$method_data = array(
			'code'     => 'mcash_ipg',
			'terms'     => $terms,
			'title'    => $this->language->get('text_title'),
			'sort_order' => $this->config->get('mcash_ipg_sort_order')
		);

		return $method_data;
	}

	public function getMcashInvoice($orderID) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "mcash_ipg_orders` (`order_id`) VALUES ('" . $orderID . "')");
		return "O-".$this->db->getLastId();
	}

	public function getOrderIDByInvoice($invoice) {
		$pieces = explode("-", $invoice);
		if (count($pieces) != 2) {
			return "0";
		}
		$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mcash_ipg_orders`  WHERE id = '" . $pieces[1] . "'");
		if ($qry->num_rows) {
			return $qry->row['order_id'];
		} else {
			return "0";
		}
	}

	public function insertPassword($invoiceID, $amount, $mcashReferenceID, $customerMobile, $statusCode, $password, $checksum) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "mcash_ipg_passwords` WHERE `invoice_id` = '".$invoiceID. "'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "mcash_ipg_passwords` (`invoice_id`,`mcash_reference_id`,`amount`,`customer_mobile`,`status_code`,`password`,`cksum`) "
			." VALUES "
			."('".$invoiceID. "','".$mcashReferenceID. "','".$amount. "','".$customerMobile. "',".$statusCode. ",'".$password."','".$checksum."')");
	}

	public function getPassword($invoiceID) {
		$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mcash_ipg_passwords`  WHERE `invoice_id` = '" . $invoiceID . "'");
		if ($qry->num_rows) {
			return $qry->row;
		} else {
			return null;
		}
	}
}