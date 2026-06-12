<?php
/**
 * Response Time Service
 *
 * @package    BusinessHours\Services
 */

namespace BusinessHours\Services;

use BusinessHours\Bootstrap;
use BusinessHours\Models\ResponseTime;
use BusinessHours\Repositories\SettingsRepository;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class ResponseTimeService
{
    /** @var string */
    private $table;

    /** @var SettingsRepository */
    private $settingsRepo;

    public function __construct()
    {
        $tables = Bootstrap::getTableNames();
        $this->table = $tables['response_times'];
        $this->settingsRepo = new SettingsRepository();
    }

    /**
     * Get the appropriate response time message for the current context
     *
     * @param int|null $departmentId
     * @return array|null
     */
    public function getMessage($departmentId = null)
    {
        $availService = new AvailabilityService();
        $status = $availService->getCurrentStatus($departmentId);

        // Determine the context
        $context = $this->resolveContext($status);

        // Find the matching response time entry
        $query = Capsule::table($this->table)
            ->where('context', $context)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'asc');

        if ($departmentId !== null) {
            // Department-specific or global
            $query->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                  ->orWhereNull('department_id');
            });
        } else {
            $query->whereNull('department_id');
        }

        $row = $query->first();

        if (!$row) {
            return null;
        }

        $rt = ResponseTime::fromRow($row);

        return [
            'message'           => $rt->message,
            'estimated_minutes' => $rt->estimatedMinutes,
            'formatted_time'    => $rt->getFormattedEstimate(),
            'context'           => $rt->context,
        ];
    }

    /**
     * Get the estimated response time in minutes
     *
     * @param int|null $departmentId
     * @return int|null
     */
    public function getEstimatedMinutes($departmentId = null)
    {
        $msg = $this->getMessage($departmentId);
        return $msg ? $msg['estimated_minutes'] : null;
    }

    /**
     * Get all response time entries
     *
     * @return ResponseTime[]
     */
    public function getAll()
    {
        $results = Capsule::table($this->table)
            ->orderBy('sort_order', 'asc')
            ->get();

        $items = [];
        foreach ($results as $row) {
            $items[] = ResponseTime::fromRow($row);
        }
        return $items;
    }

    /**
     * Save a response time entry
     *
     * @param ResponseTime $rt
     * @return int
     */
    public function save(ResponseTime $rt)
    {
        if ($rt->id) {
            Capsule::table($this->table)
                ->where('id', $rt->id)
                ->update($rt->toArray());
            return $rt->id;
        }

        return Capsule::table($this->table)->insertGetId($rt->toArray());
    }

    /**
     * Delete a response time entry
     *
     * @param int $id
     * @return int
     */
    public function delete($id)
    {
        return Capsule::table($this->table)
            ->where('id', (int) $id)
            ->delete();
    }

    /**
     * Resolve the context from the current status
     *
     * @param array $status
     * @return string
     */
    private function resolveContext($status)
    {
        if (isset($status['source'])) {
            if ($status['source'] === 'holiday') {
                return 'holiday';
            }
        }

        if ($status['is_open']) {
            return 'business_hours';
        }

        return 'after_hours';
    }
}
