<?php
class ControllerExtensionPaymentMCashIpg extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/mcash_ipg');
        $this->document->setTitle('mCash (by Mobitel)');
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_mcash_ipg', $this->request->post);
            $this->session->data['success'] = 'Saved.';
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['button_save'] = $this->language->get('text_button_save');
        $data['button_cancel'] = $this->language->get('text_button_cancel');
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
        $data['entry_mobile_number'] = $this->language->get('entry_mobile_number');
        $data['entry_token_password'] = $this->language->get('entry_token_password');
		$data['entry_mode'] = $this->language->get('entry_mode');

		$data['action'] = $this->url->link('extension/payment/mcash_ipg', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_mcash_ipg_merchant_id'])) {
            $data['payment_mcash_ipg_merchant_id'] = $this->request->post['payment_mcash_ipg_merchant_id'];
        } else {
            $data['payment_mcash_ipg_merchant_id'] = $this->config->get('payment_mcash_ipg_merchant_id');
        }

        if (isset($this->request->post['payment_mcash_ipg_mobile_number'])) {
            $data['payment_mcash_ipg_mobile_number'] = $this->request->post['payment_mcash_ipg_mobile_number'];
        } else {
            $data['payment_mcash_ipg_mobile_number'] = $this->config->get('payment_mcash_ipg_mobile_number');
        }

        if (isset($this->request->post['payment_mcash_ipg_token_password'])) {
            $data['payment_mcash_ipg_token_password'] = $this->request->post['payment_mcash_ipg_token_password'];
        } else {
            $data['payment_mcash_ipg_token_password'] = $this->config->get('payment_mcash_ipg_token_password');
        }

        if (isset($this->request->post['payment_mcash_ipg_status'])) {
            $data['payment_mcash_ipg_status'] = $this->request->post['payment_mcash_ipg_status'];
        } else {
            $data['payment_mcash_ipg_status'] = $this->config->get('payment_mcash_ipg_status');
        }

        if (isset($this->request->post['payment_mcash_ipg_order_status_id'])) {
            $data['payment_mcash_ipg_order_status_id'] = $this->request->post['payment_mcash_ipg_order_status_id'];
        } else {
            $data['payment_mcash_ipg_order_status_id'] = $this->config->get('payment_mcash_ipg_order_status_id');
        }

		if (isset($this->request->post['payment_mcash_ipg_mode'])) {
			$data['payment_mcash_ipg_mode'] = $this->request->post['payment_mcash_ipg_mode'];
		} else {
			$data['payment_mcash_ipg_mode'] = $this->config->get('payment_mcash_ipg_mode');
		}

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->request->post['payment_mcash_ipg_sort_order'])) {
            $data['payment_mcash_ipg_sort_order'] = $this->request->post['payment_mcash_ipg_sort_order'];
        } else {
            $data['payment_mcash_ipg_sort_order'] = $this->config->get('payment_mcash_ipg_sort_order');
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/mcash_ipg', 'user_token=' . $this->session->data['user_token'], true)
        );

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/mcash_ipg', $data));
    }

    public function install() {
        $this->load->model('extension/payment/mcash_ipg');
        $this->model_extension_payment_mcash_ipg->install();
    }

    public function uninstall() {
        $this->load->model('extension/payment/mcash_ipg');
        $this->model_extension_payment_mcash_ipg->uninstall();
    }
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/mcash_ipg')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}