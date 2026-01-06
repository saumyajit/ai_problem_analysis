<?php declare(strict_types=1);

namespace Modules\AIProblemAnalysis\Services;

class AIAnalyzer {
    
    private array $config;
    private string $backendUsed;
    private array $backends;

    public function __construct(array $config) {
        $this->config = $config;
        $this->backendUsed = '';
        
        // Initialize available backends
        $this->backends = [
            'claude' => new Backends\ClaudeBackend($config),
            'openai' => new Backends\OpenAIBackend($config),
            'custom' => new Backends\CustomBackend($config)
        ];
    }

    /**
     * Analyze event data using AI
     */
    public function analyze(array $eventData): array {
        $prompt = $this->buildPrompt($eventData);
        
        // Try primary backend
        $primaryBackend = $this->config['ai_backend'] ?? 'claude';
        
        if (isset($this->backends[$primaryBackend]) && $this->backends[$primaryBackend]->isConfigured()) {
            try {
                $result = $this->backends[$primaryBackend]->analyze($prompt);
                $this->backendUsed = $primaryBackend;
                return $this->parseAnalysis($result);
            } catch (\Exception $e) {
                error_log("Primary backend ($primaryBackend) failed: " . $e->getMessage());
            }
        }

        // Fallback to other configured backends
        foreach ($this->backends as $name => $backend) {
            if ($name === $primaryBackend) {
                continue;
            }
            
            if ($backend->isConfigured()) {
                try {
                    $result = $backend->analyze($prompt);
                    $this->backendUsed = $name;
                    return $this->parseAnalysis($result);
                } catch (\Exception $e) {
                    error_log("Fallback backend ($name) failed: " . $e->getMessage());
                }
            }
        }

        throw new \Exception('No AI backend available or all backends failed');
    }

    /**
     * Build comprehensive prompt for AI analysis
     */
    private function buildPrompt(array $eventData): string {
        $event = $eventData['event'] ?? [];
        $trigger = $eventData['trigger'] ?? null;
        $itemHistory = $eventData['item_history'] ?? [];
        $relatedEvents = $eventData['related_events'] ?? [];
        $hostInventory = $eventData['host_inventory'] ?? [];

        $prompt = "You are an expert system administrator analyzing a Zabbix monitoring alert. Provide a comprehensive analysis.\n\n";
        
        // Event information
        $prompt .= "## PROBLEM DETAILS\n";
        $prompt .= "Problem: " . ($event['name'] ?? 'Unknown') . "\n";
        $prompt .= "Severity: " . $this->getSeverityName($trigger['priority'] ?? 0) . "\n";
        $prompt .= "Time: " . date('Y-m-d H:i:s', $event['clock'] ?? time()) . "\n";
        
        if (!empty($event['hosts'])) {
            $host = $event['hosts'][0];
            $prompt .= "Host: " . ($host['name'] ?? $host['host']) . "\n";
        }

        // Trigger details
        if ($trigger) {
            $prompt .= "\n## TRIGGER INFORMATION\n";
            $prompt .= "Description: " . ($trigger['description'] ?? '') . "\n";
            $prompt .= "Expression: " . ($trigger['expression'] ?? '') . "\n";
            if (!empty($trigger['comments'])) {
                $prompt .= "Comments: " . $trigger['comments'] . "\n";
            }
        }

        // Item history
        if (!empty($itemHistory)) {
            $prompt .= "\n## MONITORED METRICS (Last " . $this->config['include_history_hours'] . " hours)\n";
            foreach ($itemHistory as $itemid => $historyData) {
                $item = $historyData['item'];
                $data = $historyData['data'];
                
                $prompt .= "\nMetric: " . $item['name'] . " (Key: " . $item['key_'] . ")\n";
                
                if (!empty($data)) {
                    $values = array_slice($data, 0, 10); // Last 10 values
                    $prompt .= "Recent values:\n";
                    foreach ($values as $value) {
                        $timestamp = date('H:i:s', $value['clock']);
                        $val = $value['value'] ?? '';
                        if (!empty($item['units'])) {
                            $val .= ' ' . $item['units'];
                        }
                        $prompt .= "  [$timestamp] $val\n";
                    }
                    
                    // Calculate statistics
                    $numericValues = array_filter(array_column($data, 'value'), 'is_numeric');
                    if (!empty($numericValues)) {
                        $prompt .= "  Stats: Min=" . min($numericValues) . ", Max=" . max($numericValues);
                        $prompt .= ", Avg=" . round(array_sum($numericValues) / count($numericValues), 2) . "\n";
                    }
                }
            }
        }

        // Related events
        if (!empty($relatedEvents)) {
            $prompt .= "\n## RELATED EVENTS (Recent context)\n";
            $recentEvents = array_slice($relatedEvents, 0, 10);
            foreach ($recentEvents as $relEvent) {
                $time = date('H:i:s', $relEvent['clock']);
                $name = $relEvent['name'] ?? 'Unknown';
                $prompt .= "[$time] $name\n";
            }
        }

        // Host inventory
        if (!empty($hostInventory)) {
            $prompt .= "\n## HOST INFORMATION\n";
            $host = $hostInventory[0];
            
            if (!empty($host['groups'])) {
                $groups = array_column($host['groups'], 'name');
                $prompt .= "Groups: " . implode(', ', $groups) . "\n";
            }
            
            if (!empty($host['interfaces'])) {
                $interface = $host['interfaces'][0];
                $prompt .= "Interface: " . ($interface['ip'] ?? $interface['dns'] ?? 'N/A') . "\n";
            }
            
            if (!empty($host['inventory'])) {
                $inv = $host['inventory'];
                if (!empty($inv['os'])) $prompt .= "OS: " . $inv['os'] . "\n";
                if (!empty($inv['type'])) $prompt .= "Type: " . $inv['type'] . "\n";
                if (!empty($inv['location'])) $prompt .= "Location: " . $inv['location'] . "\n";
            }
        }

        $prompt .= "\n## REQUIRED ANALYSIS\n";
        $prompt .= "Please provide:\n";
        $prompt .= "1. ROOT CAUSE ANALYSIS: What is most likely causing this problem?\n";
        $prompt .= "2. IMMEDIATE ACTIONS: What should be checked right now?\n";
        $prompt .= "3. RESOLUTION STEPS: Step-by-step guide to fix this issue\n";
        $prompt .= "4. PREVENTIVE MEASURES: How to prevent this from happening again?\n";
        $prompt .= "5. SEVERITY ASSESSMENT: Is this critical? What's the business impact?\n\n";
        $prompt .= "Format your response clearly with headers and bullet points for easy reading.";

        return $prompt;
    }

    /**
     * Parse and structure AI response
     */
    private function parseAnalysis(string $response): array {
        return [
            'raw_response' => $response,
            'formatted_response' => $this->formatResponse($response),
            'timestamp' => time()
        ];
    }

    /**
     * Format the response for better display
     */
    private function formatResponse(string $response): string {
        // Convert markdown-style headers to HTML
        $response = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $response);
        $response = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $response);
        
        // Convert bullet points
        $response = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $response);
        $response = preg_replace('/^- (.+)$/m', '<li>$1</li>', $response);
        
        // Wrap consecutive list items in ul tags
        $response = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $response);
        
        // Convert numbered lists
        $response = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $response);
        
        // Convert line breaks to <br>
        $response = nl2br($response);
        
        return $response;
    }

    /**
     * Get severity name from priority value
     */
    private function getSeverityName(int $priority): string {
        $severities = [
            0 => 'Not classified',
            1 => 'Information',
            2 => 'Warning',
            3 => 'Average',
            4 => 'High',
            5 => 'Disaster'
        ];
        
        return $severities[$priority] ?? 'Unknown';
    }

    /**
     * Get the backend that was used for analysis
     */
    public function getBackendUsed(): string {
        return $this->backendUsed;
    }
}
