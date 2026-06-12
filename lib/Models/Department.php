<?php
/**
 * Department Model
 *
 * @package    BusinessHours\Models
 */

namespace BusinessHours\Models;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

class Department
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $slug;

    /** @var string|null */
    public $description;

    /** @var string */
    public $timezone;

    /** @var bool */
    public $is24x7;

    /** @var int */
    public $sortOrder;

    /** @var string active|disabled|archived */
    public $status;

    /** @var string|null Hex color */
    public $color;

    /** @var string|null FontAwesome icon class */
    public $icon;

    /** @var string */
    public $createdAt;

    /** @var string */
    public $updatedAt;

    /**
     * Create a Department instance from a database row
     *
     * @param object|array $data
     * @return self
     */
    public static function fromRow($data)
    {
        $data = (object) $data;
        $dept = new self();

        $dept->id          = (int) $data->id;
        $dept->name        = (string) $data->name;
        $dept->slug        = (string) $data->slug;
        $dept->description = isset($data->description) ? $data->description : null;
        $dept->timezone    = (string) $data->timezone;
        $dept->is24x7      = (bool) $data->is_24x7;
        $dept->sortOrder   = (int) $data->sort_order;
        $dept->status      = (string) $data->status;
        $dept->color       = isset($data->color) ? $data->color : null;
        $dept->icon        = isset($data->icon) ? $data->icon : null;
        $dept->createdAt   = isset($data->created_at) ? (string) $data->created_at : '';
        $dept->updatedAt   = isset($data->updated_at) ? (string) $data->updated_at : '';

        return $dept;
    }

    /**
     * Convert to array for database insertion
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'timezone'    => $this->timezone,
            'is_24x7'     => $this->is24x7 ? 1 : 0,
            'sort_order'  => $this->sortOrder,
            'status'      => $this->status,
            'color'       => $this->color,
            'icon'        => $this->icon,
        ];
    }

    /**
     * Convert to JSON-serializable array
     *
     * @return array
     */
    public function toJson()
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'timezone'    => $this->timezone,
            'is_24x7'     => $this->is24x7,
            'sort_order'  => $this->sortOrder,
            'status'      => $this->status,
            'color'       => $this->color,
            'icon'        => $this->icon,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }

    /**
     * Check if the department is active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Generate a slug from a name
     *
     * @param string $name
     * @return string
     */
    public static function generateSlug($name)
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
}
