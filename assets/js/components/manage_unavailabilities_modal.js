/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

/**
 * Backend Calendar Unavailability Events Modal
 *
 * This module implements the unavailability events modal functionality.
 *
 * Old Module Name: BackendCalendarUnavailabilityEventsModal
 */
App.Components.ManageUnavailabilitiesModal = (function () {
    function bindEventHandlers() {
        /**
         * Event: Manage Unavailable Dialog Save Button "Click"
         *
         * Stores the unavailable period changes or inserts a new record.
         */
        $('#manage-unavailable #save-unavailable').on('click', () => {
            const $dialog = $('#manage-unavailable');
            $dialog.find('.modal-message').addClass('d-none');
            $dialog.find('.is-invalid').removeClass('is-invalid');

            const startMoment = moment($dialog.find('#unavailable-start').datetimepicker('getDate'));

            if (!startMoment.isValid()) {
                $dialog.find('#unavailable-start').addClass('is-invalid');

                return;
            }

            const endMoment = moment($dialog.find('#unavailable-end').datetimepicker('getDate'));

            if (!endMoment.isValid()) {
                $dialog.find('#unavailable-end').addClass('is-invalid');

                return;
            }

            if (startMoment.isAfter(endMoment)) {
                // Start time is after end time - display message to user.
                $dialog
                    .find('.modal-message')
                    .text(App.Lang.start_date_before_end_error)
                    .addClass('alert-danger')
                    .removeClass('d-none');

                $dialog.find('#unavailable-start, #unavailable-end').addClass('is-invalid');

                return;
            }

            // Unavailable period records go to the appointments table.
            const unavailable = {
                start_datetime: startMoment.format('YYYY-MM-DD HH:mm:ss'),
                end_datetime: endMoment.format('YYYY-MM-DD HH:mm:ss'),
                notes: $dialog.find('#unavailable-notes').val(),
                id_users_provider: $('#unavailable-provider').val()
            };

            if ($dialog.find('#unavailable-id').val() !== '') {
                // Set the id value, only if we are editing an appointment.
                unavailable.id = $dialog.find('#unavailable-id').val();
            }

            const successCallback = () => {
                // Display success message to the user.
                Backend.displayNotification(App.Lang.unavailable_saved);

                // Close the modal dialog and refresh the calendar appointments.
                $dialog.find('.alert').addClass('d-none');

                $dialog.modal('hide');

                $('#select-filter-item').trigger('change');
            };

            App.Http.Calendar.saveUnavailable(unavailable, successCallback, null);
        });

        /**
         * Event : Insert Unavailable Time Period Button "Click"
         *
         * When the user clicks this button a popup dialog appears and the use can set a time period where
         * he cannot accept any appointments.
         */
        $('#insert-unavailable').on('click', () => {
            resetUnavailableDialog();

            const $dialog = $('#manage-unavailable');

            // Set the default datetime values.
            const startMoment = moment();

            const currentMin = parseInt(startMoment.format('mm'));

            if (currentMin > 0 && currentMin < 15) {
                startMoment.set({minutes: 15});
            } else if (currentMin > 15 && currentMin < 30) {
                startMoment.set({minutes: 30});
            } else if (currentMin > 30 && currentMin < 45) {
                startMoment.set({minutes: 45});
            } else {
                startMoment.add(1, 'hour').set({minutes: 0});
            }

            if ($('.calendar-view').length === 0) {
                $dialog.find('#unavailable-provider').val($('#select-filter-item').val()).closest('.form-group').hide();
            }

            $dialog
                .find('#unavailable-start')
                .val(App.Utils.Date.format(startMoment.toDate(), App.Vars.date_format, App.Vars.time_format, true));
            $dialog
                .find('#unavailable-end')
                .val(
                    App.Utils.Date.format(
                        startMoment.add(1, 'hour').toDate(),
                        App.Vars.date_format,
                        App.Vars.time_format,
                        true
                    )
                );
            $dialog.find('.modal-header h3').text(App.Lang.new_unavailable_title);
            $dialog.modal('show');
        });
    }

    /**
     * Reset unavailable dialog form.
     *
     * Reset the "#manage-unavailable" dialog. Use this method to bring the dialog to the initial state
     * before it becomes visible to the user.
     */
    function resetUnavailableDialog() {
        const $dialog = $('#manage-unavailable');

        $dialog.find('#unavailable-id').val('');

        // Set default time values
        const start = App.Utils.Date.format(moment().toDate(), App.Vars.date_format, App.Vars.time_format, true);

        const end = App.Utils.Date.format(
            moment().add(1, 'hour').toDate(),
            App.Vars.date_format,
            App.Vars.time_format,
            true
        );

        let dateFormat;

        switch (App.Vars.date_format) {
            case 'DMY':
                dateFormat = 'dd/mm/yy';
                break;
            case 'MDY':
                dateFormat = 'mm/dd/yy';
                break;
            case 'YMD':
                dateFormat = 'yy/mm/dd';
                break;
        }

        const firstWeekday = App.Vars.first_weekday;

        const firstWeekdayId = App.Utils.Date.getWeekdayId(firstWeekday);

        $dialog.find('#unavailable-start').datetimepicker({
            dateFormat: dateFormat,
            timeFormat: App.Vars.time_format === 'regular' ? 'h:mm tt' : 'HH:mm',

            // Translation
            dayNames: [
                App.Lang.sunday,
                App.Lang.monday,
                App.Lang.tuesday,
                App.Lang.wednesday,
                App.Lang.thursday,
                App.Lang.friday,
                App.Lang.saturday
            ],
            dayNamesShort: [
                App.Lang.sunday.substr(0, 3),
                App.Lang.monday.substr(0, 3),
                App.Lang.tuesday.substr(0, 3),
                App.Lang.wednesday.substr(0, 3),
                App.Lang.thursday.substr(0, 3),
                App.Lang.friday.substr(0, 3),
                App.Lang.saturday.substr(0, 3)
            ],
            dayNamesMin: [
                App.Lang.sunday.substr(0, 2),
                App.Lang.monday.substr(0, 2),
                App.Lang.tuesday.substr(0, 2),
                App.Lang.wednesday.substr(0, 2),
                App.Lang.thursday.substr(0, 2),
                App.Lang.friday.substr(0, 2),
                App.Lang.saturday.substr(0, 2)
            ],
            monthNames: [
                App.Lang.january,
                App.Lang.february,
                App.Lang.march,
                App.Lang.april,
                App.Lang.may,
                App.Lang.june,
                App.Lang.july,
                App.Lang.august,
                App.Lang.september,
                App.Lang.october,
                App.Lang.november,
                App.Lang.december
            ],
            prevText: App.Lang.previous,
            nextText: App.Lang.next,
            currentText: App.Lang.now,
            closeText: App.Lang.close,
            timeOnlyTitle: App.Lang.select_time,
            timeText: App.Lang.time,
            hourText: App.Lang.hour,
            minuteText: App.Lang.minutes,
            firstDay: firstWeekdayId
        });
        $dialog.find('#unavailable-start').val(start);

        $dialog.find('#unavailable-end').datetimepicker({
            dateFormat: dateFormat,
            timeFormat: App.Vars.time_format === 'regular' ? 'h:mm tt' : 'HH:mm',

            // Translation
            dayNames: [
                App.Lang.sunday,
                App.Lang.monday,
                App.Lang.tuesday,
                App.Lang.wednesday,
                App.Lang.thursday,
                App.Lang.friday,
                App.Lang.saturday
            ],
            dayNamesShort: [
                App.Lang.sunday.substr(0, 3),
                App.Lang.monday.substr(0, 3),
                App.Lang.tuesday.substr(0, 3),
                App.Lang.wednesday.substr(0, 3),
                App.Lang.thursday.substr(0, 3),
                App.Lang.friday.substr(0, 3),
                App.Lang.saturday.substr(0, 3)
            ],
            dayNamesMin: [
                App.Lang.sunday.substr(0, 2),
                App.Lang.monday.substr(0, 2),
                App.Lang.tuesday.substr(0, 2),
                App.Lang.wednesday.substr(0, 2),
                App.Lang.thursday.substr(0, 2),
                App.Lang.friday.substr(0, 2),
                App.Lang.saturday.substr(0, 2)
            ],
            monthNames: [
                App.Lang.january,
                App.Lang.february,
                App.Lang.march,
                App.Lang.april,
                App.Lang.may,
                App.Lang.june,
                App.Lang.july,
                App.Lang.august,
                App.Lang.september,
                App.Lang.october,
                App.Lang.november,
                App.Lang.december
            ],
            prevText: App.Lang.previous,
            nextText: App.Lang.next,
            currentText: App.Lang.now,
            closeText: App.Lang.close,
            timeOnlyTitle: App.Lang.select_time,
            timeText: App.Lang.time,
            hourText: App.Lang.hour,
            minuteText: App.Lang.minutes,
            firstDay: firstWeekdayId
        });
        $dialog.find('#unavailable-end').val(end);

        // Clear the unavailable notes field.
        $dialog.find('#unavailable-notes').val('');
    }

    function initialize() {
        const $unavailabilityProvider = $('#unavailable-provider');

        for (const index in App.Vars.available_providers) {
            const provider = App.Vars.available_providers[index];

            $unavailabilityProvider.append(new Option(provider.first_name + ' ' + provider.last_name, provider.id));
        }

        bindEventHandlers();
    }

    return {
        resetUnavailableDialog,
        initialize
    };
})();
