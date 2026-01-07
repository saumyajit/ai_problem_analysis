<?php declare(strict_types=1);

namespace Modules\AIProblemAnalysis\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use Modules\AIProblemAnalysis\Services\AIAnalyzer;

class Analyze extends CController {

    /**
     * Check user permissions
     */
    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    /**
     * Check input parameters
     */
    protected function checkInput(): bool {
        $fields = [
            'event_data' => 'required|json'
        ];

        $ret = $this->validateInput($fields);

        if (!$ret) {
            $this->setResponse(
                new CControllerResponseFatal()
            );
        }

        return $ret;
    }

    /**
     * Main controller logic - perform AI analysis
     */
    protected function doAction(): void {
        $eventData = json_decode($this->getInput('event_data'), true);
        
        if (!$eventData) {
            $this->setResponse(new CControllerResponseData([
                'success' => false,
                'error' => 'Invalid event data'
            ]));
            return;
        }

        try {
            $analyzer = new AIAnalyzer($this->getModule()->getConfig());
            $analysis = $analyzer->analyze($eventData);

            $this->setResponse(new CControllerResponseData([
                'success' => true,
                'analysis' => $analysis,
                'backend_used' => $analyzer->getBackendUsed(),
                'timestamp' => time()
            ]));

        } catch (\Exception $e) {
            error_log('AI Analysis Error: ' . $e->getMessage());
            
            $this->setResponse(new CControllerResponseData([
                'success' => false,
                'error' => 'Analysis failed: ' . $e->getMessage()
            ]));
        }
    }

    /**
     * Get module instance
     */
    private function getModule() {
        return \APP::ModuleManager()->getModule('ai-problem-analysis');
    }
}
