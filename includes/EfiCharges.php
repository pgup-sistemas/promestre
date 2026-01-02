<?php

class EfiCharges {
    private $clientId;
    private $clientSecret;
    private $baseUrl;
    private $token;

    public function __construct($clientId, $clientSecret, $environment = 'production') {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        $env = strtolower((string)$environment);
        $this->baseUrl = ($env === 'sandbox')
            ? 'https://cobrancas-h.api.efipay.com.br'
            : 'https://cobrancas.api.efipay.com.br';
    }

    private function getBasicAuth() {
        return base64_encode($this->clientId . ':' . $this->clientSecret);
    }

    public function authenticate() {
        $url = $this->baseUrl . '/v1/authorize';

        $curl = curl_init();

        $config = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"grant_type":"client_credentials"}',
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $this->getBasicAuth(),
                'Content-Type: application/json'
            ],
        ];

        curl_setopt_array($curl, $config);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new Exception('cURL Error #: ' . $err);
        }

        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            $this->token = $data['access_token'];
            return $this->token;
        }

        throw new Exception('Erro ao autenticar: ' . ($data['error_description'] ?? $response));
    }

    private function request($method, $path, $body = null) {
        if (!$this->token) {
            $this->authenticate();
        }

        $url = $this->baseUrl . $path;

        $curl = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ];

        $config = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($body !== null) {
            $config[CURLOPT_POSTFIELDS] = json_encode($body);
        }

        curl_setopt_array($curl, $config);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new Exception('cURL Error #: ' . $err);
        }

        $data = json_decode($response, true);
        return $data ?: ['raw' => $response];
    }

    public function createPaymentLinkOneStep(array $payload) {
        return $this->request('POST', '/v1/charge/one-step/link', $payload);
    }

    public function createPlan(array $payload) {
        return $this->request('POST', '/v1/plan', $payload);
    }

    public function createPlanSubscriptionOneStepLink($planId, array $payload) {
        return $this->request('POST', '/v1/plan/' . urlencode((string)$planId) . '/subscription/one-step/link', $payload);
    }

    public function getSubscription($subscriptionId) {
        return $this->request('GET', '/v1/subscription/' . urlencode((string)$subscriptionId), null);
    }

    public function cancelSubscription($subscriptionId) {
        return $this->request('PUT', '/v1/subscription/' . urlencode((string)$subscriptionId) . '/cancel', null);
    }
}
