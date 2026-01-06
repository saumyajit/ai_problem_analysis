<?php declare(strict_types=1);

namespace Modules\AIProblemAnalysis\Services\Backends;

interface AIBackendInterface {
    
    /**
     * Check if backend is properly configured
     */
    public function isConfigured(): bool;
    
    /**
     * Perform AI analysis
     * 
     * @param string $prompt The analysis prompt
     * @return string The AI response
     * @throws \Exception on failure
     */
    public function analyze(string $prompt): string;
    
    /**
     * Get backend name
     */
    public function getName(): string;
}
