<?php
class ControllerExtensionRecurringOpayo extends Controller {
	private $error = array();
			
	public function index() {
		$content = '';
		
		if (!empty($this->request->get['order_recurring_id'])) {
			$this->load->language('extension/recurring/opayo');
		
			$this->load->model('account/recurring');
			
			$data['order_recurring_id'] = $this->request->get['order_recurring_id'];

			$order_recurring_info = $this->model_account_recurring->getOrderRecurring($data['order_recurring_id']);
			
			if ($order_recurring_info) {
				$data['button_enable_recurring'] = $this->language->get('button_enable_recurring');
				$data['button_disable_recurring'] = $this->language->get('button_disable_recurring');
				
				$data['recurring_status'] = $order_recurring_info['status'];
				
				$data['info_url'] =  str_replace('&amp;', '&', $this->url->link('extension/recurring/opayo/getRecurringInfo', 'order_recurring_id=' . $data['order_recurring_id'], true));
				$data['enable_url'] =  str_replace('&amp;', '&', $this->url->link('extension/recurring/opayo/enableRecurring', '', true));
				$data['disable_url'] =  str_replace('&amp;', '&', $this->url->link('extension/recurring/opayo/disableRecurring', '', true));
				
				$content = $this->load->view('extension/recurring/opayo', $data);
			}
		}
		
		return $content;
	}
		
	public function getRecurringInfo() {
		$this->response->setOutput($this->index());
	}
	
	public function enableRecurring() {
		if (!empty($this->request->post['order_recurring_id'])) {
			$this->load->language('extension/recurring/opayo');
			
			$this->load->model('extension/payment/opayo');
			
			$order_recurring_id = $this->request->post['order_recurring_id'];
			
			$this->model_extension_payment_opayo->editRecurringStatus($order_recurring_id, 1);
			
			$data['success'] = $this->language->get('success_enable_recurring');	
		}
						
		$data['error'] = $this->error;
				
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}
	
	public function disableRecurring() {
		if (!empty($this->request->post['order_recurring_id'])) {
			$this->load->language('extension/recurring/opayo');
			
			$this->load->model('extension/payment/opayo');
			
			$order_recurring_id = $this->request->post['order_recurring_id'];
			
			$this->model_extension_payment_opayo->editRecurringStatus($order_recurring_id, 2);
			
			$data['success'] = $this->language->get('success_disable_recurring');	
		}
						
		$data['error'] = $this->error;
				
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}
}