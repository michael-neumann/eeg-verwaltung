<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once ABSPATH . 'wp-admin/includes/user.php';

function eeg_verw_registration_bootstrap()
{
    add_shortcode('eeg_verw_register', 'eeg_verw_register_shortcode');
}

function eeg_verw_register_shortcode($atts = [])
{
    if (is_user_logged_in()) {
        return '<p>' . esc_html__('Du bist bereits angemeldet.', 'eeg-verwaltung') . '</p>';
    }
    $errors = [];
    $success = false;

    if (isset($_POST['eeg_verw_reg_nonce']) && wp_verify_nonce($_POST['eeg_verw_reg_nonce'], 'eeg_verw_reg')) {

        $mitgliedsart_id = 0;
        if (isset($_POST['mitgliedsart_id'])) {
            $mitgliedsart_id = absint($_POST['mitgliedsart_id']);
        }

        $firma = sanitize_text_field($_POST['firma'] ?? '');
        $uid = sanitize_text_field($_POST['uid'] ?? '');
        $vorname = sanitize_text_field($_POST['vorname'] ?? '');
        $nachname = sanitize_text_field($_POST['nachname'] ?? '');
        $strasse = sanitize_text_field($_POST['strasse'] ?? '');
        $hausnummer = sanitize_text_field($_POST['hausnummer'] ?? '');
        $plz = sanitize_text_field($_POST['plz'] ?? '');
        $ort = sanitize_text_field($_POST['ort'] ?? '');
        $telefonnummer = sanitize_text_field($_POST['telefonnummer'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $dokumentenart = sanitize_text_field($_POST['dokumentenart'] ?? '');
        $dokumentennummer = sanitize_text_field($_POST['dokumentennummer'] ?? '');
        $iban = sanitize_text_field($_POST['iban'] ?? '');
        $kontoinhaber = sanitize_text_field($_POST['kontoinhaber'] ?? '');
        $status = 1;
        $aktiv = 1;

        $consent = isset($_POST['eeg_privacy']) ? 1 : 0;

        if (empty($vorname)) $errors[] = __('Vorname fehlt.', 'eeg-verwaltung');
        if (empty($nachname)) $errors[] = __('Nachname fehlt.', 'eeg-verwaltung');
        if (!is_email($email)) $errors[] = __('E-Mail ist ungültig.', 'eeg-verwaltung');
        if (!$consent) $errors[] = __('Bitte Datenschutzbestimmungen akzeptieren.', 'eeg-verwaltung');
        if (email_exists($email)) $errors[] = __('Für diese E-Mail existiert bereits ein Konto.', 'eeg-verwaltung');

        if (empty($errors)) {
            $username = sanitize_user($email, true);
            if (username_exists($username)) {
                $username = sanitize_user($vorname . '.' . $nachname . '.' . wp_generate_password(4, false, false), true);
            }
            $password = wp_generate_password(20, true, true);
            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                $errors[] = sprintf(__('Konto konnte nicht erstellt werden: %s', 'eeg-verwaltung'), $user_id->get_error_message());
            } else {
                wp_update_user([
                        'ID' => $user_id,
                        'first_name' => $vorname,
                        'last_name' => $nachname,
                        'display_name' => trim($vorname . ' ' . $nachname),
                        'role' => 'mitglied'
                ]);
                global $wpdb;
                $table = eeg_verw_table_mitglieder();
                $mitgliedsnummer = eeg_verw_get_mitgliedsnummer();
                $ip = function_exists('eeg_verw_get_client_ip') ? eeg_verw_get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
                $now = current_time('mysql');
                $ip_bin = function_exists('inet_pton') ? (inet_pton($ip) ?: null) : null;

                $wpdb->insert($table, [
                        'user_id' => $user_id,
                        'mitgliedsnummer' => $mitgliedsnummer,
                        'mitgliedsart_id' => $mitgliedsart_id,
                        'vorname' => $vorname,
                        'nachname' => $nachname,
                        'firma' => $firma,
                        'strasse' => $strasse,
                        'hausnummer' => $hausnummer,
                        'plz' => $plz,
                        'ort' => $ort,
                        'telefonnummer' => $telefonnummer,
                        'email' => $email,
                        'uid' => $uid,
                        'dokumentenart' => $dokumentenart,
                        'dokumentennummer' => $dokumentennummer,
                        'iban' => $iban,
                        'kontoinhaber' => $kontoinhaber,
                        'consent_at' => ($consent ? $now : null),
                        'consent_ip' => $ip_bin,
                        'status' => $status,
                        'active' => $aktiv,
                        'created_at' => $now,
                        'updated_at' => $now,
                ], [
                        '%d', // user_id
                        '%d', // mitgliedsnummer
                        '%d', // mitgliedsart_id
                        '%s', // vorname
                        '%s', // nachname
                        '%s', // company
                        '%s', // strasse
                        '%s', // hausnummer
                        '%s', // plz
                        '%s', // ort
                        '%s', // telefonnummer
                        '%s', // email
                        '%s', // uid
                        '%s', // dokumentenart
                        '%s', // dokumentennummer
                        '%s', // iban
                        '%s', // kontoinhaber
                        '%s', // consent_at
                        '%s', // consent_ip
                        '%d', // status
                        '%d', // active
                        '%s', // created_at
                        '%s', // updated_at
                ]);


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
    if ($success) {
        echo '<div class="notice notice-success" style="padding:10px;border:1px solid #46b450;">';
        echo '<p>' . esc_html__('Danke! Bitte prüfe deine E-Mail, um dein Passwort zu setzen und dich anzumelden.', 'eeg-verwaltung') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }

    if (!empty($errors)) {
        echo '<div class="notice notice-error" style="padding:10px;border:1px solid #dc3232;"><ul>';
        foreach ($errors as $e) echo '<li>' . esc_html($e) . '</li>';
        echo '</ul></div>';
    }
    ?>
    <form method="post">
        <?php wp_nonce_field('eeg_verw_reg', 'eeg_verw_reg_nonce'); ?>


    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php echo esc_html($title); ?>
        </h1>
        <a href="<?php echo esc_url($list_url); ?>" class="page-title-action">
            <?php esc_html_e('Zurück zur Liste', 'eeg-verwaltung'); ?>
        </a>
        <hr class="wp-header-end"/>

        <?php settings_errors('eeg_mitglieder'); ?>

        <style>
            #row_firma,
            #row_uid {
                display: none;
            }
        </style>
        <form method="post" action="">
            <?php
            wp_nonce_field('eeg_mitglied_edit');
            ?>
            <input type="hidden" name="eeg_action" value="save">
            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">

            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="mitgliedsnummer"><?php esc_html_e('Mitgliedsnummer', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="mitgliedsnummer" id="mitgliedsnummer" type="text"
                               class="regular-text"
                               value="<?php echo esc_attr($row['mitgliedsnummer']); ?>"/>
                        <p class="description">
                            <?php esc_html_e('Leer lassen, um automatisch eine Nummer zu vergeben.', 'eeg-verwaltung'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="mitgliedsart_id"><?php esc_html_e('Mitgliedsart', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <select name="mitgliedsart_id" id="mitgliedsart_id">
                            <?php foreach ($arten as $art) : ?>
                                <option
                                        value="<?php echo esc_attr($art['id']); ?>"
                                        data-uid-pflicht="<?php echo (int)$art['uid_pflicht']; ?>"
                                        data-firmenname-pflicht="<?php echo (int)$art['firmenname_pflicht']; ?>"
                                        <?php selected($row['mitgliedsart_id'], $art['id']); ?>
                                >
                                    <?php echo esc_html($art['bezeichnung']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    </td>
                </tr>

                <tr class="form-field" id="row_firma">
                    <th scope="row">
                        <label for="firma"><?php _e('Firmenname', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input
                                name="firma"
                                id="firma"
                                type="text"
                                class="regular-text"
                                value="<?php echo esc_attr($row['firma']); ?>"
                        />
                    </td>
                </tr>

                <tr class="form-field" id="row_uid">
                    <th scope="row">
                        <label for="uid"><?php _e('UID', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input
                                name="uid"
                                id="uid"
                                type="text"
                                class="regular-text"
                                value="<?php echo esc_attr($row['uid']); ?>"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="vorname"><?php esc_html_e('Vorname', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="vorname" id="vorname" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['vorname']); ?>" required/>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="nachname"><?php esc_html_e('Nachname', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="nachname" id="nachname" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['nachname']); ?>" required/>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="strasse"><?php esc_html_e('Straße / Hausnummer', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="strasse" id="strasse" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['strasse']); ?>" required/>
                        <input name="hausnummer" id="hausnummer" type="text" class="small-text"
                               value="<?php echo esc_attr($row['hausnummer']); ?>"/>
                    </td>
                </tr>

                <tr>

                <tr>
                    <th scope="row">
                        <label for="ort"><?php esc_html_e('PLZ / Ort', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="plz" id="plz" type="text" class="small-text"
                               value="<?php echo esc_attr($row['plz']); ?>" required/>
                        <input name="ort" id="ort" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['ort']); ?>" required/>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="telefonnummer"><?php esc_html_e('Telefonnummer', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input
                                name="telefonnummer"
                                id="telefonnummer"
                                type="text"
                                class="regular-text"
                                value="<?php echo esc_attr($row['telefonnummer']); ?>"
                                placeholder="+43 660 1234567"
                                required
                        />
                        <p class="description">
                            <?php esc_html_e('Standardmäßig +43, kann bei Bedarf angepasst werden.', 'eeg-verwaltung'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="email"><?php esc_html_e('E-Mail', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="email" id="email" type="email" class="regular-text"
                               value="<?php echo esc_attr($row['email']); ?>" required/>
                        <p class="description">
                            <?php esc_html_e('Wird als Benutzername, zur Kommunikation und Rechnsungsversand verwendet.', 'eeg-verwaltung'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dokumentenart"><?php esc_html_e('Dokumentenart', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <select name="dokumentenart" id="dokumentenart">
                            <option value=""><?php esc_html_e('- Bitte wählen -', 'eeg-verwaltung'); ?></option>
                            <option value="Reisepass" <?php selected($row['dokumentenart'], 'Reisepass'); ?>>
                                <?php esc_html_e('Reisepass', 'eeg-verwaltung'); ?>
                            </option>
                            <option value="Führerschein" <?php selected($row['dokumentenart'], 'Führerschein'); ?>>
                                <?php esc_html_e('Führerschein', 'eeg-verwaltung'); ?>
                            </option>
                            <option value="Personalausweis" <?php selected($row['dokumentenart'], 'Personalausweis'); ?>>
                                <?php esc_html_e('Personalausweis', 'eeg-verwaltung'); ?>
                            </option>
                            <option value="Firmenbuchnummer" <?php selected($row['dokumentenart'], 'Firmenbuchnummer'); ?>>
                                <?php esc_html_e('Firmenbuchnummer', 'eeg-verwaltung'); ?>
                            </option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="dokumentennummer"><?php esc_html_e('Dokumentennummer', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="dokumentennummer" id="dokumentennummer" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['dokumentennummer']); ?>" required/>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="iban"><?php esc_html_e('IBAN', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input
                                name="iban"
                                id="iban"
                                type="text"
                                class="regular-text"
                                value="<?php echo esc_attr($row['iban']); ?>"
                                placeholder="AT__ ____ ____ ____ ____"
                                required
                        />
                        <p class="description">
                            <?php esc_html_e('Der IBAN wird serverseitig auf Gültigkeit geprüft.', 'eeg-verwaltung'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="kontoinhaber"><?php esc_html_e('Kontoinhaber', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="kontoinhaber" id="kontoinhaber" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['kontoinhaber']); ?>"/>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="status"><?php esc_html_e('Interner Status', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="status" id="status" type="number" class="small-text"
                               value="<?php echo esc_attr($row['status']); ?>"/>
                        <p class="description">
                            <?php esc_html_e('Interner numerischer Status (frei belegbar).', 'eeg-verwaltung'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php esc_html_e('Aktiv', 'eeg-verwaltung'); ?>
                    </th>
                    <td>
                        <label for="aktiv">
                            <input name="aktiv" id="aktiv" type="checkbox" value="1"
                                    <?php checked((int)$row['aktiv'], 1); ?> />
                            <?php esc_html_e('Mitglied ist aktiv', 'eeg-verwaltung'); ?>
                        </label>
                    </td>
                </tr>
                </tbody>
            </table>

            <?php
            if (!empty($row['id'])) {
                submit_button(__('Speichern', 'eeg-verwaltung'));
            } else {
                submit_button(__('Mitglied anlegen', 'eeg-verwaltung'));
            }
            ?>
        </form>
    </div>

    <!-- IMask für IBAN und Telefonnummer im Admin-Formular -->
    <script src="https://unpkg.com/imask"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof IMask === 'undefined') {
                return;
            }

            // IBAN
            var ibanInput = document.getElementById('iban');
            if (ibanInput) {
                IMask(ibanInput, {
                    mask: 'aa00 0000 0000 0000 0000',
                    lazy: false,
                    prepareChar: function (str) {
                        return str.toUpperCase();
                    }
                });
            }

            // Telefonnummer: + vorgegeben, danach Ziffern oder Leerzeichen, flexibel
            var phoneInput = document.getElementById('telefonnummer');
            if (phoneInput) {
                if (!phoneInput.value) {
                    phoneInput.value = '+43 ';
                }

                IMask(phoneInput, {
                    mask: '+00000000000000000000',
                    lazy: true,
                    placeholderChar: '',   // <<< Unterstriche deaktiviert
                    definitions: {
                        '0': /[0-9 ]/ // Ziffern oder Leerzeichen
                    }
                });
            }


            // E-Mail: keine Leerzeichen, sehr lockere Maske (alles außer Space, optional @)
            var emailInput = document.getElementById('email');
            if (emailInput) {
                IMask(emailInput, {
                    mask: /^\S*@?\S*$/
                });
            }
        });
    </script>

    <!-- Firmenfelder einblenden bei bedarf -->
    <script type="text/javascript">
        (function ($) {

            function updateFirmaUidVisibility() {
                var $select = $('#mitgliedsart_id');
                if ($select.length === 0) {
                    return;
                }

                var $selected = $select.find('option:selected');
                var uidPflicht = parseInt($selected.data('uid-pflicht'), 10) === 1;
                var firmennamePflicht = parseInt($selected.data('firmenname-pflicht'), 10) === 1;

                var $rowFirma = $('#row_firma');
                var $rowUid = $('#row_uid');
                var $inputFirma = $('#firma');
                var $inputUid = $('#uid');

                // Firmenname
                if (firmennamePflicht) {
                    $rowFirma.show();
                    $inputFirma.prop('required', true);
                } else {
                    $rowFirma.hide();
                    $inputFirma.prop('required', false).val('');
                }

                // UID
                if (uidPflicht) {
                    $rowUid.show();
                    $inputUid.prop('required', true);
                } else {
                    $rowUid.hide();
                    $inputUid.prop('required', false).val('');
                }
            }

            $(document).ready(function () {
                $('#mitgliedsart_id').on('change', updateFirmaUidVisibility);
                updateFirmaUidVisibility(); // Initialzustand
            });

        })(jQuery);
    </script>

        <p>
            <label>
                <input type="checkbox" name="eeg_privacy"
                       value="1" <?php checked(isset($_POST['eeg_privacy']), true); ?> required>
                <?php echo esc_html__('Ich habe die Datenschutzbestimmungen gelesen und akzeptiere sie.', 'eeg-verwaltung'); ?>
            </label>
        </p>
        <p>
            <button type="submit"><?php echo esc_html__('Registrieren', 'eeg-verwaltung'); ?></button>
        </p>
    </form>
    <?php
    return ob_get_clean();
}
