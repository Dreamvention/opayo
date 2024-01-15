<?php
namespace Opencart\Catalog\Model\Extension\Opayo\Payment;
class Opayo extends \Opencart\System\Engine\Model {
	
	public function getMethod(array $address): array {
		$this->load->language('extension/opayo/payment/opayo');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_opayo_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

		if (!$this->config->get('payment_opayo_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = [];

		if ($status) {
			$method_data = [
				'code'       => 'opayo',
				'title'      => $this->language->get('text_title'),
				'sort_order' => $this->config->get('payment_opayo_sort_order')
			];
		}

		return $method_data;
	}
	
	public function getMethods(array $address = []): array {
		$this->load->language('extension/opayo/payment/opayo');
			
		if (!$this->config->get('config_checkout_payment_address')) {
			$status = true;
		} elseif (!$this->config->get('payment_opayo_geo_zone_id')) {
			$status = true;
		} else {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_opayo_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

			if ($query->num_rows) {
				$status = true;
			} else {
				$status = false;
			}
		}

		if ($status) {
			$option_data['opayo'] = [
				'code' => 'opayo.opayo',
				'name' => $this->language->get('text_title')
			];
				
			$method_data = [
				'code'       => 'opayo',
				'name'       => $this->language->get('text_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_opayo_sort_orderr')
			];
		}

		return $method_data;
	}
	
	public function getCards(int $customer_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "opayo_card` WHERE `customer_id` = '" . (int)$customer_id . "' ORDER BY `card_id`");

		$card_data = [];

		foreach ($query->rows as $row) {
			$card_data[] = [
				'card_id' => $row['card_id'],
				'customer_id' => $row['customer_id'],
				'token' => $row['token'],
				'digits' => '**** ' . $row['digits'],
				'expiry' => $row['expiry'],
				'type' => $row['type'],
			];
		}
		
		return $card_data;
	}

	public function addCard(array $card_data): int {		
		$this->db->query("INSERT INTO `" . DB_PREFIX . "opayo_card` SET `customer_id` = '" . $this->db->escape($card_data['customer_id']) . "', `digits` = '" . $this->db->escape($card_data['Last4Digits']) . "', `expiry` = '" . $this->db->escape($card_data['ExpiryDate']) . "', `type` = '" . $this->db->escape($card_data['CardType']) . "', `token` = '" . $this->db->escape($card_data['Token']) . "'");
		
		return $this->db->getLastId();
	}

	public function updateCard(int $card_id, string $token): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "opayo_card` SET `token` = '" . $this->db->escape($token) . "' WHERE `card_id` = '" . (int)$card_id . "'");
	}

	public function getCard(int $card_id, string $token): array|bool {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "opayo_card` WHERE (`card_id` = '" . $this->db->escape($card_id) . "' OR `token` = '" . $this->db->escape($token) . "') AND `customer_id` = '" . (int)$this->customer->getId() . "'");

		if ($query->num_rows) {
			return $query->row;
		} else {
			return false;
		}
	}

	public function deleteCard(int $card_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "opayo_card` WHERE `card_id` = '" . $this->db->escape($card_id) . "'");
	}
	
	public function addOrder(int $order_id, array $response_data, array $payment_data, string $card_id): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "opayo_order` SET `order_id` = '" . (int)$order_id . "', `VPSTxId` = '" . $this->db->escape($response_data['VPSTxId']) . "', `VendorTxCode` = '" . $this->db->escape($payment_data['VendorTxCode']) . "', `SecurityKey` = '" . $this->db->escape($response_data['SecurityKey']) . "', `TxAuthNo` = '" . $this->db->escape($response_data['TxAuthNo']) . "', `date_added` = now(), `date_modified` = now(), `currency_code` = '" . $this->db->escape($payment_data['Currency']) . "', `total` = '" . $this->currency->format($payment_data['Amount'], $payment_data['Currency'], false, false) . "', `card_id` = '" . $this->db->escape($card_id) . "'");

		return $this->db->getLastId();
	}

	public function getOrder(int $order_id): array|bool {
		$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "opayo_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		if ($qry->num_rows) {
			$order = $qry->row;
			$order['transactions'] = $this->getOrderTransactions($order['opayo_order_id']);

			return $order;
		} else {
			return false;
		}
	}

	public function updateOrder(array $order_info, array $data): int {
		$this->db->query("UPDATE `" . DB_PREFIX . "opayo_order` SET `SecurityKey` = '" . $this->db->escape($data['SecurityKey']) . "',  `VPSTxId` = '" . $this->db->escape($data['VPSTxId']) . "', `TxAuthNo` = '" . $this->db->escape($data['TxAuthNo']) . "' WHERE `order_id` = '" . (int)$order_info['order_id'] . "'");

		return $this->db->getLastId();
	}

	public function deleteOrder(int $vendor_tx_code): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "opayo_order` WHERE order_id = '" . $vendor_tx_code . "'");
	}

	public function addOrderTransaction(int $opayo_order_id, string $type, array $order_info): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "opayo_order_transaction` SET `opayo_order_id` = '" . (int)$opayo_order_id . "', `date_added` = now(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], false, false) . "'");
	}

	private function getOrderTransactions(int $opayo_order_id): array|bool {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "opayo_order_transaction` WHERE `opayo_order_id` = '" . (int)$opayo_order_id . "'");

		if ($query->num_rows) {
			return $query->rows;
		} else {
			return false;
		}
	}
	
	public function editSubscriptionStatus(int $subscription_id, int $subscription_status_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "subscription` SET `subscription_status_id` = '" . (int)$subscription_status_id . "' WHERE `subscription_id` = '" . (int)$subscription_id . "'");
	}

	public function editSubscriptionRemainingDateNext(int $subscription_id, int $remaining, int $trial_remaining, string $date_next): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "subscription` SET `remaining` = '" .  (int)$remaining .  "', `trial_remaining` = '" .  (int)$trial_remaining .  "', `date_next` = '" . $this->db->escape($date_next) . "' WHERE `subscription_id` = '" . (int)$subscription_id . "'");
	}
	
	public function getSubscriptionsByOrderId(int $order_id): array {
		if (VERSION >= '4.0.2.0') {
			$query = $this->db->query("SELECT `s`.`subscription_id` FROM `" . DB_PREFIX . "subscription` `s` JOIN `" . DB_PREFIX . "order` `o` USING(`order_id`) WHERE `s`.`order_id` = '" . (int)$order_id . "' AND `o`.`payment_method` LIKE '%opayo%'");
		} else {
			$query = $this->db->query("SELECT `s`.`subscription_id` FROM `" . DB_PREFIX . "subscription` `s` JOIN `" . DB_PREFIX . "order` `o` USING(`order_id`) WHERE `s`.`order_id` = '" . (int)$order_id . "' AND `o`.`payment_code` = 'opayo'");
		}

		$subscription_data = [];

		foreach ($query->rows as $subscription) {
			$subscription_data[] = $this->getSubscription($subscription['subscription_id']);
		}
			
		return $subscription_data;
	}
	
	public function getSubscription(int $subscription_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "subscription` WHERE `subscription_id` = '" . (int)$subscription_id . "'");
		
		return $query->row;
	}

	public function subscriptionPayment(array $item, string $vendor_tx_code): void {
		$this->load->model('checkout/subscription');
		$this->load->model('extension/payment/opayo');
						
		$subscription_id = $item['subscription_id'];
		$subscription_name = '';
		
		if ($item['trial_status'] && $item['trial_duration'] && $subscription['trial_remaining']) {
			$trial_remaining = $item['trial_duration'] - 1;
			$remaining = $item['duration'];
		} elseif ($item['duration'] && $subscription['remaining']) {
			$trial_remaining = $item['trial_duration'];
			$remaining = $item['duration'] - 1;
		} else {
			$trial_remaining = $item['trial_duration'];
			$remaining = $item['duration'];
		}
		
		$date_next = $item['date_next'];

		if ($item['trial_status'] && $item['trial_duration']) {
			$date_next = date('Y-m-d', strtotime('+' . $item['trial_cycle'] . ' ' . $item['trial_frequency']));
		} elseif ($item['duration'] && $item['remaining']) {
			$date_next = date('Y-m-d', strtotime('+' . $item['cycle'] . ' ' . $item['frequency']));
		}
		
		$this->editSubscriptionStatus($subscription_id, $this->config->get('config_subscription_active_status_id'));
		$this->editSubscriptionRemainingDateNext($subscription_id, $remaining, $trial_remaining, $date_next);
	}
	
	public function cronPayment(): array {
		$this->load->model('checkout/order');
		$this->load->model('catalog/product');
		
		$subscriptions = $this->getSubscriptions();
		$cron_data = array();
		$i = 0;

		foreach ($subscriptions as $subscription) {
			if ($subscription['subscription_status_id'] == $this->config->get('config_subscription_active_status_id')) {
				$order_info = $this->model_checkout_order->getOrder($subscription['order_id']);
				
				$opayo_order_info = $this->getOrder($subscription['order_id']);
				
				if ($subscription['trial_status'] && $subscription['trial_duration'] && $subscription['trial_remaining']) {
					$trial_remaining = $subscription['trial_remaining'] - 1;
					$remaining = $subscription['duration'];
				} elseif ($subscription['duration'] && $subscription['remaining']) {
					$trial_remaining = $subscription['trial_duration'];
					$remaining = $subscription['remaining'] - 1;
				} else {
					$trial_remaining = $subscription['trial_remaining'];
					$remaining = $subscription['remaining'];
				}
				
				$date_next = $subscription['date_next'];

				if ($subscription['trial_status'] && $subscription['trial_duration']) {
					$date_next = date('Y-m-d', strtotime('+' . $subscription['trial_cycle'] . ' ' . $subscription['trial_frequency']));
				} elseif ($subscription['duration'] && $subscription['remaining']) {
					$date_next = date('Y-m-d', strtotime('+' . $subscription['cycle'] . ' ' . $subscription['frequency']));
				}
				
				$price = 0;
				
				if ($subscription['trial_status'] && (!$subscription['trial_duration'] || $subscription['trial_remaining'])) {
					$price = $subscription['trial_price'];
				} elseif (!$subscription['duration'] || $subscription['remaining']) {
					$price = $subscription['price'];
				}
				
				$subscription_name = '';
				
				if (VERSION >= '4.0.2.0') {			
					$product_info = $this->model_catalog_product->getProduct($subscription['product_id']);

					if ($product_info) {
						$subscription_name = $product_info['name'];
					}
				} else {
					$subscription_name = $subscription['name'];
				}
								
				if (date_format($trial_end, 'Y-m-d H:i:s') >= date_format($subscription_end, 'Y-m-d H:i:s')) {
					$recurring_expiry = date_format($trial_end, 'Y-m-d');
				} else {
					$recurring_expiry = date_format($subscription_end, 'Y-m-d');
				}
			
				$recurring_frequency = date_diff(new DateTime('now'), new DateTime(date_format($date_next, 'Y-m-d H:i:s')))->days;
				
				$response_data = $this->setPaymentData($order_info, $opayo_order_info, $price, $subscription['subscription_id'], $subscription_name, $recurring_expiry, $recurring_frequency, $i);

				$cron_data[] = $response_data;

				if ($response_data['RepeatResponseData_' . $i++]['Status'] == 'OK') {
					$this->editSubscriptionRemainingDateNext($subscription['subscription_id'], $remaining, $trial_remaining, $date_next);
				}
			}
		}
		
		$log = new \Opencart\System\Library\Log('opayo_subscription_orders.log');
		$log->write(print_r($cron_data, true));
		
		return $cron_data;
	}

	private function setPaymentData(array $order_info, array $opayo_order_info, float $price, int $subscription_id, string $subscription_name, $i = null): array {
		// Setting
		$_config = new Config();
		$_config->load('opayo');
			
		$config_setting = $_config->get('payze_opayo');
		
		$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_opayo_setting'));
		
		if ($setting['general']['environment'] == 'live') {
			$url = 'https://live.opayo.eu.elavon.com/gateway/service/repeat.vsp';
			$payment_data['VPSProtocol'] = '4.00';
		} elseif ($setting['general']['environment'] == 'test') {
			$url = 'https://sandbox.opayo.eu.elavon.com/gateway/service/repeat.vsp';
			$payment_data['VPSProtocol'] = '4.00';
		}

		$payment_data['TxType'] = 'REPEAT';
		$payment_data['Vendor'] = $this->config->get('payment_opayo_vendor');
		$payment_data['VendorTxCode'] = $subscription_id . 'RSD' . strftime("%Y%m%d%H%M%S") . mt_rand(1, 999);
		$payment_data['Amount'] = $this->currency->format($price, $this->session->data['currency'], false, false);
		$payment_data['Currency'] = $this->session->data['currency'];
		$payment_data['Description'] = substr($subscription_name, 0, 100);
		$payment_data['RelatedVPSTxId'] = trim($opayo_order_info['VPSTxId'], '{}');
		$payment_data['RelatedVendorTxCode'] = $opayo_order_info['VendorTxCode'];
		$payment_data['RelatedSecurityKey'] = $opayo_order_info['SecurityKey'];
		$payment_data['RelatedTxAuthNo'] = $opayo_order_info['TxAuthNo'];
		$payment_data['COFUsage'] = 'SUBSEQUENT';
		$payment_data['InitiatedType'] = 'MIT';
		$payment_data['MITType'] = 'RECURRING';
		
		if (!empty($order_info['shipping_lastname'])) {
			$payment_data['DeliverySurname'] = substr($order_info['shipping_lastname'], 0, 20);
			$payment_data['DeliveryFirstnames'] = substr($order_info['shipping_firstname'], 0, 20);
			$payment_data['DeliveryAddress1'] = substr($order_info['shipping_address_1'], 0, 100);

			if ($order_info['shipping_address_2']) {
				$payment_data['DeliveryAddress2'] = $order_info['shipping_address_2'];
			}

			$payment_data['DeliveryCity'] = substr($order_info['shipping_city'], 0, 40);
			$payment_data['DeliveryPostCode'] = substr($order_info['shipping_postcode'], 0, 10);
			$payment_data['DeliveryCountry'] = $order_info['shipping_iso_code_2'];

			if ($order_info['shipping_iso_code_2'] == 'US') {
				$payment_data['DeliveryState'] = $order_info['shipping_zone_code'];
			}

			$payment_data['CustomerName'] = substr($order_info['firstname'] . ' ' . $order_info['lastname'], 0, 100);
			$payment_data['DeliveryPhone'] = substr($order_info['telephone'], 0, 20);
		} else {
			$payment_data['DeliveryFirstnames'] = $order_info['payment_firstname'];
			$payment_data['DeliverySurname'] = $order_info['payment_lastname'];
			$payment_data['DeliveryAddress1'] = $order_info['payment_address_1'];

			if ($order_info['payment_address_2']) {
				$payment_data['DeliveryAddress2'] = $order_info['payment_address_2'];
			}

			$payment_data['DeliveryCity'] = $order_info['payment_city'];
			$payment_data['DeliveryPostCode'] = $order_info['payment_postcode'];
			$payment_data['DeliveryCountry'] = $order_info['payment_iso_code_2'];

			if ($order_info['payment_iso_code_2'] == 'US') {
				$payment_data['DeliveryState'] = $order_info['payment_zone_code'];
			}

			$payment_data['DeliveryPhone'] = $order_info['telephone'];
		}
		
		$response_data = $this->sendCurl($url, $payment_data, $i);
		
		$response_data['VendorTxCode'] = $payment_data['VendorTxCode'];
		$response_data['Amount'] = $payment_data['Amount'];
		$response_data['Currency'] = $payment_data['Currency'];

		return $response_data;
	}

	private function calculateSchedule(string $frequency, string $next_payment, string $cycle) {
		if ($frequency == 'semi_month') {
			$day = date_format($next_payment, 'd');
			$value = 15 - $day;
			$is_even = false;
			
			if ($cycle % 2 == 0) {
				$is_even = true;
			}

			$odd = ($cycle + 1) / 2;
			$plus_even = ($cycle / 2) + 1;
			$minus_even = $cycle / 2;

			if ($day == 1) {
				$odd = $odd - 1;
				$plus_even = $plus_even - 1;
				$day = 16;
			}

			if ($day <= 15 && $is_even) {
				$next_payment->modify('+' . $value . ' day');
				$next_payment->modify('+' . $minus_even . ' month');
			} elseif ($day <= 15) {
				$next_payment->modify('first day of this month');
				$next_payment->modify('+' . $odd . ' month');
			} elseif ($day > 15 && $is_even) {
				$next_payment->modify('first day of this month');
				$next_payment->modify('+' . $plus_even . ' month');
			} elseif ($day > 15) {
				$next_payment->modify('+' . $value . ' day');
				$next_payment->modify('+' . $odd . ' month');
			}
		} else {
			$next_payment->modify('+' . $cycle . ' ' . $frequency);
		}
		
		return $next_payment;
	}
	
	private function getSubscriptions(): array {
		if (VERSION >= '4.0.2.0') {
			$query = $this->db->query("SELECT `s`.`subscription_id` FROM `" . DB_PREFIX . "subscription` `s` JOIN `" . DB_PREFIX . "order` `o` USING(`order_id`) WHERE `s`.`subscription_status_id` = '" . (int)$this->config->get('config_subscription_active_status_id') . "' AND DATE(`s`.`date_next`) <= NOW() AND `o`.`payment_method` LIKE '%opayo%'");
		} else {
			$query = $this->db->query("SELECT `s`.`subscription_id` FROM `" . DB_PREFIX . "subscription` `s` JOIN `" . DB_PREFIX . "order` `o` USING(`order_id`) WHERE `s`.`subscription_status_id` = '" . (int)$this->config->get('config_subscription_active_status_id') . "' AND DATE(`s`.`date_next`) <= NOW() AND `o`.`payment_code` = 'opayo'");
		}

		$subscription_data = [];

		foreach ($query->rows as $subscription) {
			$subscription_data[] = $this->getSubscription($subscription['subscription_id']);
		}
			
		return $subscription_data;
	}

	public function updateCronRunTime(): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'opayo' AND `key` = 'payment_opayo_last_cron_run'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`) VALUES (0, 'opayo', 'payment_opayo_last_cron_run', NOW(), 0)");
	}

	public function sendCurl(string $url, array $payment_data, $i = null): array {
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payment_data));

		$response = curl_exec($curl);
		
		curl_close($curl);
		
		$data = array();

		$response_info = explode(chr(10), $response);

		foreach ($response_info as $string) {
			if (strpos($string, '=') === false) {
				continue;
			}
			
			$parts = explode('=', $string, 2);
			
			if ($i !== null) {
				$data['RepeatResponseData_' . $i][trim($parts[0])] = trim($parts[1]);
			} else {
				$data[trim($parts[0])] = trim($parts[1]);
			}
		}
		
		return $data;
	}

	public function log(string $title, array|string $data): void {
		$_config = new \Opencart\System\Engine\Config();
		$_config->addPath(DIR_EXTENSION . 'opayo/system/config/');
		$_config->load('opayo');
			
		$config_setting = $_config->get('opayo_setting');
		
		$setting = array_replace_recursive((array)$config_setting, (array)$this->config->get('payment_opayo_setting'));
		
		if ($setting['general']['debug']) {
			$log = new \Opencart\System\Library\Log('opayo.log');
			
			$log->write($title . ': ' . print_r($data, true));
		}
	}

	public function subscriptionPayments(): bool {
		/*
		 * Used by the checkout to state the module
		 * supports subscription subscriptions.
		 */
		return true;
	}
}