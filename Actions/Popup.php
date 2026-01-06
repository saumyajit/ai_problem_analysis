<?php declare(strict_types=1);

namespace Modules\AIProblemAnalysis\Actions;

use CController;
use CControllerResponseData;
use CControllerResponseFatal;
use API;
use CWebUser;

class Popup extends CController {

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
            'eventid' => 'required|db events.eventid'
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
     * Main controller logic
     */
    protected function doAction(): void {
        $eventid = $this->getInput('eventid');
        
        // Gather comprehensive event data
        $eventData = $this->gatherEventData($eventid);
        
        if (!$eventData) {
            $this->setResponse(new CControllerResponseData([
                'error' => 'Failed to retrieve event data'
            ]));
            return;
        }

        $response = new CControllerResponseData([
            'title' => 'AI Problem Analysis',
            'eventid' => $eventid,
            'event_data' => $eventData,
            'user' => [
                'debug_mode' => $this->getDebugMode()
            ]
        ]);

        $this->setResponse($response);
    }

    /**
     * Gather comprehensive event data for AI analysis
     */
    private function gatherEventData(string $eventid): ?array {
        try {
            // Get event details
            $events = API::Event()->get([
                'output' => 'extend',
                'eventids' => $eventid,
                'selectHosts' => ['hostid', 'host', 'name', 'status'],
                'selectRelatedObject' => 'extend',
                'select_acknowledges' => 'extend'
            ]);

            if (empty($events)) {
                return null;
            }

            $event = $events[0];
            $triggerid = $event['objectid'];

            // Get trigger details
            $triggers = API::Trigger()->get([
                'output' => 'extend',
                'triggerids' => $triggerid,
                'selectFunctions' => 'extend',
                'selectItems' => ['itemid', 'name', 'key_', 'value_type', 'units', 'hostid'],
                'selectHosts' => ['hostid', 'host', 'name'],
                'expandExpression' => true
            ]);

            $trigger = !empty($triggers) ? $triggers[0] : null;

            // Get item history
            $itemHistory = [];
            if ($trigger && !empty($trigger['items'])) {
                $itemHistory = $this->getItemHistory($trigger['items'], $event['clock']);
            }

            // Get related events (within time window)
            $relatedEvents = $this->getRelatedEvents($event['hosts'], $event['clock']);

            // Get host inventory
            $hostInventory = $this->getHostInventory($event['hosts']);

            return [
                'event' => $event,
                'trigger' => $trigger,
                'item_history' => $itemHistory,
                'related_events' => $relatedEvents,
                'host_inventory' => $hostInventory
            ];

        } catch (\Exception $e) {
            error_log('Error gathering event data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get item history data
     */
    private function getItemHistory(array $items, int $eventTime): array {
        $history = [];
        $config = $this->getModule()->getConfig();
        $timeFrom = $eventTime - ($config['include_history_hours'] * 3600);

        foreach ($items as $item) {
            try {
                $historyData = API::History()->get([
                    'output' => 'extend',
                    'itemids' => $item['itemid'],
                    'time_from' => $timeFrom,
                    'time_till' => $eventTime,
                    'sortfield' => 'clock',
                    'sortorder' => 'DESC',
                    'limit' => 100
                ]);

                $history[$item['itemid']] = [
                    'item' => $item,
                    'data' => $historyData
                ];
            } catch (\Exception $e) {
                error_log('Error fetching history for item ' . $item['itemid'] . ': ' . $e->getMessage());
            }
        }

        return $history;
    }

    /**
     * Get related events on the same host
     */
    private function getRelatedEvents(array $hosts, int $eventTime): array {
        if (empty($hosts)) {
            return [];
        }

        $hostids = array_column($hosts, 'hostid');
        $timeFrom = $eventTime - (3600 * 2); // 2 hours before

        try {
            return API::Event()->get([
                'output' => 'extend',
                'hostids' => $hostids,
                'time_from' => $timeFrom,
                'time_till' => $eventTime + 3600,
                'sortfield' => 'clock',
                'sortorder' => 'DESC',
                'limit' => 50,
                'selectRelatedObject' => ['description', 'priority']
            ]);
        } catch (\Exception $e) {
            error_log('Error fetching related events: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get host inventory information
     */
    private function getHostInventory(array $hosts): array {
        if (empty($hosts)) {
            return [];
        }

        $hostids = array_column($hosts, 'hostid');

        try {
            return API::Host()->get([
                'output' => ['hostid', 'host', 'name'],
                'hostids' => $hostids,
                'selectInventory' => 'extend',
                'selectGroups' => ['groupid', 'name'],
                'selectInterfaces' => ['interfaceid', 'ip', 'dns', 'port', 'type']
            ]);
        } catch (\Exception $e) {
            error_log('Error fetching host inventory: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get module instance
     */
    private function getModule() {
        return APP::ModuleManager()->getModule('ai-problem-analysis');
    }
}
