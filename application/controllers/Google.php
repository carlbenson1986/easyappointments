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
 * Google Controller
 *
 * This controller handles the Google Calendar synchronization operations.
 *
 * @property CI_Session session
 * @property CI_Loader load
 * @property CI_Input input
 * @property CI_Output output
 * @property CI_Config config
 * @property CI_Lang lang
 * @property CI_Cache cache
 * @property CI_DB_query_builder db
 * @property CI_Security security
 * @property Google_Sync google_sync
 * @property Ics_file ics_file
 * @property Appointments_Model appointments_model
 * @property Providers_Model providers_model
 * @property Services_Model services_model
 * @property Customers_Model customers_model
 * @property Settings_Model settings_model
 * @property Timezones timezones
 * @property Roles_Model roles_model
 * @property Secretaries_Model secretaries_model
 * @property Admins_Model admins_model
 * @property User_Model user_model
 *
 * @package Controllers
 */
class Google extends CI_Controller {
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
    }

    /**
     * Authorize Google Calendar API usage for a specific provider.
     *
     * Since it is required to follow the web application flow, in order to retrieve a refresh token from the Google API
     * service, this method is going to authorize the given provider.
     *
     * @param int $provider_id The provider id, for whom the sync authorization is made.
     */
    public function oauth($provider_id)
    {
        // Store the provider id for use on the callback function.
        $this->session->set_userdata('oauth_provider_id', $provider_id);

        // Redirect browser to google user content page.
        $this->load->library('google_sync');
        header('Location: ' . $this->google_sync->get_auth_url());
    }

    /**
     * Callback method for the Google Calendar API authorization process.
     *
     * Once the user grants consent with his Google Calendar data usage, the Google OAuth service will redirect him back
     * in this page. Here we are going to store the refresh token, because this is what will be used to generate access
     * tokens in the future.
     *
     * IMPORTANT: Because it is necessary to authorize the application using the web server flow (see official
     * documentation of OAuth), every Easy!Appointments installation should use its own calendar api key. So in every
     * api console account, the "http://path-to-Easy!Appointments/google/oauth_callback" should be included in an allowed redirect URL.
     */
    public function oauth_callback()
    {
        if ($this->input->get('code'))
        {
            $this->load->library('Google_sync');
            $token = $this->google_sync->authenticate($this->input->get('code'));

            // Store the token into the database for future reference.
            $oauth_provider_id = $this->session->userdata('oauth_provider_id');

            if ($oauth_provider_id)
            {
                $this->load->model('providers_model');
                $this->providers_model->set_setting('google_sync', TRUE, $oauth_provider_id);
                $this->providers_model->set_setting('google_token', $token, $oauth_provider_id);
                $this->providers_model->set_setting('google_calendar', 'primary', $oauth_provider_id);
            }
            else
            {
                $this->output->set_output('<h1>Sync provider id not specified!</h1>');
            }
        }
        else
        {
            $this->output->set_output('<h1>Authorization Failed!</h1>');
        }
    }

    /**
     * Complete synchronization of appointments between Google Calendar and Easy!Appointments.
     *
     * This method will completely sync the appointments of a provider with his Google Calendar account. The sync period
     * needs to be relatively small, because a lot of API calls might be necessary and this will lead to consuming the
     * Google limit for the Calendar API usage.
     *
     * @param int $provider_id Provider record to be synced.
     */
    public static function sync($provider_id = NULL)
    {
        try
        {
            $framework = get_instance();

            // The user must be logged in.
            $framework->load->library('session');

            if ($framework->session->userdata('user_id') == FALSE && is_cli() === FALSE)
            {
                return;
            }

            if ($provider_id === NULL)
            {
                throw new Exception('Provider id not specified.');
            }

            $framework->load->model('appointments_model');
            $framework->load->model('providers_model');
            $framework->load->model('services_model');
            $framework->load->model('customers_model');
            $framework->load->model('settings_model');

            $provider = $framework->providers_model->get_row($provider_id);

            // Check whether the selected provider has google sync enabled.
            $google_sync = $framework->providers_model->get_setting('google_sync', $provider['id']);

            if ( ! $google_sync)
            {
                throw new Exception('The selected provider has not the google synchronization setting enabled.');
            }

            $google_token = json_decode($framework->providers_model->get_setting('google_token', $provider['id']));
            $framework->load->library('google_sync');
            $framework->google_sync->refresh_token($google_token->refresh_token);

            // Fetch provider's appointments that belong to the sync time period.
            $sync_past_days = $framework->providers_model->get_setting('sync_past_days', $provider['id']);
            $sync_future_days = $framework->providers_model->get_setting('sync_future_days', $provider['id']);
            $start = strtotime('-' . $sync_past_days . ' days', strtotime(date('Y-m-d')));
            $end = strtotime('+' . $sync_future_days . ' days', strtotime(date('Y-m-d')));

            $where_clause = [
                'start_datetime >=' => date('Y-m-d H:i:s', $start),
                'end_datetime <=' => date('Y-m-d H:i:s', $end),
                'id_users_provider' => $provider['id']
            ];

            $appointments = $framework->appointments_model->get_batch($where_clause);

            $company_settings = [
                'company_name' => $framework->settings_model->get_setting('company_name'),
                'company_link' => $framework->settings_model->get_setting('company_link'),
                'company_email' => $framework->settings_model->get_setting('company_email')
            ];

            $provider_timezone = new DateTimeZone($provider['timezone']);

            // Sync each appointment with Google Calendar by following the project's sync protocol (see documentation).
            foreach ($appointments as $appointment)
            {
                if ($appointment['is_unavailable'] == FALSE)
                {
                    $service = $framework->services_model->get_row($appointment['id_services']);
                    $customer = $framework->customers_model->get_row($appointment['id_users_customer']);
                }
                else
                {
                    $service = NULL;
                    $customer = NULL;
                }

                // If current appointment not synced yet, add to Google Calendar.
                if ($appointment['id_google_calendar'] == NULL)
                {
                    $google_event = $framework->google_sync->add_appointment($appointment, $provider,
                        $service, $customer, $company_settings);
                    $appointment['id_google_calendar'] = $google_event->id;
                    $framework->appointments_model->add($appointment); // Save the Google Calendar ID.
                }
                else
                {
                    // Appointment is synced with google calendar.
                    try
                    {
                        $google_event = $framework->google_sync->get_event($provider, $appointment['id_google_calendar']);

                        if ($google_event->status == 'cancelled')
                        {
                            throw new Exception('Event is cancelled, remove the record from Easy!Appointments.');
                        }

                        // If Google Calendar event is different from Easy!Appointments appointment then update
                        // Easy!Appointments record.
                        $is_different = FALSE;
                        $appt_start = strtotime($appointment['start_datetime']);
                        $appt_end = strtotime($appointment['end_datetime']);
                        $event_start = new DateTime($google_event->getStart()->getDateTime());
                        $event_start->setTimezone($provider_timezone);
                        $event_end = new DateTime($google_event->getEnd()->getDateTime());
                        $event_end->setTimezone($provider_timezone);


                        if ($appt_start != $event_start->getTimestamp() || $appt_end != $event_end->getTimestamp()
                            || $appointment['notes'] !== $google_event->getDescription())
                        {
                            $is_different = TRUE;
                        }

                        if ($is_different)
                        {
                            $appointment['start_datetime'] = $event_start->format('Y-m-d H:i:s');
                            $appointment['end_datetime'] = $event_end->format('Y-m-d H:i:s');
                            $appointment['notes'] = $google_event->getDescription();
                            $framework->appointments_model->add($appointment);
                        }

                    }
                    catch (Exception $exception)
                    {
                        // Appointment not found on Google Calendar, delete from Easy!Appoinmtents.
                        $framework->appointments_model->delete($appointment['id']);
                        $appointment['id_google_calendar'] = NULL;
                    }
                }
            }

            // Add Google Calendar events that do not exist in Easy!Appointments.
            $google_calendar = $provider['settings']['google_calendar'];
            $google_events = $framework->google_sync->get_sync_events($google_calendar, $start, $end);

            foreach ($google_events->getItems() as $google_event)
            {
                if ($google_event->getStatus() === 'cancelled')
                {
                    continue;
                }

                if ($google_event->getStart() === NULL || $google_event->getEnd() === NULL)
                {
                    continue;
                }

                $results = $framework->appointments_model->get_batch(['id_google_calendar' => $google_event->getId()]);

                if ( ! empty($results))
                {
                    continue;
                }

                $event_start = new DateTime($google_event->getStart()->getDateTime());
                $event_start->setTimezone($provider_timezone);
                $event_end = new DateTime($google_event->getEnd()->getDateTime());
                $event_end->setTimezone($provider_timezone);

                // Record doesn't exist in the Easy!Appointments, so add the event now.
                $appointment = [
                    'start_datetime' => $event_start->format('Y-m-d H:i:s'),
                    'end_datetime' => $event_end->format('Y-m-d H:i:s'),
                    'is_unavailable' => TRUE,
                    'location' => $google_event->getLocation(),
                    'notes' => $google_event->getSummary() . ' ' . $google_event->getDescription(),
                    'id_users_provider' => $provider_id,
                    'id_google_calendar' => $google_event->getId(),
                    'id_users_customer' => NULL,
                    'id_services' => NULL,
                ];

                $framework->appointments_model->add($appointment);
            }

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $framework->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $framework->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }


}
