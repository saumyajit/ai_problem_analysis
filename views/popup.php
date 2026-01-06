<?php
/**
 * AI Problem Analysis Popup View
 */

$output = [
    'header' => $data['title'],
    'body' => (new CDiv())
        ->addClass('ai-analysis-popup')
        ->addItem([
            (new CDiv())
                ->addClass('ai-analysis-loading')
                ->addItem([
                    (new CDiv())->addClass('loader'),
                    (new CDiv('Analyzing problem...'))->addClass('loading-text')
                ])
                ->addStyle('text-align: center; padding: 40px;'),
            
            (new CDiv())
                ->addClass('ai-analysis-content')
                ->addStyle('display: none;'),
            
            (new CDiv())
                ->addClass('ai-analysis-error')
                ->addStyle('display: none; color: red; padding: 20px;')
        ]),
    'buttons' => [
        [
            'title' => _('Close'),
            'class' => 'btn-alt',
            'action' => 'popup.close();'
        ]
    ],
    'script_inline' => '
        (function() {
            const eventData = ' . json_encode($data['event_data']) . ';
            const eventId = ' . json_encode($data['eventid']) . ';
            
            // Perform AI analysis
            fetch("' . (new CUrl('zabbix.php'))
                ->setArgument('action', 'ai.analysis.analyze')
                ->getUrl() . '", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "event_data=" + encodeURIComponent(JSON.stringify(eventData))
            })
            .then(response => response.json())
            .then(data => {
                const loadingEl = document.querySelector(".ai-analysis-loading");
                const contentEl = document.querySelector(".ai-analysis-content");
                const errorEl = document.querySelector(".ai-analysis-error");
                
                loadingEl.style.display = "none";
                
                if (data.success && data.analysis) {
                    contentEl.innerHTML = `
                        <div class="ai-analysis-header">
                            <div class="analysis-meta">
                                <span class="backend-badge">Backend: ${data.backend_used}</span>
                                <span class="timestamp">${new Date(data.timestamp * 1000).toLocaleString()}</span>
                            </div>
                        </div>
                        <div class="ai-analysis-body">
                            ${data.analysis.formatted_response}
                        </div>
                    `;
                    contentEl.style.display = "block";
                } else {
                    errorEl.textContent = data.error || "Analysis failed";
                    errorEl.style.display = "block";
                }
            })
            .catch(error => {
                const loadingEl = document.querySelector(".ai-analysis-loading");
                const errorEl = document.querySelector(".ai-analysis-error");
                
                loadingEl.style.display = "none";
                errorEl.textContent = "Error: " + error.message;
                errorEl.style.display = "block";
            });
        })();
    '
];

echo json_encode($output);
?>
