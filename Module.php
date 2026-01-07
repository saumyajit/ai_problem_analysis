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
        // Only show for Zabbix users+ (adjust as needed)
        if (CWebUser::getType() < USER_TYPE_ZABBIX_USER) {
            return;
        }

        // Add to Problems submenu (no menu entry needed for popup module)
        APP::Component()->get('menu.main')
            ->findOrAdd(_('Monitoring'))
            ->getSubmenu()
            ->find(_('Problems'))
            ->setAttribute('actions', ['ai.analysis.popup']);
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
