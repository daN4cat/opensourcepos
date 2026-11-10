<?php

namespace Tests\Libraries;

use App\Libraries\Shopify_lib;
use App\Libraries\ShopifyConnector;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionProperty;

/**
 * @internal
 */
final class Shopify_libTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        class_exists(Shopify_lib::class);
    }

    public function testConstructNormalizesValidShopEndpoints(): void
    {
        $connector = new ShopifyConnector('https://my-test-store.myshopify.com/', 'key123', 'shpat_secret456');
        $this->assertSame('my-test-store.myshopify.com', $connector->getShopUrl());

        $connector = new ShopifyConnector('subdomain-only', 'key123', 'shpat_secret456');
        $this->assertSame('subdomain-only.myshopify.com', $connector->getShopUrl());

        $connector = new ShopifyConnector('https://my-test-store.myshopify.com/admin/apps', 'key123', 'shpat_secret456');
        $this->assertSame('my-test-store.myshopify.com', $connector->getShopUrl());

        $connector = new ShopifyConnector('UPPERCASE-STORE.myshopify.com', 'key123', 'shpat_secret456');
        $this->assertSame('uppercase-store.myshopify.com', $connector->getShopUrl());
    }

    public function testConstructRejectsArbitraryAndMaliciousDomains(): void
    {
        $maliciousDomains = [
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
            'attacker.com:8443',
        ];

        foreach ($maliciousDomains as $domain) {
            $connector = new ShopifyConnector($domain, 'key123', 'shpat_secret456');
            $this->assertSame('', $connector->getShopUrl(), "Domain {$domain} should be rejected");
            $this->assertFalse($connector->isConfigured(), "Domain {$domain} should cause isConfigured() to be false");
            $this->assertFalse($connector->call('GET', 'shop'), "Domain {$domain} must not trigger any API calls");
        }
    }

    public function testIsConfiguredRequiresEndpointAndToken(): void
    {
        $connectorEmpty = new ShopifyConnector('', '', '');
        $connectorEmpty->resetConfig('', '', '');
        $this->assertFalse($connectorEmpty->isConfigured());

        $connectorValid = new ShopifyConnector('test.myshopify.com', 'key123', 'shpat_secret456');
        $this->assertTrue($connectorValid->isConfigured());
    }

    public function testResetConfigUpdatesShopUrlAndConfiguration(): void
    {
        $shopifyLib = new Shopify_lib([
            'shop_url'       => 'initial.myshopify.com',
            'api_key'        => 'key1',
            'api_secret_key' => 'secret1',
        ]);
        $this->assertSame('initial.myshopify.com', $shopifyLib->getShopUrl());

        $shopifyLib->resetConfig('new-shop.myshopify.com', 'key2', 'secret2');
        $this->assertSame('new-shop.myshopify.com', $shopifyLib->getShopUrl());
        $this->assertTrue($shopifyLib->isConfigured());
    }

    public function testFindCustomerRejectsEmptyEmailAndPhone(): void
    {
        $shopifyLib = new Shopify_lib([
            'shop_url'       => 'test.myshopify.com',
            'api_key'        => 'key',
            'api_secret_key' => 'secret',
        ]);

        $this->assertFalse($shopifyLib->findCustomerByEmail(''));
        $this->assertFalse($shopifyLib->findCustomerByPhone(''));
        $this->assertFalse($shopifyLib->getCustomerInfo('', ''));
    }

    public function testSyncMethodsReturnFalseWhenUnconfigured(): void
    {
        $shopifyLib = new Shopify_lib();
        $shopifyLib->resetConfig('', '', '');

        $this->assertFalse($shopifyLib->addOrUpdateCustomer(['first_name' => 'John', 'email' => 'john@example.com']));
        $this->assertFalse($shopifyLib->removeCustomer('john@example.com'));
        $this->assertFalse($shopifyLib->authenticate());
    }

    public function testNormalizePhoneNormalizesUkUsAndInternationalNumbers(): void
    {
        $shopifyLib = new Shopify_lib([
            'shop_url'       => 'colibri-boutique-london.myshopify.com',
            'api_key'        => 'key',
            'api_secret_key' => 'secret',
        ]);

        $this->assertSame('+447554401729', $shopifyLib->normalizePhone('07554401729'));
        $this->assertSame('+447554401729', $shopifyLib->normalizePhone('07554 401 729'));
        $this->assertSame('+447554401729', $shopifyLib->normalizePhone('07554-401-729'));
        $this->assertSame('+442079460192', $shopifyLib->normalizePhone('020 7946 0192'));
        $this->assertSame('+447554401729', $shopifyLib->normalizePhone('+44 (0) 7554 401729'));
        $this->assertSame('+447554401729', $shopifyLib->normalizePhone('00447554401729'));
        $this->assertSame('+447554401729', $shopifyLib->normalizePhone('+447554401729'));
        $this->assertSame('+12025550143', $shopifyLib->normalizePhone('(202) 555-0143', 'US'));
        $this->assertSame('+61412345678', $shopifyLib->normalizePhone('0412 345 678', 'AU'));
        $this->assertSame('', $shopifyLib->normalizePhone(''));
        $this->assertSame('', $shopifyLib->normalizePhone('   '));
    }

    public function testGetLastWarningDefaultsToEmptyString(): void
    {
        $shopifyLib = new Shopify_lib();
        $this->assertSame('', $shopifyLib->getLastWarning());
    }

    public function testAddOrUpdateCustomerRetriesWithoutPhoneAndSetsWarningWhenPhoneRejected(): void
    {
        $mockConnector = $this->createMock(ShopifyConnector::class);
        $mockConnector->method('isConfigured')->willReturn(true);
        $mockConnector->method('getShopUrl')->willReturn('colibri-boutique-london.myshopify.com');
        $mockConnector->method('getLastError')->willReturn('{"errors":{"phone":["Phone has already been taken"]}}');

        $callCount = 0;
        $mockConnector->method('call')->willReturnCallback(function (string $httpVerb, string $method, array $args = []) use (&$callCount) {
            if ($httpVerb === 'GET') {
                return ['customers' => []];
            }
            if ($httpVerb === 'POST' && $method === 'customers') {
                $callCount++;
                if ($callCount === 1) {
                    $this->assertArrayHasKey('phone', $args['customer']);

                    return false;
                }
                $this->assertArrayNotHasKey('phone', $args['customer']);
                $this->assertArrayNotHasKey('sms_marketing_consent', $args['customer']);

                return ['customer' => ['id' => 999, 'email' => 'alice@example.com']];
            }

            return false;
        });

        $shopifyLib    = new Shopify_lib();
        $connectorProp = new ReflectionProperty(Shopify_lib::class, 'connector');
        $connectorProp->setValue($shopifyLib, $mockConnector);

        $result = $shopifyLib->addOrUpdateCustomer([
            'first_name'   => 'Alice',
            'last_name'    => 'Smith',
            'email'        => 'alice@example.com',
            'phone_number' => '07554401729',
        ], ['consent' => 1]);

        $this->assertIsArray($result);
        $this->assertSame(999, $result['id']);
        $this->assertStringContainsString('Shopify rejected phone +447554401729', $shopifyLib->getLastWarning());
    }
}
