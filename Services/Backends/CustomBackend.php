<?php declare(strict_types=1);

namespace Modules\AIProblemAnalysis\Services\Backends;

class CustomBackend implements AIBackendInterface {
    
    private array $config;
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function isConfigured(): bool {
        return !empty($this->config['custom_api_url']) && 
               !empty($this->config['custom_api_key']);
    }
    
    public function analyze(string $prompt): string {
        $apiUrl = $this->config['custom_api_url'];
        $apiKey = $this->config['custom_api_key'];
        $timeout = $this->config['analysis_timeout'] ?? 30;
        
        // Generic format - can be adjusted based on your API
        $data = [
            'prompt' => $prompt,
            'max_tokens' => 4096
        ];
        
        $ch = curl_init($apiUrl);
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
            throw new \Exception("Custom API request failed: $error");
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("Custom API returned HTTP $httpCode");
        }
        
        $result = json_decode($response, true);
        
        // Try to extract response from common formats
        if (isset($result['response'])) {
            return $result['response'];
        } elseif (isset($result['text'])) {
            return $result['text'];
        } elseif (isset($result['content'])) {
            return $result['content'];
        } elseif (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }
        
        throw new \Exception('Could not extract response from custom API result');
    }
    
    public function getName(): string {
        return 'Custom AI Backend';
    }
}
