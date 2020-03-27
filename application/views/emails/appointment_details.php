<html>
<head>
    <title><?= lang('appointment_details_title') ?></title>
</head>
<body style="font: 13px arial, helvetica, tahoma;">
    <div class="email-container" style="width: 650px; border: 1px solid #eee;">
        <div id="header" style="background-color: #3DD481; border-bottom: 4px solid #1A865F;
                height: 45px; padding: 10px 15px;">
            <strong id="logo" style="color: white; font-size: 20px;
                    text-shadow: 1px 1px 1px #8F8888; margin-top: 10px; display: inline-block">
                    <?= $company_name ?></strong>
        </div>

        <div id="content" style="padding: 10px 15px;">
            <h2><?= $email_title ?></h2>
            <p><?= $email_message ?></p>

            <h2><?= lang('appointment_details_title') ?></h2>
            <table id="appointment-details">
                <tr>
                    <td class="label" style="padding: 3px;font-weight: bold;"><?= lang('service') ?></td>
                    <td style="padding: 3px;"><?= $appointment_service ?></td>
                </tr>
                <tr>
                    <td class="label" style="padding: 3px;font-weight: bold;"><?= lang('provider') ?></td>
                    <td style="padding: 3px;"><?= $appointment_provider ?></td>
                </tr>
                <tr>
                    <td class="label" style="padding: 3px;font-weight: bold;"><?= lang('start') ?></td>
                    <td style="padding: 3px;"><?= $appointment_start_date ?></td>
                </tr>
                <tr>
                    <td class="label" style="padding: 3px;font-weight: bold;"><?= lang('end') ?></td>
                    <td style="padding: 3px;"><?= $appointment_end_date ?></td>
                </tr>
            </table>

            <h2><?= lang('customer_details_title') ?></h2>
            <table id="customer-details">
                <tr>
                    <td class="label" style="padding: 3px;font-weight: bold;"><?= lang('name') ?></td>
                    <td style="padding: 3px;"><?= $customer_name ?></td>
                </tr>
                <tr>
                    <td class="label" style="padding: 3px;font-weight: bold;"><?= lang('email') ?></td>
                    <td style="padding: 3px;"><?= $customer_email ?></td>
                </tr>
                <tr>
                    <td class="label" style="padding: 3px;font-weight: bold;"><?= lang('phone_number') ?></td>
                    <td style="padding: 3px;"><?= $customer_phone ?></td>
                </tr>
                <tr>
                    <td class="label" style="padding: 3px;font-weight: bold;"><?= lang('address') ?></td>
                    <td style="padding: 3px;"><?= $customer_address ?></td>
                </tr>
            </table>

            <h2><?= lang('appointment_link_title') ?></h2>
            <a href="<?= $appointment_link ?>" style="width: 600px;"><?= $appointment_link ?></a>
        </div>

        <div id="footer" style="padding: 10px; text-align: center; margin-top: 10px;
                border-top: 1px solid #EEE; background: #FAFAFA;">
            Powered by
            <a href="https://easyappointments.org" style="text-decoration: none;">Easy!Appointments</a>
            |
            <a href="<?= $company_link ?>" style="text-decoration: none;"><?= $company_name ?></a>
        </div>
    </div>
</body>
</html>
