<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Account controller.
 *
 * Handles current account related operations.
 *
 * @package Controllers
 */
class Account extends EA_Controller {
    /**
     * Account constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('customers_model');
        $this->load->model('services_model');
        $this->load->model('providers_model');
        $this->load->model('roles_model');
        $this->load->model('settings_model');

        $this->load->library('accounts');
        $this->load->library('google_sync');
        $this->load->library('notifications');
        $this->load->library('synchronization');
        $this->load->library('timezones');
    }

    /**
     * Render the settings page.
     */
    public function index()
    {
        session(['dest_url' => site_url('account')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_USER_SETTINGS))
        {
            if ($user_id)
            {
                abort(403, 'Forbidden');
            }

            redirect('login');

            return;
        }

        $account = $this->users_model->find($user_id);

        script_vars([
            'account' => $account,
        ]);

        html_vars([
            'page_title' => lang('settings'),
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'grouped_timezones' => $this->timezones->to_grouped_array(),
        ]);

        $this->load->view('pages/account');
    }

    /**
     * Save general settings.
     */
    public function save()
    {
        try
        {
            if (cannot('edit', PRIV_USER_SETTINGS))
            {
                throw new Exception('You do not have the required permissions for this task.');
            }

            $account = request('account');

            $account['id'] = session('user_id');

            $this->users_model->only($account, [
                'id',
                'first_name',
                'last_name',
                'email',
                'mobile_number',
                'phone_number',
                'address',
                'city',
                'state',
                'zip_code',
                'notes',
                'timezone',
                'language',
                'settings'
            ]);
            
            $this->users_model->only($account['settings'], [
                'username',
                'password',
                'notifications',
                'calendar_view'
            ]);

            $this->users_model->save($account);

            session([
                'user_email' => $account['email'],
                'username' => $account['settings']['username'],
                'timezone' => $account['timezone'],
            ]);

            response();
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }

    /**
     * Make sure the username is valid and unique in the database.
     */
    public function validate_username()
    {
        try
        {
            $username = request('username');

            $user_id = request('user_id');

            $is_valid = $this->users_model->validate_username($username, $user_id);

            json_response([
                'is_valid' => $is_valid,
            ]);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }
}
