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
 * Calendar page.
 *
 * This module implements the functionality of the backend calendar page.
 */
App.Pages.Calendar = (function () {
    const $insertWorkingPlanException = $('#insert-working-plan-exception');

    /**
     * Add the page event listeners.
     */
    function addEventListeners() {
        const $calendarPage = $('#calendar-page');

        $calendarPage.on('click', '#toggle-fullscreen', (event) => {
            const $toggleFullscreen = $(event.target);
            const element = document.documentElement;
            const isFullScreen = document.fullScreenElement || document.mozFullScreen || document.webkitIsFullScreen;

            if (isFullScreen) {
                // Exit fullscreen mode.
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }

                $toggleFullscreen.removeClass('btn-success').addClass('btn-light');
            } else {
                // Switch to fullscreen mode.
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                } else if (element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                }
                $toggleFullscreen.removeClass('btn-light').addClass('btn-success');
            }
        });

        $insertWorkingPlanException.on('click', () => {
            const providerId = $('#select-filter-item').val();

            const provider = vars('available_providers').find((availableProvider) => {
                return Number(availableProvider.id) === Number(providerId);
            });

            if (!provider) {
                throw new Error('Provider could not be found: ' + providerId);
            }

            App.Components.WorkingPlanExceptionsModal.add().done((date, workingPlanException) => {
                const successCallback = () => {
                    App.Layouts.Backend.displayNotification(lang('working_plan_exception_saved'));

                    const workingPlanExceptions = JSON.parse(provider.settings.working_plan_exceptions) || {};

                    workingPlanExceptions[date] = workingPlanException;

                    for (let index in vars('available_providers')) {
                        const availableProvider = vars('available_providers')[index];

                        if (Number(availableProvider.id) === Number(providerId)) {
                            vars('available_providers')[index].settings.working_plan_exceptions =
                                JSON.stringify(workingPlanExceptions);
                            break;
                        }
                    }

                    $('#select-filter-item').trigger('change'); // Update the calendar.
                };

                App.Http.Calendar.saveWorkingPlanException(
                    date,
                    workingPlanException,
                    providerId,
                    successCallback,
                    null
                );
            });
        });
    }

    /**
     * Initialize the module.
     *
     * This function makes the necessary initialization for the default backend calendar page.
     *
     * If this module is used in another page then this function might not be needed.
     *
     * @param {String} view Optional (default), the calendar view to be loaded.
     */
    function initialize() {
        // Load and initialize the calendar view.
        if (vars('calendar_view') === 'table') {
            App.Utils.CalendarTableView.initialize();
        } else {
            App.Utils.CalendarDefaultView.initialize();
        }

        addEventListeners();
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        initialize
    };
})();
