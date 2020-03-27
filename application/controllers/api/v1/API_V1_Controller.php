<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

use EA\Engine\Types\NonEmptyText;

/**
 * API V1 Controller
 *
 * Parent controller class for the API v1 resources. Extend this class instead of the CI_Controller
 * and call the parent constructor.
 *
 * @package Controllers
 * @subpackage API
 */
class API_V1_Controller extends CI_Controller {
    /**
     * Class Constructor
     *
     * This constructor will handle the common operations of each API call.
     *
     * Important: Do not forget to call the this constructor from the child classes.
     *
     * Notice: At the time being only the basic authentication is supported. Make sure
     * that you use the API through SSL/TLS for security.
     */
    public function __construct()
    {
        try
        {
            parent::__construct();

            $this->load->model('settings_model');

            $api_token = $this->settings_model->get_setting('api_token');

            $authorization = new \EA\Engine\Api\V1\Authorization($this);

            if ( ! empty($api_token) && $api_token === $this->_getBearerToken())
            {
                return;
            }

            if ( ! isset($_SERVER['PHP_AUTH_USER']))
            {
                $this->_requestAuthentication();
                return;
            }

            $username = new NonEmptyText($_SERVER['PHP_AUTH_USER']);
            $password = new NonEmptyText($_SERVER['PHP_AUTH_PW']);
            $authorization->basic($username, $password);
        } catch (\Exception $exception)
        {
            exit($this->_handleException($exception));
        }
    }

    /**
     * Returns the bearer token value.
     *
     * @return string
     */
    protected function _getBearerToken()
    {
        $headers = $this->_getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if ( ! empty($headers))
        {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches))
            {
                return $matches[1];
            }
        }
        return NULL;
    }

    /**
     * Returns the authorization header.
     *
     * @return string
     */
    protected function _getAuthorizationHeader()
    {
        $headers = NULL;

        if (isset($_SERVER['Authorization']))
        {
            $headers = trim($_SERVER['Authorization']);
        } else
        {
            if (isset($_SERVER['HTTP_AUTHORIZATION']))
            {
                //Nginx or fast CGI
                $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
            } elseif (function_exists('apache_request_headers'))
            {
                $requestHeaders = apache_request_headers();
                // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
                $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
                //print_r($requestHeaders);
                if (isset($requestHeaders['Authorization']))
                {
                    $headers = trim($requestHeaders['Authorization']);
                }
            }
        }
        return $headers;
    }

    /**
     * Sets request authentication headers.
     */
    protected function _requestAuthentication()
    {
        header('WWW-Authenticate: Basic realm="Easy!Appointments"');
        header('HTTP/1.0 401 Unauthorized');
        exit('You are not authorized to use the API.');
    }

    /**
     * Outputs the required headers and messages for exception handling.
     *
     * Call this method from catch blocks of child controller callbacks.
     *
     * @param \Exception $exception Thrown exception to be outputted.
     */
    protected function _handleException(\Exception $exception)
    {
        $error = [
            'code' => $exception->getCode() ?: 500,
            'message' => $exception->getMessage(),
        ];

        $header = $exception instanceof \EA\Engine\Api\V1\Exception
            ? $exception->getCode() . ' ' . $exception->getHeader()
            : '500 Internal Server Error';

        header('HTTP/1.0 ' . $header);
        header('Content-Type: application/json');

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($error, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    /**
     * Throw an API exception stating that the requested record was not found.
     *
     * @throws \EA\Engine\Api\V1\Exception
     */
    protected function _throwRecordNotFound()
    {
        throw new \EA\Engine\Api\V1\Exception('The requested record was not found!', 404, 'Not Found');
    }
}
