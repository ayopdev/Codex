(function ($) {
    'use strict';

    $(document).on('change', '.msmc-currency-switcher__select', function () {
        var $form = $(this).closest('form');
        if ($form.length) {
            $form.trigger('submit');
        }
    });
})(jQuery);
