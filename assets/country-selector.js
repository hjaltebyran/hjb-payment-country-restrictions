/**
 * HJB Payment Country Restrictions – Country Modal
 */
(function ($) {
    'use strict';

    var cfg   = window.hjbPgcrSelector || {};
    var $body = $('body');

    $(function () {
        var $overlay = $('#hjb-pgcr-modal-overlay');
        var $select  = $('#hjb-pgcr-modal-country');
        var $btn     = $('#hjb-pgcr-modal-confirm');
        var $spinner = $btn.find('.hjb-pgcr-btn-spinner');

        if ( ! $overlay.length ) {
            hideDuplicateCountryFields();
            wireChangeButton();
            return;
        }

        // Enable confirm button only when a country is selected.
        $select.on('change', function () {
            $btn.prop('disabled', $(this).val() === '');
        });
        $select.trigger('change');

        $btn.on('click', function () {
            var country = $select.val();
            if ( ! country ) return;

            $btn.prop('disabled', true);
            $spinner.show();

            $.ajax({
                url:    cfg.ajaxUrl,
                method: 'POST',
                data:   { action: 'hjb_pgcr_set_country', nonce: cfg.nonce, country: country }
            })
            .done(function (response) {
                if ( response && response.success ) {
                    // Always do a full page reload after country selection.
                    // update_checkout alone is not enough for Svea to reinitialize its iframe,
                    // and for non-Nordic countries Svea's scripts must be dequeued server-side.
                    $overlay.fadeOut(150);
                    window.location.reload();
                } else {
                    var msg = ( response && response.data && response.data.message )
                        ? response.data.message
                        : ( cfg.i18n && cfg.i18n.error ? cfg.i18n.error : 'Error' );
                    alert(msg);
                    $btn.prop('disabled', false);
                    $spinner.hide();
                }
            })
            .fail(function (xhr) {
                console.error('[HJB] AJAX error', xhr.status, xhr.responseText);
                alert(cfg.i18n && cfg.i18n.error ? cfg.i18n.error : 'Error');
                $btn.prop('disabled', false);
                $spinner.hide();
            });
        });

        $select.on('keydown', function (e) {
            if ( e.key === 'Enter' && $(this).val() ) $btn.trigger('click');
        });
    });

    function wireChangeButton() {
        // Simple: redirect to checkout with ?hjb_reset=1 – server clears session and redirects back.
        $body.on('click', '#hjb-pgcr-change-country', function () {
            window.location.href = window.location.pathname + '?hjb_reset=1';
        });
    }

    function hideDuplicateCountryFields() {
        var selectors = [
            '#billing_country_field',
            '.woocommerce-billing-fields #billing_country_field',
            '.woocommerce-shipping-fields #shipping_country_field',
            '.sco-country-select',
            '.svea-checkout-country'
        ].join(',');

        $(selectors).hide();

        if ( window.MutationObserver ) {
            var observer = new MutationObserver(function () { $(selectors).hide(); });
            var target = document.getElementById('sco-checkout')
                      || document.querySelector('.woocommerce-checkout')
                      || document.body;
            observer.observe(target, { childList: true, subtree: true });
            $body.one('updated_checkout', function () { observer.disconnect(); });
        }
    }

}(jQuery));
