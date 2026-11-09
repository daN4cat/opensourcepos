<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Shopify REST API client Connector
 *
 * Interface for communicating with the Shopify Admin REST API
 *
 */

class ShopifyConnector
{
	/**
	 * Shop Domain / URL
	 *
	 * @var string
	 */
	private $_shop_url = '';

	/**
	 * API Key
	 *
	 * @var string
	 */
	private $_api_key = '';

	/**
	 * API Secret Key / Admin API Access Token
	 *
	 * @var string
	 */
	private $_api_secret_key = '';

	/**
	 * Shopify Admin API Version
	 *
	 * @var string
	 */
	private $_api_version = '2024-04';

	/**
	 * API Endpoint URL
	 *
	 * @var string
	 */
	private $_api_endpoint = '';

	/**
	 * Last error message
	 *
	 * @var string
	 */
	private $_last_error = '';

	/**
	 * Last HTTP response code
	 *
	 * @var int
	 */
	private $_last_http_code = 0;

	/**
	 * Constructor
	 */
	public function __construct($shop_url = '', $api_key = '', $api_secret_key = '')
	{
		if(empty($shop_url) && empty($api_key) && empty($api_secret_key) && function_exists('get_instance'))
		{
			$CI =& get_instance();
			if($CI && isset($CI->Appconfig))
			{
				$this->_shop_url = isset($CI->encryption) ? $CI->encryption->decrypt($CI->Appconfig->get('shopify_shop_url')) : FALSE;
				if($this->_shop_url === FALSE || empty($this->_shop_url))
				{
					$this->_shop_url = $CI->Appconfig->get('shopify_shop_url');
				}

				$this->_api_key = isset($CI->encryption) ? $CI->encryption->decrypt($CI->Appconfig->get('shopify_api_key')) : FALSE;
				if($this->_api_key === FALSE || empty($this->_api_key))
				{
					$this->_api_key = $CI->Appconfig->get('shopify_api_key');
				}

				$this->_api_secret_key = isset($CI->encryption) ? $CI->encryption->decrypt($CI->Appconfig->get('shopify_api_secret_key')) : FALSE;
				if($this->_api_secret_key === FALSE || empty($this->_api_secret_key))
				{
					$this->_api_secret_key = $CI->Appconfig->get('shopify_api_secret_key');
				}
			}
		}
		else
		{
			$this->_shop_url = $shop_url;
			$this->_api_key = $api_key;
			$this->_api_secret_key = $api_secret_key;
		}

		$this->_setup_endpoint();
	}

	/**
	 * Reset connector configuration
	 *
	 * @param string $shop_url
	 * @param string $api_key
	 * @param string $api_secret_key
	 */
	public function resetConfig($shop_url = '', $api_key = '', $api_secret_key = '')
	{
		$this->_last_error = '';
		$this->_last_http_code = 0;
		$this->_shop_url = trim($shop_url);
		$this->_api_key = trim($api_key);
		$this->_api_secret_key = trim($api_secret_key);
		$this->_api_endpoint = '';
		$this->_setup_endpoint();
	}

	/**
	 * Normalize and validate shop domain, and configure endpoint URL.
	 * Enforces strict myshopify.com subdomain validation to prevent SSRF and token leakage.
	 */
	private function _setup_endpoint()
	{
		// Fallback: if shop_url is empty but api_key looks like a myshopify domain
		if(empty($this->_shop_url) && !empty($this->_api_key) && strpos($this->_api_key, 'myshopify.com') !== FALSE)
		{
			$this->_shop_url = $this->_api_key;
			$this->_api_key = '';
		}

		if(!empty($this->_shop_url))
		{
			$shop = trim($this->_shop_url);
			// Strip protocol
			$shop = preg_replace('#^https?://#i', '', $shop);
			// Strip userinfo if present
			if(strpos($shop, '@') !== FALSE)
			{
				$shop = substr($shop, strrpos($shop, '@') + 1);
			}
			// Strip path, query string, or fragment
			$shop = preg_replace('~[/\\\\?#].*$~', '', $shop);
			// Strip port
			$shop = preg_replace('#:[0-9]+$#', '', $shop);
			$shop = strtolower(trim($shop));

			// If only the subdomain was provided, append .myshopify.com
			if(!empty($shop) && strpos($shop, '.') === FALSE)
			{
				$shop .= '.myshopify.com';
			}

			// Validate against strict Shopify store domain pattern: {subdomain}.myshopify.com
			// Subdomain must be 1-60 alphanumeric characters or hyphens, starting and ending with alphanumeric.
			if(preg_match('/^[a-z0-9]([a-z0-9\-]{0,58}[a-z0-9])?\.myshopify\.com$/', $shop))
			{
				$this->_shop_url = $shop;
				$this->_api_endpoint = 'https://' . $this->_shop_url . '/admin/api/' . $this->_api_version . '/';
			}
			else
			{
				$this->_shop_url = '';
				$this->_api_endpoint = '';
				$this->_last_error = 'Invalid Shopify shop domain. Must be in the format your-store.myshopify.com';
				log_message('error', 'ShopifyConnector: Rejected invalid shop domain: ' . $shop);
			}
		}
	}

	/**
	 * Check if the connector has minimum required configuration
	 *
	 * @return bool
	 */
	public function isConfigured()
	{
		return !empty($this->_api_endpoint) && (!empty($this->_api_secret_key) || !empty($this->_api_key));
	}

	/**
	 * Get normalized shop URL
	 *
	 * @return string
	 */
	public function getShopUrl()
	{
		return $this->_shop_url;
	}

	/**
	 * Get last error message
	 *
	 * @return string
	 */
	public function getLastError()
	{
		return $this->_last_error;
	}

	/**
	 * Get last HTTP response code
	 *
	 * @return int
	 */
	public function getLastHttpCode()
	{
		return $this->_last_http_code;
	}

	/**
	 * Call a Shopify API method
	 *
	 * @param  string $httpVerb The HTTP method (GET, POST, PUT, DELETE)
	 * @param  string $method   The API method path (e.g. 'shop', 'customers')
	 * @param  array  $args     Parameters or body arguments
	 * @return mixed            Associative array of decoded JSON or boolean FALSE on error
	 */
	public function call($httpVerb = 'POST', $method = '', $args = array())
	{
		if($this->isConfigured())
		{
			return $this->_request($httpVerb, $method, $args);
		}

		return FALSE;
	}

	/**
	 * Builds the request URL based on method and HTTP verb
	 *
	 * @param  string $httpVerb
	 * @param  string $method
	 * @param  array  $args
	 * @return string
	 */
	private function _build_request_url($httpVerb = 'POST', $method = '', $args = array())
	{
		$method = ltrim($method, '/');

		// Ensure .json extension is appended to Shopify Admin REST endpoints
		if(strpos($method, '.json') === FALSE && strpos($method, '?') === FALSE)
		{
			$method .= '.json';
		}
		elseif(strpos($method, '?') !== FALSE && strpos($method, '.json?') === FALSE)
		{
			$method = str_replace('?', '.json?', $method);
		}

		if($httpVerb == 'GET' && !empty($args))
		{
			return $this->_api_endpoint . $method . (strpos($method, '?') === FALSE ? '?' : '&') . http_build_query($args);
		}

		return $this->_api_endpoint . $method;
	}

	/**
	 * Performs the underlying cURL HTTP request
	 *
	 * @param  string $httpVerb
	 * @param  string $method
	 * @param  array  $args
	 * @return mixed
	 */
	private function _request($httpVerb, $method, $args = array())
	{
		$this->_last_error = '';
		$this->_last_http_code = 0;

		if(($ch = curl_init()) !== FALSE)
		{
			$url = $this->_build_request_url($httpVerb, $method, $args);

			$headers = array(
				'Content-Type: application/json',
				'Accept: application/json'
			);

			// Shopify Admin API Access Token
			// Modern Shopify apps use the X-Shopify-Access-Token header with an access token (shpat_...)
			$token = '';
			if(!empty($this->_api_secret_key) && strpos($this->_api_secret_key, 'shpat_') === 0)
			{
				$token = $this->_api_secret_key;
			}
			elseif(!empty($this->_api_key) && strpos($this->_api_key, 'shpat_') === 0)
			{
				$token = $this->_api_key;
			}
			else
			{
				$token = !empty($this->_api_secret_key) ? $this->_api_secret_key : $this->_api_key;
			}

			if(!empty($token))
			{
				$token = preg_replace('/[\r\n]/', '', trim($token));
				$headers[] = 'X-Shopify-Access-Token: ' . $token;
			}

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_USERAGENT, 'OSPOS-Shopify/1.0');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpVerb);

			if(in_array($httpVerb, array('POST', 'PUT', 'PATCH')) && !empty($args))
			{
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
			}

			$result = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$this->_last_http_code = $http_code;

			if(curl_errno($ch))
			{
				$curl_err = curl_error($ch);
				$this->_last_error = $curl_err;
				log_message('error', 'Shopify cURL error: ' . $curl_err);
			}

			curl_close($ch);

			if($http_code >= 200 && $http_code < 300)
			{
				if($http_code == 204 || empty($result) || trim($result) === '{}')
				{
					return array();
				}

				$decoded = json_decode($result, TRUE);
				return is_array($decoded) ? $decoded : array();
			}
			else
			{
				$this->_last_error = $result;
				log_message('error', 'Shopify API returned HTTP ' . $http_code . ' [' . $httpVerb . ' ' . $method . ']: ' . $result);
				return FALSE;
			}
		}

		return FALSE;
	}
}


/**
 * Shopify library, usable from CodeIgniter controllers and models
 *
 * Library for syncing and managing customer data with Shopify
 */

class Shopify_lib
{
	private $_connector;

	/**
	 * Last warning message from non-fatal retry operations
	 *
	 * @var string
	 */
	private $_last_warning = '';

	public function __construct(array $params = array())
	{
		$shop_url = (count($params) > 0 && !empty($params['shop_url'])) ? $params['shop_url'] : '';
		$api_key = (count($params) > 0 && !empty($params['api_key'])) ? $params['api_key'] : '';
		$api_secret_key = (count($params) > 0 && !empty($params['api_secret_key'])) ? $params['api_secret_key'] : '';
		$this->_connector = new ShopifyConnector($shop_url, $api_key, $api_secret_key);
	}

	/**
	 * Get last warning message
	 *
	 * @return string
	 */
	public function getLastWarning()
	{
		return $this->_last_warning;
	}

	/**
	 * Reset credentials and endpoint configuration
	 *
	 * @param string $shop_url
	 * @param string $api_key
	 * @param string $api_secret_key
	 */
	public function resetConfig($shop_url = '', $api_key = '', $api_secret_key = '')
	{
		$this->_connector->resetConfig($shop_url, $api_key, $api_secret_key);
	}

	/**
	 * Get normalized shop URL
	 *
	 * @return string
	 */
	public function getShopUrl()
	{
		return $this->_connector->getShopUrl();
	}

	/**
	 * Get last error message
	 *
	 * @return string
	 */
	public function getLastError()
	{
		return $this->_connector->getLastError();
	}

	/**
	 * Get last HTTP response code
	 *
	 * @return int
	 */
	public function getLastHttpCode()
	{
		return $this->_connector->getLastHttpCode();
	}

	/**
	 * Check if Shopify integration has credentials configured
	 *
	 * @return bool
	 */
	public function isConfigured()
	{
		return $this->_connector->isConfigured();
	}

	/**
	 * Authenticate credentials against Shopify Admin REST API
	 *
	 * @return bool
	 */
	public function authenticate()
	{
		$response = $this->getShop();
		return is_array($response) && !empty($response['shop']);
	}

	/**
	 * Get shop details from Shopify
	 *
	 * @return array|bool
	 */
	public function getShop()
	{
		return $this->_connector->call('GET', 'shop');
	}

	/**
	 * Get list of customers from Shopify
	 *
	 * @param array $parameters
	 * @return array|bool
	 */
	public function getCustomers(array $parameters = array())
	{
		return $this->_connector->call('GET', 'customers', $parameters);
	}

	/**
	 * Get a single customer by Shopify ID
	 *
	 * @param int|string $customer_id
	 * @param array $parameters
	 * @return array|bool
	 */
	public function getCustomerById($customer_id, array $parameters = array())
	{
		$result = $this->_connector->call('GET', 'customers/' . $customer_id, $parameters);
		if(is_array($result) && !empty($result['customer']))
		{
			return $result['customer'];
		}

		return FALSE;
	}

	/**
	 * Search for a customer by email
	 *
	 * @param string $email
	 * @return array|bool
	 */
	public function findCustomerByEmail($email)
	{
		$email = trim($email);
		if(empty($email))
		{
			return FALSE;
		}

		$escaped_email = addcslashes($email, '"\\');
		$result = $this->_connector->call('GET', 'customers/search', array('query' => 'email:"' . $escaped_email . '"'));
		if(is_array($result) && !empty($result['customers']) && is_array($result['customers']) && count($result['customers']) > 0)
		{
			foreach($result['customers'] as $customer)
			{
				if(isset($customer['email']) && strcasecmp(trim($customer['email']), $email) === 0)
				{
					return $customer;
				}
			}
			// Fallback: return first match
			return $result['customers'][0];
		}

		return FALSE;
	}

	/**
	 * Normalize a phone number to international E.164 format (+[country code][number])
	 * as required by Shopify's Admin REST and GraphQL APIs.
	 *
	 * E.g., UK local mobile '07554401729' -> '+447554401729'
	 *
	 * @param string $phone
	 * @param string $country
	 * @return string E.164 formatted phone number or original phone if normalization cannot be determined
	 */
	public function normalizePhone($phone, $country = '')
	{
		$phone = trim($phone);
		if(empty($phone))
		{
			return '';
		}

		// Handle common international formatting with bracketed trunk prefix, e.g. +44 (0) 7554...
		$phone = preg_replace('/\(\s*0\s*\)/', '', $phone);

		// Remove all characters except digits and plus sign
		$cleaned = preg_replace('/[^\d+]/', '', $phone);
		if(empty($cleaned))
		{
			return '';
		}

		// Replace international double-zero prefix (e.g. 0044 -> +44)
		if(strpos($cleaned, '00') === 0)
		{
			$cleaned = '+' . substr($cleaned, 2);
		}

		// If starts with +440 (UK trunk prefix left after +44), drop the trunk 0
		if(strpos($cleaned, '+440') === 0 && strlen($cleaned) >= 14)
		{
			$cleaned = '+44' . substr($cleaned, 4);
		}

		// If already in international format starting with +
		if(strpos($cleaned, '+') === 0)
		{
			$digits = preg_replace('/[^\d]/', '', $cleaned);
			if(strlen($digits) >= 8 && strlen($digits) <= 15)
			{
				return '+' . $digits;
			}
			return $cleaned;
		}

		// Only digits remaining
		$digits = $cleaned;

		// Map common country names and ISO codes to country dialing codes
		$country_calling_codes = array(
			'GB' => '44', 'UK' => '44', 'GBR' => '44', 'UNITED KINGDOM' => '44', 'ENGLAND' => '44', 'SCOTLAND' => '44', 'WALES' => '44',
			'US' => '1', 'USA' => '1', 'UNITED STATES' => '1',
			'CA' => '1', 'CAN' => '1', 'CANADA' => '1',
			'AU' => '61', 'AUS' => '61', 'AUSTRALIA' => '61',
			'IE' => '353', 'IRL' => '353', 'IRELAND' => '353',
			'FR' => '33', 'FRA' => '33', 'FRANCE' => '33',
			'DE' => '49', 'DEU' => '49', 'GERMANY' => '49',
			'IT' => '39', 'ITA' => '39', 'ITALY' => '39',
			'ES' => '34', 'ESP' => '34', 'SPAIN' => '34',
			'NL' => '31', 'NLD' => '31', 'NETHERLANDS' => '31',
			'NZ' => '64', 'NZL' => '64', 'NEW ZEALAND' => '64',
			'IN' => '91', 'IND' => '91', 'INDIA' => '91',
			'ZA' => '27', 'ZAF' => '27', 'SOUTH AFRICA' => '27',
			'BR' => '55', 'BRA' => '55', 'BRAZIL' => '55',
			'MX' => '52', 'MEX' => '52', 'MEXICO' => '52',
			'SG' => '65', 'SGP' => '65', 'SINGAPORE' => '65',
			'AE' => '971', 'ARE' => '971', 'UNITED ARAB EMIRATES' => '971'
		);

		$calling_code = '';
		$country_upper = strtoupper(trim((string)$country));
		if(!empty($country_upper) && isset($country_calling_codes[$country_upper]))
		{
			$calling_code = $country_calling_codes[$country_upper];
		}

		// If no customer country is provided, inspect store/shop defaults
		if(empty($calling_code))
		{
			if(function_exists('get_instance'))
			{
				$CI =& get_instance();
				if($CI && isset($CI->config))
				{
					$store_country = strtoupper(trim((string)$CI->config->item('country_codes')));
					if(!empty($store_country) && isset($country_calling_codes[$store_country]))
					{
						$calling_code = $country_calling_codes[$store_country];
					}
				}
			}

			// If shop domain indicates London or UK
			$shop_url = strtolower($this->getShopUrl());
			if(strpos($shop_url, 'london') !== FALSE || strpos($shop_url, '.uk') !== FALSE || strpos($shop_url, '-uk') !== FALSE)
			{
				$calling_code = '44';
			}
		}

		// UK specific mobile and national format heuristic:
		// UK mobile numbers are 11 digits starting with 07 (e.g. 07554401729).
		// UK landline numbers are 10 or 11 digits starting with 01, 02, 03.
		if((empty($calling_code) || $calling_code === '44') && strpos($digits, '07') === 0 && strlen($digits) === 11)
		{
			return '+44' . substr($digits, 1);
		}
		if((empty($calling_code) || $calling_code === '44') && (strpos($digits, '01') === 0 || strpos($digits, '02') === 0 || strpos($digits, '03') === 0) && (strlen($digits) === 10 || strlen($digits) === 11))
		{
			return '+44' . substr($digits, 1);
		}

		// North American Numbering Plan (NANP: US/CA):
		// 10 digits without leading 0/1 (e.g. 5551234567) -> +15551234567
		// 11 digits with leading 1 (e.g. 15551234567) -> +15551234567
		if(($calling_code === '1' || empty($calling_code)) && strlen($digits) === 10 && $digits[0] >= '2' && $digits[0] <= '9')
		{
			if($calling_code === '1')
			{
				return '+1' . $digits;
			}
		}
		if(($calling_code === '1' || empty($calling_code)) && strlen($digits) === 11 && $digits[0] === '1')
		{
			return '+' . $digits;
		}

		// General national format with leading 0 when calling code is known
		if(!empty($calling_code))
		{
			if(strpos($digits, '0') === 0)
			{
				$digits = substr($digits, 1);
			}
			$normalized = '+' . $calling_code . $digits;
			if(strlen($normalized) >= 9 && strlen($normalized) <= 16)
			{
				return $normalized;
			}
		}

		// If already starts with a country calling code without leading plus (e.g. 447554401729)
		if(strlen($digits) >= 11 && strlen($digits) <= 15)
		{
			foreach(array('44', '1', '61', '33', '49', '39', '34', '31', '353', '64') as $code)
			{
				if(strpos($digits, $code) === 0)
				{
					return '+' . $digits;
				}
			}
		}

		return $phone;
	}

	/**
	 * Search for a customer by phone number
	 *
	 * @param string $phone
	 * @param string $country
	 * @return array|bool
	 */
	public function findCustomerByPhone($phone, $country = '')
	{
		$phone = trim($phone);
		if(empty($phone))
		{
			return FALSE;
		}

		$normalized = $this->normalizePhone($phone, $country);
		$search_phone = !empty($normalized) ? $normalized : $phone;

		$escaped_search_phone = addcslashes($search_phone, '"\\');
		$result = $this->_connector->call('GET', 'customers/search', array('query' => 'phone:"' . $escaped_search_phone . '"'));
		if(is_array($result) && !empty($result['customers']) && is_array($result['customers']) && count($result['customers']) > 0)
		{
			return $result['customers'][0];
		}

		// Fallback to searching with raw phone if normalized was different
		if($search_phone !== $phone)
		{
			$escaped_phone = addcslashes($phone, '"\\');
			$result = $this->_connector->call('GET', 'customers/search', array('query' => 'phone:"' . $escaped_phone . '"'));
			if(is_array($result) && !empty($result['customers']) && is_array($result['customers']) && count($result['customers']) > 0)
			{
				return $result['customers'][0];
			}
		}

		return FALSE;
	}

	/**
	 * Get customer information by email and/or phone
	 *
	 * @param string $email
	 * @param string $phone
	 * @param string $country
	 * @return array|bool
	 */
	public function getCustomerInfo($email, $phone = '', $country = '')
	{
		if(!$this->isConfigured())
		{
			return FALSE;
		}

		if(!empty($email))
		{
			$customer = $this->findCustomerByEmail($email);
			if($customer !== FALSE)
			{
				return $customer;
			}
		}

		if(!empty($phone))
		{
			$customer = $this->findCustomerByPhone($phone, $country);
			if($customer !== FALSE)
			{
				return $customer;
			}
		}

		return FALSE;
	}

	/**
	 * Create a new customer in Shopify
	 *
	 * @param array $customer_data
	 * @return array|bool
	 */
	public function addCustomer(array $customer_data)
	{
		$result = $this->_connector->call('POST', 'customers', array('customer' => $customer_data));
		if(is_array($result) && !empty($result['customer']))
		{
			return $result['customer'];
		}

		return FALSE;
	}

	/**
	 * Update an existing customer in Shopify
	 *
	 * @param int|string $customer_id
	 * @param array $customer_data
	 * @return array|bool
	 */
	public function updateCustomer($customer_id, array $customer_data)
	{
		$result = $this->_connector->call('PUT', 'customers/' . $customer_id, array('customer' => $customer_data));
		if(is_array($result) && !empty($result['customer']))
		{
			return $result['customer'];
		}

		return FALSE;
	}

	/**
	 * Remove a customer from Shopify by email, phone, or ID
	 *
	 * @param int|string $email_or_phone_or_id
	 * @return bool
	 */
	public function removeCustomer($email_or_phone_or_id)
	{
		if(!$this->isConfigured())
		{
			return FALSE;
		}

		$customer_id = NULL;

		if(is_numeric($email_or_phone_or_id))
		{
			$customer_id = $email_or_phone_or_id;
		}
		elseif(is_string($email_or_phone_or_id) && !empty($email_or_phone_or_id))
		{
			$identifier = trim($email_or_phone_or_id);
			if(strpos($identifier, '@') !== FALSE)
			{
				$customer = $this->findCustomerByEmail($identifier);
				if($customer !== FALSE && !empty($customer['id']))
				{
					$customer_id = $customer['id'];
				}
			}
			else
			{
				$customer = $this->findCustomerByPhone($identifier);
				if($customer !== FALSE && !empty($customer['id']))
				{
					$customer_id = $customer['id'];
				}
			}
		}

		if(!empty($customer_id))
		{
			$result = $this->_connector->call('DELETE', 'customers/' . $customer_id);
			return $result !== FALSE;
		}

		return FALSE;
	}

	/**
	 * Add or update a customer in Shopify using OSPOS person and customer data
	 *
	 * @param array|string $person_data
	 * @param array $customer_data
	 * @return array|bool
	 */
	public function addOrUpdateCustomer($person_data, $customer_data = array())
	{
		$this->_last_warning = '';

		if(!$this->isConfigured())
		{
			return FALSE;
		}

		// Support flexible arguments if $person_data is an email string
		if(is_string($person_data))
		{
			$email = $person_data;
			$first_name = is_string($customer_data) ? $customer_data : '';
			$last_name = func_num_args() > 2 ? func_get_arg(2) : '';
			$phone = func_num_args() > 3 ? func_get_arg(3) : '';

			$person_data = array(
				'email' => $email,
				'first_name' => $first_name,
				'last_name' => $last_name,
				'phone_number' => $phone
			);
			$customer_data = array();
		}

		$email = isset($person_data['email']) ? trim($person_data['email']) : '';
		$phone = isset($person_data['phone_number']) ? trim($person_data['phone_number']) : '';
		$country = isset($person_data['country']) ? trim($person_data['country']) : '';

		$normalized_phone = $this->normalizePhone($phone, $country);
		if(!empty($normalized_phone))
		{
			$phone = $normalized_phone;
		}

		// Shopify requires at least email or phone number to create a customer
		if(empty($email) && empty($phone))
		{
			log_message('debug', 'Shopify customer sync skipped: neither email nor phone number is provided.');
			return FALSE;
		}

		// Look for existing customer first to manage tags and address ID
		$existing_customer = FALSE;
		if(!empty($email))
		{
			$existing_customer = $this->findCustomerByEmail($email);
		}
		if($existing_customer === FALSE && !empty($phone))
		{
			$existing_customer = $this->findCustomerByPhone($phone, $country);
		}

		// Preserve existing tags while ensuring OSPOS tag is present
		$tags = array('OSPOS');
		if($existing_customer !== FALSE && !empty($existing_customer['tags']))
		{
			$existing_tags = array_map('trim', explode(',', $existing_customer['tags']));
			if(!in_array('OSPOS', $existing_tags))
			{
				$existing_tags[] = 'OSPOS';
			}
			$tags = array_filter($existing_tags);
		}

		$shopify_payload = array(
			'first_name' => isset($person_data['first_name']) ? $person_data['first_name'] : '',
			'last_name' => isset($person_data['last_name']) ? $person_data['last_name'] : '',
			'tags' => implode(', ', $tags)
		);

		$consent_specified = isset($customer_data['consent']);
		$is_consented = $consent_specified ? !empty($customer_data['consent']) : ($existing_customer === FALSE);

		if(!empty($email))
		{
			$shopify_payload['email'] = $email;
			$shopify_payload['verified_email'] = TRUE;

			// Set email marketing consent based on customer privacy/marketing consent
			if($consent_specified || $existing_customer === FALSE)
			{
				$shopify_payload['email_marketing_consent'] = array(
					'state' => $is_consented ? 'subscribed' : 'not_subscribed',
					'opt_in_level' => 'single_opt_in',
					'consent_updated_at' => date('c')
				);
			}
		}

		if(!empty($phone))
		{
			$shopify_payload['phone'] = $phone;

			// Set SMS marketing consent if phone number is provided
			if($consent_specified || $existing_customer === FALSE)
			{
				$shopify_payload['sms_marketing_consent'] = array(
					'state' => $is_consented ? 'subscribed' : 'not_subscribed',
					'opt_in_level' => 'single_opt_in',
					'consent_updated_at' => date('c')
				);
			}
		}

		if(!empty($person_data['comments']))
		{
			$shopify_payload['note'] = $person_data['comments'];
		}

		// Only attach addresses if physical address information is provided
		// Shopify rejects address payloads missing physical address information with HTTP 422
		$has_physical_address = !empty($person_data['address_1']) || !empty($person_data['city']) || !empty($person_data['zip']) || !empty($person_data['state']) || !empty($person_data['country']);

		if($has_physical_address)
		{
			$address = array();
			if(!empty($person_data['address_1']))
			{
				$address['address1'] = $person_data['address_1'];
			}
			if(!empty($person_data['address_2']))
			{
				$address['address2'] = $person_data['address_2'];
			}
			if(!empty($person_data['city']))
			{
				$address['city'] = $person_data['city'];
			}
			if(!empty($person_data['state']))
			{
				$address['province'] = $person_data['state'];
			}
			if(!empty($person_data['zip']))
			{
				$address['zip'] = $person_data['zip'];
			}
			if(!empty($person_data['country']))
			{
				$address['country'] = $person_data['country'];
			}
			if(!empty($customer_data['company_name']))
			{
				$address['company'] = $customer_data['company_name'];
			}
			if(!empty($person_data['first_name']))
			{
				$address['first_name'] = $person_data['first_name'];
			}
			if(!empty($person_data['last_name']))
			{
				$address['last_name'] = $person_data['last_name'];
			}
			if(!empty($phone))
			{
				$address['phone'] = $phone;
			}

			// If updating existing customer with an existing default address, attach its ID to update it instead of duplicating
			if($existing_customer !== FALSE && !empty($existing_customer['default_address']['id']))
			{
				$address['id'] = $existing_customer['default_address']['id'];
			}

			$shopify_payload['addresses'] = array($address);
		}

		if($existing_customer !== FALSE && !empty($existing_customer['id']))
		{
			$result = $this->updateCustomer($existing_customer['id'], $shopify_payload);

			// Retry without phone if Shopify rejected phone formatting or phone is already taken
			if($result === FALSE && !empty($shopify_payload['phone']))
			{
				$rejection_reason = $this->getLastError();
				log_message('error', 'Shopify customer update rejected phone ' . $shopify_payload['phone'] . ' for ID ' . $existing_customer['id'] . ': ' . $rejection_reason . '. Retrying without phone.');
				unset($shopify_payload['phone']);
				unset($shopify_payload['sms_marketing_consent']);
				if(!empty($shopify_payload['addresses'][0]['phone']))
				{
					unset($shopify_payload['addresses'][0]['phone']);
				}
				$result = $this->updateCustomer($existing_customer['id'], $shopify_payload);
				if($result !== FALSE)
				{
					$reason_str = 'rejected by Shopify';
					$err_decoded = json_decode($rejection_reason, TRUE);
					if(isset($err_decoded['errors']['phone']))
					{
						$reason_str = is_array($err_decoded['errors']['phone']) ? implode(', ', $err_decoded['errors']['phone']) : $err_decoded['errors']['phone'];
					}
					$this->_last_warning = 'Shopify rejected phone ' . $phone . ' (' . $reason_str . ')';
				}
			}

			return $result;
		}
		else
		{
			$result = $this->addCustomer($shopify_payload);

			// Retry without phone if Shopify rejected phone formatting or phone is already taken
			if($result === FALSE && !empty($shopify_payload['phone']))
			{
				$rejection_reason = $this->getLastError();
				log_message('error', 'Shopify customer creation rejected phone ' . $shopify_payload['phone'] . ': ' . $rejection_reason . '. Retrying without phone.');
				unset($shopify_payload['phone']);
				unset($shopify_payload['sms_marketing_consent']);
				if(!empty($shopify_payload['addresses'][0]['phone']))
				{
					unset($shopify_payload['addresses'][0]['phone']);
				}
				$result = $this->addCustomer($shopify_payload);
				if($result !== FALSE)
				{
					$reason_str = 'rejected by Shopify';
					$err_decoded = json_decode($rejection_reason, TRUE);
					if(isset($err_decoded['errors']['phone']))
					{
						$reason_str = is_array($err_decoded['errors']['phone']) ? implode(', ', $err_decoded['errors']['phone']) : $err_decoded['errors']['phone'];
					}
					$this->_last_warning = 'Shopify rejected phone ' . $phone . ' (' . $reason_str . ')';
				}
			}

			// If creation failed because email was already taken (e.g. race condition), search and update
			if($result === FALSE && !empty($email))
			{
				$existing_customer = $this->findCustomerByEmail($email);
				if($existing_customer !== FALSE && !empty($existing_customer['id']))
				{
					$result = $this->updateCustomer($existing_customer['id'], $shopify_payload);
				}
			}

			return $result;
		}
	}

	/**
	 * Mailchimp compatibility alias
	 */
	public function addOrUpdateMember($email, $first_name, $last_name, $status = '', $parameters = array())
	{
		$person_data = array(
			'email' => $email,
			'first_name' => $first_name,
			'last_name' => $last_name
		);

		return $this->addOrUpdateCustomer($person_data, $parameters);
	}

	/**
	 * Mailchimp compatibility alias
	 */
	public function removeMember($email)
	{
		return $this->removeCustomer($email);
	}

	/**
	 * Mailchimp compatibility alias
	 */
	public function getMemberInfo($email)
	{
		return $this->getCustomerInfo($email);
	}
}

?>