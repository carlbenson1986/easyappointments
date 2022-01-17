/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Booking confirmation page.
 *
 * This module implements the functionality of the booking confirmation page.
 */
App.Pages.BookingConfirmation = (function () {
    const $addToGoogleCalendar = $('#add-to-google-calendar');
    /**
     * Handle Authorization Result
     *
     * This method handles the authorization result. If the user granted access to his data, then the
     * appointment is going to be added to his calendar.
     *
     * @param {Boolean} authResult The user's authorization result.
     */
    function handleAuthResult(authResult) {
        try {
            if (authResult.error) {
                throw new Error('Could not authorize user.');
            }

            // The user has granted access, add the appointment to his calendar. Before making the event.insert request
            // the the event resource data must be prepared.
            const providerData = App.Vars.provider_data;

            const appointmentData = App.Vars.appointment_data;

            // Create a valid Google Calendar API resource for the new event.
            const resource = {
                summary: App.Vars.service_data.name,
                location: App.Vars.company_name,
                start: {
                    dateTime: moment.tz(appointmentData.start_datetime, providerData.timezone).format()
                },
                end: {
                    dateTime: moment.tz(appointmentData.end_datetime, providerData.timezone).format()
                },
                attendees: [
                    {
                        email: App.Vars.provider_data.email,
                        displayName: App.Vars.provider_data.first_name + ' ' + App.Vars.provider_data.last_name
                    }
                ]
            };

            gapi.client.load('calendar', 'v3', function () {
                const request = gapi.client.calendar.events.insert({
                    calendarId: 'primary',
                    resource: resource
                });

                request.execute(function (response) {
                    if (response.error) {
                        throw new Error('Could not add the event to Google Calendar.');
                    }

                    $('#success-frame').append(
                        $('<br/>'),
                        $('<div/>', {
                            'class': 'alert alert-success col-xs-12',
                            'html': [
                                $('<h4/>', {
                                    'text': App.Lang.success
                                }),
                                $('<p/>', {
                                    'text': App.Lang.appointment_added_to_google_calendar
                                }),
                                $('<a/>', {
                                    'href': response.htmlLink,
                                    'text': App.Lang.view_appointment_in_google_calendar
                                })
                            ]
                        })
                    );
                    $addToGoogleCalendar.hide();
                });
            });
        } catch (error) {
            // The user denied access or something else happened, display corresponding message on the screen.
            $('#success-frame').append(
                $('<br/>'),
                $('<div/>', {
                    'class': 'alert alert-danger col-xs-12',
                    'html': [
                        $('<h4/>', {
                            'text': App.Lang.oops_something_went_wrong
                        }),
                        $('<p/>', {
                            'text': App.Lang.could_not_add_to_google_calendar
                        }),
                        $('<pre/>', {
                            'text': error.message
                        })
                    ]
                })
            );
        }
    }

    /**
     * Add the page event listeners.
     */
    function addEventListeners() {
        /**
         * Event: Add Appointment to Google Calendar "Click"
         *
         * This event handler adds the appointment to the users Google Calendar Account. The event is going to
         * be added to the "primary" calendar. In order to use the API the javascript client library provided by
         * Google is necessary.
         */
        $addToGoogleCalendar.on('click', function () {
            gapi.client.setApiKey(App.Vars.google_api_key);

            gapi.auth.authorize(
                {
                    client_id: App.Vars.google_client_id,
                    scope: App.Vars.google_api_scope,
                    immediate: false
                },
                handleAuthResult
            );
        });
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        addEventListeners();
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {};
})();
