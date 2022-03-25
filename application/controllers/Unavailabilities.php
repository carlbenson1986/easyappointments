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
 * Unavailabilities controller.
 *
 * Handles the unavailabilities related operations.
 *
 * @package Controllers
 */
class Unavailabilities extends EA_Controller {
    /**
     * Unavailabilities constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('unavailabilities_model');
        $this->load->model('roles_model');

        $this->load->library('accounts');
        $this->load->library('timezones');
    }

    /**
     * Filter unavailabilities by the provided keyword.
     */
    public function search()
    {
        try
        {
            if (cannot('view', PRIV_APPOINTMENTS))
            {
                abort(403, 'Forbidden');
            }

            $keyword = request('keyword', '');

            $order_by = 'name ASC';

            $limit = request('limit', 1000);
            
            $offset = 0;

            $unavailabilities = $this->unavailabilities_model->search($keyword, $limit, $offset, $order_by);

            json_response($unavailabilities);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }

    /**
     * Create a unavailability.
     */
    public function create()
    {
        try
        {
            if (cannot('add', PRIV_APPOINTMENTS))
            {
                abort(403, 'Forbidden');
            }

            $unavailability = json_decode(request('unavailability'), TRUE);

            $unavailability_id = $this->unavailabilities_model->save($unavailability);

            json_response([
                'success' => TRUE,
                'id' => $unavailability_id
            ]);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }

    /**
     * Update a unavailability.
     */
    public function update()
    {
        try
        {
            if (cannot('edit', PRIV_APPOINTMENTS))
            {
                abort(403, 'Forbidden');
            }

            $unavailability = json_decode(request('unavailability'), TRUE);

            $unavailability_id = $this->unavailabilities_model->save($unavailability);

            json_response([
                'success' => TRUE,
                'id' => $unavailability_id
            ]);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }

    /**
     * Remove a unavailability.
     */
    public function destroy()
    {
        try
        {
            if (cannot('delete', PRIV_APPOINTMENTS))
            {
                abort(403, 'Forbidden');
            }

            $unavailability_id = request('unavailability_id');

            $this->unavailabilities_model->delete($unavailability_id);

            json_response([
                'success' => TRUE,
            ]);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }

    /**
     * Find an unavailability.
     */
    public function find()
    {
        try
        {
            if (cannot('view', PRIV_APPOINTMENTS))
            {
                abort(403, 'Forbidden');
            }

            $unavailability_id = request('unavailability_id');

            $unavailability = $this->unavailabilities_model->find($unavailability_id);

            json_response($unavailability);
        }
        catch (Throwable $e)
        {
            json_exception($e);
        }
    }
}
