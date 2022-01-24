<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Settings model.
 *
 * Handles all the database operations of the setting resource.
 *
 * @package Models
 */
class Settings_model extends EA_Model {
    /**
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    /**
     * @var array 
     */
    protected $api_resource = [
        'name' => 'name',  
        'value' => 'value',  
    ];

    /**
     * Save (insert or update) a setting.
     *
     * @param array $setting Associative array with the setting data.
     *
     * @return int Returns the setting ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $setting): int
    {
        $this->validate($setting);

        if (empty($setting['id']))
        {
            return $this->insert($setting);
        }
        else
        {
            return $this->update($setting);
        }
    }

    /**
     * Validate the setting data.
     *
     * @param array $setting Associative array with the setting data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $setting)
    {
        // If a setting ID is provided then check whether the record really exists in the database.
        if ( ! empty($setting['id']))
        {
            $count = $this->db->get_where('settings', ['id' => $setting['id']])->num_rows();

            if ( ! $count)
            {
                throw new InvalidArgumentException('The provided setting ID does not exist in the database: ' . $setting['id']);
            }
        }

        // Make sure all required fields are provided.
        if (
            empty($setting['name'])
        )
        {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($setting, TRUE));
        }
    }

    /**
     * Insert a new setting into the database.
     *
     * @param array $setting Associative array with the setting data.
     *
     * @return int Returns the setting ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $setting): int
    {
        $setting['create_datetime'] = date('Y-m-d H:i:s');
        $setting['update_datetime'] = date('Y-m-d H:i:s');
        
        if ( ! $this->db->insert('settings', $setting))
        {
            throw new RuntimeException('Could not insert setting.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing setting.
     *
     * @param array $setting Associative array with the setting data.
     *
     * @return int Returns the setting ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $setting): int
    {
        $setting['update_datetime'] = date('Y-m-d H:i:s');
        
        if ( ! $this->db->update('settings', $setting, ['id' => $setting['id']]))
        {
            throw new RuntimeException('Could not update setting.');
        }

        return $setting['id'];
    }

    /**
     * Remove an existing setting from the database.
     
     * @param int $setting_id Setting ID.
     * @param bool $force_delete Override soft delete.
     *
     * @throws RuntimeException
     */
    public function delete(int $setting_id,  bool $force_delete = FALSE)
    {
        if ($force_delete)
        {
            $this->db->delete('settings', ['id' => $setting_id]);
        }
        else
        {
            $this->db->update('settings', ['delete_datetime' => date('Y-m-d H:i:s')], ['id' => $setting_id]);
        }
    }

    /**
     * Get a specific setting from the database.
     *
     * @param int $setting_id The ID of the record to be returned.
     * @param bool $with_trashed
     *
     * @return array Returns an array with the setting data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $setting_id, bool $with_trashed = FALSE): array
    {
        if ( ! $with_trashed)
        {
            $this->db->where('delete_datetime IS NULL');
        }
        
        $setting = $this->db->get_where('settings', ['id' => $setting_id])->row_array();

        if ( ! $setting)
        {
            throw new InvalidArgumentException('The provided setting ID was not found in the database: ' . $setting_id);
        }

        $this->cast($setting);

        return $setting;
    }

    /**
     * Get a specific field value from the database.
     *
     * @param int $setting_id Setting ID.
     * @param string $field Name of the value to be returned.
     *
     * @return string Returns the selected setting value from the database.
     *
     * @throws InvalidArgumentException
     */
    public function value(int $setting_id, string $field): string
    {
        if (empty($field))
        {
            throw new InvalidArgumentException('The field argument is cannot be empty.');
        }

        if (empty($setting_id))
        {
            throw new InvalidArgumentException('The setting ID argument cannot be empty.');
        }

        // Check whether the setting exists.
        $query = $this->db->get_where('settings', ['id' => $setting_id]);

        if ( ! $query->num_rows())
        {
            throw new InvalidArgumentException('The provided setting ID was not found in the database: ' . $setting_id);
        }

        // Check if the required field is part of the setting data.
        $setting = $query->row_array();

        $this->cast($setting);

        if ( ! array_key_exists($field, $setting))
        {
            throw new InvalidArgumentException('The requested field was not found in the setting data: ' . $field);
        }

        return $setting[$field];
    }

    /**
     * Get all settings that match the provided criteria.
     *
     * @param array|string $where Where conditions
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     * @param bool $with_trashed
     * 
     * @return array Returns an array of settings.
     */
    public function get($where = NULL, int $limit = NULL, int $offset = NULL, string $order_by = NULL, bool $with_trashed = FALSE): array
    {
        if ($where !== NULL)
        {
            $this->db->where($where);
        }

        if ($order_by !== NULL)
        {
            $this->db->order_by($order_by);
        }

        if ( ! $with_trashed)
        {
            $this->db->where('delete_datetime IS NULL');
        }

        $settings = $this->db->get('settings', $limit, $offset)->result_array();

        foreach ($settings as &$setting)
        {
            $this->cast($setting);
        }

        return $settings;
    }

    /**
     * Get the query builder interface, configured for use with the settings table.
     *
     * @return CI_DB_query_builder
     */
    public function query(): CI_DB_query_builder
    {
        return $this->db->from('settings');
    }

    /**
     * Search settings by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     * @param bool $with_trashed
     * 
     * @return array Returns an array of settings.
     */
    public function search(string $keyword, int $limit = NULL, int $offset = NULL, string $order_by = NULL, bool $with_trashed = FALSE): array
    {
        if ( ! $with_trashed)
        {
            $this->db->where('delete_datetime IS NULL');
        }
        
        $settings = $this
            ->db
            ->select()
            ->from('settings')
            ->like('name', $keyword)
            ->or_like('value', $keyword)
            ->limit($limit)
            ->offset($offset)
            ->order_by($order_by)
            ->get()
            ->result_array();

        foreach ($settings as &$setting)
        {
            $this->cast($setting);
        }

        return $settings;
    }

    /**
     * Load related resources to a setting.
     *
     * @param array $setting Associative array with the setting data.
     * @param array $resources Resource names to be attached.
     *
     * @throws InvalidArgumentException
     */
    public function load(array &$setting, array $resources)
    {
        // Users do not currently have any related resources. 
    }

    /**
     * Convert the database setting record to the equivalent API resource.
     *
     * @param array $setting Setting data.
     */
    public function api_encode(array &$setting)
    {
        $encoded_resource = [
            'name' => $setting['name'],
            'value' => $setting['value']
        ];

        $setting = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database setting record.
     *
     * @param array $setting API resource.
     * @param array|null $base Base setting data to be overwritten with the provided values (useful for updates).
     */
    public function api_decode(array &$setting, array $base = NULL)
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('name', $setting))
        {
            $decoded_resource['name'] = $setting['name'];
        }

        if (array_key_exists('value', $setting))
        {
            $decoded_resource['value'] = $setting['value'];
        }

        $setting = $decoded_resource;
    }
}
