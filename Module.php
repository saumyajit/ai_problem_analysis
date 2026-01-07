<?php
declare(strict_types = 1);

namespace Modules\AIProblemAnalysis;

use APP;
use CController as CAction;
use CWebUser;
use CMenuItem;

// Smart CModule detection like your working module
if (!class_exists('Zabbix\Core\CModule') && class_exists('Core\CModule')) {
    class_alias('Core\CModule', 'Zabbix\Core\CModule');
}

use Zabbix\Core\CModule;

/**
 * AI Problem Analysis Module
 */
class Module extends CModule {

    /**
     * Initialize module.
     */
	public function init(): void {
		if (CWebUser::getType() < USER_TYPE_ZABBIX_USER) {
			return;
		}
	
		// CRITICAL: Load assets ONLY on problem.view pages
		$action = $_REQUEST['action'] ?? '';
		if (strpos($action, 'problem.view') !== false || strpos($action, 'problem.list') !== false) {
			// Register JS/CSS with correct module-relative paths
			$module_path = APP::ModuleManager()->getModule('ai-problem-analysis')->getPath();
			APP::Component()->get('page.header')
				->addCssFile($module_path . '/assets/css/ai-analysis.css')
				->addJsFile($module_path . '/assets/js/ai-analysis.js');
		}
	}

    /**
     * Get default module configuration
     */
    public function getConfig(): array {
        return [
            'ai_backend' => 'claude',
            'anthropic_api_key' => '',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4',
            'claude_model' => 'claude-sonnet-4-20250514',
            'custom_api_url' => '',
            'custom_api_key' => '',
            'analysis_timeout' => 30,
            'include_history_hours' => 24,
            'cache_duration' => 300
        ];
    }

    public function onBeforeAction(CAction $action): void {}
    public function onTerminate(CAction $action): void {}
}
