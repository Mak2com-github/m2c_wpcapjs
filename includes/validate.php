<?php
if (!defined('ABSPATH')) exit;

/**
 * Guard CapJS pour Ninja Forms via WP REST API.
 */
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    $route  = $request->get_route();            // ex: /ninja-forms/v2/forms/1/submissions
    $method = $request->get_method();           // ex: POST

    // Cible uniquement les soumissions Ninja Forms
    if ($method === 'POST' && strpos($route, '/ninja-forms/v2/forms/') === 0) {

        // 1) Récupère le token depuis le body (JSON) ou params (x-www-form-urlencoded)
        $params = $request->get_body_params();
        if (empty($params)) {
            // body JSON ?
            $raw = $request->get_body();
            if ($raw) {
                $json = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $params = $json;
                }
            }
        }

        $token = '';
        if (!empty($params['cap-token'])) {
            $token = sanitize_text_field($params['cap-token']);
        } elseif (!empty($params['cap_token'])) {
            $token = sanitize_text_field($params['cap_token']);
        }

        if (!$token) {
            return new WP_Error('capjs_missing', 'Veuillez valider le captcha avant de soumettre le formulaire.', ['status' => 400]);
        }

        // 2) Valide auprès de CapJS
        $server_url = esc_url_raw(get_option('m2c_capjs_server_url', 'https://cap.mak2com.fr'));
        $res = wp_remote_post(trailingslashit($server_url) . 'api/validate', [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode(['token' => $token]),
            'timeout' => 10,
        ]);

        if (is_wp_error($res)) {
            return new WP_Error('capjs_network', 'Erreur de communication avec le service CapJS.', ['status' => 400]);
        }

        $body = json_decode(wp_remote_retrieve_body($res), true);
        if (empty($body['success'])) {
            return new WP_Error('capjs_invalid', 'Captcha invalide, veuillez réessayer.', ['status' => 400]);
        }
    }

    return $result; // OK, on laisse Ninja Forms continuer
}, 10, 3);