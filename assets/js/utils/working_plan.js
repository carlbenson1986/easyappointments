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
 * Working plan utility.
 *
 * This module implements the functionality of working plans.
 */
App.Utils.WorkingPlan = (function () {
    /**
     * Class WorkingPlan
     *
     * Contains the working plan functionality. The working plan DOM elements must be same
     * in every page this class is used.
     *
     * @class WorkingPlan
     */
    const WorkingPlan = function () {
        /**
         * This flag is used when trying to cancel row editing. It is
         * true only whenever the user presses the cancel button.
         *
         * @type {Boolean}
         */
        this.enableCancel = false;

        /**
         * This flag determines whether the jeditables are allowed to submit. It is
         * true only whenever the user presses the save button.
         *
         * @type {Boolean}
         */
        this.enableSubmit = false;
    };

    /**
     * Setup the dom elements of a given working plan.
     *
     * @param {Object} workingPlan Contains the working hours and breaks for each day of the week.
     */
    WorkingPlan.prototype.setup = function (workingPlan) {
        const weekDayId = App.Utils.Date.getWeekdayId(App.Vars.first_weekday);
        const workingPlanSorted = App.Utils.Date.sortWeekDictionary(workingPlan, weekDayId);

        $('.working-plan tbody').empty();
        $('.breaks tbody').empty();

        // Build working plan day list starting with the first weekday as set in the General settings
        const timeFormat = App.Vars.time_format === 'regular' ? 'h:mm a' : 'HH:mm';

        $.each(
            workingPlanSorted,
            function (index, workingDay) {
                const day = this.convertValueToDay(index);

                const dayDisplayName = App.Utils.String.upperCaseFirstLetter(day);

                $('<tr/>', {
                    'html': [
                        $('<td/>', {
                            'html': [
                                $('<div/>', {
                                    'class': 'checkbox form-check',
                                    'html': [
                                        $('<input/>', {
                                            'class': 'form-check-input',
                                            'type': 'checkbox',
                                            'id': index
                                        }),
                                        $('<label/>', {
                                            'class': 'form-check-label',
                                            'text': dayDisplayName,
                                            'for': index
                                        })
                                    ]
                                })
                            ]
                        }),
                        $('<td/>', {
                            'html': [
                                $('<input/>', {
                                    'id': index + '-start',
                                    'class': 'work-start form-control form-control-sm'
                                })
                            ]
                        }),
                        $('<td/>', {
                            'html': [
                                $('<input/>', {
                                    'id': index + '-end',
                                    'class': 'work-start form-control form-control-sm'
                                })
                            ]
                        })
                    ]
                }).appendTo('.working-plan tbody');

                if (workingDay) {
                    $('#' + index).prop('checked', true);
                    $('#' + index + '-start').val(moment(workingDay.start, 'HH:mm').format(timeFormat).toLowerCase());
                    $('#' + index + '-end').val(moment(workingDay.end, 'HH:mm').format(timeFormat).toLowerCase());

                    // Sort day's breaks according to the starting hour
                    workingDay.breaks.sort(function (break1, break2) {
                        // We can do a direct string comparison since we have time based on 24 hours clock.
                        return break1.start.localeCompare(break2.start);
                    });

                    workingDay.breaks.forEach(function (workingDayBreak) {
                        $('<tr/>', {
                            'html': [
                                $('<td/>', {
                                    'class': 'break-day editable',
                                    'text': dayDisplayName
                                }),
                                $('<td/>', {
                                    'class': 'break-start editable',
                                    'text': moment(workingDayBreak.start, 'HH:mm').format(timeFormat).toLowerCase()
                                }),
                                $('<td/>', {
                                    'class': 'break-end editable',
                                    'text': moment(workingDayBreak.end, 'HH:mm').format(timeFormat).toLowerCase()
                                }),
                                $('<td/>', {
                                    'html': [
                                        $('<button/>', {
                                            'type': 'button',
                                            'class': 'btn btn-outline-secondary btn-sm edit-break',
                                            'title': App.Lang.edit,
                                            'html': [
                                                $('<span/>', {
                                                    'class': 'fas fa-edit'
                                                })
                                            ]
                                        }),
                                        $('<button/>', {
                                            'type': 'button',
                                            'class': 'btn btn-outline-secondary btn-sm delete-break',
                                            'title': App.Lang.delete,
                                            'html': [
                                                $('<span/>', {
                                                    'class': 'fas fa-trash-alt'
                                                })
                                            ]
                                        }),
                                        $('<button/>', {
                                            'type': 'button',
                                            'class': 'btn btn-outline-secondary btn-sm save-break d-none',
                                            'title': App.Lang.save,
                                            'html': [
                                                $('<span/>', {
                                                    'class': 'fas fa-check-circle'
                                                })
                                            ]
                                        }),
                                        $('<button/>', {
                                            'type': 'button',
                                            'class': 'btn btn-outline-secondary btn-sm cancel-break d-none',
                                            'title': App.Lang.cancel,
                                            'html': [
                                                $('<span/>', {
                                                    'class': 'fas fa-ban'
                                                })
                                            ]
                                        })
                                    ]
                                })
                            ]
                        }).appendTo('.breaks tbody');
                    });
                } else {
                    $('#' + index).prop('checked', false);
                    $('#' + index + '-start').prop('disabled', true);
                    $('#' + index + '-end').prop('disabled', true);
                }
            }.bind(this)
        );

        // Make break cells editable.
        this.editableDayCell($('.breaks .break-day'));
        this.editableTimeCell($('.breaks').find('.break-start, .break-end'));
    };

    /**
     * Setup the dom elements of a given working plan exception.
     *
     * @param {Object} workingPlanExceptions Contains the working plan exception.
     */
    WorkingPlan.prototype.setupWorkingPlanExceptions = function (workingPlanExceptions) {
        for (const date in workingPlanExceptions) {
            const workingPlanException = workingPlanExceptions[date];

            this.renderWorkingPlanExceptionRow(date, workingPlanException).appendTo('.working-plan-exceptions tbody');
        }
    };

    /**
     * Enable editable break day.
     *
     * This method makes editable the break day cells.
     *
     * @param {Object} $selector The jquery selector ready for use.
     */
    WorkingPlan.prototype.editableDayCell = function ($selector) {
        const weekDays = {};
        weekDays[App.Lang.sunday] = App.Lang.sunday; //'Sunday';
        weekDays[App.Lang.monday] = App.Lang.monday; //'Monday';
        weekDays[App.Lang.tuesday] = App.Lang.tuesday; //'Tuesday';
        weekDays[App.Lang.wednesday] = App.Lang.wednesday; //'Wednesday';
        weekDays[App.Lang.thursday] = App.Lang.thursday; //'Thursday';
        weekDays[App.Lang.friday] = App.Lang.friday; //'Friday';
        weekDays[App.Lang.saturday] = App.Lang.saturday; //'Saturday';

        $selector.editable(
            function (value, settings) {
                return value;
            },
            {
                type: 'select',
                data: weekDays,
                event: 'edit',
                height: '30px',
                submit: '<button type="button" class="d-none submit-editable">Submit</button>',
                cancel: '<button type="button" class="d-none cancel-editable">Cancel</button>',
                onblur: 'ignore',
                onreset: function (settings, td) {
                    if (!this.enableCancel) {
                        return false; // disable ESC button
                    }
                }.bind(this),
                onsubmit: function (settings, td) {
                    if (!this.enableSubmit) {
                        return false; // disable Enter button
                    }
                }.bind(this)
            }
        );
    };

    /**
     * Enable editable break time.
     *
     * This method makes editable the break time cells.
     *
     * @param {Object} $selector The jquery selector ready for use.
     */
    WorkingPlan.prototype.editableTimeCell = function ($selector) {
        $selector.editable(
            function (value, settings) {
                // Do not return the value because the user needs to press the "Save" button.
                return value;
            },
            {
                event: 'edit',
                height: '30px',
                submit: $('<button/>', {
                    'type': 'button',
                    'class': 'd-none submit-editable',
                    'text': App.Lang.save
                }).get(0).outerHTML,
                cancel: $('<button/>', {
                    'type': 'button',
                    'class': 'd-none cancel-editable',
                    'text': App.Lang.cancel
                }).get(0).outerHTML,
                onblur: 'ignore',
                onreset: function (settings, td) {
                    if (!this.enableCancel) {
                        return false; // disable ESC button
                    }
                }.bind(this),
                onsubmit: function (settings, td) {
                    if (!this.enableSubmit) {
                        return false; // disable Enter button
                    }
                }.bind(this)
            }
        );
    };

    /**
     * Enable editable break time.
     *
     * This method makes editable the break time cells.
     *
     * @param {String} date In "Y-m-d" format.
     * @param {Object} workingPlanException Contains exception information.
     */
    WorkingPlan.prototype.renderWorkingPlanExceptionRow = function (date, workingPlanException) {
        const timeFormat = App.Vars.time_format === 'regular' ? 'h:mm a' : 'HH:mm';

        return $('<tr/>', {
            'data': {
                'date': date,
                'workingPlanException': workingPlanException
            },
            'html': [
                $('<td/>', {
                    'class': 'working-plan-exception-date',
                    'text': App.Utils.Date.format(date, App.Vars.date_format, App.Vars.time_format, false)
                }),
                $('<td/>', {
                    'class': 'working-plan-exception--start',
                    'text': moment(workingPlanException.start, 'HH:mm').format(timeFormat).toLowerCase()
                }),
                $('<td/>', {
                    'class': 'working-plan-exception--end',
                    'text': moment(workingPlanException.end, 'HH:mm').format(timeFormat).toLowerCase()
                }),
                $('<td/>', {
                    'html': [
                        $('<button/>', {
                            'type': 'button',
                            'class': 'btn btn-outline-secondary btn-sm edit-working-plan-exception',
                            'title': App.Lang.edit,
                            'html': [
                                $('<span/>', {
                                    'class': 'fas fa-edit'
                                })
                            ]
                        }),
                        $('<button/>', {
                            'type': 'button',
                            'class': 'btn btn-outline-secondary btn-sm delete-working-plan-exception',
                            'title': App.Lang.delete,
                            'html': [
                                $('<span/>', {
                                    'class': 'fas fa-trash-alt'
                                })
                            ]
                        })
                    ]
                })
            ]
        });
    };

    /**
     * Bind the event handlers.
     */
    WorkingPlan.prototype.bindEventHandlers = function () {
        /**
         * Event: Day Checkbox "Click"
         *
         * Enable or disable the time selection for each day.
         */
        $('.working-plan tbody').on('click', 'input:checkbox', function () {
            const id = $(this).attr('id');

            if ($(this).prop('checked') === true) {
                $('#' + id + '-start')
                    .prop('disabled', false)
                    .val('9:00 AM');
                $('#' + id + '-end')
                    .prop('disabled', false)
                    .val('6:00 PM');
            } else {
                $('#' + id + '-start')
                    .prop('disabled', true)
                    .val('');
                $('#' + id + '-end')
                    .prop('disabled', true)
                    .val('');
            }
        });

        /**
         * Event: Add Break Button "Click"
         *
         * A new row is added on the table and the user can enter the new break
         * data. After that he can either press the save or cancel button.
         */
        $('.add-break').on(
            'click',
            function () {
                const timeFormat = App.Vars.time_format === 'regular' ? 'h:mm a' : 'HH:mm';

                const $newBreak = $('<tr/>', {
                    'html': [
                        $('<td/>', {
                            'class': 'break-day editable',
                            'text': App.Lang.sunday
                        }),
                        $('<td/>', {
                            'class': 'break-start editable',
                            'text': moment('12:00', 'HH:mm').format(timeFormat).toLowerCase()
                        }),
                        $('<td/>', {
                            'class': 'break-end editable',
                            'text': moment('14:00', 'HH:mm').format(timeFormat).toLowerCase()
                        }),
                        $('<td/>', {
                            'html': [
                                $('<button/>', {
                                    'type': 'button',
                                    'class': 'btn btn-outline-secondary btn-sm edit-break',
                                    'title': App.Lang.edit,
                                    'html': [
                                        $('<span/>', {
                                            'class': 'fas fa-edit'
                                        })
                                    ]
                                }),
                                $('<button/>', {
                                    'type': 'button',
                                    'class': 'btn btn-outline-secondary btn-sm delete-break',
                                    'title': App.Lang.delete,
                                    'html': [
                                        $('<span/>', {
                                            'class': 'fas fa-trash-alt'
                                        })
                                    ]
                                }),
                                $('<button/>', {
                                    'type': 'button',
                                    'class': 'btn btn-outline-secondary btn-sm save-break d-none',
                                    'title': App.Lang.save,
                                    'html': [
                                        $('<span/>', {
                                            'class': 'fas fa-check-circle'
                                        })
                                    ]
                                }),
                                $('<button/>', {
                                    'type': 'button',
                                    'class': 'btn btn-outline-secondary btn-sm cancel-break d-none',
                                    'title': App.Lang.cancel,
                                    'html': [
                                        $('<span/>', {
                                            'class': 'fas fa-ban'
                                        })
                                    ]
                                })
                            ]
                        })
                    ]
                }).appendTo('.breaks tbody');

                // Bind editable and event handlers.
                this.editableDayCell($newBreak.find('.break-day'));
                this.editableTimeCell($newBreak.find('.break-start, .break-end'));
                $newBreak.find('.edit-break').trigger('click');
            }.bind(this)
        );

        /**
         * Event: Edit Break Button "Click"
         *
         * Enables the row editing for the "Breaks" table rows.
         */
        $(document).on('click', '.edit-break', function () {
            // Reset previous editable table cells.
            const $previousEdits = $(this).closest('table').find('.editable');

            $previousEdits.each(function (index, editable) {
                if (editable.reset) {
                    editable.reset();
                }
            });

            // Make all cells in current row editable.
            $(this).parent().parent().children().trigger('edit');
            $(this)
                .parent()
                .parent()
                .find('.break-start input, .break-end input')
                .timepicker({
                    timeFormat: App.Vars.time_format === 'regular' ? 'h:mm tt' : 'HH:mm',
                    currentText: App.Lang.now,
                    closeText: App.Lang.close,
                    timeOnlyTitle: App.Lang.select_time,
                    timeText: App.Lang.time,
                    hourText: App.Lang.hour,
                    minuteText: App.Lang.minutes
                });
            $(this).parent().parent().find('.break-day select').focus();

            // Show save - cancel buttons.
            const $tr = $(this).closest('tr');
            $tr.find('.edit-break, .delete-break').addClass('d-none');
            $tr.find('.save-break, .cancel-break').removeClass('d-none');
            $tr.find('select,input:text').addClass('form-control form-control-sm');
        });

        /**
         * Event: Delete Break Button "Click"
         *
         * Removes the current line from the "Breaks" table.
         */
        $(document).on('click', '.delete-break', function () {
            $(this).parent().parent().remove();
        });

        /**
         * Event: Cancel Break Button "Click"
         *
         * Bring the ".breaks" table back to its initial state.
         *
         * @param {jQuery.Event} event
         */
        $(document).on(
            'click',
            '.cancel-break',
            function (event) {
                const element = event.target;
                const $modifiedRow = $(element).closest('tr');
                this.enableCancel = true;
                $modifiedRow.find('.cancel-editable').trigger('click');
                this.enableCancel = false;

                $modifiedRow.find('.edit-break, .delete-break').removeClass('d-none');
                $modifiedRow.find('.save-break, .cancel-break').addClass('d-none');
            }.bind(this)
        );

        /**
         * Event: Save Break Button "Click"
         *
         * Save the editable values and restore the table to its initial state.
         *
         * @param {jQuery.Event} event
         */
        $(document).on(
            'click',
            '.save-break',
            function (event) {
                // Break's start time must always be prior to break's end.
                const element = event.target;

                const $modifiedRow = $(element).closest('tr');

                const startMoment = moment($modifiedRow.find('.break-start input').val(), 'HH:mm');

                const endMoment = moment($modifiedRow.find('.break-end input').val(), 'HH:mm');

                if (startMoment.isAfter(endMoment)) {
                    $modifiedRow.find('.break-end input').val(
                        startMoment
                            .add(1, 'hour')
                            .format(App.Vars.time_format === 'regular' ? 'h:mm a' : 'HH:mm')
                            .toLowerCase()
                    );
                }

                this.enableSubmit = true;
                $modifiedRow.find('.editable .submit-editable').trigger('click');
                this.enableSubmit = false;

                $modifiedRow.find('.save-break, .cancel-break').addClass('d-none');
                $modifiedRow.find('.edit-break, .delete-break').removeClass('d-none');
            }.bind(this)
        );

        /**
         * Event: Add Working Plan Exception Button "Click"
         *
         * A new row is added on the table and the user can enter the new working plan exception.
         */
        $(document).on(
            'click',
            '.add-working-plan-exception',
            function () {
                App.Components.WorkingPlanExceptionsModal.add().done(
                    function (date, workingPlanException) {
                        const $tr = null;

                        $('.working-plan-exceptions tbody tr').each(function (index, tr) {
                            if (date === $(tr).data('date')) {
                                $tr = $(tr);
                                return false;
                            }
                        });

                        let $newTr = this.renderWorkingPlanExceptionRow(date, workingPlanException);

                        if ($tr) {
                            $tr.replaceWith($newTr);
                        } else {
                            $newTr.appendTo('.working-plan-exceptions tbody');
                        }
                    }.bind(this)
                );
            }.bind(this)
        );

        /**
         * Event: Edit working plan exception Button "Click"
         *
         * Enables the row editing for the "working plan exception" table rows.
         *
         * @param {jQuery.Event} event
         */
        $(document).on(
            'click',
            '.edit-working-plan-exception',
            function (event) {
                const $tr = $(event.target).closest('tr');
                const date = $tr.data('date');
                const workingPlanException = $tr.data('workingPlanException');

                App.Components.WorkingPlanExceptionsModal.edit(date, workingPlanException).done(
                    function (date, workingPlanException) {
                        $tr.replaceWith(this.renderWorkingPlanExceptionRow(date, workingPlanException));
                    }.bind(this)
                );
            }.bind(this)
        );

        /**
         * Event: Delete working plan exception Button "Click"
         *
         * Removes the current line from the "working plan exceptions" table.
         */
        $(document).on('click', '.delete-working-plan-exception', function () {
            $(this).closest('tr').remove();
        });
    };

    /**
     * Get the working plan settings.
     *
     * @return {Object} Returns the working plan settings object.
     */
    WorkingPlan.prototype.get = function () {
        const workingPlan = {};

        $('.working-plan input:checkbox').each(
            function (index, checkbox) {
                const id = $(checkbox).attr('id');
                if ($(checkbox).prop('checked') === true) {
                    workingPlan[id] = {
                        start: moment($('#' + id + '-start').val(), 'HH:mm').format('HH:mm'),
                        end: moment($('#' + id + '-end').val(), 'HH:mm').format('HH:mm'),
                        breaks: []
                    };

                    $('.breaks tr').each(
                        function (index, tr) {
                            const day = this.convertDayToValue($(tr).find('.break-day').text());

                            if (day === id) {
                                const start = $(tr).find('.break-start').text();
                                const end = $(tr).find('.break-end').text();

                                workingPlan[id].breaks.push({
                                    start: moment(
                                        start,
                                        App.Vars.time_format === 'regular' ? 'h:mm a' : 'HH:mm'
                                    ).format('HH:mm'),
                                    end: moment(end, App.Vars.time_format === 'regular' ? 'h:mm a' : 'HH:mm').format(
                                        'HH:mm'
                                    )
                                });
                            }
                        }.bind(this)
                    );

                    // Sort breaks increasingly by hour within day
                    workingPlan[id].breaks.sort(function (break1, break2) {
                        // We can do a direct string comparison since we have time based on 24 hours clock.
                        return break1.start.localeCompare(break2.start);
                    });
                } else {
                    workingPlan[id] = null;
                }
            }.bind(this)
        );

        return workingPlan;
    };

    /**
     * Get the working plan exceptions settings.
     *
     * @return {Object} Returns the working plan exceptions settings object.
     */
    WorkingPlan.prototype.getWorkingPlanExceptions = function () {
        const workingPlanExceptions = {};

        $('.working-plan-exceptions tbody tr').each(function (index, tr) {
            const $tr = $(tr);
            const date = $tr.data('date');
            workingPlanExceptions[date] = $tr.data('workingPlanException');
        });

        return workingPlanExceptions;
    };

    /**
     * Enables or disables the timepicker functionality from the working plan input text fields.
     *
     * @param {Boolean} [disabled] If true then the timepickers will be disabled.
     */
    WorkingPlan.prototype.timepickers = function (disabled) {
        disabled = disabled || false;

        if (disabled === false) {
            // Set timepickers where needed.
            $('.working-plan input:text').timepicker({
                timeFormat: App.Vars.time_format === 'regular' ? 'h:mm tt' : 'HH:mm',
                currentText: App.Lang.now,
                closeText: App.Lang.close,
                timeOnlyTitle: App.Lang.select_time,
                timeText: App.Lang.time,
                hourText: App.Lang.hour,
                minuteText: App.Lang.minutes,

                onSelect: function (datetime, inst) {
                    // Start time must be earlier than end time.
                    const startMoment = moment($(this).parent().parent().find('.work-start').val(), 'HH:mm');

                    const endMoment = moment($(this).parent().parent().find('.work-end').val(), 'HH:mm');

                    if (startMoment > endMoment) {
                        $(this)
                            .parent()
                            .parent()
                            .find('.work-end')
                            .val(
                                startMoment
                                    .add(1, 'hour')
                                    .format(App.Vars.time_format === 'regular' ? 'h:mm a' : 'HH:mm')
                                    .toLowerCase()
                            );
                    }
                }
            });
        } else {
            $('.working-plan input').timepicker('destroy');
        }
    };

    /**
     * Reset the current plan back to the company's default working plan.
     */
    WorkingPlan.prototype.reset = function () {};

    /**
     * This is necessary for translated days.
     *
     * @param {String} value Day value could be like "monday", "tuesday" etc.
     */
    WorkingPlan.prototype.convertValueToDay = function (value) {
        switch (value) {
            case 'sunday':
                return App.Lang.sunday;
            case 'monday':
                return App.Lang.monday;
            case 'tuesday':
                return App.Lang.tuesday;
            case 'wednesday':
                return App.Lang.wednesday;
            case 'thursday':
                return App.Lang.thursday;
            case 'friday':
                return App.Lang.friday;
            case 'saturday':
                return App.Lang.saturday;
        }
    };

    /**
     * This is necessary for translated days.
     *
     * @param {String} day Day value could be like "Monday", "Tuesday" etc.
     */
    WorkingPlan.prototype.convertDayToValue = function (day) {
        switch (day) {
            case App.Lang.sunday:
                return 'sunday';
            case App.Lang.monday:
                return 'monday';
            case App.Lang.tuesday:
                return 'tuesday';
            case App.Lang.wednesday:
                return 'wednesday';
            case App.Lang.thursday:
                return 'thursday';
            case App.Lang.friday:
                return 'friday';
            case App.Lang.saturday:
                return 'saturday';
        }
    };

    return WorkingPlan;
})();
