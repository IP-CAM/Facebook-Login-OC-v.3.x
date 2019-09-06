<?php
class ControllerExtensionModuleFbLogin extends Controller {

    public function index(){
        $data = array();
        $this->load->language('extension/module/fb_login');

        $data['status_fb_login'] = $this->config->get('module_fb_login_status');

        $data['request'] =  $this->url->link('extension/module/fb_login/fblogin', '', true);

        $data['app_id'] = $this->config->get('module_fb_login_app_id');

        $data['location_code'] = $this->config->get('module_fb_login_app_loc');

        return $this->load->view('extension/module/fb_login', $data);
    }

    public function fblogin()
    {
        $this->load->language('extension/module/fb_login');
        $this->load->model('account/customer');
        $this->load->model('account/activity');

        $json = array();
        $data = array();

        if (!isset($this->request->get['email']) || $this->request->get['email'] == 'undefined') {
            $json['error'][] = $this->language->get('error_email');
        }

        if (!isset($this->request->get['fname'])) {
            $json['error'][] = $this->language->get('error_fname');
        }

        if (!isset($this->request->get['lname'])) {
            $json['error'][] = $this->language->get('error_lname');
        }

        if (!isset($this->request->get['fb_id'])) {
            $json['error'][] = $this->language->get('error_fb_id');
        }

        if (!$json) {
            $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->get['email']);

            // Customer already registered , Only Log in the customer
            if (!empty($customer_info)) {
                if ($customer_info && $this->customer->login($customer_info['email'], '', true)) {
                    // Unset guest
                    unset($this->session->data['guest']);

                    // Default Addresses
                    $this->load->model('account/address');

                    if ($this->config->get('config_tax_customer') == 'payment') {
                        $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
                    }

                    if ($this->config->get('config_tax_customer') == 'shipping') {
                        $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
                    }

                    $this->model_account_customer->deleteLoginAttempts($customer_info['email']);
                }
                if ($this->customer->isLogged()) {
                    $this->model_account_activity->addActivity('login', array(
                        'customer_id' => $customer_info['customer_id'],
                        'name' => 'FB: ' . $this->request->get['fname'] . ' ' . $this->request->get['lname']
                    ));

                    $json['success'] = 'login';
                } else {
                    $json['error'][] = $this->language->get('error_login');
                }
                // Create new customer account
            } else {
                $data['firstname'] = $this->request->get['fname'];
                $data['lastname'] = $this->request->get['lname'];
                $data['email'] = $this->request->get['email'];
                $data['telephone'] = '';
                $data['password'] = rand(10000, 99999);

                $customer_id = $this->model_account_customer->addCustomer($data);

                if ($customer_id && $this->customer->login($data['email'], $data['password'])) {
                    // Unset guest
                    unset($this->session->data['guest']);

                    // Default Addresses
                    $this->load->model('account/address');

                    if ($this->config->get('config_tax_customer') == 'payment') {
                        $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
                    }

                    if ($this->config->get('config_tax_customer') == 'shipping') {
                        $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
                    }
                }

                if ($this->customer->isLogged()) {
                    $json['success'] = 'register';
                } else {
                    $json['error'][] = $this->language->get('error_register');
                }

            }
        }
    }
}
