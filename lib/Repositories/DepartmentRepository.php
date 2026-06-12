<?php
/**
 * Department Repository
 *
 * @package    BusinessHours\Repositories
 */

namespace BusinessHours\Repositories;

use BusinessHours\Bootstrap;
use BusinessHours\Models\Department;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class DepartmentRepository
{
    /** @var string */
    private $table;

    public function __construct()
    {
        $tables = Bootstrap::getTableNames();
        $this->table = $tables['departments'];
    }

    /**
     * Get all departments
     *
     * @param bool $activeOnly Only return active departments
     * @return Department[]
     */
    public function getAll($activeOnly = false)
    {
        $query = Capsule::table($this->table)->orderBy('sort_order', 'asc');

        if ($activeOnly) {
            $query->where('status', 'active');
        }

        $results = $query->get();
        $departments = [];

        foreach ($results as $row) {
            $departments[] = Department::fromRow($row);
        }

        return $departments;
    }

    /**
     * Get a department by ID
     *
     * @param int $id
     * @return Department|null
     */
    public function getById($id)
    {
        $row = Capsule::table($this->table)->where('id', (int) $id)->first();
        return $row ? Department::fromRow($row) : null;
    }

    /**
     * Get a department by slug
     *
     * @param string $slug
     * @return Department|null
     */
    public function getBySlug($slug)
    {
        $row = Capsule::table($this->table)->where('slug', $slug)->first();
        return $row ? Department::fromRow($row) : null;
    }

    /**
     * Create a new department
     *
     * @param Department $department
     * @return int Inserted ID
     */
    public function create(Department $department)
    {
        $data = $department->toArray();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return Capsule::table($this->table)->insertGetId($data);
    }

    /**
     * Update an existing department
     *
     * @param int $id
     * @param array $data
     * @return int Number of rows affected
     */
    public function update($id, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return Capsule::table($this->table)
            ->where('id', (int) $id)
            ->update($data);
    }

    /**
     * Delete a department
     *
     * @param int $id
     * @return int Number of rows affected
     */
    public function delete($id)
    {
        return Capsule::table($this->table)
            ->where('id', (int) $id)
            ->delete();
    }

    /**
     * Check if a slug already exists
     *
     * @param string $slug
     * @param int|null $excludeId Exclude this ID from the check
     * @return bool
     */
    public function slugExists($slug, $excludeId = null)
    {
        $query = Capsule::table($this->table)->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', (int) $excludeId);
        }

        return $query->exists();
    }

    /**
     * Update the sort order for a department
     *
     * @param int $id
     * @param int $sortOrder
     * @return void
     */
    public function updateSortOrder($id, $sortOrder)
    {
        Capsule::table($this->table)
            ->where('id', (int) $id)
            ->update(['sort_order' => (int) $sortOrder, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Update the status of a department
     *
     * @param int $id
     * @param string $status
     * @return void
     */
    public function updateStatus($id, $status)
    {
        $allowed = ['active', 'disabled', 'archived'];
        if (!in_array($status, $allowed)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        Capsule::table($this->table)
            ->where('id', (int) $id)
            ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Get the count of active departments
     *
     * @return int
     */
    public function getActiveCount()
    {
        return Capsule::table($this->table)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Synchronize departments from WHMCS core tblticketdepartments
     *
     * @return int Number of synchronized departments
     */
    public function syncWhmcsDepartments()
    {
        // Safely add whmcs_dept_id column if it doesn't exist
        $columns = Capsule::schema()->getColumnListing($this->table);
        if (!in_array('whmcs_dept_id', $columns)) {
            Capsule::schema()->table($this->table, function ($table) {
                $table->unsignedInteger('whmcs_dept_id')->nullable()->after('id')->index();
            });
        }

        // Get WHMCS departments
        $whmcsDepts = [];
        try {
            if (Capsule::schema()->hasTable('tblticketdepartments')) {
                $whmcsDepts = Capsule::table('tblticketdepartments')->get();
            }
        } catch (\Exception $e) {
            return 0; // Ignore if table doesn't exist or other error
        }

        $synced = 0;
        $order = $this->getActiveCount() + 1;

        foreach ($whmcsDepts as $wDept) {
            $exists = Capsule::table($this->table)->where('whmcs_dept_id', $wDept->id)->first();
            
            if ($exists) {
                // Update name
                Capsule::table($this->table)
                    ->where('id', $exists->id)
                    ->update([
                        'name' => $wDept->name,
                        'description' => $wDept->description,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
            } else {
                // Insert new mapped department
                $slug = Department::generateSlug($wDept->name);
                
                // Ensure unique slug
                $originalSlug = $slug;
                $counter = 1;
                while ($this->slugExists($slug)) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                Capsule::table($this->table)->insert([
                    'whmcs_dept_id' => $wDept->id,
                    'name' => $wDept->name,
                    'slug' => $slug,
                    'description' => $wDept->description,
                    'timezone' => date_default_timezone_get() ?: 'America/New_York',
                    'is_24x7' => 0,
                    'sort_order' => $order++,
                    'status' => 'active',
                    'color' => '#3b82f6',
                    'icon' => 'fa-ticket-alt',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            $synced++;
        }

        return $synced;
    }
}
