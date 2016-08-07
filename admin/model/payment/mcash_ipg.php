<?php

class ModelPaymentMCashIpg extends Model {

    public function install() {
        $this->db->query("
        CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mcash_ipg_orders` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `order_id` varchar(128) NOT NULL,
          PRIMARY KEY (`id`)
        )ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

		$this->db->query("
        CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "mcash_ipg_passwords` (
		  `invoice_id` varchar(128) NOT NULL,
		  `mcash_reference_id` varchar(128) NOT NULL,
		  `amount` varchar(20) NOT NULL,
		  `customer_mobile` varchar(20) NOT NULL,
		  `status_code` int(11) NOT NULL,
		  `password` varchar(128) NOT NULL,
		  `cksum` varchar(128) NOT NULL,
          PRIMARY KEY (`invoice_id`)
        )ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "mcash_ipg_orders`;");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "mcash_ipg_passwords`;");
    }
}
