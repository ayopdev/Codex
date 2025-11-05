(function ($) {
    'use strict';

    $(function () {
        var settings = window.MSMCMultiCurrencyAdmin || {};
        var $form = $('.msmc-refresh-form');
        if (!$form.length || !settings.restUrl) {
            return;
        }

        $form.on('submit', function (event) {
            event.preventDefault();
            var $button = $form.find('button[type="submit"]');
            var $notice = $form.find('.msmc-refresh-notice');
            if (!$notice.length) {
                $notice = $('<span class="msmc-refresh-notice"></span>').appendTo($form);
            }

            $button.prop('disabled', true);
            $notice.text(settings.loadingText || 'Updating rates…');

            fetch(settings.restUrl, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': settings.nonce,
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Request failed with status ' + response.status);
                    }
                    return response.json();
                })
                .then(function () {
                    $notice.text(settings.successText || 'Exchange rates updated.');
                })
                .catch(function (error) {
                    $notice.text(settings.errorText || ('Error: ' + error.message));
                })
                .finally(function () {
                    $button.prop('disabled', false);
                });
        });
    });
})(jQuery);
