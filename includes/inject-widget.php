<?php
/**
 * Marque la page comme nécessitant l'injection CapJS
 */

if (!defined('ABSPATH')) exit;

function m2c_capjs_mark_page($content) {
    if (strpos($content, 'ninja-forms-form') === false) return $content;

    // Ajoute juste un marqueur invisible que le JS détectera (optionnel, car le JS détecte déjà automatiquement)
    $marker = '<div id="capjs-injection-marker" style="display:none;"></div>';
    return $content . $marker;
}
add_filter('the_content', 'm2c_capjs_mark_page');
