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
 * Providers model.
 *
 * Handles all the database operations of the provider resource.
 *
 * @package Models
 */
class Providers_model extends EA_Model {
    /**
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'is_private' => 'boolean',
        'id_roles' => 'integer',
    ];

    /**
     * @var array
     */
    protected $api_resource = [
        'id' => 'id',
        'firstName' => 'first_name',
        'lastName' => 'last_name',
        'email' => 'email',
        'mobile' => 'mobile_number',
        'phone' => 'phone_number',
        'address' => 'address',
        'city' => 'city',
        'state' => 'state',
        'zip' => 'zip_code',
        'timezone' => 'timezone',
        'language' => 'language',
        'notes' => 'notes',
        'isPrivate' => 'is_private',
        'roleId' => 'id_roles',
    ];

    /**
     * Save (insert or update) a provider.
     *
     * @param array $provider Associative array with the provider data.
     *
     * @return int Returns the provider ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $provider): int
    {
        $this->validate($provider);

        if (empty($provider['id']))
        {
            return $this->insert($provider);
        }
        else
        {
            return $this->update($provider);
        }
    }

    /**
     * Validate the provider data.
     *
     * @param array $provider Associative array with the provider data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $provider)
    {
        // If a provider ID is provided then check whether the record really exists in the database.
        if ( ! empty($provider['id']))
        {
            $count = $this->db->get_where('users', ['id' => $provider['id']])->num_rows();

            if ( ! $count)
            {
                throw new InvalidArgumentException('The provided provider ID does not exist in the database: ' . $provider['id']);
            }
        }

        // Make sure all required fields are provided. 
        if (
            empty($provider['first_name'])
            || empty($provider['last_name'])
            || empty($provider['email'])
            || empty($provider['phone_number'])
        )
        {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($provider, TRUE));
        }

        // Validate the email address.
        if ( ! filter_var($provider['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new InvalidArgumentException('Invalid email address provided: ' . $provider['email']);
        }

        // Validate provider services.
        if ( ! empty($provider['services']))
        {
            // Make sure the provided service entries are numeric values.
            foreach ($provider['services'] as $service_id)
            {
                if ( ! is_numeric($service_id))
                {
                    throw new InvalidArgumentException('The provided provider services are invalid: ' . print_r($provider, TRUE));
                }
            }
        }

        // Make sure the username is unique. 
        if ( ! empty($provider['settings']['username']))
        {
            $provider_id = $provider['id'] ?? NULL;

            if ( ! $this->validate_username($provider['settings']['username'], $provider_id))
            {
                throw new InvalidArgumentException('The provided username is already in use, please use a different one.');
            }
        }

        // Validate the password. 
        if ( ! empty($provider['settings']['password']))
        {
            if (strlen($provider['settings']['password']) < MIN_PASSWORD_LENGTH)
            {
                throw new InvalidArgumentException('The provider password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long.');
            }
        }

        // New users must always have a password value set. 
        if (empty($provider['id']) && empty($provider['settings']['password']))
        {
            throw new InvalidArgumentException('The provider password cannot be empty when inserting a new record.');
        }

        // Validate calendar view type value.
        if (
            ! empty($provider['settings']['calendar_view'])
            && ! in_array($provider['settings']['calendar_view'], [CALENDAR_VIEW_DEFAULT, CALENDAR_VIEW_TABLE])
        )
        {
            throw new InvalidArgumentException('The provided calendar view is invalid: ' . $provider['settings']['calendar_view']);
        }

        // Make sure the email address is unique.
        $provider_id = $provider['id'] ?? NULL;

        $count = $this
            ->db
            ->select()
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->where('users.email', $provider['email'])
            ->where('users.id !=', $provider_id)
            ->get()
            ->num_rows();

        if ($count > 0)
        {
            throw new InvalidArgumentException('The provided email address is already in use, please use a different one.');
        }
    }

    /**
     * Validate the provider username.
     *
     * @param string $username Provider username.
     * @param int|null $provider_id Provider ID.
     *
     * @return bool Returns the validation result.
     */
    public function validate_username(string $username, int $provider_id = NULL): bool
    {
        if ( ! empty($provider_id))
        {
            $this->db->where('id_users !=', $provider_id);
        }

        return $this->db->get_where('user_settings', ['username' => $username])->num_rows() === 0;
    }

    /**
     * Insert a new provider into the database.
     *
     * @param array $provider Associative array with the provider data.
     *
     * @return int Returns the provider ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $provider): int
    {
        $provider['create_datetime'] = date('Y-m-d H:i:s');
        $provider['update_datetime'] = date('Y-m-d H:i:s');
        $provider['id_roles'] = $this->get_provider_role_id();

        $service_ids = $provider['services'];

        $settings = $provider['settings'];
        
        unset(
            $provider['services'],
            $provider['settings']
        );

        if ( ! $this->db->insert('users', $provider))
        {
            throw new RuntimeException('Could not insert provider.');
        }

        $provider['id'] = $this->db->insert_id();
        $settings['salt'] = generate_salt();
        $settings['password'] = hash_password($settings['salt'], $settings['password']);

        $this->save_settings($provider['id'], $settings);
        $this->save_service_ids($provider['id'], $service_ids);

        return $provider['id'];
    }

    /**
     * Update an existing provider.
     *
     * @param array $provider Associative array with the provider data.
     *
     * @return int Returns the provider ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $provider): int
    {
        $provider['update_datetime'] = date('Y-m-d H:i:s');
        
        $service_ids = $provider['services'];

        $settings = $provider['settings'];

        unset(
            $provider['services'],
            $provider['settings']
        );

        if (isset($settings['password']))
        {
            $existing_settings = $this->db->get_where('user_settings', ['id_users' => $provider['id']])->row_array();

            if (empty($existing_settings))
            {
                throw new RuntimeException('No settings record found for provider with ID: ' . $provider['id']);
            }

            $settings['password'] = hash_password($existing_settings['salt'], $settings['password']);
        }

        if ( ! $this->db->update('users', $provider, ['id' => $provider['id']]))
        {
            throw new RuntimeException('Could not update provider.');
        }

        $this->save_settings($provider['id'], $settings);
        $this->save_service_ids($provider['id'], $service_ids);

        return $provider['id'];
    }

    /**
     * Remove an existing provider from the database.
     *
     * @param int $provider_id Provider ID.
     * @param bool $force_delete Override soft delete.
     *
     * @throws RuntimeException
     */
    public function delete(int $provider_id, bool $force_delete = FALSE)
    {
        if ($force_delete)
        {
            $this->db->delete('users', ['id' => $provider_id]);
        }
        else
        {
            $this->db->update('users', ['delete_datetime' => date('Y-m-d H:i:s')], ['id' => $provider_id]);
        }
    }

    /**
     * Get a specific provider from the database.
     *
     * @param int $provider_id The ID of the record to be returned.
     * @param bool $with_trashed
     *
     * @return array Returns an array with the provider data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $provider_id, bool $with_trashed = FALSE): array
    {
        if ( ! $with_trashed)
        {
            $this->db->where('delete_datetime IS NULL');
        }
        
        $provider = $this->db->get_where('users', ['id' => $provider_id])->row_array();

        if ( ! $provider)
        {
            throw new InvalidArgumentException('The provided provider ID was not found in the database: ' . $provider_id);
        }

        $this->cast($provider);

        $provider['settings'] = $this->db->get_where('user_settings', ['id_users' => $provider_id])->row_array();

        unset($provider['settings']['id_users']);

        $service_provider_connections = $this->db->get_where('services_providers', ['id_users' => $provider_id])->result_array();

        $provider['services'] = [];

        foreach ($service_provider_connections as $service_provider_connection)
        {
            $provider['services'][] = (int)$service_provider_connection['id_services'];
        }

        return $provider;
    }

    /**
     * Get a specific field value from the database.
     *
     * @param int $provider_id Provider ID.
     * @param string $field Name of the value to be returned.
     *
     * @return string Returns the selected provider value from the database.
     *
     * @throws InvalidArgumentException
     */
    public function value(int $provider_id, string $field): string
    {
        if (empty($field))
        {
            throw new InvalidArgumentException('The field argument is cannot be empty.');
        }

        if (empty($provider_id))
        {
            throw new InvalidArgumentException('The provider ID argument cannot be empty.');
        }

        // Check whether the provider exists.
        $query = $this->db->get_where('users', ['id' => $provider_id]);

        if ( ! $query->num_rows())
        {
            throw new InvalidArgumentException('The provided provider ID was not found in the database: ' . $provider_id);
        }

        // Check if the required field is part of the provider data.
        $provider = $query->row_array();

        $this->cast($provider);

        if ( ! array_key_exists($field, $provider))
        {
            throw new InvalidArgumentException('The requested field was not found in the provider data: ' . $field);
        }

        return $provider[$field];
    }

    /**
     * Get all providers that match the provided criteria.
     *
     * @param array|string $where Where conditions
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     * @param bool $with_trashed
     * 
     * @return array Returns an array of providers.
     */
    public function get($where = NULL, int $limit = NULL, int $offset = NULL, string $order_by = NULL, bool $with_trashed = FALSE): array
    {
        $role_id = $this->get_provider_role_id();

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

        $providers = $this->db->get_where('users', ['id_roles' => $role_id], $limit, $offset)->result_array();

        foreach ($providers as &$provider)
        {
            $this->cast($provider);

            $provider['settings'] = $this->db->get_where('user_settings', ['id_users' => $provider['id']])->row_array();

            unset(
                $provider['settings']['id_users'],
                $provider['settings']['password'],
                $provider['settings']['salt']
            );

            $provider['services'] = [];

            $service_provider_connections = $this->db->get_where('services_providers', ['id_users' => $provider['id']])->result_array();

            foreach ($service_provider_connections as $service_provider_connection)
            {
                $provider['services'][] = (int)$service_provider_connection['id_services'];
            }
        }

        return $providers;
    }


    /**
     * Get the provider role ID.
     *
     * @return int Returns the role ID.
     */
    public function get_provider_role_id(): int
    {
        $role = $this->db->get_where('roles', ['slug' => DB_SLUG_PROVIDER])->row_array();

        if (empty($role))
        {
            throw new RuntimeException('The provider role was not found in the database.');
        }

        return $role['id'];
    }

    /**
     * Save the provider settings.
     *
     * @param int $provider_id Provider ID.
     * @param array $settings Associative array with the settings data.
     *
     * @throws InvalidArgumentException
     */
    protected function save_settings(int $provider_id, array $settings)
    {
        if (empty($settings))
        {
            throw new InvalidArgumentException('The settings argument cannot be empty.');
        }

        // Make sure the settings record exists in the database. 
        $count = $this->db->get_where('user_settings', ['id_users' => $provider_id])->num_rows();

        if ( ! $count)
        {
            $this->db->insert('user_settings', ['id_users' => $provider_id]);
        }

        foreach ($settings as $name => $value)
        {
            // Sort working plans exceptions in descending order that they are easier to modify later on. 
            if ($name === 'working_plan_exceptions')
            {
                $value = json_decode($value, TRUE);

                if ( ! $value)
                {
                    $value = [];
                }

                krsort($value);

                $value = json_encode($value);
            }

            $this->set_setting($provider_id, $name, $value);
        }
    }

    /**
     * Set the value of a provider setting.
     *
     * @param int $provider_id Provider ID.
     * @param string $name Setting name.
     * @param mixed $value Setting value.
     */
    public function set_setting(int $provider_id, string $name, $value = NULL)
    {
        if ( ! $this->db->update('user_settings', [$name => $value], ['id_users' => $provider_id]))
        {
            throw new RuntimeException('Could not set the new provider setting value: ' . $name);
        }
    }

    /**
     * Get the value of a provider setting.
     *
     * @param int $provider_id Provider ID.
     * @param string $name Setting name.
     *
     * @return string Returns the value of the requested user setting.
     */
    public function get_setting(int $provider_id, string $name): string
    {
        $settings = $this->db->get_where('user_settings', ['id_users' => $provider_id])->row_array();

        if (empty($settings[$name]))
        {
            throw new RuntimeException('The requested setting value was not found: ' . $provider_id);
        }

        return $settings[$name];
    }

    /**
     * Save the provider service IDs.
     *
     * @param int $provider_id Provider ID.
     * @param array $service_ids Service IDs.
     */
    protected function save_service_ids(int $provider_id, array $service_ids)
    {
        // Re-insert the provider-service connections. 
        $this->db->delete('services_providers', ['id_users' => $provider_id]);

        foreach ($service_ids as $service_id)
        {
            $service_provider_connection = [
                'id_users' => $provider_id,
                'id_services' => $service_id
            ];

            $this->db->insert('services_providers', $service_provider_connection);
        }
    }

    /**
     * Save a new or existing working plan exception.
     *
     * @param int $provider_id Provider ID.
     * @param string $date Working plan exception date (in YYYY-MM-DD format).
     * @param array $working_plan_exception Associative array with the working plan exception data.
     *
     * @throws InvalidArgumentException
     */
    public function save_working_plan_exception(int $provider_id, string $date, array $working_plan_exception)
    {
        // Validate the working plan exception data.
        $start = date('H:i', strtotime($working_plan_exception['start']));

        $end = date('H:i', strtotime($working_plan_exception['end']));

        if ($start > $end)
        {
            throw new InvalidArgumentException('Working plan exception start date must be before the end date.');
        }

        // Make sure the provider record exists.
        $where = [
            'id' => $provider_id,
            'id_roles' => $this->db->get_where('roles', ['slug' => DB_SLUG_PROVIDER])->row()->id
        ];

        if ($this->db->get_where('users', $where)->num_rows() === 0)
        {
            throw new InvalidArgumentException('Provider ID was not found in the database: ' . $provider_id);
        }

        $provider = $this->find($provider_id);

        // Store the working plan exception.
        $working_plan_exceptions = json_decode($provider['settings']['working_plan_exceptions'], TRUE);

        if ( ! isset($working_plan_exception['breaks']))
        {
            $working_plan_exception['breaks'] = [];
        }

        $working_plan_exceptions[$date] = $working_plan_exception;

        $provider['settings']['working_plan_exceptions'] = json_encode($working_plan_exceptions);

        $this->update($provider);
    }

    /**
     * Delete a provider working plan exception.
     *
     * @param string $date The working plan exception date (in YYYY-MM-DD format).
     * @param int $provider_id The selected provider record id.
     *
     * @throws Exception If $provider_id argument is invalid.
     */
    public function delete_working_plan_exception(int $provider_id, string $date)
    {
        $provider = $this->find($provider_id);

        $working_plan_exceptions = json_decode($provider['settings']['working_plan_exceptions'], TRUE);

        if ( ! isset($working_plan_exceptions[$date]))
        {
            return; // The selected date does not exist in provider's settings.
        }

        unset($working_plan_exceptions[$date]);

        $provider['settings']['working_plan_exceptions'] = empty($working_plan_exceptions) ? new stdClass() : $working_plan_exceptions;

        $this->update($provider);
    }

    /**
     * Get all the provider records that are assigned to at least one service.
     *
     * @param bool $without_private Only include the public providers.
     *
     * @return array Returns an array of providers.
     */
    public function get_available_providers(bool $without_private = FALSE): array
    {
        if ($without_private)
        {
            $this->db->where('users.is_private', FALSE);
        }

        $providers = $this
            ->db
            ->select('users.*')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->where('users.delete_datetime IS NULL')
            ->order_by('first_name ASC, last_name ASC, email ASC')
            ->get()
            ->result_array();

        foreach ($providers as &$provider)
        {
            $provider['settings'] = $this->db->get_where('user_settings', ['id_users' => $provider['id']])->row_array();

            unset(
                $provider['settings']['id_users'],
                $provider['settings']['username'],
                $provider['settings']['password'],
                $provider['settings']['salt']
            );

            $provider['services'] = [];

            $service_provider_connections = $this->db->get_where('services_providers', ['id_users' => $provider['id']])->result_array();

            foreach ($service_provider_connections as $service_provider_connection)
            {
                $provider['services'][] = (int)$service_provider_connection['id_services'];
            }
        }

        return $providers;
    }

    /**
     * Get the query builder interface, configured for use with the users (provider-filtered) table.
     *
     * @return CI_DB_query_builder
     */
    public function query(): CI_DB_query_builder
    {
        $role_id = $this->get_provider_role_id();

        return $this->db->from('users')->where('id_roles', $role_id);
    }

    /**
     * Search providers by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     * @param bool $with_trashed
     * 
     * @return array Returns an array of providers.
     */
    public function search(string $keyword, int $limit = NULL, int $offset = NULL, string $order_by = NULL, bool $with_trashed = FALSE): array
    {
        $role_id = $this->get_provider_role_id();

        if ( ! $with_trashed)
        {
            $this->db->where('delete_datetime IS NULL');
        }
        
        $providers = $this
            ->db
            ->select()
            ->from('users')
            ->where('id_roles', $role_id)
            ->group_start()
            ->like('first_name', $keyword)
            ->or_like('last_name', $keyword)
            ->or_like('email', $keyword)
            ->or_like('phone_number', $keyword)
            ->or_like('mobile_number', $keyword)
            ->or_like('address', $keyword)
            ->or_like('city', $keyword)
            ->or_like('state', $keyword)
            ->or_like('zip_code', $keyword)
            ->or_like('notes', $keyword)
            ->group_end()
            ->limit($limit)
            ->offset($offset)
            ->order_by($order_by)
            ->get()
            ->result_array();

        foreach ($providers as &$provider)
        {
            $this->cast($provider);

            $provider['settings'] = $this->db->get_where('user_settings', ['id_users' => $provider['id']])->row_array();

            unset(
                $provider['settings']['id_users'],
                $provider['settings']['password'],
                $provider['settings']['salt']
            );

            $provider['services'] = [];

            $service_provider_connections = $this->db->get_where('services_providers', ['id_users' => $provider['id']])->result_array();

            foreach ($service_provider_connections as $service_provider_connection)
            {
                $provider['services'][] = (int)$service_provider_connection['id_services'];
            }
        }

        return $providers;
    }

    /**
     * Load related resources to a provider.
     *
     * @param array $provider Associative array with the provider data.
     * @param array $resources Resource names to be attached ("services" supported).
     *
     * @throws InvalidArgumentException
     */
    public function load(array &$provider, array $resources)
    {
        if (empty($provider) || empty($resources))
        {
            return;
        }

        foreach ($resources as $resource)
        {
            switch ($resource)
            {
                case 'services':
                    $provider['services'] = $this
                        ->db
                        ->select('services.*')
                        ->from('services')
                        ->join('services_providers', 'services_providers.id_services = services.id', 'inner')
                        ->where('id_users', $provider['id'])
                        ->get()
                        ->result_array();
                    break;

                default:
                    throw new InvalidArgumentException('The requested provider relation is not supported: ' . $resource);
            }
        }
    }

    /**
     * Convert the database provider record to the equivalent API resource.
     *
     * @param array $provider Provider data.
     */
    public function api_encode(array &$provider)
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $provider) ? (int)$provider['id'] : NULL,
            'firstName' => $provider['first_name'],
            'lastName' => $provider['last_name'],
            'email' => $provider['email'],
            'mobile' => $provider['mobile_number'],
            'phone' => $provider['phone_number'],
            'address' => $provider['address'],
            'city' => $provider['city'],
            'state' => $provider['state'],
            'zip' => $provider['zip_code'],
            'notes' => $provider['notes'],
            'timezone' => $provider['timezone'],
        ];

        if (array_key_exists('services', $provider))
        {
            $encoded_resource['services'] = $provider['services'];
        }

        if (array_key_exists('settings', $provider))
        {
            $encoded_resource['settings'] = [
                'username' => $provider['settings']['username'],
                'notifications' => filter_var($provider['settings']['notifications'], FILTER_VALIDATE_BOOLEAN),
                'calendarView' => $provider['settings']['calendar_view'],
                'googleSync' => array_key_exists('google_sync', $provider['settings'])
                    ? filter_var($provider['settings']['google_sync'], FILTER_VALIDATE_BOOLEAN)
                    : NULL,
                'googleCalendar' => array_key_exists('google_calendar', $provider['settings'])
                    ? $provider['settings']['google_calendar']
                    : NULL,
                'googleToken' => array_key_exists('google_token', $provider['settings'])
                    ? $provider['settings']['google_token']
                    : NULL,
                'syncFutureDays' => array_key_exists('sync_future_days', $provider['settings'])
                    ? (int)$provider['settings']['sync_future_days']
                    : NULL,
                'syncPastDays' => array_key_exists('sync_past_days', $provider['settings'])
                    ? (int)$provider['settings']['sync_past_days']
                    : NULL,
                'workingPlan' => array_key_exists('working_plan', $provider['settings'])
                    ? json_decode($provider['settings']['working_plan'], TRUE)
                    : NULL,
                'workingPlanExceptions' => array_key_exists('working_plan_exceptions', $provider['settings'])
                    ? json_decode($provider['settings']['working_plan_exceptions'], TRUE)
                    : NULL,
            ];
        }

        $provider = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database provider record.
     *
     * @param array $provider API resource.
     * @param array|null $base Base provider data to be overwritten with the provided values (useful for updates).
     */
    public function api_decode(array &$provider, array $base = NULL)
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $provider))
        {
            $decoded_resource['id'] = $provider['id'];
        }

        if (array_key_exists('firstName', $provider))
        {
            $decoded_resource['first_name'] = $provider['firstName'];
        }

        if (array_key_exists('lastName', $provider))
        {
            $decoded_resource['last_name'] = $provider['lastName'];
        }

        if (array_key_exists('email', $provider))
        {
            $decoded_resource['email'] = $provider['email'];
        }

        if (array_key_exists('mobile', $provider))
        {
            $decoded_resource['mobile_number'] = $provider['mobile'];
        }

        if (array_key_exists('phone', $provider))
        {
            $decoded_resource['phone_number'] = $provider['phone'];
        }

        if (array_key_exists('address', $provider))
        {
            $decoded_resource['address'] = $provider['address'];
        }

        if (array_key_exists('city', $provider))
        {
            $decoded_resource['city'] = $provider['city'];
        }

        if (array_key_exists('state', $provider))
        {
            $decoded_resource['state'] = $provider['state'];
        }

        if (array_key_exists('zip', $provider))
        {
            $decoded_resource['zip_code'] = $provider['zip'];
        }

        if (array_key_exists('notes', $provider))
        {
            $decoded_resource['notes'] = $provider['notes'];
        }

        if (array_key_exists('timezone', $provider))
        {
            $decoded_resource['timezone'] = $provider['timezone'];
        }

        if (array_key_exists('services', $provider))
        {
            $decoded_resource['services'] = $provider['services'];
        }

        if (array_key_exists('settings', $provider))
        {
            if (empty($decoded_resource['settings']))
            {
                $decoded_resource['settings'] = [];
            }

            if (array_key_exists('username', $provider['settings']))
            {
                $decoded_resource['settings']['username'] = $provider['settings']['username'];
            }

            if (array_key_exists('password', $provider['settings']))
            {
                $decoded_resource['settings']['password'] = $provider['settings']['password'];
            }

            if (array_key_exists('calendarView', $provider['settings']))
            {
                $decoded_resource['settings']['calendar_view'] = $provider['settings']['calendarView'];
            }

            if (array_key_exists('notifications', $provider['settings']))
            {
                $decoded_resource['settings']['notifications'] = filter_var($provider['settings']['notifications'],
                    FILTER_VALIDATE_BOOLEAN);
            }

            if (array_key_exists('googleSync', $provider['settings']))
            {
                $decoded_resource['settings']['google_sync'] = filter_var($provider['settings']['googleSync'],
                    FILTER_VALIDATE_BOOLEAN);
            }

            if (array_key_exists('googleCalendar', $provider['settings']))
            {
                $decoded_resource['settings']['google_calendar'] = $provider['settings']['googleCalendar'];
            }

            if (array_key_exists('googleToken', $provider['settings']))
            {
                $decoded_resource['settings']['google_token'] = $provider['settings']['googleToken'];
            }

            if (array_key_exists('syncFutureDays', $provider['settings']))
            {
                $decoded_resource['settings']['sync_future_days'] = $provider['settings']['syncFutureDays'];
            }

            if (array_key_exists('syncPastDays', $provider['settings']))
            {
                $decoded_resource['settings']['sync_past_days'] = $provider['settings']['syncPastDays'];
            }

            if (array_key_exists('workingPlan', $provider['settings']))
            {
                $decoded_resource['settings']['working_plan'] = json_encode($provider['settings']['workingPlan']);
            }

            if (array_key_exists('workingPlanExceptions', $provider['settings']))
            {
                $decoded_resource['settings']['working_plan_exceptions'] = json_encode($provider['settings']['workingPlanExceptions']);
            }
        }

        $provider = $decoded_resource;
    }
}
