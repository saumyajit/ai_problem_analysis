<?php declare(strict_types=1);

namespace Modules\AIProblemAnalysis\Services\Backends;

class OpenAIBackend implements AIBackendInterface {
    
    private array $config;
    private const API_URL = 'https://api.openai.com/v1/chat/completions';
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function isConfigured(): bool {
        return !empty($this->config['openai_api_key']);
    }
    
    public function analyze(string $prompt): string {
        $apiKey = $this->config['openai_api_key'];
        $model = $this->config['openai_model'] ?? 'gpt-4';
        $timeout = $this->config['analysis_timeout'] ?? 30;
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert system administrator analyzing Zabbix monitoring alerts. Provide clear, actionable analysis.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 4096,
            'temperature' => 0.7
        ];
        
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("OpenAI API request failed: $error");
        }
        
        if ($httpCode !== 200) {
            $errorMsg = "OpenAI API returned HTTP $httpCode";
            if ($response) {
                $decoded = json_decode($response, true);
                if (isset($decoded['error']['message'])) {
                    $errorMsg .= ': ' . $decoded['error']['message'];
                }
            }
            throw new \Exception($errorMsg);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid response format from OpenAI API');
        }
        
        return $result['choices'][0]['message']['content'];
    }
    
    public function getName(): string {
        return 'OpenAI GPT';
    }
}
