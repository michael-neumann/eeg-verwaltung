<?php
if (!defined('ABSPATH')) { exit; }
require_once ABSPATH . 'wp-admin/includes/user.php';

function eeg_verw_registration_bootstrap(){
    add_shortcode('eeg_verw_register', 'eeg_verw_register_shortcode');
}

function eeg_verw_register_shortcode($atts = []){
    if (is_user_logged_in()) {
        return '<p>'.esc_html__('Du bist bereits angemeldet.','eeg-verwaltung').'</p>';
    }
    $errors = [];
    $success = false;

    if (isset($_POST['eeg_verw_reg_nonce']) && wp_verify_nonce($_POST['eeg_verw_reg_nonce'], 'eeg_verw_reg')) {
        $vorname  = sanitize_text_field($_POST['first_name'] ?? '');
        $nachname = sanitize_text_field($_POST['last_name'] ?? '');
        $email    = sanitize_email($_POST['user_email'] ?? '');
        $phone    = sanitize_text_field($_POST['phone'] ?? '');
        $company  = sanitize_text_field($_POST['company'] ?? '');
        $custom   = sanitize_text_field($_POST['eeg_custom_field'] ?? '');
        $consent  = isset($_POST['eeg_privacy']) ? 1 : 0;

        if (empty($vorname))  $errors[] = __('Vorname fehlt.','eeg-verwaltung');
        if (empty($nachname)) $errors[] = __('Nachname fehlt.','eeg-verwaltung');
        if (!is_email($email)) $errors[] = __('E-Mail ist ungültig.','eeg-verwaltung');
        if (!$consent) $errors[] = __('Bitte Datenschutzbestimmungen akzeptieren.','eeg-verwaltung');
        if (email_exists($email)) $errors[] = __('Für diese E-Mail existiert bereits ein Konto.','eeg-verwaltung');

        if (empty($errors)){
            $username = sanitize_user($email, true);
            if (username_exists($username)) {
                $username = sanitize_user($vorname . '.' . $nachname . '.' . wp_generate_password(4,false,false), true);
            }
            $password = wp_generate_password(20, true, true);
            $user_id  = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)){
                $errors[] = sprintf(__('Konto konnte nicht erstellt werden: %s','eeg-verwaltung'), $user_id->get_error_message());
            } else {
                wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $vorname,
                    'last_name'  => $nachname,
                    'display_name' => trim($vorname.' '.$nachname),
                    'role' => 'mitglied'
                ]);
                global $wpdb;
                $table = eeg_verw_table_mitglieder();
                $member_no = eeg_verw_get_mitgliedsnummer($user_id);
                $ip = function_exists('eeg_verw_get_client_ip') ? eeg_verw_get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
                $now = current_time('mysql');
                $ip_bin = function_exists('inet_pton') ? (inet_pton($ip) ?: null) : null;

                $wpdb->insert($table, [
                    'user_id' => $user_id,
                    'member_no' => $member_no,
                    'company' => $company,
                    'phone' => $phone,
                    'custom_field' => $custom,
                    'consent_at' => ($consent ? $now : null),
                    'consent_ip' => $ip_bin,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], [
                    '%d','%s','%s','%s','%s','%s','%s','%d','%s','%s'
                ]);

                // Optional meta mirror
                update_user_meta($user_id, 'eeg_custom_field', $custom);
                update_user_meta($user_id, 'phone', $phone);
                update_user_meta($user_id, 'company', $company);
                if ($consent){
                    update_user_meta($user_id, 'eeg_consent_checked', 1);
                    update_user_meta($user_id, 'eeg_consent_time', $now);
                    update_user_meta($user_id, 'eeg_consent_ip', $ip);
                }

                if (function_exists('wp_send_new_user_notifications')) {
                    wp_send_new_user_notifications($user_id, 'user');
                } else {
                    wp_new_user_notification($user_id, null, 'user');
                }
                $success = true;
            }
        }
    }

    ob_start();
    if ($success){
        echo '<div class="notice notice-success" style="padding:10px;border:1px solid #46b450;">';
        echo '<p>'.esc_html__('Danke! Bitte prüfe deine E-Mail, um dein Passwort zu setzen und dich anzumelden.','eeg-verwaltung').'</p>';
        echo '</div>';
        return ob_get_clean();
    }

    if (!empty($errors)){
        echo '<div class="notice notice-error" style="padding:10px;border:1px solid #dc3232;"><ul>';
        foreach ($errors as $e) echo '<li>'.esc_html($e).'</li>';
        echo '</ul></div>';
    }
    ?>
    <form method="post">
        <?php wp_nonce_field('eeg_verw_reg', 'eeg_verw_reg_nonce'); ?>
        <p>
            <label><?php echo esc_html__('Vorname','eeg-verwaltung'); ?><br>
                <input type="text" name="first_name" value="<?php echo esc_attr($_POST['first_name'] ?? ''); ?>" required>
            </label>
        </p>
        <p>
            <label><?php echo esc_html__('Nachname','eeg-verwaltung'); ?><br>
                <input type="text" name="last_name" value="<?php echo esc_attr($_POST['last_name'] ?? ''); ?>" required>
            </label>
        </p>
        <p>
            <label><?php echo esc_html__('E-Mail-Adresse','eeg-verwaltung'); ?><br>
                <input type="email" name="user_email" value="<?php echo esc_attr($_POST['user_email'] ?? ''); ?>" required>
            </label>
        </p>
        <p>
            <label><?php echo esc_html__('Telefon','eeg-verwaltung'); ?><br>
                <input type="text" name="phone" value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>">
            </label>
        </p>
        <p>
            <label><?php echo esc_html__('Firma','eeg-verwaltung'); ?><br>
                <input type="text" name="company" value="<?php echo esc_attr($_POST['company'] ?? ''); ?>">
            </label>
        </p>
        <p>
            <label><?php echo esc_html__('Zusatzfeld','eeg-verwaltung'); ?><br>
                <input type="text" name="eeg_custom_field" value="<?php echo esc_attr($_POST['eeg_custom_field'] ?? ''); ?>">
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="eeg_privacy" value="1" <?php checked(isset($_POST['eeg_privacy']), true); ?> required>
                <?php echo esc_html__('Ich habe die Datenschutzbestimmungen gelesen und akzeptiere sie.','eeg-verwaltung'); ?>
            </label>
        </p>
        <p>
            <button type="submit"><?php echo esc_html__('Registrieren','eeg-verwaltung'); ?></button>
        </p>
    </form>
    <?php
    return ob_get_clean();
}
