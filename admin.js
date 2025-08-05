var settings = {
        ajaxUrl: KPJKalkulator.ajaxUrl,
        nonce: KPJKalkulator.nonce,
        selectors: {
            form: '#kpj-pracownik-settings-form',
            submitBtn: '#kpj-pracownik-settings-submit',
            spinner: '#kpj-pracownik-settings-spinner',
            notices: '#kpj-pracownik-admin-notices'
        },
        i18n: KPJKalkulator.i18n
    };

    function initAdminSettings() {
        fetchCurrentSettings()
            .done(function(response){
                if (response.success && response.data) {
                    populateForm(response.data);
                } else {
                    showNotification('error', response.data || settings.i18n.fetch_error);
                }
            })
            .fail(function(){
                showNotification('error', settings.i18n.fetch_error);
            });
        bindAdminForm();
    }

    function bindAdminForm() {
        $(document).on('submit', settings.selectors.form, function(e){
            e.preventDefault();
            saveSettings($(this));
        });
    }

    function fetchCurrentSettings() {
        return $.ajax({
            url: settings.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'kpj_pracownik_get_settings',
                security: settings.nonce
            }
        });
    }

    function populateForm(data) {
        var $form = $(settings.selectors.form);
        $.each(data, function(key, value){
            var $field = $form.find('[name="' + key + '"]');
            if (!$field.length) return;
            var type = $field.attr('type');
            if (type === 'checkbox') {
                $field.prop('checked', !!value);
            } else if (type === 'radio') {
                $field.filter('[value="' + value + '"]').prop('checked', true);
            } else {
                $field.val(value);
            }
        });
    }

    function saveSettings($form) {
        var $btn = $form.find(settings.selectors.submitBtn);
        var $spinner = $form.find(settings.selectors.spinner);
        $btn.prop('disabled', true);
        $spinner.show();

        var data = $form.serializeArray();
        data.push({ name: 'action', value: 'kpj_pracownik_save_settings' });
        data.push({ name: 'security', value: settings.nonce });

        $.ajax({
            url: settings.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: data
        })
        .done(function(response){
            if (response.success) {
                showNotification('success', response.data || settings.i18n.save_success);
            } else {
                showNotification('error', response.data || settings.i18n.save_error);
            }
        })
        .fail(function(){
            showNotification('error', settings.i18n.save_error);
        })
        .always(function(){
            $btn.prop('disabled', false);
            $spinner.hide();
        });
    }

    function showNotification(type, message) {
        var $container = $(settings.selectors.notices);
        if (!$container.length) {
            $container = $('<div/>', {
                id: settings.selectors.notices.replace('#',''),
                class: 'kpj-admin-notices'
            });
            $(settings.selectors.form).before($container);
        }
        var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        var $notice = $('<div/>', {
            class: 'notice ' + noticeClass + ' is-dismissible'
        });
        $notice.append($('<p>').text(message));
        $container.empty().append($notice);
        setTimeout(function(){
            $notice.fadeOut(300, function(){ $(this).remove(); });
        }, 5000);
    }

    $(document).ready(initAdminSettings);
})(jQuery);