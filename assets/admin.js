/**
 * HJB Payment Country Restrictions – Admin JS
 */
(function ($) {
    'use strict';

    var i18n = hjbPgcr.i18n || {};
    var regions = hjbPgcr.regions || {};

    /**
     * Initialize everything on DOM ready.
     */
    $(function () {
        initSelect2();
        initAccordion();
        initModeToggle();
        initPreview();
        initSave();
    });

    /**
     * Initialize Select2 on country selects.
     */
    function initSelect2() {
        $('.hjb-pgcr-country-select').each(function () {
            $(this).select2({
                width: '100%',
                placeholder: i18n.selectCountries || 'Välj länder...',
                allowClear: true,
            });
            // Trigger preview update when selection changes.
            $(this).on('change', function () {
                updatePreview($(this).closest('.hjb-pgcr-gateway'));
            });
        });
    }

    /**
     * Accordion toggle for gateway cards.
     */
    function initAccordion() {
        $(document).on('click', '.hjb-pgcr-gateway-header', function (e) {
            var $gateway = $(this).closest('.hjb-pgcr-gateway');
            var $body = $gateway.find('.hjb-pgcr-gateway-body');

            $gateway.toggleClass('open');
            $body.slideToggle(200);
        });
    }

    /**
     * Show/hide restriction config based on selected mode.
     */
    function initModeToggle() {
        $(document).on('change', '.hjb-pgcr-mode-field input[type="radio"]', function () {
            var $gateway = $(this).closest('.hjb-pgcr-gateway');
            var mode = $(this).val();
            var $config = $gateway.find('.hjb-pgcr-restrictions-config');

            if (mode === 'none') {
                $config.slideUp(200);
            } else {
                $config.slideDown(200);
            }

            updatePreview($gateway);
            updateGatewayIcon($gateway, mode);
        });
    }

    /**
     * Update the gateway icon based on whether rules are active.
     */
    function updateGatewayIcon($gateway, mode) {
        var $icon = $gateway.find('.hjb-pgcr-gateway-icon .dashicons');
        if (mode !== 'none') {
            $gateway.addClass('has-rules');
            $icon.removeClass('dashicons-shield-alt').addClass('dashicons-shield');
        } else {
            $gateway.removeClass('has-rules');
            $icon.removeClass('dashicons-shield').addClass('dashicons-shield-alt');
        }
    }

    /**
     * Initialize preview for all gateways.
     */
    function initPreview() {
        // Listen for region checkbox changes.
        $(document).on('change', '.hjb-pgcr-checkbox input[type="checkbox"]', function () {
            updatePreview($(this).closest('.hjb-pgcr-gateway'));
        });

        // Initial preview for gateways that already have rules.
        $('.hjb-pgcr-gateway.has-rules').each(function () {
            updatePreview($(this));
        });
    }

    /**
     * Update the live preview for a gateway card.
     */
    function updatePreview($gateway) {
        var $preview = $gateway.find('.hjb-pgcr-preview');
        var $label = $preview.find('.hjb-pgcr-preview-label');
        var $countries = $preview.find('.hjb-pgcr-preview-countries');
        var mode = $gateway.find('.hjb-pgcr-mode-field input[type="radio"]:checked').val();

        // Remove all preview state classes.
        $preview.removeClass('preview-whitelist preview-blacklist preview-none');

        if (mode === 'none') {
            $preview.addClass('preview-none');
            $label.text(i18n.noRestrictions);
            $countries.html('');
            return;
        }

        // Collect selected region country codes.
        var countryCodes = [];
        $gateway.find('.hjb-pgcr-checkbox input[type="checkbox"]:checked').each(function () {
            var slug = $(this).val();
            if (regions[slug] && regions[slug].countries) {
                countryCodes = countryCodes.concat(regions[slug].countries);
            }
        });

        // Collect individually selected countries.
        var selected = $gateway.find('.hjb-pgcr-country-select').val() || [];
        countryCodes = countryCodes.concat(selected);

        // Deduplicate and sort.
        countryCodes = [...new Set(countryCodes)].sort();

        if (mode === 'whitelist') {
            $preview.addClass('preview-whitelist');
            $label.text(i18n.showFor + ' ' + countryCodes.length + ' ' + i18n.countries + ':');
        } else {
            $preview.addClass('preview-blacklist');
            $label.text(i18n.hideFor + ' ' + countryCodes.length + ' ' + i18n.countries + ':');
        }

        // Render country tags.
        if (countryCodes.length === 0) {
            $countries.html('<em>' + (i18n.noRestrictions || 'Inga länder valda') + '</em>');
        } else if (countryCodes.length > 50) {
            // If many countries, show truncated list.
            var shown = countryCodes.slice(0, 50);
            var html = shown.map(function (c) {
                return '<span class="country-tag">' + c + '</span>';
            }).join('');
            html += ' <em>+' + (countryCodes.length - 50) + ' till</em>';
            $countries.html(html);
        } else {
            $countries.html(countryCodes.map(function (c) {
                return '<span class="country-tag">' + c + '</span>';
            }).join(''));
        }
    }

    /**
     * Handle form submission via AJAX.
     */
    function initSave() {
        $('#hjb-pgcr-form').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $spinner = $form.find('.hjb-pgcr-saving-spinner');
            var $btn = $form.find('button[type="submit"]');
            var $notices = $('#hjb-pgcr-notices');

            // Collect settings.
            var settings = {};
            $form.find('.hjb-pgcr-gateway').each(function () {
                var $gw = $(this);
                var gatewayId = $gw.data('gateway-id');

                var mode = $gw.find('.hjb-pgcr-mode-field input[type="radio"]:checked').val() || 'none';

                var selectedRegions = [];
                $gw.find('.hjb-pgcr-checkbox input[type="checkbox"]:checked').each(function () {
                    selectedRegions.push($(this).val());
                });

                var selectedCountries = $gw.find('.hjb-pgcr-country-select').val() || [];

                settings[gatewayId] = {
                    mode: mode,
                    regions: selectedRegions,
                    countries: selectedCountries,
                };
            });

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $notices.empty();

            $.ajax({
                url: hjbPgcr.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hjb_pgcr_save',
                    nonce: hjbPgcr.nonce,
                    settings: settings,
                },
                success: function (response) {
                    if (response.success) {
                        $notices.html(
                            '<div class="notice notice-success is-dismissible"><p>' +
                            (response.data.message || i18n.saved) +
                            '</p></div>'
                        );
                    } else {
                        $notices.html(
                            '<div class="notice notice-error is-dismissible"><p>' +
                            (response.data.message || i18n.error) +
                            '</p></div>'
                        );
                    }
                },
                error: function () {
                    $notices.html(
                        '<div class="notice notice-error is-dismissible"><p>' +
                        i18n.error +
                        '</p></div>'
                    );
                },
                complete: function () {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');

                    // Scroll to top of form to show notice.
                    $('html, body').animate({
                        scrollTop: $notices.offset().top - 50,
                    }, 300);
                },
            });
        });
    }

})(jQuery);
