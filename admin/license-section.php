<?php
if (!defined('ABSPATH')) exit;

/**
 * Écran de licence (Réglages > CapJS) : état, saisie et retrait de la clé, vérification manuelle.
 */

/**
 * Traitement des formulaires de licence (admin-post.php) : activer, retirer, vérifier.
 */
add_action('admin_post_m2c_capjs_license', function() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Vous n’avez pas le droit de modifier la licence.', 'capjs-integration'), '', array('response' => 403));
    }
    check_admin_referer('m2c_capjs_license');

    $op = isset($_POST['op']) ? sanitize_key(wp_unslash($_POST['op'])) : '';
    $notice = '';
    if ('activate' === $op && !m2c_capjs_license_from_constant()) {
        $key = isset($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : '';
        m2c_capjs_license_activate($key);
        $notice = 'activated';
    } elseif ('deactivate' === $op && !m2c_capjs_license_from_constant()) {
        m2c_capjs_license_deactivate();
        $notice = 'deactivated';
    } elseif ('check' === $op) {
        $result = m2c_capjs_license_check(true);
        set_transient('m2c_capjs_check_message_' . get_current_user_id(), $result['message'], 5 * MINUTE_IN_SECONDS);
        $notice = 'checked';
    }

    wp_safe_redirect(add_query_arg(array('page' => 'm2c-capjs', 'licence' => $notice), admin_url('options-general.php')) . '#m2c-capjs-licence');
    exit;
});

/**
 * Sans licence valide, le captcha est désactivé (ou le sera à la fin du délai de reprise) : le dire sur toutes les
 * pages de l'administration, pour ne pas laisser croire qu'un site est protégé alors qu'il ne l'est pas.
 */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;

    $status = m2c_capjs_license_status();
    if ('grace' === $status['state']) {
        $class = 'notice-warning';
    } elseif (in_array($status['state'], array('ended', 'missing', 'invalid'), true)) {
        $class = 'notice-error';
    } else {
        return;
    }

    printf(
        '<div class="notice %s"><p><strong>CapJS :</strong> %s <a href="%s">Gérer la licence</a></p></div>',
        esc_attr($class),
        esc_html($status['message']),
        esc_url(admin_url('options-general.php?page=m2c-capjs#m2c-capjs-licence'))
    );
});

/**
 * Bloc « Licence » de la page de réglages.
 */
function m2c_capjs_license_section() {
    $public = m2c_capjs_license_public_status();
    $data = $public['license'];
    $remote = $public['remote'];
    $notices = array(
        'activated'   => 'Clé de licence enregistrée.',
        'deactivated' => 'Clé de licence retirée.',
        'checked'     => '',
    );
    $notice = isset($_GET['licence']) ? sanitize_key(wp_unslash($_GET['licence'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $check_key = 'm2c_capjs_check_message_' . get_current_user_id();
    $check = 'checked' === $notice ? get_transient($check_key) : false;
    if (false !== $check) delete_transient($check_key);
    $reasons = array(
        'expired'         => 'Licence expirée',
        'domain_mismatch' => 'Domaine non couvert',
        'suspended'       => 'Licence suspendue',
        'revoked'         => 'Licence révoquée',
        'removed'         => 'Clé retirée',
        'grace_ended'     => 'Délai de reprise terminé',
    );
    $tones = array('valid' => 'success', 'dev' => 'success', 'grace' => 'warning', 'ended' => 'error', 'missing' => 'error', 'invalid' => 'error');
    $tone = isset($tones[$public['state']]) ? $tones[$public['state']] : 'info';
    ?>
    <h2 id="m2c-capjs-licence">Licence</h2>
    <?php if (isset($notices[$notice]) && '' !== $notices[$notice]): ?>
        <div class="notice notice-success inline" role="status"><p><?php echo esc_html($notices[$notice]); ?></p></div>
    <?php endif; ?>
    <?php if (is_string($check) && '' !== $check): ?>
        <div class="notice notice-info inline" role="status"><p><?php echo esc_html($check); ?></p></div>
    <?php endif; ?>
    <div class="notice notice-<?php echo esc_attr($tone); ?> inline"><p><?php echo esc_html($public['message']); ?></p></div>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">Site</th>
            <td><?php echo esc_html($public['host']); ?></td>
        </tr>
        <?php if ($public['reason']): ?>
        <tr>
            <th scope="row">Motif</th>
            <td><?php echo esc_html(isset($reasons[$public['reason']]) ? $reasons[$public['reason']] : $public['reason']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($data): ?>
        <tr>
            <th scope="row">Numéro</th>
            <td><?php echo esc_html($data['id']); ?></td>
        </tr>
        <tr>
            <th scope="row">Client</th>
            <td><?php echo esc_html($data['client']); ?></td>
        </tr>
        <tr>
            <th scope="row">Domaines couverts</th>
            <td><?php echo esc_html(implode(', ', $data['domains'])); ?> (sous-domaines compris)</td>
        </tr>
        <tr>
            <th scope="row">Expiration</th>
            <td><?php echo esc_html($data['expires'] ? wp_date('j F Y', strtotime($data['expires'])) : 'Aucune'); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($remote): ?>
        <tr>
            <th scope="row">Dernière vérification</th>
            <td><?php echo esc_html(wp_date('j F Y à H:i', (int) $remote['checked_at']) . ' — ' . $remote['message']); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($public['key_hint']): ?>
        <tr>
            <th scope="row">Clé</th>
            <td><code><?php echo esc_html($public['key_hint']); ?></code><?php echo $public['from_constant'] ? ' (définie dans wp-config.php)' : ''; ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <?php if ($public['from_constant']): ?>
        <p>La clé est définie dans <code>wp-config.php</code> (constante <code>M2CCJ_LICENSE_KEY</code>).</p>
    <?php else: ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="m2c_capjs_license">
            <input type="hidden" name="op" value="activate">
            <?php wp_nonce_field('m2c_capjs_license'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="m2c-capjs-key">Clé fournie par mak2com</label></th>
                    <td>
                        <textarea id="m2c-capjs-key" name="key" rows="3" class="large-text code" spellcheck="false" autocomplete="off" required aria-describedby="m2c-capjs-key-help" placeholder="M2CCJ-…"></textarea>
                        <p class="description" id="m2c-capjs-key-help">Inutile en développement ou en recette (adresses en .local, .test, dev., staging., preprod., recette.…). La clé se génère sur licences.mak2com.fr.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button($public['key_hint'] ? 'Remplacer la clé' : 'Activer la licence', 'primary', 'submit', false); ?>
        </form>
    <?php endif; ?>

    <div style="margin-top:1em">
        <?php if ($data && '' !== m2c_capjs_license_server()): ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
            <input type="hidden" name="action" value="m2c_capjs_license">
            <input type="hidden" name="op" value="check">
            <?php wp_nonce_field('m2c_capjs_license'); ?>
            <?php submit_button('Vérifier maintenant auprès de mak2com', 'secondary', 'submit', false); ?>
        </form>
        <?php endif; ?>
        <?php if ($public['key_hint'] && !$public['from_constant']): ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('Retirer la clé ? Le captcha sera désactivé sur ce site (les formulaires resteront utilisables sans captcha).');">
            <input type="hidden" name="action" value="m2c_capjs_license">
            <input type="hidden" name="op" value="deactivate">
            <?php wp_nonce_field('m2c_capjs_license'); ?>
            <?php submit_button('Retirer la clé de ce site', 'delete', 'submit', false); ?>
        </form>
        <?php endif; ?>
    </div>

    <h3>Ce que couvre la licence</h3>
    <ul class="ul-disc">
        <li><strong>Licence valide</strong> : captcha et mises à jour automatiques.</li>
        <li><strong>Fin de licence</strong> (expiration, suspension, résiliation, changement de prestataire) : <strong>le captcha est désactivé</strong> ; il n’est plus affiché ni vérifié et les formulaires restent utilisables sans captcha. La licence renouvelée ou réactivée le rétablit aussitôt.</li>
        <li>Une indisponibilité du serveur de licences n’a <strong>aucun effet</strong> sur le site.</li>
        <li><strong>Sans clé</strong> : aucun captcha, sauf en développement et recette (et pendant les 30 jours de reprise d’un site installé avant la licence).</li>
    </ul>
    <hr>
    <?php
}
