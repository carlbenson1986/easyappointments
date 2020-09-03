<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#35A768">
    <title><?= lang('appointment_registered') . ' - ' . $company_name ?></title>

    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/ext/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset_url('assets/css/frontend.css') ?>">
    <script src="<?= asset_url('assets/ext/fontawesome/js/all.min.js') ?>"></script>

    <link rel="icon" type="image/x-icon" href="<?= asset_url('assets/img/favicon.ico') ?>">
    <link rel="icon" sizes="192x192" href="<?= asset_url('assets/img/logo.png') ?>">
</head>
<body>
    <div id="main" class="container">
        <div class="row wrapper">
            <div id="success-frame" class="col-12 border my-auto frame-container">
                <div>
                    <img id="success-icon" class="mt-0 mb-2" src="<?= base_url('assets/img/success.png') ?>" />
                </div>

                <div>
                    <h3><?= lang('appointment_registered') ?></h3>

                    <p>
                        <?=  lang('appointment_details_was_sent_to_you') ?>
                    </p>

                    <p>
                        <strong>
                            <?= lang('check_spam_folder') ?>
                        </strong>
                    </p>

                    <a href="<?= site_url() ?>" class="btn btn-success btn-large">
                        <i class="far fa-calendar-alt"></i>
                        <?= lang('go_to_booking_page') ?>
                    </a>

                    <?php if(config('google_sync_feature')): ?>
                        <button id="add-to-google-calendar" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            <?= lang('add_to_google_calendar') ?>
                        </button>
                    <?php endif ?>

                    <?php if (isset($exceptions)): ?>
                        <div class="m-2">
                            <h4><?= lang('unexpected_issues') ?></h4>

                            <?php foreach($exceptions as $exception): ?>
                                <?= exceptionToHtml($exception) ?>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/ext/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/ext/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/ext/datejs/date.min.js') ?>"></script>
    <script src="https://apis.google.com/js/client.js"></script>

    <script>
        var GlobalVariables = {
            'csrfToken'         : <?= json_encode($this->security->get_csrf_hash()) ?>,
            'appointmentData'   : <?= json_encode($appointment_data) ?>,
            'providerData'      : <?= json_encode($provider_data) ?>,
            'serviceData'       : <?= json_encode($service_data) ?>,
            'companyName'       : <?= json_encode($company_name) ?>,
            'googleApiKey'      : <?= json_encode(config('google_api_key')) ?>,
            'googleClientId'    : <?= json_encode(config('google_client_id')) ?>,
            'googleApiScope'    : 'https://www.googleapis.com/auth/calendar'
        };

        var EALang = <?= json_encode($this->lang->language) ?>;
    </script>

    <script src="<?= asset_url('assets/js/frontend_book_success.js') ?>"></script>
    <script src="<?= asset_url('assets/js/polyfill.js') ?>"></script>
    <script src="<?= asset_url('assets/js/general_functions.js') ?>"></script>

    <?php google_analytics_script() ?>
</body>
</html>
