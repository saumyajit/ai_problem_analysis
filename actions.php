<?php declare(strict_types=1);

/**
 * AI Problem Analysis Module - Actions Configuration
 * 
 * This file registers the module's actions with Zabbix
 */

return [
    // Popup action - displays the analysis modal
    'ai.analysis.popup' => [
        'class' => \Modules\AIProblemAnalysis\Actions\Popup::class,
        'view' => 'popup',
        'layout' => 'layout.json'
    ],
    
    // Analyze action - performs the actual AI analysis
    'ai.analysis.analyze' => [
        'class' => \Modules\AIProblemAnalysis\Actions\Analyze::class,
        'layout' => 'layout.json'
    ]
];
