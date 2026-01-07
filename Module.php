<?php declare(strict_types=1);

namespace Modules\AIProblemAnalysis;

use APP;
use CController;
use CControllerResponseData;
use CControllerResponseFatal;

use Zabbix\Core\CModule;

class Module extends CModule {

    /**
     * Initialize module
     */
    public function init(): void {
        APP::Component()->get('menu.main')
            ->findOrAdd(_('Monitoring'))
            ->getSubmenu()
            ->find(_('Problems'))
            ->setAction('problem.view');
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

    /**
     * Handle popup action
     */
    public static function popup(): CController {
        return new Actions\Popup();
    }
}
