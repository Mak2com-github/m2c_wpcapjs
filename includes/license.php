<?php
if (!defined('ABSPATH')) exit;

/**
 * Licence mak2com : clé signée Ed25519 vérifiée hors ligne, complétée par une vérification en ligne
 * (révocation, suspension) auprès de M2C Licences. Protocole : m2c-licences-server/docs/protocole.md.
 *
 * Format : M2CCJ-<charge utile JSON en base64url>.<signature en base64url>
 *
 * Règle de l'extension : le captcha ne fonctionne qu'avec une licence valide (ou en développement et recette).
 * Licence expirée, révoquée, suspendue, d'un autre domaine ou retirée → le captcha est DÉSACTIVÉ : il n'est plus
 * affiché ni vérifié, les formulaires du site restent utilisables sans captcha. Le renouvellement ou la
 * réactivation de la licence le rétablit aussitôt. Une panne du serveur de licences ne change jamais l'état.
 *
 * Sans clé, hors développement et recette : même chose. Exception : un site déjà configuré lors du passage à la
 * 2.0.0 bénéficie d'un délai de reprise de 30 jours (état « grace ») pour saisir sa clé sans perdre sa protection ;
 * passé ce délai sans clé, le captcha est désactivé.
 *
 * Aucune requête vers le serveur de licences pendant l'affichage d'une page publique : tâche planifiée
 * quotidienne (au plus une vérification par semaine) ou action de l'administrateur.
 *
 * Constantes possibles dans wp-config.php :
 *   define('M2CCJ_LICENSE_KEY', 'M2CCJ-…');   // clé définie hors de la base
 *   define('M2CCJ_LICENSE_SERVER', 'https://…'); // autre serveur de licences (essais)
 */

define('M2C_CAPJS_LICENSE_PRODUCT', 'm2c_wpcapjs');
define('M2C_CAPJS_LICENSE_PREFIX', 'M2CCJ-');
define('M2C_CAPJS_LICENSE_OPTION', 'm2c_capjs_license');

/**
 * Clé publique Ed25519 du produit « m2c_wpcapjs » sur M2C Licences (base64, fiche du produit).
 * Volontairement une fonction sans filtre ni constante : la vérification ne doit pas pouvoir être contournée.
 * Tant que la valeur est celle d'attente, aucune clé n'est reconnue et la publication est refusée par l'action GitHub.
 */
function m2c_capjs_license_public_key() {
    return 'REMPLACER_PAR_LA_CLE_PUBLIQUE_DU_PRODUIT';
}

function m2c_capjs_license_stored() {
    $stored = get_option(M2C_CAPJS_LICENSE_OPTION, array());

    return is_array($stored) ? $stored : array();
}

function m2c_capjs_license_save($stored) {
    update_option(M2C_CAPJS_LICENSE_OPTION, $stored, true);
    m2c_capjs_license_reset_cache();
}

function m2c_capjs_license_from_constant() {
    return defined('M2CCJ_LICENSE_KEY') && M2CCJ_LICENSE_KEY;
}

function m2c_capjs_license_key() {
    if (m2c_capjs_license_from_constant()) return trim((string) M2CCJ_LICENSE_KEY);
    $stored = m2c_capjs_license_stored();

    return trim((string) (isset($stored['key']) ? $stored['key'] : ''));
}

function m2c_capjs_license_b64url_decode($data) {
    $data = strtr((string) $data, '-_', '+/');
    $pad = strlen($data) % 4;
    if ($pad) $data .= str_repeat('=', 4 - $pad);

    return base64_decode($data, true); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
}

/**
 * Vérifie une donnée signée « <charge utile b64url>.<signature b64url> » et renvoie la charge utile décodée.
 * $public_key sert aux tests uniquement : les appels de l'extension utilisent toujours la clé du produit.
 */
function m2c_capjs_license_verify_signed($signed, $public_key = null) {
    $public_key = null === $public_key ? m2c_capjs_license_public_key() : $public_key;
    $parts = explode('.', (string) $signed);
    if (2 !== count($parts)) return null;

    $payload = m2c_capjs_license_b64url_decode($parts[0]);
    $signature = m2c_capjs_license_b64url_decode($parts[1]);
    $public = base64_decode((string) $public_key, true); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
    if (false === $payload || false === $signature || false === $public || 64 !== strlen($signature) || 32 !== strlen($public)) return null;
    if (!function_exists('sodium_crypto_sign_verify_detached')) return null;

    try {
        if (!sodium_crypto_sign_verify_detached($signature, $payload, $public)) return null;
    } catch (Throwable $e) {
        return null;
    }
    $data = json_decode($payload, true);

    return is_array($data) ? $data : null;
}

/**
 * Décode et vérifie une clé de licence.
 *
 * @return array|null Charge utile normalisée, ou null si la clé est mal formée ou la signature invalide.
 */
function m2c_capjs_license_decode($key, $public_key = null) {
    $key = (string) preg_replace('/\s+/', '', (string) $key);
    if (0 !== strpos($key, M2C_CAPJS_LICENSE_PREFIX)) return null;

    $data = m2c_capjs_license_verify_signed(substr($key, strlen(M2C_CAPJS_LICENSE_PREFIX)), $public_key);
    if (!$data || empty($data['id']) || empty($data['domains']) || !is_array($data['domains'])) return null;
    if (isset($data['product']) && M2C_CAPJS_LICENSE_PRODUCT !== $data['product']) return null;

    return array(
        'id'      => (string) $data['id'],
        'client'  => (string) (isset($data['client']) ? $data['client'] : ''),
        'domains' => array_values(array_map('strval', $data['domains'])),
        'plan'    => (string) (isset($data['plan']) ? $data['plan'] : 'site'),
        'expires' => !empty($data['expires']) ? (string) $data['expires'] : null,
        'issued'  => (string) (isset($data['issued']) ? $data['issued'] : ''),
    );
}

/**
 * Hôte du site en minuscules, sans « www. » (comme M2C Licences le normalise).
 */
function m2c_capjs_license_host() {
    $host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));

    return (string) preg_replace('/^www\./', '', $host);
}

function m2c_capjs_license_domain_matches($host, $domains) {
    $host = (string) preg_replace('/^www\./', '', strtolower((string) $host));
    foreach ($domains as $domain) {
        $domain = (string) preg_replace('/^(\*\.|www\.)/', '', strtolower(trim((string) $domain)));
        if ('' !== $domain && ($host === $domain || substr($host, -strlen('.' . $domain)) === '.' . $domain)) return true;
    }

    return false;
}

/**
 * Environnement de développement ou de recette : fonctionne sans clé.
 */
function m2c_capjs_license_is_dev_host($host) {
    $host = strtolower((string) $host);
    if ('localhost' === $host || filter_var($host, FILTER_VALIDATE_IP)) return true;

    foreach (array('.local', '.localhost', '.test', '.invalid', '.example', '.lndo.site', '.ddev.site', '.wp.local') as $suffix) {
        if (substr($host, -strlen($suffix)) === $suffix) return true;
    }
    foreach (array('dev.', 'staging.', 'preprod.', 'recette.', 'test.', 'demo.') as $prefix) {
        if (0 === strpos($host, $prefix)) return true;
    }

    return (bool) apply_filters('m2c_capjs_license_dev_host', false, $host);
}

/**
 * Charge utile vérifiée de la clé courante (vérification mémorisée : sodium_compat est lent en PHP pur).
 */
function m2c_capjs_license_data() {
    $key = m2c_capjs_license_key();
    if ('' === $key) return null;

    $hash = md5($key . '|' . m2c_capjs_license_public_key());
    $stored = m2c_capjs_license_stored();
    if (isset($stored['verified']['hash']) && $stored['verified']['hash'] === $hash) {
        return $stored['verified']['data'];
    }

    $data = m2c_capjs_license_decode($key);
    $stored['verified'] = array('hash' => $hash, 'data' => $data);
    if ($data) $stored['ever_activated'] = true;
    update_option(M2C_CAPJS_LICENSE_OPTION, $stored, true);

    return $data;
}

/**
 * Dernière réponse du serveur, seulement si elle concerne la clé courante.
 */
function m2c_capjs_license_remote_for($data) {
    $stored = m2c_capjs_license_stored();
    $remote = isset($stored['remote']) && is_array($stored['remote']) ? $stored['remote'] : array();
    if (!$data || !isset($remote['id']) || $remote['id'] !== $data['id']) return array();

    return $remote;
}

/**
 * État de la licence pour ce site.
 *
 * state : valid, dev (développement, recette), grace (site configuré avant la 2.0.0, délai de reprise en cours) :
 * le captcha fonctionne ; ended (licence terminée), missing (aucune clé), invalid (clé illisible) : captcha désactivé.
 * reason (état ended) : expired, domain_mismatch, suspended, revoked, removed, grace_ended.
 * Les mises à jour ne sont proposées qu'en valid et dev.
 *
 * @param bool $reset Vide la mémoire de l'état (après un changement de clé ou de réponse du serveur).
 */
function m2c_capjs_license_status($reset = false) {
    static $status = null;
    if ($reset) {
        $status = null;

        return array();
    }
    if (null !== $status) return $status;

    $host = m2c_capjs_license_host();
    $key = m2c_capjs_license_key();
    $data = m2c_capjs_license_data();
    $stored = m2c_capjs_license_stored();
    $remote = m2c_capjs_license_remote_for($data);
    $dev = m2c_capjs_license_is_dev_host($host);
    $reason = '';
    $ever = !empty($stored['ever_activated']);
    $grace_until = isset($stored['grace_until']) ? (int) $stored['grace_until'] : 0;
    $remote_status = isset($remote['status']) ? $remote['status'] : '';

    if ($data && !$dev && !m2c_capjs_license_domain_matches($host, $data['domains'])) {
        $state = 'ended';
        $reason = 'domain_mismatch';
        $message = sprintf('Licence valable pour %s, pas pour %s.', implode(', ', $data['domains']), $host);
    } elseif ($data && !$dev && in_array($remote_status, array('revoked', 'suspended', 'expired', 'domain_mismatch'), true)) {
        // Décision du serveur (révocation, suspension, expiration avancée, domaine retiré de la licence).
        $state = 'ended';
        $reason = $remote_status;
        $messages = array(
            'revoked'         => 'Licence révoquée par mak2com.',
            'suspended'       => 'Licence suspendue par mak2com.',
            'expired'         => 'Licence expirée.',
            'domain_mismatch' => sprintf('Ce site (%s) n’est plus couvert par la licence.', $host),
        );
        $message = $messages[$reason];
    } elseif ($data && !$dev && $data['expires'] && strtotime($data['expires'] . ' 23:59:59') < time()) {
        $state = 'ended';
        $reason = 'expired';
        $message = sprintf('Licence expirée depuis le %s.', wp_date('j F Y', strtotime($data['expires'])));
    } elseif ($data) {
        $state = 'valid';
        $message = $data['expires'] ? sprintf('Licence valide jusqu’au %s.', wp_date('j F Y', strtotime($data['expires']))) : 'Licence valide, sans date d’expiration.';
    } elseif ($dev) {
        $state = 'dev';
        $message = 'Environnement de développement ou de recette : le captcha fonctionne sans licence.';
    } elseif (!$ever && $grace_until > time()) {
        $state = 'grace';
        $message = sprintf(
            'Ce site utilisait CapJS avant l’arrivée de la licence : le captcha continue de fonctionner jusqu’au %s. Saisissez la clé fournie par mak2com avant cette date, sinon il sera désactivé.',
            wp_date('j F Y', $grace_until)
        );
        if ('' !== $key) $message .= ' La clé saisie est invalide : vérifiez qu’elle a été copiée en entier.';
    } elseif ('' !== $key) {
        $state = $ever ? 'ended' : 'invalid';
        $reason = $ever ? 'removed' : '';
        $message = 'Clé de licence invalide. Vérifiez qu’elle a été copiée en entier.';
    } elseif ($ever) {
        $state = 'ended';
        $reason = 'removed';
        $message = 'Aucune licence active sur ce site.';
    } elseif ($grace_until > 0) {
        $state = 'ended';
        $reason = 'grace_ended';
        $message = 'Le délai de reprise de 30 jours est terminé et aucune clé de licence n’a été saisie.';
    } else {
        $state = 'missing';
        $message = 'Saisissez la clé de licence fournie par mak2com pour activer le captcha.';
    }

    if (in_array($state, array('ended', 'missing', 'invalid'), true)) {
        $message .= ' Le captcha est désactivé sur ce site : il n’est plus affiché ni vérifié, les formulaires restent utilisables sans captcha.';
    }

    $status = array(
        'state'       => $state,
        'reason'      => $reason,
        'data'        => $data,
        'host'        => $host,
        'message'     => $message,
        'remote'      => $remote,
        'grace_until' => 'grace' === $state ? $grace_until : 0,
    );

    return $status;
}
function m2c_capjs_license_reset_cache() {
    m2c_capjs_license_status(true);
}

/**
 * Le captcha fonctionne sur le site public : développement, licence valide ou délai de reprise en cours.
 */
function m2c_capjs_license_runs() {
    $status = m2c_capjs_license_status();

    return in_array($status['state'], array('dev', 'valid', 'grace'), true);
}

/**
 * Mises à jour : licence valide (ou développement avec une clé).
 */
function m2c_capjs_license_premium() {
    $status = m2c_capjs_license_status();

    return in_array($status['state'], array('valid', 'dev'), true);
}

/**
 * Enregistre la clé saisie, vérifie sa signature et interroge le serveur.
 */
function m2c_capjs_license_activate($key) {
    $key = (string) preg_replace('/\s+/', '', (string) $key);
    $stored = m2c_capjs_license_stored();
    unset($stored['verified'], $stored['remote']);
    $stored['key'] = $key;
    $stored['activated_at'] = current_time('mysql');
    m2c_capjs_license_save($stored);

    if (m2c_capjs_license_data()) m2c_capjs_license_check(true);
    m2c_capjs_license_reset_cache();
    m2c_capjs_update_clear_cache();

    return m2c_capjs_license_status();
}

/**
 * Retire la clé : le captcha est désactivé (sauf en développement et recette).
 */
function m2c_capjs_license_deactivate() {
    $key = m2c_capjs_license_key();
    if ('' !== $key) m2c_capjs_license_remote_deactivate($key);

    $stored = m2c_capjs_license_stored();
    unset($stored['key'], $stored['verified'], $stored['remote'], $stored['activated_at']);
    m2c_capjs_license_save($stored);
    m2c_capjs_update_clear_cache();

    return m2c_capjs_license_status();
}

/**
 * Installation antérieure à la licence (déjà configurée au passage à la 2.0.0) : délai de reprise de 30 jours
 * pour saisir la clé sans perdre la protection d'un site en service. Passé ce délai sans clé, le captcha est
 * désactivé. Exécutée une seule fois ; une installation neuve n'en bénéficie jamais.
 */
function m2c_capjs_license_upgrade() {
    if (version_compare((string) get_option('m2c_capjs_version', '0'), '2.0.0', '>=')) return;

    $stored = m2c_capjs_license_stored();
    $configured = '' !== (string) get_option('m2c_capjs_server_url', '') && '' !== (string) get_option('m2c_capjs_site_key', '');
    if ($configured && empty($stored['key']) && empty($stored['ever_activated']) && empty($stored['grace_until'])) {
        $stored['grace_until'] = time() + 30 * DAY_IN_SECONDS;
        m2c_capjs_license_save($stored);
    }
    update_option('m2c_capjs_version', '2.0.0');
}

/**
 * Enregistre une réponse signée du serveur (déjà vérifiée par m2c_capjs_license_check).
 */
function m2c_capjs_license_store_remote($remote) {
    $stored = m2c_capjs_license_stored();
    $stored['remote'] = $remote;
    m2c_capjs_license_save($stored);
}

/**
 * Données affichées sur l'écran de licence.
 */
function m2c_capjs_license_public_status() {
    $status = m2c_capjs_license_status();
    $key = m2c_capjs_license_key();

    return array(
        'state'         => $status['state'],
        'reason'        => $status['reason'],
        'message'       => $status['message'],
        'host'          => $status['host'],
        'from_constant' => m2c_capjs_license_from_constant(),
        'key_hint'      => '' !== $key ? substr($key, 0, 10) . '…' . substr($key, -6) : '',
        'license'       => $status['data'],
        'remote'        => $status['remote'],
        'grace_until'   => $status['grace_until'],
    );
}

/* -------------------------------------------------------------------------
 * Dialogue avec le serveur de licences (révocation, suspension, désactivation)
 * ---------------------------------------------------------------------- */

/**
 * Adresse du serveur de licences (surchargée par M2CCJ_LICENSE_SERVER ou le filtre, pour les essais :
 * les réponses restent vérifiées avec la clé publique du produit).
 */
function m2c_capjs_license_server() {
    $url = defined('M2CCJ_LICENSE_SERVER') ? (string) M2CCJ_LICENSE_SERVER : 'https://licences.mak2com.fr';

    return untrailingslashit((string) apply_filters('m2c_capjs_license_server', $url));
}

/**
 * Vérification planifiée : une fois par semaine si tout va bien, une fois par jour si la licence pose problème.
 */
function m2c_capjs_license_maybe_check() {
    $stored = m2c_capjs_license_stored();
    $remote = isset($stored['remote']) && is_array($stored['remote']) ? $stored['remote'] : array();
    $last = isset($stored['last_attempt']) ? (int) $stored['last_attempt'] : 0;
    $status = isset($remote['status']) ? $remote['status'] : 'active';
    $interval = in_array($status, array('active', 'unknown'), true) ? WEEK_IN_SECONDS : DAY_IN_SECONDS;

    if (time() - $last >= $interval) m2c_capjs_license_check();
}

/**
 * Interroge le serveur et mémorise sa réponse signée.
 *
 * @return array{ok:bool,message:string}
 */
function m2c_capjs_license_check($force = false) {
    $key = m2c_capjs_license_key();
    $data = m2c_capjs_license_data();
    if ('' === m2c_capjs_license_server() || '' === $key || !$data) {
        return array('ok' => false, 'message' => 'Aucune licence valide à vérifier.');
    }

    $stored = m2c_capjs_license_stored();
    $stored['last_attempt'] = time();
    update_option(M2C_CAPJS_LICENSE_OPTION, $stored, true);

    $response = wp_remote_post(
        m2c_capjs_license_server() . '/api/v1/check',
        array(
            'timeout' => $force ? 8 : 5,
            'body'    => array(
                'product' => M2C_CAPJS_LICENSE_PRODUCT,
                'key'     => $key,
                'site'    => m2c_capjs_license_host(),
                'version' => M2C_CAPJS_VERSION,
                'wp'      => get_bloginfo('version'),
                'php'     => PHP_VERSION,
            ),
        )
    );
    if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
        m2c_capjs_license_reset_cache();

        return array('ok' => false, 'message' => 'Serveur de licences injoignable : l’état actuel est conservé.');
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    $statement = is_array($body) && isset($body['statement']) ? (string) $body['statement'] : '';
    $last_checked = isset($stored['remote']['checked_at']) ? (int) $stored['remote']['checked_at'] : 0;
    $remote = m2c_capjs_license_parse_statement($statement, $data['id'], $last_checked);
    if (!$remote) {
        m2c_capjs_license_reset_cache();

        return array(
            'ok'      => false,
            'message' => is_array($body) && !empty($body['message']) ? sanitize_text_field((string) $body['message']) : 'Réponse du serveur non reconnue : l’état actuel est conservé.',
        );
    }

    m2c_capjs_license_store_remote($remote);

    return array('ok' => true, 'message' => $remote['message']);
}

/**
 * Vérifie une réponse signée du serveur : signature, produit, licence, site et fraîcheur (une réponse plus
 * ancienne que la dernière retenue est refusée).
 */
function m2c_capjs_license_parse_statement($statement, $license_id, $last_checked_at = 0, $public_key = null) {
    $payload = '' === (string) $statement ? null : m2c_capjs_license_verify_signed($statement, $public_key);
    if (!$payload
        || !isset($payload['type']) || 'status' !== $payload['type']
        || !isset($payload['product']) || M2C_CAPJS_LICENSE_PRODUCT !== $payload['product']
        || !isset($payload['id']) || $license_id !== $payload['id']
        || !isset($payload['site']) || m2c_capjs_license_host() !== $payload['site']
        || (int) (isset($payload['checked_at']) ? $payload['checked_at'] : 0) < $last_checked_at
    ) {
        return null;
    }

    $status = isset($payload['status']) ? (string) $payload['status'] : '';
    if (!in_array($status, array('active', 'unknown', 'suspended', 'revoked', 'expired', 'domain_mismatch'), true)) return null;

    return array(
        'id'         => $license_id,
        'status'     => $status,
        'message'    => sanitize_text_field((string) (isset($payload['message']) ? $payload['message'] : '')),
        'checked_at' => (int) $payload['checked_at'],
    );
}

/**
 * Prévient le serveur que la clé est retirée de ce site (sans attendre sa réponse).
 */
function m2c_capjs_license_remote_deactivate($key) {
    if ('' === m2c_capjs_license_server()) return;

    wp_remote_post(
        m2c_capjs_license_server() . '/api/v1/deactivate',
        array(
            'timeout'  => 3,
            'blocking' => false,
            'body'     => array(
                'product' => M2C_CAPJS_LICENSE_PRODUCT,
                'key'     => $key,
                'site'    => m2c_capjs_license_host(),
            ),
        )
    );
}

/* -------------------------------------------------------------------------
 * Planification : une tâche quotidienne, jamais de requête pendant l'affichage d'une page publique
 * ---------------------------------------------------------------------- */

function m2c_capjs_license_schedule() {
    if (!wp_next_scheduled('m2c_capjs_license_daily')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'm2c_capjs_license_daily');
    }
}

function m2c_capjs_license_unschedule() {
    wp_clear_scheduled_hook('m2c_capjs_license_daily');
}

add_action('m2c_capjs_license_daily', 'm2c_capjs_license_maybe_check');
// Une extension remplacée par ses fichiers n'exécute pas le crochet d'activation : la tâche est aussi planifiée à l'administration.
add_action('admin_init', 'm2c_capjs_license_schedule');
register_activation_hook(M2C_CAPJS_FILE, 'm2c_capjs_license_schedule');
register_deactivation_hook(M2C_CAPJS_FILE, 'm2c_capjs_license_unschedule');
