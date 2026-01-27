<?php
if (!defined('ABSPATH')) { exit; }

function eeg_verw_admin_einstellungen_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Nicht erlaubt.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_admin_referer('eeg_einstellungen_save');

        $length_raw = isset($_POST['laenge_mitgliedsnummer']) ? trim((string)$_POST['laenge_mitgliedsnummer']) : '';
        $length_value = $length_raw === '' ? null : max(0, (int)$length_raw);

        $data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'adresse' => sanitize_text_field($_POST['adresse'] ?? ''),
            'plz' => sanitize_text_field($_POST['plz'] ?? ''),
            'ort' => sanitize_text_field($_POST['ort'] ?? ''),
            'telefonnummer' => sanitize_text_field($_POST['telefonnummer'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'homepage' => sanitize_text_field($_POST['homepage'] ?? ''),
            'uid' => sanitize_text_field($_POST['uid'] ?? ''),
            'steuernummer' => sanitize_text_field($_POST['steuernummer'] ?? ''),
            'creditor_id' => sanitize_text_field($_POST['creditor_id'] ?? ''),
            'zvr' => sanitize_text_field($_POST['zvr'] ?? ''),
            'rc_nr' => sanitize_text_field($_POST['rc_nr'] ?? ''),
            'gerichtsstand' => sanitize_text_field($_POST['gerichtsstand'] ?? ''),
            'eeg_faktura_benutzer' => sanitize_text_field($_POST['eeg_faktura_benutzer'] ?? ''),
            'eeg_faktura_passwort' => sanitize_text_field($_POST['eeg_faktura_passwort'] ?? ''),
            'laenge_mitgliedsnummer' => $length_value,
        ];

        eeg_verw_update_einstellungen($data);
        add_settings_error(
            'eeg_einstellungen',
            'eeg_einstellungen_saved',
            __('Einstellungen gespeichert.', 'eeg-verwaltung'),
            'updated'
        );
    }

    $settings = eeg_verw_get_einstellungen();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('EEG Einstellungen', 'eeg-verwaltung'); ?></h1>
        <?php settings_errors('eeg_einstellungen'); ?>
        <form method="post">
            <?php wp_nonce_field('eeg_einstellungen_save'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th><label for="name"><?php esc_html_e('Name', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="name" id="name" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['name'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="adresse"><?php esc_html_e('Adresse', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="adresse" id="adresse" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['adresse'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="plz"><?php esc_html_e('PLZ', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="plz" id="plz" type="text" class="small-text"
                               value="<?php echo esc_attr($settings['plz'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="ort"><?php esc_html_e('Ort', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="ort" id="ort" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['ort'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="telefonnummer"><?php esc_html_e('Telefonnummer', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="telefonnummer" id="telefonnummer" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['telefonnummer'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="email"><?php esc_html_e('E-Mail', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="email" id="email" type="email" class="regular-text"
                               value="<?php echo esc_attr($settings['email'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="homepage"><?php esc_html_e('Homepage', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="homepage" id="homepage" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['homepage'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="uid"><?php esc_html_e('UID', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="uid" id="uid" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['uid'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="steuernummer"><?php esc_html_e('Steuernummer', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="steuernummer" id="steuernummer" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['steuernummer'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="creditor_id"><?php esc_html_e('Creditor ID', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="creditor_id" id="creditor_id" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['creditor_id'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="zvr"><?php esc_html_e('ZVR', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="zvr" id="zvr" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['zvr'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="rc_nr"><?php esc_html_e('RC Nr', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="rc_nr" id="rc_nr" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['rc_nr'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="gerichtsstand"><?php esc_html_e('Gerichtsstand', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="gerichtsstand" id="gerichtsstand" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['gerichtsstand'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="eeg_faktura_benutzer"><?php esc_html_e('EEG Faktura Benutzer', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="eeg_faktura_benutzer" id="eeg_faktura_benutzer" type="text" class="regular-text"
                               value="<?php echo esc_attr($settings['eeg_faktura_benutzer'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="eeg_faktura_passwort"><?php esc_html_e('EEG Faktura Passwort', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="eeg_faktura_passwort" id="eeg_faktura_passwort" type="password" class="regular-text"
                               value="<?php echo esc_attr($settings['eeg_faktura_passwort'] ?? ''); ?>"/></td>
                </tr>
                <tr>
                    <th><label for="laenge_mitgliedsnummer"><?php esc_html_e('Länge der Mitgliedsnummer', 'eeg-verwaltung'); ?></label></th>
                    <td>
                        <input name="laenge_mitgliedsnummer" id="laenge_mitgliedsnummer" type="number" min="0"
                               class="small-text" value="<?php echo esc_attr($settings['laenge_mitgliedsnummer'] ?? ''); ?>"/>
                        <p class="description"><?php esc_html_e('Anzahl der Stellen für Mitgliedsnummern (führt zu führenden Nullen).', 'eeg-verwaltung'); ?></p>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php submit_button(__('Speichern', 'eeg-verwaltung')); ?>
        </form>
    </div>
    <?php
}
