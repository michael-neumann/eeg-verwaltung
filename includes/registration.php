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

    global $wpdb;

    $table = eeg_verw_table_mitglieder();
    $table_a = eeg_verw_table_mitgliedsarten();

    $mitgliedsart_id = 0;
    $firma = '';
    $uid = '';
    $vorname = '';
    $nachname = '';
    $strasse = '';
    $hausnummer = '';
    $plz = '';
    $ort = '';
    $telefonnummer = '';
    $email = '';
    $dokumentenart = '';
    $dokumentennummer = '';
    $iban = '';
    $kontoinhaber = '';
    $status = 1;
    $aktiv = 1;

    if (isset($_POST['eeg_verw_reg_nonce']) && wp_verify_nonce($_POST['eeg_verw_reg_nonce'], 'eeg_verw_reg')) {

        $mitgliedsart_id = absint($_POST['mitgliedsart_id']);
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

        if (empty($vorname)) {
            $errors[] = __('Vorname fehlt.', 'eeg-verwaltung');
        }
        if (empty($nachname)) {
            $errors[] = __('Nachname fehlt.', 'eeg-verwaltung');
        }
        if (!is_email($email)) {
            $errors[] = __('E-Mail ist ungültig.', 'eeg-verwaltung');
        }
        if (!$consent) {
            $errors[] = __('Bitte Datenschutzbestimmungen akzeptieren.', 'eeg-verwaltung');
        }
        if (email_exists($email)) {
            $errors[] = __('Für diese E-Mail existiert bereits ein Konto.', 'eeg-verwaltung');
        }

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
                        'role' => 'mitglied',
                ]);

                $mitgliedsnummer = eeg_verw_get_mitgliedsnummer();
                // IP jetzt als TEXT
                $ip = function_exists('eeg_verw_get_client_ip') ? eeg_verw_get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
                $now = current_time('mysql');

                $wpdb->insert(
                        $table,
                        [
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
                                'consent_ip' => $ip, // IP als TEXT
                                'status' => $status,
                                'aktiv' => $aktiv,
                                'created_at' => $now,
                                'updated_at' => $now,
                        ],
                        [
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
                                '%s', // consent_ip (TEXT)
                                '%d', // status
                                '%d', // active
                                '%s', // created_at
                                '%s', // updated_at
                        ]
                );

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

    $arten = $wpdb->get_results(
            "SELECT id, bezeichnung, uid_pflicht, firmenname_pflicht FROM {$table_a} WHERE aktiv = 1 ORDER BY sort_order ASC",
            ARRAY_A
    );

    if (!empty($errors)) {
        echo '<div class="notice notice-error" style="padding:10px;border:1px solid #dc3232;"><ul>';
        foreach ($errors as $e) {
            echo '<li>' . esc_html($e) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>
    <style>
        .eeg-verw-form p {
            margin-bottom: 1em;
        }

        .eeg-verw-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .eeg-verw-form input.regular-text,
        .eeg-verw-form input.small-text,
        .eeg-verw-form select {
            max-width: 400px;
            width: 100%;
            box-sizing: border-box;
        }

        .eeg-verw-form .inline-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .eeg-verw-form .field-group {
            flex: 1 1 180px;
            min-width: 0;
        }

        .eeg-verw-form .field-group label {
            margin-bottom: 3px;
        }

        .eeg-verw-form .field-group input.small-text {
            max-width: 120px;
        }

        #row_firma,
        #row_uid {
            display: none;
        }
    </style>

    <form method="post" class="eeg-verw-form">
        <?php wp_nonce_field('eeg_verw_reg', 'eeg_verw_reg_nonce'); ?>
        <fieldset class="eeg-section eeg-section--mitglied">
            <legend><?php esc_html_e('Mitgliedsdaten', 'eeg-verwaltung'); ?></legend>

            <p>
                <label for="mitgliedsart_id"><?php esc_html_e('Mitgliedsart', 'eeg-verwaltung'); ?></label>
                <select name="mitgliedsart_id" id="mitgliedsart_id">
                    <?php foreach ($arten as $art) : ?>
                        <option
                                value="<?php echo esc_attr($art['id']); ?>"
                                data-uid-pflicht="<?php echo (int)$art['uid_pflicht']; ?>"
                                data-firmenname-pflicht="<?php echo (int)$art['firmenname_pflicht']; ?>"
                                <?php selected($mitgliedsart_id, $art['id']); ?>
                        >
                            <?php echo esc_html($art['bezeichnung']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p id="row_firma">
                <label for="firma"><?php _e('Firmenname', 'eeg-verwaltung'); ?></label>
                <input
                        name="firma"
                        id="firma"
                        type="text"
                        class="regular-text"
                        value="<?php echo esc_attr($firma); ?>"
                />
            </p>

            <p id="row_uid">
                <label for="uid"><?php _e('UID', 'eeg-verwaltung'); ?></label>
                <input
                        name="uid"
                        id="uid"
                        type="text"
                        class="small-text"
                        value="<?php echo esc_attr($uid); ?>"
                />
            </p>
            <p class="inline-row">
            <span class="field-group">
                <label for="vorname"><?php esc_html_e('Vorname', 'eeg-verwaltung'); ?></label>
                <input name="vorname" id="vorname" type="text" class="regular-text"
                       value="<?php echo esc_attr($vorname); ?>" required/>
            </span>
                <span class="field-group">
                <label for="nachname"><?php esc_html_e('Nachname', 'eeg-verwaltung'); ?></label>
                <input name="nachname" id="nachname" type="text" class="regular-text"
                       value="<?php echo esc_attr($nachname); ?>" required/>
            </span>
            </p>
            <!-- Straße / Hausnummer nebeneinander, aber je ein Label über dem Feld -->
            <p class="inline-row">
            <span class="field-group">
                <label for="strasse"><?php esc_html_e('Straße', 'eeg-verwaltung'); ?></label>
                <input name="strasse" id="strasse" type="text" class="regular-text"
                       value="<?php echo esc_attr($strasse); ?>" required/>
            </span>
                <span class="field-group">
                <label for="hausnummer"><?php esc_html_e('Hausnummer', 'eeg-verwaltung'); ?></label>
                <input name="hausnummer" id="hausnummer" type="text" class="small-text"
                       value="<?php echo esc_attr($hausnummer); ?>"/>
            </span>
            </p>

            <!-- PLZ / Ort nebeneinander, aber je ein Label über dem Feld -->
            <p class="inline-row">
            <span class="field-group">
                <label for="plz"><?php esc_html_e('PLZ', 'eeg-verwaltung'); ?></label>
                <input name="plz" id="plz" type="text" class="small-text"
                       value="<?php echo esc_attr($plz); ?>" required/>
            </span>
                <span class="field-group">
                <label for="ort"><?php esc_html_e('Ort', 'eeg-verwaltung'); ?></label>
                <input name="ort" id="ort" type="text" class="regular-text"
                       value="<?php echo esc_attr($ort); ?>" required/>
            </span>
            </p>

            <p class="inline-row">
            <span class="field-group">
            <label for="dokumentenart"><?php esc_html_e('Dokumentenart', 'eeg-verwaltung'); ?></label>
            <select name="dokumentenart" id="dokumentenart">
                <option value=""><?php esc_html_e('- Bitte wählen -', 'eeg-verwaltung'); ?></option>
                <option value="Reisepass" <?php selected($dokumentenart, 'Reisepass'); ?>>
                    <?php esc_html_e('Reisepass', 'eeg-verwaltung'); ?>
                </option>
                <option value="Führerschein" <?php selected($dokumentenart, 'Führerschein'); ?>>
                    <?php esc_html_e('Führerschein', 'eeg-verwaltung'); ?>
                </option>
                <option value="Personalausweis" <?php selected($dokumentenart, 'Personalausweis'); ?>>
                    <?php esc_html_e('Personalausweis', 'eeg-verwaltung'); ?>
                </option>
                <option value="Firmenbuchnummer" <?php selected($dokumentenart, 'Firmenbuchnummer'); ?>>
                    <?php esc_html_e('Firmenbuchnummer', 'eeg-verwaltung'); ?>
                </option>
            </select>
                </span>
                <span class="field-group">
            <label for="dokumentennummer"><?php esc_html_e('Dokumentennummer', 'eeg-verwaltung'); ?></label>
            <input name="dokumentennummer" id="dokumentennummer" type="text" class="regular-text"
                   value="<?php echo esc_attr($dokumentennummer); ?>" required/>
    </span>
            </p>

        </fieldset>

        <fieldset class="eeg-section eeg-section-kontakt">
            <legend><?php esc_html_e('Kontaktdaten', 'eeg-verwaltung'); ?></legend>

            <p>
                <label for="telefonnummer"><?php esc_html_e('Telefonnummer', 'eeg-verwaltung'); ?></label>
                <input
                        name="telefonnummer"
                        id="telefonnummer"
                        type="text"
                        class="regular-text"
                        value="<?php echo esc_attr($telefonnummer); ?>"
                        placeholder="+43 660 1234567"
                        required
                />
            </p>

            <p>
                <label for="email"><?php esc_html_e('E-Mail', 'eeg-verwaltung'); ?></label>
                <input name="email" id="email" type="email" class="regular-text"
                       value="<?php echo esc_attr($email); ?>" required/>
            </p>
        </fieldset>
        <fieldset class="eeg-section eeg-section--bank">
            <legend><?php esc_html_e('Bankdaten', 'eeg-verwaltung'); ?></legend>

            <p>Bankdaten für die SEPA Einzugsermächtigung. Bei positiver Prüfung werden einmalig € 20,- Einschreibebetrag eingezogen. Danach wird der Strombezug und Einspeisung gemäß der aktuellen Tarife über dieses Konto abgewickelt.</p>

            <p>
                <label for="iban"><?php esc_html_e('IBAN', 'eeg-verwaltung'); ?></label>
                <input
                        name="iban"
                        id="iban"
                        type="text"
                        class="regular-text"
                        value="<?php echo esc_attr($iban); ?>"
                        placeholder="AT__ ____ ____ ____ ____"
                        required
                />
            </p>

            <p>
                <label for="kontoinhaber"><?php esc_html_e('Name des Kontoinhaber (wenn Abweichend)', 'eeg-verwaltung'); ?></label>
                <input name="kontoinhaber" id="kontoinhaber" type="text" class="regular-text"
                       value="<?php echo esc_attr($kontoinhaber); ?>"/>
            </p>
        </fieldset>
        <!-- IMask für IBAN und Telefonnummer -->
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

                // Telefonnummer
                var phoneInput = document.getElementById('telefonnummer');
                if (phoneInput) {
                    if (!phoneInput.value) {
                        phoneInput.value = '+43 ';
                    }

                    IMask(phoneInput, {
                        mask: '+00000000000000000000',
                        lazy: true,
                        placeholderChar: '',
                        definitions: {
                            '0': /[0-9 ]/
                        }
                    });
                }

                // E-Mail
                var emailInput = document.getElementById('email');
                if (emailInput) {
                    IMask(emailInput, {
                        mask: /^\S*@?\S*$/
                    });
                }
            });
        </script>

        <!-- Firmenfelder einblenden bei Bedarf – OHNE jQuery -->
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                var select = document.getElementById('mitgliedsart_id');
                if (!select) {
                    return;
                }

                var rowFirma = document.getElementById('row_firma');
                var rowUid = document.getElementById('row_uid');
                var inputFirma = document.getElementById('firma');
                var inputUid = document.getElementById('uid');

                function updateFirmaUidVisibility() {
                    var option = select.options[select.selectedIndex];
                    if (!option) {
                        return;
                    }

                    var uidPflicht = parseInt(option.getAttribute('data-uid-pflicht') || '0', 10) === 1;
                    var firmennamePflicht = parseInt(option.getAttribute('data-firmenname-pflicht') || '0', 10) === 1;

                    // Firmenname
                    if (firmennamePflicht) {
                        if (rowFirma) rowFirma.style.display = 'block';
                        if (inputFirma) inputFirma.required = true;
                    } else {
                        if (rowFirma) rowFirma.style.display = 'none';
                        if (inputFirma) {
                            inputFirma.required = false;
                            inputFirma.value = '';
                        }
                    }

                    // UID
                    if (uidPflicht) {
                        if (rowUid) rowUid.style.display = 'block';
                        if (inputUid) inputUid.required = true;
                    } else {
                        if (rowUid) rowUid.style.display = 'none';
                        if (inputUid) {
                            inputUid.required = false;
                            inputUid.value = '';
                        }
                    }
                }

                select.addEventListener('change', updateFirmaUidVisibility);
                updateFirmaUidVisibility(); // Initialzustand
            });
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
