/**
 * CapJS Integration pour WooCommerce
 *
 * Gère l'initialisation et la soumission du widget CapJS
 * pour tous les formulaires WooCommerce
 */

(function($) {
    'use strict';

    // Mode debug (mettre à true pour activer les logs)
    var DEBUG = false;

    /**
     * Log de debug
     */
    function debugLog(message, data) {
        if (DEBUG) {
            console.log('[CapJS WooCommerce] ' + message, data || '');
        }
    }

    /**
     * Initialiser les widgets CapJS
     */
    function initCapJSWidgets() {
        $('.capjs-woocommerce cap-widget').each(function() {
            var $widget = $(this);
            var $container = $widget.closest('.capjs-widget-container');
            var $form = $widget.closest('form');
            var $tokenInput = $container.find('.capjs-wc-token');

            // Skip si déjà initialisé
            if ($widget.data('capjs-initialized')) {
                return;
            }

            $widget.data('capjs-initialized', true);
            debugLog('Widget initialisé', $widget.attr('id'));

            var widgetElement = $widget[0];

            // Vérifier le token régulièrement et le mettre à jour dans le champ caché
            var checkTokenInterval = setInterval(function() {
                if (widgetElement && widgetElement.token) {
                    $tokenInput.val(widgetElement.token);
                    debugLog('Token mis à jour', widgetElement.token);
                } else {
                    $tokenInput.val('');
                }
            }, 500);

            // Nettoyer l'interval si le widget est supprimé
            $widget.on('remove', function() {
                clearInterval(checkTokenInterval);
            });

            // Intercepter la soumission du formulaire
            $form.on('submit', function(e) {
                var token = widgetElement && widgetElement.token ? widgetElement.token : '';

                if (!token) {
                    e.preventDefault();

                    // Afficher un message d'erreur
                    var $errorContainer = $container.find('.capjs-error');
                    if ($errorContainer.length === 0) {
                        $errorContainer = $('<div class="capjs-error woocommerce-error" role="alert"></div>');
                        $container.append($errorContainer);
                    }

                    $errorContainer.html('Veuillez valider le captcha avant de soumettre le formulaire.');

                    // Scroller vers le message d'erreur
                    $('html, body').animate({
                        scrollTop: $errorContainer.offset().top - 100
                    }, 500);

                    debugLog('Soumission bloquée - Token manquant');
                    return false;
                }

                // S'assurer que le token est dans le champ caché
                $tokenInput.val(token);
                debugLog('Soumission autorisée avec token', token);
            });
        });
    }

    /**
     * Réinitialiser les widgets après un événement AJAX
     */
    function resetCapJSWidgets() {
        $('.capjs-woocommerce cap-widget').each(function() {
            $(this).data('capjs-initialized', false);
        });
        initCapJSWidgets();
    }

    /**
     * Initialisation au chargement de la page
     */
    $(document).ready(function() {
        debugLog('Initialisation au chargement de la page');
        initCapJSWidgets();
    });

    /**
     * Réinitialiser après les mises à jour AJAX de WooCommerce
     */
    $(document.body).on('updated_checkout', function() {
        debugLog('Checkout mis à jour via AJAX');
        resetCapJSWidgets();
    });

    /**
     * Réinitialiser après la mise à jour des fragments (panier, etc.)
     */
    $(document.body).on('wc_fragments_refreshed', function() {
        debugLog('Fragments WooCommerce rafraîchis');
        resetCapJSWidgets();
    });

    /**
     * Réinitialiser après un changement de variation de produit
     */
    $(document.body).on('found_variation', function() {
        debugLog('Variation de produit trouvée');
        resetCapJSWidgets();
    });

    /**
     * Observer les changements du DOM pour les formulaires chargés dynamiquement
     */
    if (window.MutationObserver) {
        var observer = new MutationObserver(function(mutations) {
            var shouldReinit = false;

            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            // Vérifier si un widget CapJS a été ajouté
                            if ($(node).find('.capjs-woocommerce').length > 0 ||
                                $(node).hasClass('capjs-woocommerce')) {
                                shouldReinit = true;
                            }
                        }
                    });
                }
            });

            if (shouldReinit) {
                debugLog('Nouveau widget détecté via MutationObserver');
                setTimeout(function() {
                    resetCapJSWidgets();
                }, 100);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Gestion spécifique pour le formulaire de checkout
     */
    $(document).ready(function() {
        var $checkoutForm = $('form.checkout');

        if ($checkoutForm.length > 0) {
            debugLog('Formulaire de checkout détecté');

            // Intercepter la validation du checkout
            $checkoutForm.on('checkout_place_order', function() {
                var $widget = $('.capjs-woocommerce cap-widget');

                if ($widget.length === 0) {
                    return true; // Pas de widget, laisser passer
                }

                var widgetElement = $widget[0];
                var token = widgetElement && widgetElement.token ? widgetElement.token : '';
                var $tokenInput = $('.capjs-wc-token');

                if (!token) {
                    // Afficher une erreur WooCommerce
                    $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();

                    $checkoutForm.prepend(
                        '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' +
                        '<ul class="woocommerce-error" role="alert">' +
                        '<li>Veuillez valider le captcha avant de finaliser votre commande.</li>' +
                        '</ul>' +
                        '</div>'
                    );

                    // Scroller vers le haut
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
        }
    });

})(jQuery);
