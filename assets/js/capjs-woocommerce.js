/**
 * CapJS Integration pour WooCommerce
 */

(function($) {
    'use strict';

    var DEBUG = false;

    function debugLog(message, data) {
        if (DEBUG) {
            console.log('[CapJS WooCommerce] ' + message, data || '');
        }
    }

    function initCapJSWidgets() {
        $('.capjs-woocommerce').each(function() {
            var $container = $(this);
            var $widget = $container.find('cap-widget');
            var $form = $container.closest('form');
            var $tokenInput = $container.find('.capjs-wc-token');

            if ($widget.length === 0) return;
            if ($container.data('capjs-initialized')) return;

            $container.data('capjs-initialized', true);
            debugLog('Widget initialisé', $widget.attr('id'));

            var widgetElement = $widget[0];

            var checkTokenInterval = setInterval(function() {
                if (widgetElement && widgetElement.token) {
                    $tokenInput.val(widgetElement.token);
                } else {
                    $tokenInput.val('');
                }
            }, 500);

            $container.data('capjs-interval', checkTokenInterval);
        });
    }

    function resetCapJSWidgets() {
        $('.capjs-woocommerce').each(function() {
            var $container = $(this);
            var oldInterval = $container.data('capjs-interval');
            if (oldInterval) {
                clearInterval(oldInterval);
            }
            $container.data('capjs-initialized', false);
        });
        initCapJSWidgets();
    }

    $(document).ready(function() {
        debugLog('Initialisation au chargement de la page');
        initCapJSWidgets();
    });

    $(document.body).on('updated_checkout', function() {
        debugLog('Checkout mis à jour via AJAX');
        resetCapJSWidgets();
    });

    $(document.body).on('wc_fragments_refreshed', function() {
        debugLog('Fragments WooCommerce rafraîchis');
        resetCapJSWidgets();
    });

    if (window.MutationObserver) {
        var reinitTimer = null;
        var observer = new MutationObserver(function(mutations) {
            var shouldReinit = false;

            for (var i = 0; i < mutations.length; i++) {
                var addedNodes = mutations[i].addedNodes;
                for (var j = 0; j < addedNodes.length; j++) {
                    var node = addedNodes[j];
                    if (node.nodeType === 1) {
                        if ($(node).find('.capjs-woocommerce').length > 0 ||
                            $(node).hasClass('capjs-woocommerce')) {
                            shouldReinit = true;
                            break;
                        }
                    }
                }
                if (shouldReinit) break;
            }

            if (shouldReinit) {
                debugLog('Nouveau widget détecté via MutationObserver');
                if (reinitTimer) clearTimeout(reinitTimer);
                reinitTimer = setTimeout(function() {
                    resetCapJSWidgets();
                }, 200);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Gestion du checkout : n'intercepte QUE si un widget CapJS se trouve
     * directement dans le formulaire checkout (pas dans un formulaire login adjacent)
     */
    $(document).ready(function() {
        var $checkoutForm = $('form.checkout');

        if ($checkoutForm.length === 0) return;

        debugLog('Formulaire de checkout détecté');

        $checkoutForm.on('checkout_place_order', function() {
            var $checkoutWidget = $checkoutForm.find('.capjs-woocommerce cap-widget');

            if ($checkoutWidget.length === 0) {
                debugLog('Pas de widget CapJS dans le checkout, passage autorisé');
                return true;
            }

            var widgetElement = $checkoutWidget[0];
            var token = widgetElement && widgetElement.token ? widgetElement.token : '';
            var $tokenInput = $checkoutForm.find('.capjs-wc-token');

            if (!token) {
                $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();

                $checkoutForm.prepend(
                    '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' +
                    '<ul class="woocommerce-error" role="alert">' +
                    '<li>Veuillez valider le captcha avant de finaliser votre commande.</li>' +
                    '</ul>' +
                    '</div>'
                );

                $('html, body').animate({
                    scrollTop: $checkoutForm.offset().top - 100
                }, 500);

                debugLog('Checkout bloqué - Token manquant');
                return false;
            }

            $tokenInput.val(token);
            debugLog('Checkout autorisé avec token', token);
            return true;
        });
    });

})(jQuery);
