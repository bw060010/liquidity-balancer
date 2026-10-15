(function () {
    var form = document.getElementById('deposit-planner-form');
    if (!form) return;

    var MODE_REBALANCE = form.dataset.modeRebalance || 'rebalance';
    var MODE_DEPLOY = form.dataset.modeDeploy || 'deploy_budget';
    var MODE_KEEP_A = form.dataset.modeKeepA || 'keep_a';
    var MODE_KEEP_B = form.dataset.modeKeepB || 'keep_b';

    var capitalSection = document.getElementById('new-capital-section');
    var capitalInput = document.getElementById('new_capital');
    var holdingsSection = document.getElementById('holdings-section');
    var holdingsFields = form.querySelectorAll('.holdings-field');

    function selectedMode() {
        var checked = form.querySelector('input[name="mode"]:checked');
        return checked ? checked.value : MODE_REBALANCE;
    }

    function syncModeUi() {
        var mode = selectedMode();
        var isDeploy = mode === MODE_DEPLOY;

        if (capitalSection) {
            capitalSection.hidden = !isDeploy;
            capitalSection.classList.toggle('field-group--visible', isDeploy);
        }
        if (holdingsSection) {
            holdingsSection.hidden = isDeploy;
        }
        if (capitalInput) {
            if (isDeploy) {
                capitalInput.required = true;
            } else {
                capitalInput.required = false;
                capitalInput.removeAttribute('required');
            }
        }

        holdingsFields.forEach(function (field) {
            if (isDeploy) {
                field.required = false;
                field.removeAttribute('required');
            } else if (mode === MODE_KEEP_A && field.id === 'coinA_adjusted') {
                field.required = true;
            } else if (mode === MODE_KEEP_B && field.id === 'coinB_adjusted') {
                field.required = true;
            } else if (mode === MODE_REBALANCE) {
                field.required = true;
            } else {
                field.required = false;
                field.removeAttribute('required');
            }
        });
    }

    form.querySelectorAll('input[name="mode"]').forEach(function (radio) {
        radio.addEventListener('change', syncModeUi);
    });
    syncModeUi();
})();
