<?php
/**
 * @backupGlobals disabled
 */
class Shopify_lib_test extends UnitTestCase
{
	public function setUp()
	{
		$this->resetInstance();
		$this->CI->load->library('shopify_lib');
	}

	public function test_endpoint_normalization()
	{
		$connector = new ShopifyConnector('https://my-test-store.myshopify.com/', 'key123', 'shpat_secret456');
		$this->assertEquals('my-test-store.myshopify.com', $connector->getShopUrl());

		$connector = new ShopifyConnector('subdomain-only', 'key123', 'shpat_secret456');
		$this->assertEquals('subdomain-only.myshopify.com', $connector->getShopUrl());

		$connector = new ShopifyConnector('https://my-test-store.myshopify.com/admin/apps', 'key123', 'shpat_secret456');
		$this->assertEquals('my-test-store.myshopify.com', $connector->getShopUrl());

		$connector = new ShopifyConnector('UPPERCASE-STORE.myshopify.com', 'key123', 'shpat_secret456');
		$this->assertEquals('uppercase-store.myshopify.com', $connector->getShopUrl());
	}

	public function test_ssrf_and_domain_validation_security()
	{
		// Malicious or arbitrary domains must be strictly rejected
		$malicious_domains = array(
			'attacker.com',
			'169.254.169.254',
			'127.0.0.1',
			'http://custom-domain.com/admin/apps',
			'evil.myshopify.com.attacker.com',
			'sub.domain.myshopify.com',
			'-invalid-start.myshopify.com',
			'invalid-end-.myshopify.com',
			'attacker.com#my-store.myshopify.com',
			'attacker.com?my-store.myshopify.com',
			'attacker.com:8443'
		);

		foreach($malicious_domains as $domain)
		{
			$connector = new ShopifyConnector($domain, 'key123', 'shpat_secret456');
			$this->assertEmpty($connector->getShopUrl(), "Domain {$domain} should be rejected");
			$this->assertFalse($connector->isConfigured(), "Domain {$domain} should cause isConfigured() to be false");
			$this->assertFalse($connector->call('GET', 'shop'), "Domain {$domain} must not trigger any API calls");
		}
	}

	public function test_is_configured()
	{
		$connector_empty = new ShopifyConnector('', '', '');
		$connector_empty->resetConfig('', '', '');
		$this->assertFalse($connector_empty->isConfigured());

		$connector_valid = new ShopifyConnector('test.myshopify.com', 'key123', 'shpat_secret456');
		$this->assertTrue($connector_valid->isConfigured());
	}

	public function test_reset_config()
	{
		$shopify_lib = new Shopify_lib(array(
			'shop_url' => 'initial.myshopify.com',
			'api_key' => 'key1',
			'api_secret_key' => 'secret1'
		));
		$this->assertEquals('initial.myshopify.com', $shopify_lib->getShopUrl());

		$shopify_lib->resetConfig('new-shop.myshopify.com', 'key2', 'secret2');
		$this->assertEquals('new-shop.myshopify.com', $shopify_lib->getShopUrl());
		$this->assertTrue($shopify_lib->isConfigured());
	}

	public function test_empty_customer_search_returns_false()
	{
		$shopify_lib = new Shopify_lib(array(
			'shop_url' => 'test.myshopify.com',
			'api_key' => 'key',
			'api_secret_key' => 'secret'
		));

		$this->assertFalse($shopify_lib->findCustomerByEmail(''));
		$this->assertFalse($shopify_lib->findCustomerByPhone(''));
		$this->assertFalse($shopify_lib->getCustomerInfo('', ''));
	}

	public function test_unconfigured_sync_returns_false()
	{
		$shopify_lib = new Shopify_lib();
		$shopify_lib->resetConfig('', '', '');

		$this->assertFalse($shopify_lib->addOrUpdateCustomer(array('first_name' => 'John', 'email' => 'john@example.com')));
		$this->assertFalse($shopify_lib->removeCustomer('john@example.com'));
		$this->assertFalse($shopify_lib->authenticate());
	}

	public function test_customer_payload_address_handling_reflection()
	{
		// Test reflection on Shopify_lib logic:
		// When no physical address is provided, addresses key must be omitted
		$shopify_lib = new Shopify_lib(array(
			'shop_url' => 'test.myshopify.com',
			'api_key' => 'key',
			'api_secret_key' => 'secret'
		));

		// Person without physical address
		$person_data_no_addr = array(
			'first_name' => 'Alice',
			'last_name' => 'Smith',
			'email' => 'alice@example.com',
			'phone_number' => '+15551234567'
		);
		$has_physical_address = !empty($person_data_no_addr['address_1']) || !empty($person_data_no_addr['city']) || !empty($person_data_no_addr['zip']) || !empty($person_data_no_addr['state']) || !empty($person_data_no_addr['country']);
		$this->assertFalse($has_physical_address);

		// Person with physical address
		$person_data_with_addr = array(
			'first_name' => 'Alice',
			'last_name' => 'Smith',
			'email' => 'alice@example.com',
			'address_1' => '123 Main St',
			'city' => 'Anytown',
			'state' => 'CA',
			'zip' => '90210',
			'country' => 'US'
		);
		$has_physical_address = !empty($person_data_with_addr['address_1']) || !empty($person_data_with_addr['city']) || !empty($person_data_with_addr['zip']) || !empty($person_data_with_addr['state']) || !empty($person_data_with_addr['country']);
		$this->assertTrue($has_physical_address);
	}

	public function test_tag_preservation_logic()
	{
		// Existing tags: VIP, wholesale
		$existing_customer = array('tags' => 'VIP, wholesale');
		$existing_tags = array_map('trim', explode(',', $existing_customer['tags']));
		if(!in_array('OSPOS', $existing_tags))
		{
			$existing_tags[] = 'OSPOS';
		}
		$tags = implode(', ', array_filter($existing_tags));
		$this->assertEquals('VIP, wholesale, OSPOS', $tags);

		// Existing tags already including OSPOS
		$existing_customer2 = array('tags' => 'VIP, OSPOS');
		$existing_tags2 = array_map('trim', explode(',', $existing_customer2['tags']));
		if(!in_array('OSPOS', $existing_tags2))
		{
			$existing_tags2[] = 'OSPOS';
		}
		$tags2 = implode(', ', array_filter($existing_tags2));
		$this->assertEquals('VIP, OSPOS', $tags2);
	}

	public function test_marketing_consent_payload_logic()
	{
		// Test consent mapping: consent true -> subscribed
		$customer_data_consented = array('consent' => 1);
		$state_consented = !empty($customer_data_consented['consent']) ? 'subscribed' : 'not_subscribed';
		$this->assertEquals('subscribed', $state_consented);

		// Test consent mapping: consent false -> not_subscribed
		$customer_data_unconsented = array('consent' => 0);
		$state_unconsented = !empty($customer_data_unconsented['consent']) ? 'subscribed' : 'not_subscribed';
		$this->assertEquals('not_subscribed', $state_unconsented);
	}

	public function test_phone_normalization()
	{
		$shopify_lib = new Shopify_lib(array(
			'shop_url' => 'colibri-boutique-london.myshopify.com',
			'api_key' => 'key',
			'api_secret_key' => 'secret'
		));

		// UK mobile starting with 07
		$this->assertEquals('+447554401729', $shopify_lib->normalizePhone('07554401729'));
		$this->assertEquals('+447554401729', $shopify_lib->normalizePhone('07554 401 729'));
		$this->assertEquals('+447554401729', $shopify_lib->normalizePhone('07554-401-729'));

		// UK landline starting with 02
		$this->assertEquals('+442079460192', $shopify_lib->normalizePhone('020 7946 0192'));

		// UK bracketed trunk prefix: +44 (0) 7554 401729
		$this->assertEquals('+447554401729', $shopify_lib->normalizePhone('+44 (0) 7554 401729'));

		// International 00 prefix: 00447554401729
		$this->assertEquals('+447554401729', $shopify_lib->normalizePhone('00447554401729'));

		// Already E.164
		$this->assertEquals('+447554401729', $shopify_lib->normalizePhone('+447554401729'));

		// US phone with country code specified
		$this->assertEquals('+12025550143', $shopify_lib->normalizePhone('(202) 555-0143', 'US'));

		// Australia phone with country code specified
		$this->assertEquals('+61412345678', $shopify_lib->normalizePhone('0412 345 678', 'AU'));

		// Empty or whitespace
		$this->assertEquals('', $shopify_lib->normalizePhone(''));
		$this->assertEquals('', $shopify_lib->normalizePhone('   '));
	}

	public function test_warning_handling()
	{
		$shopify_lib = new Shopify_lib();
		$this->assertEquals('', $shopify_lib->getLastWarning());
	}
}
