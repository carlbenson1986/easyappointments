<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $company_name; ?> | Easy!Appointments</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">

    <link rel="icon" type="image/x-icon"
          href="<?php echo $base_url; ?>/assets/img/favicon.ico">

    <?php
        // ------------------------------------------------------------
        // INCLUDE CSS FILES
        // ------------------------------------------------------------ ?>
    <link
        rel="stylesheet"
        type="text/css"
        href="<?php echo $base_url; ?>/assets/ext/bootstrap/css/bootstrap.min.css">
    <link
        rel="stylesheet"
        type="text/css"
        href="<?php echo $base_url; ?>/assets/ext/jquery-ui/jquery-ui.min.css">
    <link
        rel="stylesheet"
        type="text/css"
        href="<?php echo $base_url; ?>/assets/ext/jquery-qtip/jquery.qtip.min.css">
    <link
        rel="stylesheet"
        type="text/css"
        href="<?php echo $base_url; ?>/assets/ext/jquery-jscrollpane/jquery.jscrollpane.css">
    <link
        rel="stylesheet"
        type="text/css"
        href="<?php echo $base_url; ?>/assets/css/backend.css">
    <link
        rel="stylesheet"
        type="text/css"
        href="<?php echo $base_url; ?>/assets/css/general.css">

    <?php
        // ------------------------------------------------------------
        // INCLUDE JAVASCRIPT FILES
        // ------------------------------------------------------------ ?>
    <script
        type="text/javascript"
        src="<?php echo $base_url; ?>/assets/ext/jquery/jquery.min.js"></script>
    <script
        type="text/javascript"
        src="<?php echo $base_url; ?>/assets/ext/jquery-ui/jquery-ui.min.js"></script>
    <script
        type="text/javascript"
        src="<?php echo $base_url; ?>/assets/ext/jquery-qtip/jquery.qtip.min.js"></script>
    <script
        type="text/javascript"
        src="<?php echo $base_url; ?>/assets/ext/bootstrap/js/bootstrap.min.js"></script>
    <script
        type="text/javascript"
        src="<?php echo $base_url; ?>/assets/ext/datejs/date.js"></script>
    <script
        type="text/javascript"
        src="<?php echo $base_url; ?>/assets/ext/jquery-jscrollpane/jquery.jscrollpane.min.js"></script>
    <script
        type="text/javascript"
        src="<?php echo $base_url; ?>/assets/ext/jquery-mousewheel/jquery.mousewheel.js"></script>

    <script type="text/javascript">
    	// Global JavaScript Variables - Used in all backend pages.
    	var availableLanguages = <?php echo json_encode($this->config->item('available_languages')); ?>;
    	var EALang = <?php echo json_encode($this->lang->language); ?>;
    </script>
</head>

<body>
<div id="header">
    <div id="header-logo">
        <img src="<?php echo $base_url; ?>/assets/img/logo.png">
        <span><?php echo $company_name; ?></span>
    </div>

    <div id="header-menu">
        <?php // CALENDAR MENU ITEM
              // ------------------------------------------------------ ?>
        <?php $hidden = ($privileges[PRIV_APPOINTMENTS]['view'] == TRUE) ? '' : 'hidden'; ?>
        <?php $active = ($active_menu == PRIV_APPOINTMENTS) ? 'active' : ''; ?>
        <a href="<?php echo $base_url; ?>/index.php/backend" class="menu-item <?php echo $hidden; ?><?php echo $active; ?>"
                title="<?php echo $this->lang->line('manage_appointment_record_hint'); ?>">
            <?php echo $this->lang->line('calendar'); ?>
        </a>

        <?php // CUSTOMERS MENU ITEM
              // ------------------------------------------------------ ?>
        <?php $hidden = ($privileges[PRIV_CUSTOMERS]['view'] == TRUE) ? '' : 'hidden'; ?>
        <?php $active = ($active_menu == PRIV_CUSTOMERS) ? 'active' : ''; ?>
        <a href="<?php echo $base_url; ?>/index.php/backend/customers" class="menu-item <?php echo $hidden; ?><?php echo $active; ?>"
                title="<?php echo $this->lang->line('manage_customers_hint'); ?>">
            <?php echo $this->lang->line('customers'); ?>
        </a>

        <?php // SERVICES MENU ITEM
              // ------------------------------------------------------ ?>
        <?php $hidden = ($privileges[PRIV_SERVICES]['view'] == TRUE) ? '' : 'hidden'; ?>
        <?php $active = ($active_menu == PRIV_SERVICES) ? 'active' : ''; ?>
        <a href="<?php echo $base_url; ?>/index.php/backend/services" class="menu-item <?php echo $hidden; ?><?php echo $active; ?>"
                title="<?php echo $this->lang->line('manage_services_hint'); ?>">
            <?php echo $this->lang->line('services'); ?>
        </a>

        <?php // USERS MENU ITEM
              // ------------------------------------------------------ ?>
        <?php $hidden = ($privileges[PRIV_USERS]['view'] ==  TRUE) ? '' : 'hidden'; ?>
        <?php $active = ($active_menu == PRIV_USERS) ? 'active' : ''; ?>
        <a href="<?php echo $base_url; ?>/index.php/backend/users" class="menu-item <?php echo $hidden; ?><?php echo $active; ?>"
                title="<?php echo $this->lang->line('manage_users_hint'); ?>">
            <?php echo $this->lang->line('users'); ?>
        </a>

        <?php // SETTINGS MENU ITEM
              // ------------------------------------------------------ ?>
        <?php $hidden = ($privileges[PRIV_SYSTEM_SETTINGS]['view'] == TRUE
                || $privileges[PRIV_USER_SETTINGS]['view'] == TRUE) ? '' : 'hidden'; ?>
        <?php $active = ($active_menu == PRIV_SYSTEM_SETTINGS) ? 'active' : ''; ?>
        <a href="<?php echo $base_url; ?>/index.php/backend/settings" class="menu-item <?php echo $hidden; ?><?php echo $active; ?>"
                title="<?php echo $this->lang->line('settings_hint'); ?>">
            <?php echo $this->lang->line('settings'); ?>
        </a>

        <?php // LOGOUT MENU ITEM
              // ------------------------------------------------------ ?>
        <a href="<?php echo $base_url; ?>/index.php/user/logout" class="menu-item"
                title="<?php echo $this->lang->line('log_out_hint'); ?>">
            <?php echo $this->lang->line('log_out'); ?>
        </a>

    </div>
</div>

<div id="notification" style="display: none;"></div>

<div id="loading" style="display: none;">
    <img src="<?php echo $base_url; ?>/assets/img/loading.gif" />
</div>
