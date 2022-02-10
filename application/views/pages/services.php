<?php extend('layouts/backend_layout') ?>

<?php section('content') ?>

<div class="container-fluid backend-page" id="services-page">
    <div class="row" id="services">
        <div id="filter-services" class="filter-records col col-12 col-md-5">
            <form class="mb-4">
                <div class="input-group">
                    <input type="text" class="key form-control">
                    
                    <button class="filter btn btn-outline-secondary" type="submit"
                            data-tippy-content="<?= lang('filter') ?>">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <h3><?= lang('services') ?></h3>
            <div class="results"></div>
        </div>

        <div class="record-details column col-12 col-md-5">
            <div class="btn-toolbar mb-4">
                <div class="add-edit-delete-group btn-group">
                    <button id="add-service" class="btn btn-primary">
                        <i class="fas fa-plus-square me-2"></i>
                        <?= lang('add') ?>
                    </button>
                    <button id="edit-service" class="btn btn-outline-secondary" disabled="disabled">
                        <i class="fas fa-edit me-2"></i>
                        <?= lang('edit') ?>
                    </button>
                    <button id="delete-service" class="btn btn-outline-secondary" disabled="disabled">
                        <i class="fas fa-trash-alt me-2"></i>
                        <?= lang('delete') ?>
                    </button>
                </div>

                <div class="save-cancel-group" style="display:none;">
                    <button id="save-service" class="btn btn-primary">
                        <i class="fas fa-check-square me-2"></i>
                        <?= lang('save') ?>
                    </button>
                    <button id="cancel-service" class="btn btn-secondary">
                        <?= lang('cancel') ?>
                    </button>
                </div>
            </div>

            <h3><?= lang('details') ?></h3>

            <div class="form-message alert" style="display:none;"></div>

            <input type="hidden" id="id">

            <div class="mb-3">
                <label class="form-label" for="name">
                    <?= lang('name') ?>
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="name" class="form-control required" maxlength="128" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label"  for="duration">
                    <?= lang('duration_minutes') ?>
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="duration" class="form-control required" type="number" min="<?= EVENT_MINIMUM_DURATION ?>" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label"  for="price">
                    <?= lang('price') ?>
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="price" class="form-control required" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label"  for="currency">
                    <?= lang('currency') ?>

                </label>
                <input id="currency" class="form-control" maxlength="32" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label"  for="category">
                    <?= lang('category') ?>
                </label>
                <select id="category" class="form-control" disabled></select>
            </div>

            <div class="mb-3">
                <label class="form-label"  for="availabilities-type">
                    <?= lang('availabilities_type') ?>

                </label>
                <select id="availabilities-type" class="form-control" disabled>
                    <option value="<?= AVAILABILITIES_TYPE_FLEXIBLE ?>">
                        <?= lang('flexible') ?>
                    </option>
                    <option value="<?= AVAILABILITIES_TYPE_FIXED ?>">
                        <?= lang('fixed') ?>
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label" for="attendants-number" disabled>
                    <?= lang('attendants_number') ?>
                    <span class="text-danger" hidden>*</span>
                </label>
                <input id="attendants-number" class="form-control required" type="number" min="1">
            </div>

            <div class="mb-3">
                <label class="form-label"  for="location">
                    <?= lang('location') ?>

                </label>
                <input id="location" class="form-control" disabled>
            </div>
            
            <div class="mb-3">
                <?php component('color_selection', ['attributes' => 'id="color"']) ?>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="private">
                    <label class="form-check-label" for="private">
                        <?= lang('private') ?>
                    </label>
                </div>

                <div class="form-text text-muted">
                    <small>
                        <?= lang('private_hint') ?>
                    </small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label"  for="description">
                    <?= lang('description') ?>
                </label>
                <textarea id="description" rows="4" class="form-control" disabled></textarea>
            </div>
        </div>
    </div>
</div>

<?php section('content') ?>

<?php section('scripts') ?>

<script src="<?= asset_url('assets/js/utils/message.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/validation.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/url.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/services_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/categories_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/services.js') ?>"></script>

<?php section('scripts') ?>
