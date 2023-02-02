<div id="select-google-calendar" class="modal fade">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?= lang('select_google_calendar') ?></h3>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="google-calendar" class="form-label">
                        <?= lang('select_google_calendar_prompt') ?>
                    </label>
                    <select id="google-calendar" class="form-control"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    <?= lang('cancel') ?>
                </button>
                <button id="select-calendar" class="btn btn-primary">
                    <i class="fas fa-check-square me-2"></i>
                    <?= lang('select') ?>
                </button>
            </div>
        </div>
    </div>
</div>
