/**
 * AI Problem Analysis Module - Frontend Integration
 * Adds "AI Analysis" button to problem rows
 */

(function() {
    'use strict';
    
    /**
     * Initialize AI Analysis buttons on problem page
     */
    function initAIAnalysisButtons() {
        // Wait for problems table to load
        const problemsTable = document.querySelector('[data-tableid="problems"]');
        if (!problemsTable) {
            return;
        }
        
        // Add AI Analysis button to each problem row
        const problemRows = problemsTable.querySelectorAll('tbody tr');
        
        problemRows.forEach(row => {
            // Skip if button already added
            if (row.querySelector('.ai-analysis-btn')) {
                return;
            }
            
            // Get event ID from row
            const eventId = getEventIdFromRow(row);
            if (!eventId) {
                return;
            }
            
            // Create AI Analysis button
            const aiButton = createAIButton(eventId);
            
            // Insert button in actions column
            const actionsCell = row.querySelector('.list-table-actions');
            if (actionsCell) {
                actionsCell.insertBefore(aiButton, actionsCell.firstChild);
            }
        });
    }
    
    /**
     * Extract event ID from table row
     */
    function getEventIdFromRow(row) {
        // Try to find event ID in row data attributes
        if (row.dataset.eventid) {
            return row.dataset.eventid;
        }
        
        // Try to extract from checkbox
        const checkbox = row.querySelector('input[name="eventids[]"]');
        if (checkbox) {
            return checkbox.value;
        }
        
        // Try to extract from acknowledge link
        const ackLink = row.querySelector('a[href*="acknowledge"]');
        if (ackLink) {
            const match = ackLink.href.match(/eventids\[\]=(\d+)/);
            if (match) {
                return match[1];
            }
        }
        
        return null;
    }
    
    /**
     * Create AI Analysis button
     */
    function createAIButton(eventId) {
        const button = document.createElement('button');
        button.className = 'btn-icon zi-ai ai-analysis-btn';
        button.title = 'AI Analysis';
        button.type = 'button';
        button.innerHTML = '🤖'; // Robot emoji as icon
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openAIAnalysisPopup(eventId);
        });
        
        return button;
    }
    
    /**
     * Open AI Analysis popup
     */
    function openAIAnalysisPopup(eventId) {
        const popupUrl = new URL('zabbix.php', window.location.origin);
        popupUrl.searchParams.set('action', 'ai.analysis.popup');
        popupUrl.searchParams.set('eventid', eventId);
        
        PopUp('ai-analysis-popup-' + eventId, {
            url: popupUrl.toString(),
            dialogueid: 'ai-analysis',
            width: 800,
            height: 600
        });
    }
    
    /**
     * Initialize on page load and after AJAX updates
     */
    function init() {
        // Initial load
        initAIAnalysisButtons();
        
        // Re-initialize after table updates (Zabbix uses MutationObserver internally)
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    initAIAnalysisButtons();
                }
            });
        });
        
        // Observe problems table for changes
        const problemsTable = document.querySelector('[data-tableid="problems"]');
        if (problemsTable) {
            observer.observe(problemsTable, {
                childList: true,
                subtree: true
            });
        }
        
        // Also listen for Zabbix's custom events
        document.addEventListener('zbx_events_update', function() {
            setTimeout(initAIAnalysisButtons, 100);
        });
    }
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
