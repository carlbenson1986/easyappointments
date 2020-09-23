<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.3.2
 * ---------------------------------------------------------------------------- */

/**
 * Class Consent
 *
 * Handles user consent related operations.
 *
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Input $input
 * @property CI_Output $output
 * @property CI_Config $config
 * @property CI_Lang $lang
 * @property CI_Cache $cache
 * @property CI_DB_query_builder $db
 * @property CI_Security $security
 * @property Google_Sync $google_sync
 * @property Ics_file $ics_file
 * @property Appointments_Model $appointments_model
 * @property Providers_Model $providers_model
 * @property Services_Model $services_model
 * @property Customers_Model $customers_model
 * @property Consents_Model consents_model
 * @property Settings_Model $settings_model
 * @property Timezones $timezones
 * @property Roles_Model $roles_model
 * @property Secretaries_Model $secretaries_model
 * @property Admins_Model $admins_model
 * @property User_Model $user_model
 *
 * @package Controllers
 */
class Consents extends CI_Controller {
    /**
     * Save the user's consent.
     */
    public function ajax_save_consent()
    {
        try
        {
            $this->load->model('consents_model');

            $consent = $this->input->post('consent');

            $consent['ip'] = $this->input->ip_address();

            $consent['id'] = $this->consents_model->add($consent);

            $response = [
                'success' => TRUE,
                'id' => $consent['id']
            ];
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
}
