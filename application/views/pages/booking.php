<?php
/**
 * @var string $company_name
 * @var string $customer_token
 * @var string $date_format
 * @var string $time_format
 * @var string $first_weekday
 * @var bool $manage_mode
 * @var array $appointment_data
 * @var array $provider_data
 * @var array $customer_data
 * @var array $available_services
 * @var array $available_providers
 * @var array $show_field
 * @var bool $require_phone_number
 * @var string $display_any_provider
 * @var string $display_terms_and_conditions
 * @var string $display_privacy_policy
 * @var string $display_cookie_notice
 */
?>

<?php extend('layouts/booking_layout') ?>

<?php section('content') ?>

<!-- Booking Cancellation Frame -->

<?php component('booking_cancellation_frame') ?>

<!-- Select Service & Provider -->

<?php component('booking_type_step') ?>

<!-- Pick An Appointment Date -->

<?php component('booking_time_step') ?>

<!-- Enter Customer Information -->

<?php component('booking_info_step') ?>

<!-- Appointment Data Confirmation -->

<?php component('booking_final_step') ?>

<?php section('content') ?>


<?php section('scripts') ?>

<script src="<?= asset_url('assets/js/utils/date.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/message.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/validation.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/url.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/booking_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/booking.js') ?>"></script>

<?php section('scripts') ?>
