<?php declare(strict_types=1);

namespace Modules\AIProblemAnalysis\Services\Backends;

class ClaudeBackend implements AIBackendInterface {
    
    private array $config;
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function isConfigured(): bool {
        return !empty($this->config['anthropic_api_key']);
    }
    
    public function analyze(string $prompt): string {
        $apiKey = $this->config['anthropic_api_key'];
        $model = $this->config['claude_model'] ?? 'claude-sonnet-4-20250514';
        $timeout = $this->config['analysis_timeout'] ?? 30;
        
        $data = [
            'model' => $model,
            'max_tokens' => 4096,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];
        
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("Claude API request failed: $error");
        }
        
        if ($httpCode !== 200) {
            $errorMsg = "Claude API returned HTTP $httpCode";
            if ($response) {
                $decoded = json_decode($response, true);
                if (isset($decoded['error']['message'])) {
                    $errorMsg .= ': ' . $decoded['error']['message'];
                }
            }
            throw new \Exception($errorMsg);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['content'][0]['text'])) {
            throw new \Exception('Invalid response format from Claude API');
        }
        
        return $result['content'][0]['text'];
    }
    
    public function getName(): string {
        return 'Claude (Anthropic)';
    }
}
