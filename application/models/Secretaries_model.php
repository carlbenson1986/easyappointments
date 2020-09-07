<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Secretaries Model
 *
 * Handles the db actions that have to do with secretaries.
 *
 * @property CI_DB_query_builder db
 * @property CI_Loader load
 *
 * @package Models
 */
class Secretaries_Model extends CI_Model {
    /**
     * Add (insert or update) a secretary user record into database.
     *
     * @param array $secretary Contains the secretary user data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the secretary data are invalid (see validate() method).
     */
    public function add($secretary)
    {
        $this->validate($secretary);

        if ($this->exists($secretary) && ! isset($secretary['id']))
        {
            $secretary['id'] = $this->find_record_id($secretary);
        }

        if ( ! isset($secretary['id']))
        {
            $secretary['id'] = $this->insert($secretary);
        }
        else
        {
            $secretary['id'] = $this->update($secretary);
        }

        return (int)$secretary['id'];
    }

    /**
     * Validate secretary user data before add() operation is executed.
     *
     * @param array $secretary Contains the secretary user data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If secretary validation fails.
     */
    public function validate($secretary)
    {
        $this->load->helper('data_validation');

        // If a record id is provided then check whether the record exists in the database.
        if (isset($secretary['id']))
        {
            $num_rows = $this->db->get_where('users', ['id' => $secretary['id']])
                ->num_rows();
            if ($num_rows == 0)
            {
                throw new Exception('Given secretary id does not exist in database: ' . $secretary['id']);
            }
        }

        // Validate 'providers' value data type (must be array)
        if (isset($secretary['providers']) && ! is_array($secretary['providers']))
        {
            throw new Exception('Secretary providers value is not an array.');
        }

        // Validate required fields integrity.
        if ( ! isset($secretary['last_name'])
            || ! isset($secretary['email'])
            || ! isset($secretary['phone_number']))
        {
            throw new Exception('Not all required fields are provided: ' . print_r($secretary, TRUE));
        }

        // Validate secretary email address.
        if ( ! filter_var($secretary['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new Exception('Invalid email address provided: ' . $secretary['email']);
        }

        // Check if username exists.
        if (isset($secretary['settings']['username']))
        {
            $user_id = (isset($secretary['id'])) ? $secretary['id'] : '';
            if ( ! $this->validate_username($secretary['settings']['username'], $user_id))
            {
                throw new Exception ('Username already exists. Please select a different '
                    . 'username for this record.');
            }
        }

        // Validate secretary password.
        if (isset($secretary['settings']['password']))
        {
            if (strlen($secretary['settings']['password']) < MIN_PASSWORD_LENGTH)
            {
                throw new Exception('The user password must be at least '
                    . MIN_PASSWORD_LENGTH . ' characters long.');
            }
        }

        // Validate calendar view mode.
        if (isset($secretary['settings']['calendar_view']) && ($secretary['settings']['calendar_view'] !== CALENDAR_VIEW_DEFAULT
                && $secretary['settings']['calendar_view'] !== CALENDAR_VIEW_TABLE))
        {
            throw new Exception('The calendar view setting must be either "' . CALENDAR_VIEW_DEFAULT
                . '" or "' . CALENDAR_VIEW_TABLE . '", given: ' . $secretary['settings']['calendar_view']);
        }

        // When inserting a record the email address must be unique.
        $secretary_id = (isset($secretary['id'])) ? $secretary['id'] : '';

        $num_rows = $this->db
            ->select('*')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_SECRETARY)
            ->where('users.email', $secretary['email'])
            ->where('users.id <>', $secretary_id)
            ->get()
            ->num_rows();

        if ($num_rows > 0)
        {
            throw new Exception('Given email address belongs to another secretary record. '
                . 'Please use a different email.');
        }

        return TRUE;
    }

    /**
     * Validate Records Username
     *
     * @param string $username The provider records username.
     * @param int $user_id The user record id.
     *
     * @return bool Returns the validation result.
     */
    public function validate_username($username, $user_id)
    {
        $num_rows = $this->db->get_where('user_settings',
            ['username' => $username, 'id_users <> ' => $user_id])->num_rows();
        return ($num_rows > 0) ? FALSE : TRUE;
    }

    /**
     * Check whether a particular secretary record exists in the database.
     *
     * @param array $secretary Contains the secretary data. The 'email' value is required to be present at the moment.
     *
     * @return bool Returns whether the record exists or not.
     *
     * @throws Exception When the 'email' value is not present on the $secretary argument.
     */
    public function exists($secretary)
    {
        if ( ! isset($secretary['email']))
        {
            throw new Exception('Secretary email is not provided: ' . print_r($secretary, TRUE));
        }

        // This method shouldn't depend on another method of this class.
        $num_rows = $this->db
            ->select('*')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $secretary['email'])
            ->where('roles.slug', DB_SLUG_SECRETARY)
            ->get()->num_rows();

        return ($num_rows > 0) ? TRUE : FALSE;
    }

    /**
     * Find the database record id of a secretary.
     *
     * @param array $secretary Contains the secretary data. The 'email' value is required in order to find the record id.
     *
     * @return int Returns the record id
     *
     * @throws Exception When the 'email' value is not present on the $secretary array.
     */
    public function find_record_id($secretary)
    {
        if ( ! isset($secretary['email']))
        {
            throw new Exception('Secretary email was not provided: ' . print_r($secretary, TRUE));
        }

        $result = $this->db
            ->select('users.id')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $secretary['email'])
            ->where('roles.slug', DB_SLUG_SECRETARY)
            ->get();

        if ($result->num_rows() == 0)
        {
            throw new Exception('Could not find secretary record id.');
        }

        return (int)$result->row()->id;
    }

    /**
     * Insert a new secretary record into the database.
     *
     * @param array $secretary Contains the secretary data.
     *
     * @return int Returns the new record id.
     *
     * @throws Exception When the insert operation fails.
     */
    protected function insert($secretary)
    {
        $this->load->helper('general');

        $providers = $secretary['providers'];
        unset($secretary['providers']);
        $settings = $secretary['settings'];
        unset($secretary['settings']);

        $secretary['id_roles'] = $this->get_secretary_role_id();

        if ( ! $this->db->insert('users', $secretary))
        {
            throw new Exception('Could not insert secretary into the database.');
        }

        $secretary['id'] = (int)$this->db->insert_id();
        $settings['salt'] = generate_salt();
        $settings['password'] = hash_password($settings['salt'], $settings['password']);

        $this->save_providers($providers, $secretary['id']);
        $this->save_settings($settings, $secretary['id']);

        return $secretary['id'];
    }

    /**
     * Get the secretary users role id.
     *
     * @return int Returns the role record id.
     */
    public function get_secretary_role_id()
    {
        return (int)$this->db->get_where('roles', ['slug' => DB_SLUG_SECRETARY])->row()->id;
    }

    /**
     * Save a secretary handling users.
     *
     * @param array $providers Contains the provider ids that are handled by the secretary.
     * @param int $secretary_id The selected secretary record.
     *
     * @throws Exception If $providers argument is invalid.
     */
    protected function save_providers($providers, $secretary_id)
    {
        if ( ! is_array($providers))
        {
            throw new Exception('Invalid argument given $providers: ' . print_r($providers, TRUE));
        }

        // Delete old connections
        $this->db->delete('secretaries_providers', ['id_users_secretary' => $secretary_id]);

        if (count($providers) > 0)
        {
            foreach ($providers as $provider_id)
            {
                $this->db->insert('secretaries_providers', [
                    'id_users_secretary' => $secretary_id,
                    'id_users_provider' => $provider_id
                ]);
            }
        }
    }

    /**
     * Save the secretary settings (used from insert or update operation).
     *
     * @param array $settings Contains the setting values.
     * @param int $secretary_id Record id of the secretary.
     *
     * @throws Exception If $secretary_id argument is invalid.
     * @throws Exception If $settings argument is invalid.
     */
    protected function save_settings($settings, $secretary_id)
    {
        if ( ! is_numeric($secretary_id))
        {
            throw new Exception('Invalid $secretary_id argument given:' . $secretary_id);
        }

        if (count($settings) == 0 || ! is_array($settings))
        {
            throw new Exception('Invalid $settings argument given:' . print_r($settings, TRUE));
        }

        // Check if the setting record exists in db.
        $num_rows = $this->db->get_where('user_settings',
            ['id_users' => $secretary_id])->num_rows();
        if ($num_rows == 0)
        {
            $this->db->insert('user_settings', ['id_users' => $secretary_id]);
        }

        foreach ($settings as $name => $value)
        {
            $this->set_setting($name, $value, $secretary_id);
        }
    }

    /**
     * Set a provider's setting value in the database.
     *
     * The provider and settings record must already exist.
     *
     * @param string $setting_name The setting's name.
     * @param string $value The setting's value.
     * @param int $secretary_id The selected provider id.
     */
    public function set_setting($setting_name, $value, $secretary_id)
    {
        $this->db->where(['id_users' => $secretary_id]);
        return $this->db->update('user_settings', [$setting_name => $value]);
    }

    /**
     * Update an existing secretary record in the database.
     *
     * @param array $secretary Contains the secretary record data.
     *
     * @return int Returns the record id.
     *
     * @throws Exception When the update operation fails.
     */
    protected function update($secretary)
    {
        $this->load->helper('general');

        $providers = $secretary['providers'];
        unset($secretary['providers']);
        $settings = $secretary['settings'];
        unset($secretary['settings']);

        if (isset($settings['password']))
        {
            $salt = $this->db->get_where('user_settings', ['id_users' => $secretary['id']])->row()->salt;
            $settings['password'] = hash_password($salt, $settings['password']);
        }

        $this->db->where('id', $secretary['id']);
        if ( ! $this->db->update('users', $secretary))
        {
            throw new Exception('Could not update secretary record.');
        }

        $this->save_providers($providers, $secretary['id']);
        $this->save_settings($settings, $secretary['id']);

        return (int)$secretary['id'];
    }

    /**
     * Delete an existing secretary record from the database.
     *
     * @param int $secretary_id The secretary record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception When the $secretary_id is not a valid int value.
     */
    public function delete($secretary_id)
    {
        if ( ! is_numeric($secretary_id))
        {
            throw new Exception('Invalid argument type $secretary_id: ' . $secretary_id);
        }

        $num_rows = $this->db->get_where('users', ['id' => $secretary_id])->num_rows();
        if ($num_rows == 0)
        {
            return FALSE; // Record does not exist in database.
        }

        return $this->db->delete('users', ['id' => $secretary_id]);
    }

    /**
     * Get a specific secretary record from the database.
     *
     * @param int $secretary_id The id of the record to be returned.
     *
     * @return array Returns an array with the secretary user data.
     *
     * @throws Exception When the $secretary_id is not a valid int value.
     * @throws Exception When given record id does not exist in the database.
     */
    public function get_row($secretary_id)
    {
        if ( ! is_numeric($secretary_id))
        {
            throw new Exception('$secretary_id argument is not a valid numeric value: ' . $secretary_id);
        }

        // Check if record exists
        if ($this->db->get_where('users', ['id' => $secretary_id])->num_rows() == 0)
        {
            throw new Exception('The given secretary id does not match a record in the database.');
        }

        $secretary = $this->db->get_where('users', ['id' => $secretary_id])->row_array();

        $secretary_providers = $this->db->get_where('secretaries_providers',
            ['id_users_secretary' => $secretary['id']])->result_array();
        $secretary['providers'] = [];
        foreach ($secretary_providers as $secretary_provider)
        {
            $secretary['providers'][] = $secretary_provider['id_users_provider'];
        }

        $secretary['settings'] = $this->db->get_where('user_settings',
            ['id_users' => $secretary['id']])->row_array();
        unset($secretary['settings']['id_users'], $secretary['settings']['salt']);

        return $secretary;
    }

    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be returned.
     * @param int $secretary_id Record id of the value to be returned.
     *
     * @return string Returns the selected record value from the database.
     *
     * @throws Exception When the $field_name argument is not a valid string.
     * @throws Exception When the $secretary_id is not a valid int.
     * @throws Exception When the secretary record does not exist in the database.
     * @throws Exception When the selected field value is not present on database.
     */
    public function get_value($field_name, $secretary_id)
    {
        if ( ! is_string($field_name))
        {
            throw new Exception('$field_name argument is not a string: ' . $field_name);
        }

        if ( ! is_numeric($secretary_id))
        {
            throw new Exception('$secretary_id argument is not a valid numeric value: ' . $secretary_id);
        }

        // Check whether the secretary record exists.
        $result = $this->db->get_where('users', ['id' => $secretary_id]);
        if ($result->num_rows() == 0)
        {
            throw new Exception('The record with the given id does not exist in the '
                . 'database: ' . $secretary_id);
        }

        // Check if the required field name exist in database.
        $provider = $result->row_array();
        if ( ! isset($provider[$field_name]))
        {
            throw new Exception('The given $field_name argument does not exist in the '
                . 'database: ' . $field_name);
        }

        return $provider[$field_name];
    }

    /**
     * Get all, or specific secretary records from database.
     *
     * @param mixed|null $where (OPTIONAL) The WHERE clause of the query to be executed. Use this to get
     * specific secretary records.
     * @param mixed|null $order_by
     * @param int|null $limit
     * @param int|null $offset
     * @return array Returns an array with secretary records.
     */
    public function get_batch($where = NULL, $order_by = NULL, $limit = NULL, $offset = NULL)
    {
        $role_id = $this->get_secretary_role_id();

        if ($where !== NULL)
        {
            $this->db->where($where);
        }

        if ($order_by !== NULL)
        {
            $this->db->order_by($order_by);
        }

        $batch = $this->db->get_where('users', ['id_roles' => $role_id], $limit, $offset)->result_array();

        // Include every secretary providers.
        foreach ($batch as &$secretary)
        {
            $secretary_providers = $this->db->get_where('secretaries_providers',
                ['id_users_secretary' => $secretary['id']])->result_array();

            $secretary['providers'] = [];
            foreach ($secretary_providers as $secretary_provider)
            {
                $secretary['providers'][] = $secretary_provider['id_users_provider'];
            }

            $secretary['settings'] = $this->db->get_where('user_settings',
                ['id_users' => $secretary['id']])->row_array();
            unset($secretary['settings']['id_users']);
        }

        return $batch;
    }

    /**
     * Get a providers setting from the database.
     *
     * @param string $setting_name The setting name that is going to be returned.
     * @param int $secretary_id The selected provider id.
     *
     * @return string Returns the value of the selected user setting.
     */
    public function get_setting($setting_name, $secretary_id)
    {
        $provider_settings = $this->db->get_where('user_settings',
            ['id_users' => $secretary_id])->row_array();
        return $provider_settings[$setting_name];
    }
}
