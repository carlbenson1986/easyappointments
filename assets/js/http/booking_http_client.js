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
 * Booking HTTP client.
 *
 * This module implements the booking related HTTP requests.
 *
 * Old Name: FrontendBookApi
 */
App.Http.Booking = (function () {
    const $selectService = $('#select-service');
    const $selectProvider = $('#select-provider');
    const $availableHours = $('#available-hours');
    const $captchaHint = $('#captcha-hint');
    const $captchaTitle = $('.captcha-title');

    const moment = window.moment;

    let unavailableDatesBackup;
    let selectedDateStringBackup;
    let processingUnavailableDates = false;
    let searchedMonthCounter = 0;

    /**
     * Get Available Hours
     *
     * This function makes an AJAX call and returns the available hours for the selected service,
     * provider and date.
     *
     * @param {String} selectedDate The selected date of the available hours we need.
     */
    function getAvailableHours(selectedDate) {
        $availableHours.empty();

        // Find the selected service duration (it is going to be send within the "data" object).
        const serviceId = $selectService.val();

        // Default value of duration (in minutes).
        let serviceDuration = 15;

        const service = vars('available_services').find(
            (availableService) => Number(availableService.id) === Number(serviceId)
        );

        if (service) {
            serviceDuration = service.duration;
        }

        // If the manage mode is true then the appointment's start date should return as available too.
        const appointmentId = vars('manage_mode') ? vars('appointment_data').id : null;

        // Make ajax post request and get the available hours.
        const url = App.Utils.Url.siteUrl('booking/get_available_hours');

        const data = {
            csrf_token: vars('csrf_token'),
            service_id: $selectService.val(),
            provider_id: $selectProvider.val(),
            selected_date: selectedDate,
            service_duration: serviceDuration,
            manage_mode: Number(vars('manage_mode') || 0),
            appointment_id: appointmentId
        };

        $.post(url, data).done((response) => {
            $availableHours.empty();

            // The response contains the available hours for the selected provider and service. Fill the available
            // hours div with response data.
            if (response.length > 0) {
                let providerId = $selectProvider.val();

                if (providerId === 'any-provider') {
                    for (const availableProvider of vars('available_providers')) {
                        if (availableProvider.services.indexOf(Number(serviceId)) !== -1) {
                            providerId = availableProvider.id; // Use first available provider.
                            break;
                        }
                    }
                }

                const provider = vars('available_providers').find(
                    (availableProvider) => Number(providerId) === Number(availableProvider.id)
                );

                if (!provider) {
                    throw new Error('Could not find provider.');
                }

                const providerTimezone = provider.timezone;
                const selectedTimezone = $('#select-timezone').val();
                const timeFormat = vars('time_format') === 'regular' ? 'h:mm a' : 'HH:mm';

                response.forEach((availableHour) => {
                    const availableHourMoment = moment
                        .tz(selectedDate + ' ' + availableHour + ':00', providerTimezone)
                        .tz(selectedTimezone);

                    if (availableHourMoment.format('YYYY-MM-DD') !== selectedDate) {
                        return; // Due to the selected timezone the available hour belongs to another date.
                    }

                    $availableHours.append(
                        $('<button/>', {
                            'class': 'btn btn-outline-secondary w-100 shadow-none available-hour',
                            'data': {
                                'value': availableHour
                            },
                            'text': availableHourMoment.format(timeFormat)
                        })
                    );
                });

                if (App.Pages.Booking.manageMode) {
                    // Set the appointment's start time as the default selection.
                    $('.available-hour')
                        .removeClass('selected-hour')
                        .filter(
                            (index, availableHourEl) =>
                                $(availableHourEl).text() ===
                                moment(vars('appointment_data').start_datetime).format(timeFormat)
                        )
                        .addClass('selected-hour');
                } else {
                    // Set the first available hour as the default selection.
                    $('.available-hour:eq(0)').addClass('selected-hour');
                }

                App.Pages.Booking.updateConfirmFrame();
            }

            if (!$availableHours.find('.available-hour').length) {
                $availableHours.text(lang('no_available_hours'));
            }
        });
    }

    /**
     * Register an appointment to the database.
     *
     * This method will make an ajax call to the appointments controller that will register
     * the appointment to the database.
     */
    function registerAppointment() {
        const $captchaText = $('.captcha-text');

        if ($captchaText.length > 0) {
            $captchaText.removeClass('is-invalid');
            if ($captchaText.val() === '') {
                $captchaText.addClass('is-invalid');
                return;
            }
        }

        const formData = JSON.parse($('input[name="post_data"]').val());

        const data = {
            csrf_token: vars('csrf_token'),
            post_data: formData
        };

        if ($captchaText.length > 0) {
            data.captcha = $captchaText.val();
        }

        if (vars('manage_mode')) {
            data.exclude_appointment_id = vars('appointment_data').id;
        }

        const url = App.Utils.Url.siteUrl('booking/register');

        const $layer = $('<div/>');

        $.ajax({
            url: url,
            method: 'post',
            data: data,
            dataType: 'json',
            beforeSend: () => {
                $layer.appendTo('body').css({
                    background: 'white',
                    position: 'fixed',
                    top: '0',
                    left: '0',
                    height: '100vh',
                    width: '100vw',
                    opacity: '0.5'
                });
            }
        })
            .done((response) => {
                if (response.captcha_verification === false) {
                    $captchaHint.text(lang('captcha_is_wrong')).fadeTo(400, 1);

                    setTimeout(() => {
                        $captchaHint.fadeTo(400, 0);
                    }, 3000);

                    $captchaTitle.find('button').trigger('click');

                    $captchaText.addClass('is-invalid');

                    return false;
                }

                window.location.href = App.Utils.Url.siteUrl('booking_confirmation/of/' + response.appointment_hash);
            })
            .fail(() => {
                $captchaTitle.find('button').trigger('click');
            })
            .always(() => {
                $layer.remove();
            });
    }

    /**
     * Get the unavailable dates of a provider.
     *
     * This method will fetch the unavailable dates of the selected provider and service and then it will
     * select the first available date (if any). It uses the "FrontendBookApi.getAvailableHours" method to
     * fetch the appointment* hours of the selected date.
     *
     * @param {Number} providerId The selected provider ID.
     * @param {Number} serviceId The selected service ID.
     * @param {String} selectedDateString Y-m-d value of the selected date.
     */
    function getUnavailableDates(providerId, serviceId, selectedDateString) {
        if (processingUnavailableDates) {
            return;
        }

        if (!providerId || !serviceId) {
            return;
        }

        const appointmentId = App.Pages.Booking.manageMode ? vars('appointment_data').id : null;

        const url = App.Utils.Url.siteUrl('booking/get_unavailable_dates');

        const data = {
            provider_id: providerId,
            service_id: serviceId,
            selected_date: encodeURIComponent(selectedDateString),
            csrf_token: vars('csrf_token'),
            manage_mode: Number(App.Pages.Booking.manageMode),
            appointment_id: appointmentId
        };

        $.ajax({
            url: url,
            type: 'GET',
            data: data,
            dataType: 'json'
        }).done((response) => {
            if (response.is_month_unavailable) {
                if (searchedMonthCounter >= 3) {
                    searchedMonthCounter = 0;
                    return; // Stop searching
                }

                searchedMonthCounter++;
                const selectedDateMoment = moment(selectedDateString);
                selectedDateMoment.add(1, 'month');
                const nextSelectedDate = selectedDateMoment.format('YYYY-MM-DD');
                getUnavailableDates(providerId, serviceId, nextSelectedDate);
                return;
            }

            unavailableDatesBackup = response;
            selectedDateStringBackup = selectedDateString;
            applyUnavailableDates(response, selectedDateString, true);
        });
    }

    function applyPreviousUnavailableDates() {
        applyUnavailableDates(unavailableDatesBackup, selectedDateStringBackup);
    }

    function applyUnavailableDates(unavailableDates, selectedDateString, setDate) {
        setDate = setDate || false;

        processingUnavailableDates = true;

        // Select first enabled date.
        const selectedDateMoment = moment(selectedDateString);
        const selectedDate = selectedDateMoment.toDate();
        const numberOfDays = selectedDateMoment.daysInMonth();

        if (setDate && !vars('manage_mode')) {
            for (let i = 1; i <= numberOfDays; i++) {
                const currentDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), i);

                if (unavailableDates.indexOf(moment(currentDate).format('YYYY-MM-DD')) === -1) {
                    $('#select-date')[0]._flatpickr.setDate(currentDate);
                    getAvailableHours(moment(currentDate).format('YYYY-MM-DD'));
                    break;
                }
            }
        }

        // If all the days are unavailable then hide the appointments hours.
        if (unavailableDates.length === numberOfDays) {
            $availableHours.text(lang('no_available_hours'));
        }

        // Grey out unavailable dates.
        $('#select-date')[0]._flatpickr.set('disable', unavailableDates.map(unavailableDate => new Date(unavailableDate)));

        const dateQueryParam = App.Utils.Url.queryParam('date');

        if (dateQueryParam) {
            const dateQueryParamMoment = moment(dateQueryParam);

            if (dateQueryParamMoment.isValid() && !unavailableDates.includes(dateQueryParam) && dateQueryParamMoment.format('YYYY-MM') === selectedDateMoment.format('YYYY-MM')) {
                $('#select-date')[0]._flatpickr.setDate(dateQueryParamMoment.toDate());
            }
        }

        processingUnavailableDates = false;
    }

    /**
     * Delete personal information.
     *
     * @param {Number} customerToken Customer unique token.
     */
    function deletePersonalInformation(customerToken) {
        const url = App.Utils.Url.siteUrl('privacy/delete_personal_information');

        const data = {
            csrf_token: vars('csrf_token'),
            customer_token: customerToken
        };

        $.post(url, data).done(() => {
            window.location.href = vars('base_url');
        });
    }

    return {
        registerAppointment,
        getAvailableHours,
        getUnavailableDates,
        applyPreviousUnavailableDates,
        deletePersonalInformation
    };
})();
