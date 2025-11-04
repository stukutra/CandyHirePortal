<?php
/**
 * PayPal Configuration
 * Handles PayPal API integration for payments
 */

class PayPalConfig {
    private static $instance = null;
    private $clientId;
    private $clientSecret;
    private $mode; // 'sandbox' or 'live'
    private $baseUrl;

    private function __construct() {
        $this->mode = $_ENV['PAYPAL_MODE'] ?? 'sandbox';
        $this->clientId = $_ENV['PAYPAL_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['PAYPAL_CLIENT_SECRET'] ?? '';

        $this->baseUrl = $this->mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        error_log("PayPalConfig initialized - Mode: {$this->mode}, ClientID length: " . strlen($this->clientId) . ", ClientSecret length: " . strlen($this->clientSecret));
        error_log("PayPalConfig - Base URL: {$this->baseUrl}");
    }

    public static function getInstance(): PayPalConfig {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getClientId(): string {
        return $this->clientId;
    }

    public function getClientSecret(): string {
        return $this->clientSecret;
    }

    public function getMode(): string {
        return $this->mode;
    }

    public function getBaseUrl(): string {
        return $this->baseUrl;
    }

    public function isConfigured(): bool {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }
}

class PayPalClient {
    private $config;
    private $accessToken;
    private $tokenExpiry;

    public function __construct() {
        $this->config = PayPalConfig::getInstance();
    }

    /**
     * Get OAuth 2.0 access token
     */
    private function getAccessToken(): string {
        // Return cached token if still valid
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $ch = curl_init($this->config->getBaseUrl() . '/v1/oauth2/token');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US',
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_USERPWD => $this->config->getClientId() . ':' . $this->config->getClientSecret()
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log("PayPal getAccessToken - HTTP Code: {$httpCode}");
        error_log("PayPal getAccessToken - Response: " . substr($response, 0, 500));
        if ($curlError) {
            error_log("PayPal getAccessToken - cURL Error: {$curlError}");
        }

        if ($httpCode !== 200) {
            throw new Exception("Failed to get PayPal access token (HTTP {$httpCode}): " . substr($response, 0, 200));
        }

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'];
        $this->tokenExpiry = time() + ($data['expires_in'] - 60); // 60s buffer

        return $this->accessToken;
    }

    /**
     * Create a PayPal order
     */
    public function createOrder(float $amount, string $currency, string $description, array $metadata = []): array {
        $token = $this->getAccessToken();

        $isProduction = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production';
        $baseUrl = $isProduction ? 'https://app.candyhire.cloud' : 'http://localhost:4200';

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', '')
                    ],
                    'description' => $description,
                    'custom_id' => json_encode($metadata)
                ]
            ],
            'application_context' => [
                'brand_name' => 'CandyHire',
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
                'return_url' => $baseUrl . '/payment/success',
                'cancel_url' => $baseUrl . '/payment/cancel'
            ]
        ];

        $ch = curl_init($this->config->getBaseUrl() . '/v2/checkout/orders');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($orderData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            error_log('PayPal create order error: ' . $response);
            throw new Exception('Failed to create PayPal order');
        }

        return json_decode($response, true);
    }

    /**
     * Capture payment for an approved order
     */
    public function captureOrder(string $orderId): array {
        $token = $this->getAccessToken();

        $ch = curl_init($this->config->getBaseUrl() . '/v2/checkout/orders/' . $orderId . '/capture');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201 && $httpCode !== 200) {
            error_log('PayPal capture order error: ' . $response);
            throw new Exception('Failed to capture PayPal order');
        }

        return json_decode($response, true);
    }

    /**
     * Get order details
     */
    public function getOrderDetails(string $orderId): array {
        $token = $this->getAccessToken();

        $ch = curl_init($this->config->getBaseUrl() . '/v2/checkout/orders/' . $orderId);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Failed to get PayPal order details');
        }

        return json_decode($response, true);
    }
}
