<?php

class EfiPay {
    private $client_id;
    private $client_secret;
    private $certificate;
    private $baseUrl;
    private $token;

    // Ambiente de produção por padrão. Para sandbox, mude para true.
    private $sandbox = true; 

    public function __construct($client_id, $client_secret, $certificate, $sandbox = true) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->certificate = $certificate;
        $this->sandbox = $sandbox;
        
        $this->baseUrl = $this->sandbox 
            ? 'https://pix-h.api.efipay.com.br' 
            : 'https://pix.api.efipay.com.br';
    }

    private function getBasicAuth() {
        return base64_encode($this->client_id . ':' . $this->client_secret);
    }

    public function authenticate() {
        $url = $this->baseUrl . '/oauth/token';
        
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
            CURLOPT_POSTFIELDS => '{"grant_type": "client_credentials"}',
            CURLOPT_SSLCERT => $this->certificate,
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
            throw new Exception("cURL Error #: " . $err);
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['access_token'])) {
            $this->token = $data['access_token'];
            return $this->token;
        } else {
            throw new Exception("Erro ao autenticar: " . ($data['error_description'] ?? 'Erro desconhecido'));
        }
    }

    public function createCob($txid, $valor, $chave, $devedor = null, $solicitacao = '') {
        if (!$this->token) {
            $this->authenticate();
        }

        $url = $this->baseUrl . '/v2/cob/' . $txid;
        
        $body = [
            'calendario' => [
                'expiracao' => 3600 // 1 hora
            ],
            'valor' => [
                'original' => number_format($valor, 2, '.', '')
            ],
            'chave' => $chave,
            'solicitacaoPagador' => $solicitacao
        ];

        if ($devedor) {
            $body['devedor'] = $devedor;
        }

        $curl = curl_init();
        
        $config = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_SSLCERT => $this->certificate,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ],
        ];

        curl_setopt_array($curl, $config);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #: " . $err);
        }
        
        return json_decode($response, true);
    }
    
    public function getQrCode($idCob) {
        if (!$this->token) {
            $this->authenticate();
        }

        $url = $this->baseUrl . '/v2/loc/' . $idCob . '/qrcode';
        
        $curl = curl_init();
        
        $config = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSLCERT => $this->certificate,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ],
        ];

        curl_setopt_array($curl, $config);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #: " . $err);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Consultar status de uma cobrança PIX pelo txid
     */
    public function consultarCob($txid) {
        if (!$this->token) {
            $this->authenticate();
        }

        $url = $this->baseUrl . '/v2/cob/' . $txid;
        
        $curl = curl_init();
        
        $config = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSLCERT => $this->certificate,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ],
        ];

        curl_setopt_array($curl, $config);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #: " . $err);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Gerar boleto via EfiBank (cobrança única)
     */
    public function criarBoleto($valor, $vencimento, $cliente, $instrucoes = []) {
        if (!$this->token) {
            $this->authenticate();
        }

        // URL base para cobrança (diferente de PIX)
        $baseUrlBoleto = $this->sandbox 
            ? 'https://api-pix-h.gerencianet.com.br' 
            : 'https://api-pix.gerencianet.com.br';
            
        $url = $baseUrlBoleto . '/v1/charge';
        
        // Preparar dados do boleto
        $body = [
            'items' => [
                [
                    'name' => 'Mensalidade',
                    'value' => intval($valor * 100), // Valor em centavos
                    'amount' => 1
                ]
            ],
            'customer' => $cliente,
            'expire_at' => $vencimento,
            'instructions' => $instrucoes,
            'metadata' => [
                'notification_url' => SITE_URL . '/webhook_efibank.php'
            ]
        ];

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
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_SSLCERT => $this->certificate,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ],
        ];

        curl_setopt_array($curl, $config);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #: " . $err);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Consultar status de um boleto
     */
    public function consultarBoleto($charge_id) {
        if (!$this->token) {
            $this->authenticate();
        }

        $url = $this->baseUrl . '/v1/charge/' . $charge_id;
        
        $curl = curl_init();
        
        $config = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSLCERT => $this->certificate,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ],
        ];

        curl_setopt_array($curl, $config);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #: " . $err);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Cancelar cobrança PIX
     */
    public function cancelarPix($txid) {
        if (!$this->token) {
            $this->authenticate();
        }

        $url = $this->baseUrl . '/v2/cob/' . $txid;
        
        $curl = curl_init();
        
        $config = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_SSLCERT => $this->certificate,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ],
        ];

        curl_setopt_array($curl, $config);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new Exception("cURL Error #: " . $err);
        }
        
        return json_decode($response, true);
    }
}
