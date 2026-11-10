<?php

namespace App\Libraries;

use Config\OSPOS;
use Config\Services;
use Throwable;

class ShopifyConnector
{
    private string $shopUrl      = '';
    private string $apiKey       = '';
    private string $apiSecretKey = '';
    private string $apiVersion   = '2024-04';
    private string $apiEndpoint  = '';
    private string $lastError    = '';
    private int $lastHttpCode    = 0;

    public function __construct(string $shopUrl = '', string $apiKey = '', string $apiSecretKey = '')
    {
        if ($shopUrl === '' && $apiKey === '' && $apiSecretKey === '') {
            $config             = config(OSPOS::class)->settings ?? [];
            $this->shopUrl      = $this->decryptValue((string) ($config['shopify_shop_url'] ?? ''));
            $this->apiKey       = $this->decryptValue((string) ($config['shopify_api_key'] ?? ''));
            $this->apiSecretKey = $this->decryptValue((string) ($config['shopify_api_secret_key'] ?? ''));
        } else {
            $this->shopUrl      = $shopUrl;
            $this->apiKey       = $apiKey;
            $this->apiSecretKey = $apiSecretKey;
        }

        $this->setupEndpoint();
    }

    private function decryptValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            helper('security');
            if (function_exists('checkEncryption') && checkEncryption()) {
                $decrypted = Services::encrypter()->decrypt($value);
                if (is_string($decrypted) && $decrypted !== '') {
                    return $decrypted;
                }
            }
        } catch (Throwable) {
        }

        return $value;
    }

    public function resetConfig(string $shopUrl = '', string $apiKey = '', string $apiSecretKey = ''): void
    {
        $this->lastError    = '';
        $this->lastHttpCode = 0;
        $this->shopUrl      = trim($shopUrl);
        $this->apiKey       = trim($apiKey);
        $this->apiSecretKey = trim($apiSecretKey);
        $this->apiEndpoint  = '';
        $this->setupEndpoint();
    }

    /**
     * Enforces strict myshopify.com subdomain validation to prevent SSRF and token leakage.
     */
    private function setupEndpoint(): void
    {
        if ($this->shopUrl === '' && $this->apiKey !== '' && str_contains($this->apiKey, 'myshopify.com')) {
            $this->shopUrl = $this->apiKey;
            $this->apiKey  = '';
        }

        if ($this->shopUrl !== '') {
            $shop = trim($this->shopUrl);
            $shop = (string) preg_replace('#^https?://#i', '', $shop);
            if (str_contains($shop, '@')) {
                $shop = substr($shop, (int) strrpos($shop, '@') + 1);
            }
            $shop = (string) preg_replace('~[/\\\\?#].*$~', '', $shop);
            $shop = (string) preg_replace('#:[0-9]+$#', '', $shop);
            $shop = strtolower(trim($shop));

            if ($shop !== '' && ! str_contains($shop, '.')) {
                $shop .= '.myshopify.com';
            }

            if (preg_match('/^[a-z0-9]([a-z0-9\-]{0,58}[a-z0-9])?\.myshopify\.com$/', $shop) === 1) {
                $this->shopUrl     = $shop;
                $this->apiEndpoint = 'https://' . $this->shopUrl . '/admin/api/' . $this->apiVersion . '/';
            } else {
                $this->shopUrl     = '';
                $this->apiEndpoint = '';
                $this->lastError   = 'Invalid Shopify shop domain. Must be in the format your-store.myshopify.com';
                log_message('error', 'ShopifyConnector: Rejected invalid shop domain: ' . $shop);
            }
        }
    }

    public function isConfigured(): bool
    {
        return $this->apiEndpoint !== '' && ($this->apiSecretKey !== '' || $this->apiKey !== '');
    }

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getLastHttpCode(): int
    {
        return $this->lastHttpCode;
    }

    public function call(string $httpVerb = 'POST', string $method = '', array $args = []): array|bool
    {
        if ($this->isConfigured()) {
            return $this->request($httpVerb, $method, $args);
        }

        return false;
    }

    private function buildRequestUrl(string $httpVerb = 'POST', string $method = '', array $args = []): string
    {
        $method = ltrim($method, '/');

        if (! str_contains($method, '.json') && ! str_contains($method, '?')) {
            $method .= '.json';
        } elseif (str_contains($method, '?') && ! str_contains($method, '.json?')) {
            $method = str_replace('?', '.json?', $method);
        }

        if ($httpVerb === 'GET' && ! empty($args)) {
            return $this->apiEndpoint . $method . (! str_contains($method, '?') ? '?' : '&') . http_build_query($args);
        }

        return $this->apiEndpoint . $method;
    }

    private function request(string $httpVerb, string $method, array $args = []): array|bool
    {
        $this->lastError    = '';
        $this->lastHttpCode = 0;

        $ch = curl_init();
        if ($ch !== false) {
            $url = $this->buildRequestUrl($httpVerb, $method, $args);

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
            ];

            if ($this->apiSecretKey !== '' && str_starts_with($this->apiSecretKey, 'shpat_')) {
                $token = $this->apiSecretKey;
            } elseif ($this->apiKey !== '' && str_starts_with($this->apiKey, 'shpat_')) {
                $token = $this->apiKey;
            } else {
                $token = $this->apiSecretKey !== '' ? $this->apiSecretKey : $this->apiKey;
            }

            if ($token !== '') {
                $token     = (string) preg_replace('/[\r\n]/', '', trim($token));
                $headers[] = 'X-Shopify-Access-Token: ' . $token;
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_USERAGENT, 'OSPOS-Shopify/1.0');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpVerb);

            if (in_array($httpVerb, ['POST', 'PUT', 'PATCH'], true) && ! empty($args)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
            }

            $result             = curl_exec($ch);
            $httpCode           = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->lastHttpCode = $httpCode;

            if (curl_errno($ch)) {
                $curlErr         = curl_error($ch);
                $this->lastError = $curlErr;
                log_message('error', 'Shopify cURL error: ' . $curlErr);
            }

            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                if ($httpCode === 204 || empty($result) || ! is_string($result) || trim($result) === '{}') {
                    return [];
                }

                $decoded = json_decode($result, true);

                return is_array($decoded) ? $decoded : [];
            }

            $this->lastError = is_string($result) ? $result : '';
            log_message('error', 'Shopify API returned HTTP ' . $httpCode . ' [' . $httpVerb . ' ' . $method . ']: ' . $this->lastError);

            return false;
        }

        return false;
    }
}

class Shopify_lib
{
    private ShopifyConnector $connector;
    private string $lastWarning = '';

    public function __construct(array $params = [])
    {
        $shopUrl      = ! empty($params['shop_url']) ? (string) $params['shop_url'] : '';
        $apiKey       = ! empty($params['api_key']) ? (string) $params['api_key'] : '';
        $apiSecretKey = ! empty($params['api_secret_key']) ? (string) $params['api_secret_key'] : '';

        $this->connector = new ShopifyConnector($shopUrl, $apiKey, $apiSecretKey);
    }

    public function getLastWarning(): string
    {
        return $this->lastWarning;
    }

    public function resetConfig(string $shopUrl = '', string $apiKey = '', string $apiSecretKey = ''): void
    {
        $this->connector->resetConfig($shopUrl, $apiKey, $apiSecretKey);
    }

    public function getShopUrl(): string
    {
        return $this->connector->getShopUrl();
    }

    public function getLastError(): string
    {
        return $this->connector->getLastError();
    }

    public function getLastHttpCode(): int
    {
        return $this->connector->getLastHttpCode();
    }

    public function isConfigured(): bool
    {
        return $this->connector->isConfigured();
    }

    public function authenticate(): bool
    {
        $response = $this->getShop();

        return is_array($response) && ! empty($response['shop']);
    }

    public function getShop(): array|bool
    {
        return $this->connector->call('GET', 'shop');
    }

    public function getCustomers(array $parameters = []): array|bool
    {
        return $this->connector->call('GET', 'customers', $parameters);
    }

    public function getCustomerById(int|string $customerId, array $parameters = []): array|bool
    {
        $result = $this->connector->call('GET', 'customers/' . $customerId, $parameters);
        if (is_array($result) && ! empty($result['customer'])) {
            return $result['customer'];
        }

        return false;
    }

    public function findCustomerByEmail(string $email): array|bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }

        $escapedEmail = addcslashes($email, '"\\');
        $result       = $this->connector->call('GET', 'customers/search', ['query' => 'email:"' . $escapedEmail . '"']);
        if (is_array($result) && ! empty($result['customers']) && is_array($result['customers'])) {
            foreach ($result['customers'] as $customer) {
                if (isset($customer['email']) && strcasecmp(trim((string) $customer['email']), $email) === 0) {
                    return $customer;
                }
            }

            return $result['customers'][0];
        }

        return false;
    }

    /**
     * Normalize a phone number to international E.164 format (+[country code][number])
     * as required by Shopify's Admin REST and GraphQL APIs.
     */
    public function normalizePhone(string $phone, string $country = ''): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        $phone   = (string) preg_replace('/\(\s*0\s*\)/', '', $phone);
        $cleaned = (string) preg_replace('/[^\d+]/', '', $phone);
        if ($cleaned === '') {
            return '';
        }

        if (str_starts_with($cleaned, '00')) {
            $cleaned = '+' . substr($cleaned, 2);
        }

        if (str_starts_with($cleaned, '+440') && strlen($cleaned) >= 14) {
            $cleaned = '+44' . substr($cleaned, 4);
        }

        if (str_starts_with($cleaned, '+')) {
            $digits = (string) preg_replace('/[^\d]/', '', $cleaned);
            if (strlen($digits) >= 8 && strlen($digits) <= 15) {
                return '+' . $digits;
            }

            return $cleaned;
        }

        $digits = $cleaned;

        $countryCallingCodes = [
            'GB'                   => '44',
            'UK'                   => '44',
            'GBR'                  => '44',
            'UNITED KINGDOM'       => '44',
            'ENGLAND'              => '44',
            'SCOTLAND'             => '44',
            'WALES'                => '44',
            'US'                   => '1',
            'USA'                  => '1',
            'UNITED STATES'        => '1',
            'CA'                   => '1',
            'CAN'                  => '1',
            'CANADA'               => '1',
            'AU'                   => '61',
            'AUS'                  => '61',
            'AUSTRALIA'            => '61',
            'IE'                   => '353',
            'IRL'                  => '353',
            'IRELAND'              => '353',
            'FR'                   => '33',
            'FRA'                  => '33',
            'FRANCE'               => '33',
            'DE'                   => '49',
            'DEU'                  => '49',
            'GERMANY'              => '49',
            'IT'                   => '39',
            'ITA'                  => '39',
            'ITALY'                => '39',
            'ES'                   => '34',
            'ESP'                  => '34',
            'SPAIN'                => '34',
            'NL'                   => '31',
            'NLD'                  => '31',
            'NETHERLANDS'          => '31',
            'NZ'                   => '64',
            'NZL'                  => '64',
            'NEW ZEALAND'          => '64',
            'IN'                   => '91',
            'IND'                  => '91',
            'INDIA'                => '91',
            'ZA'                   => '27',
            'ZAF'                  => '27',
            'SOUTH AFRICA'         => '27',
            'BR'                   => '55',
            'BRA'                  => '55',
            'BRAZIL'               => '55',
            'MX'                   => '52',
            'MEX'                  => '52',
            'MEXICO'               => '52',
            'SG'                   => '65',
            'SGP'                  => '65',
            'SINGAPORE'            => '65',
            'AE'                   => '971',
            'ARE'                  => '971',
            'UNITED ARAB EMIRATES' => '971',
        ];

        $callingCode  = '';
        $countryUpper = strtoupper(trim($country));
        if ($countryUpper !== '' && isset($countryCallingCodes[$countryUpper])) {
            $callingCode = $countryCallingCodes[$countryUpper];
        }

        if ($callingCode === '') {
            $config       = config(OSPOS::class)->settings ?? [];
            $storeCountry = strtoupper(trim((string) ($config['country_codes'] ?? '')));
            if ($storeCountry !== '' && isset($countryCallingCodes[$storeCountry])) {
                $callingCode = $countryCallingCodes[$storeCountry];
            }

            $shopUrl = strtolower($this->getShopUrl());
            if (str_contains($shopUrl, 'london') || str_contains($shopUrl, '.uk') || str_contains($shopUrl, '-uk')) {
                $callingCode = '44';
            }
        }

        if (($callingCode === '' || $callingCode === '44') && str_starts_with($digits, '07') && strlen($digits) === 11) {
            return '+44' . substr($digits, 1);
        }
        if (
            ($callingCode === '' || $callingCode === '44')
            && (str_starts_with($digits, '01') || str_starts_with($digits, '02') || str_starts_with($digits, '03'))
            && (strlen($digits) === 10 || strlen($digits) === 11)
        ) {
            return '+44' . substr($digits, 1);
        }

        if (($callingCode === '1' || $callingCode === '') && strlen($digits) === 10 && $digits[0] >= '2' && $digits[0] <= '9') {
            if ($callingCode === '1') {
                return '+1' . $digits;
            }
        }
        if (($callingCode === '1' || $callingCode === '') && strlen($digits) === 11 && $digits[0] === '1') {
            return '+' . $digits;
        }

        if ($callingCode !== '') {
            if (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }
            $normalized = '+' . $callingCode . $digits;
            if (strlen($normalized) >= 9 && strlen($normalized) <= 16) {
                return $normalized;
            }
        }

        if (strlen($digits) >= 11 && strlen($digits) <= 15) {
            foreach (['44', '1', '61', '33', '49', '39', '34', '31', '353', '64'] as $code) {
                if (str_starts_with($digits, $code)) {
                    return '+' . $digits;
                }
            }
        }

        return $phone;
    }

    public function findCustomerByPhone(string $phone, string $country = ''): array|bool
    {
        $phone = trim($phone);
        if ($phone === '') {
            return false;
        }

        $normalized  = $this->normalizePhone($phone, $country);
        $searchPhone = $normalized !== '' ? $normalized : $phone;

        $escapedSearchPhone = addcslashes($searchPhone, '"\\');
        $result             = $this->connector->call('GET', 'customers/search', ['query' => 'phone:"' . $escapedSearchPhone . '"']);
        if (is_array($result) && ! empty($result['customers']) && is_array($result['customers'])) {
            return $result['customers'][0];
        }

        if ($searchPhone !== $phone) {
            $escapedPhone = addcslashes($phone, '"\\');
            $result       = $this->connector->call('GET', 'customers/search', ['query' => 'phone:"' . $escapedPhone . '"']);
            if (is_array($result) && ! empty($result['customers']) && is_array($result['customers'])) {
                return $result['customers'][0];
            }
        }

        return false;
    }

    public function getCustomerInfo(string $email, string $phone = '', string $country = ''): array|bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        if ($email !== '') {
            $customer = $this->findCustomerByEmail($email);
            if ($customer !== false) {
                return $customer;
            }
        }

        if ($phone !== '') {
            $customer = $this->findCustomerByPhone($phone, $country);
            if ($customer !== false) {
                return $customer;
            }
        }

        return false;
    }

    public function addCustomer(array $customerData): array|bool
    {
        $result = $this->connector->call('POST', 'customers', ['customer' => $customerData]);
        if (is_array($result) && ! empty($result['customer'])) {
            return $result['customer'];
        }

        return false;
    }

    public function updateCustomer(int|string $customerId, array $customerData): array|bool
    {
        $result = $this->connector->call('PUT', 'customers/' . $customerId, ['customer' => $customerData]);
        if (is_array($result) && ! empty($result['customer'])) {
            return $result['customer'];
        }

        return false;
    }

    public function removeCustomer(int|string $emailOrPhoneOrId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $customerId = null;

        if (is_numeric($emailOrPhoneOrId)) {
            $customerId = $emailOrPhoneOrId;
        } elseif (is_string($emailOrPhoneOrId) && $emailOrPhoneOrId !== '') {
            $identifier = trim($emailOrPhoneOrId);
            if (str_contains($identifier, '@')) {
                $customer = $this->findCustomerByEmail($identifier);
                if ($customer !== false && ! empty($customer['id'])) {
                    $customerId = $customer['id'];
                }
            } else {
                $customer = $this->findCustomerByPhone($identifier);
                if ($customer !== false && ! empty($customer['id'])) {
                    $customerId = $customer['id'];
                }
            }
        }

        if (! empty($customerId)) {
            $result = $this->connector->call('DELETE', 'customers/' . $customerId);

            return $result !== false;
        }

        return false;
    }

    public function addOrUpdateCustomer(array|string $personData, array|string $customerData = [], string $lastNameArg = '', string $phoneArg = ''): array|bool
    {
        $this->lastWarning = '';

        if (! $this->isConfigured()) {
            return false;
        }

        if (is_string($personData)) {
            $email     = $personData;
            $firstName = is_string($customerData) ? $customerData : '';

            $personData = [
                'email'        => $email,
                'first_name'   => $firstName,
                'last_name'    => $lastNameArg,
                'phone_number' => $phoneArg,
            ];
            $customerData = [];
        }

        $email   = isset($personData['email']) ? trim((string) $personData['email']) : '';
        $phone   = isset($personData['phone_number']) ? trim((string) $personData['phone_number']) : '';
        $country = isset($personData['country']) ? trim((string) $personData['country']) : '';

        $normalizedPhone = $this->normalizePhone($phone, $country);
        if ($normalizedPhone !== '') {
            $phone = $normalizedPhone;
        }

        if ($email === '' && $phone === '') {
            log_message('debug', 'Shopify customer sync skipped: neither email nor phone number is provided.');

            return false;
        }

        $existingCustomer = false;
        if ($email !== '') {
            $existingCustomer = $this->findCustomerByEmail($email);
        }
        if ($existingCustomer === false && $phone !== '') {
            $existingCustomer = $this->findCustomerByPhone($phone, $country);
        }

        $tags = ['OSPOS'];
        if ($existingCustomer !== false && ! empty($existingCustomer['tags'])) {
            $existingTags = array_map('trim', explode(',', (string) $existingCustomer['tags']));
            if (! in_array('OSPOS', $existingTags, true)) {
                $existingTags[] = 'OSPOS';
            }
            $tags = array_filter($existingTags);
        }

        $shopifyPayload = [
            'first_name' => $personData['first_name'] ?? '',
            'last_name'  => $personData['last_name'] ?? '',
            'tags'       => implode(', ', $tags),
        ];

        $consentSpecified = is_array($customerData) && isset($customerData['consent']);
        $isConsented      = $consentSpecified ? ! empty($customerData['consent']) : ($existingCustomer === false);

        if ($email !== '') {
            $shopifyPayload['email']          = $email;
            $shopifyPayload['verified_email'] = true;

            if ($consentSpecified || $existingCustomer === false) {
                $shopifyPayload['email_marketing_consent'] = [
                    'state'              => $isConsented ? 'subscribed' : 'not_subscribed',
                    'opt_in_level'       => 'single_opt_in',
                    'consent_updated_at' => date('c'),
                ];
            }
        }

        if ($phone !== '') {
            $shopifyPayload['phone'] = $phone;

            if ($consentSpecified || $existingCustomer === false) {
                $shopifyPayload['sms_marketing_consent'] = [
                    'state'              => $isConsented ? 'subscribed' : 'not_subscribed',
                    'opt_in_level'       => 'single_opt_in',
                    'consent_updated_at' => date('c'),
                ];
            }
        }

        if (! empty($personData['comments'])) {
            $shopifyPayload['note'] = $personData['comments'];
        }

        $hasPhysicalAddress = ! empty($personData['address_1'])
            || ! empty($personData['city'])
            || ! empty($personData['zip'])
            || ! empty($personData['state'])
            || ! empty($personData['country']);

        if ($hasPhysicalAddress) {
            $address = [];
            if (! empty($personData['address_1'])) {
                $address['address1'] = $personData['address_1'];
            }
            if (! empty($personData['address_2'])) {
                $address['address2'] = $personData['address_2'];
            }
            if (! empty($personData['city'])) {
                $address['city'] = $personData['city'];
            }
            if (! empty($personData['state'])) {
                $address['province'] = $personData['state'];
            }
            if (! empty($personData['zip'])) {
                $address['zip'] = $personData['zip'];
            }
            if (! empty($personData['country'])) {
                $address['country'] = $personData['country'];
            }
            if (is_array($customerData) && ! empty($customerData['company_name'])) {
                $address['company'] = $customerData['company_name'];
            }
            if (! empty($personData['first_name'])) {
                $address['first_name'] = $personData['first_name'];
            }
            if (! empty($personData['last_name'])) {
                $address['last_name'] = $personData['last_name'];
            }
            if ($phone !== '') {
                $address['phone'] = $phone;
            }

            if ($existingCustomer !== false && ! empty($existingCustomer['default_address']['id'])) {
                $address['id'] = $existingCustomer['default_address']['id'];
            }

            $shopifyPayload['addresses'] = [$address];
        }

        if ($existingCustomer !== false && ! empty($existingCustomer['id'])) {
            $result = $this->updateCustomer($existingCustomer['id'], $shopifyPayload);

            if ($result === false && ! empty($shopifyPayload['phone'])) {
                $rejectionReason = $this->getLastError();
                log_message('error', 'Shopify customer update rejected phone ' . $shopifyPayload['phone'] . ' for ID ' . $existingCustomer['id'] . ': ' . $rejectionReason . '. Retrying without phone.');
                unset($shopifyPayload['phone'], $shopifyPayload['sms_marketing_consent']);
                if (! empty($shopifyPayload['addresses'][0]['phone'])) {
                    unset($shopifyPayload['addresses'][0]['phone']);
                }
                $result = $this->updateCustomer($existingCustomer['id'], $shopifyPayload);
                if ($result !== false) {
                    $reasonStr  = 'rejected by Shopify';
                    $errDecoded = json_decode($rejectionReason, true);
                    if (isset($errDecoded['errors']['phone'])) {
                        $reasonStr = is_array($errDecoded['errors']['phone']) ? implode(', ', $errDecoded['errors']['phone']) : (string) $errDecoded['errors']['phone'];
                    }
                    $this->lastWarning = 'Shopify rejected phone ' . $phone . ' (' . $reasonStr . ')';
                }
            }

            return $result;
        }

        $result = $this->addCustomer($shopifyPayload);

        if ($result === false && ! empty($shopifyPayload['phone'])) {
            $rejectionReason = $this->getLastError();
            log_message('error', 'Shopify customer creation rejected phone ' . $shopifyPayload['phone'] . ': ' . $rejectionReason . '. Retrying without phone.');
            unset($shopifyPayload['phone'], $shopifyPayload['sms_marketing_consent']);
            if (! empty($shopifyPayload['addresses'][0]['phone'])) {
                unset($shopifyPayload['addresses'][0]['phone']);
            }
            $result = $this->addCustomer($shopifyPayload);
            if ($result !== false) {
                $reasonStr  = 'rejected by Shopify';
                $errDecoded = json_decode($rejectionReason, true);
                if (isset($errDecoded['errors']['phone'])) {
                    $reasonStr = is_array($errDecoded['errors']['phone']) ? implode(', ', $errDecoded['errors']['phone']) : (string) $errDecoded['errors']['phone'];
                }
                $this->lastWarning = 'Shopify rejected phone ' . $phone . ' (' . $reasonStr . ')';
            }
        }

        if ($result === false && $email !== '') {
            $existingCustomer = $this->findCustomerByEmail($email);
            if ($existingCustomer !== false && ! empty($existingCustomer['id'])) {
                $result = $this->updateCustomer($existingCustomer['id'], $shopifyPayload);
            }
        }

        return $result;
    }

    public function addOrUpdateMember(string $email, string $firstName, string $lastName, string $status = '', array $parameters = []): array|bool
    {
        $personData = [
            'email'      => $email,
            'first_name' => $firstName,
            'last_name'  => $lastName,
        ];

        return $this->addOrUpdateCustomer($personData, $parameters);
    }

    public function removeMember(string $email): bool
    {
        return $this->removeCustomer($email);
    }

    public function getMemberInfo(string $email): array|bool
    {
        return $this->getCustomerInfo($email);
    }
}
