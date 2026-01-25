<?php
if (!defined('ABSPATH')) { exit; }

function eeg_verw_admin_import_page()
{
    eeg_verw_render_import_page();
}

function eeg_verw_render_import_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('Nicht erlaubt.', 'eeg-verwaltung'));
    }

    $preview = null;
    $import_result = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = sanitize_key($_POST['eeg_import_action'] ?? '');
        if ($action === 'preview') {
            check_admin_referer('eeg_import_preview');
            $preview = eeg_verw_handle_import_preview();
        }
        if ($action === 'import') {
            check_admin_referer('eeg_import_apply');
            $import_result = eeg_verw_handle_import_apply();
        }
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Stammdaten & Zählpunkte importieren', 'eeg-verwaltung'); ?></h1>
        <p><?php esc_html_e('Lade eine Excel-Datei (XLSX) hoch. Vor dem Import werden alle Änderungen angezeigt. Fehlende Daten werden nicht gelöscht.', 'eeg-verwaltung'); ?></p>

        <?php if (is_wp_error($preview)) : ?>
            <div class="notice notice-error"><p><?php echo esc_html($preview->get_error_message()); ?></p></div>
        <?php endif; ?>

        <?php if (is_wp_error($import_result)) : ?>
            <div class="notice notice-error"><p><?php echo esc_html($import_result->get_error_message()); ?></p></div>
        <?php endif; ?>

        <?php if (is_array($import_result)) : ?>
            <div class="notice notice-success">
                <p>
                    <?php
                    printf(
                        esc_html__('Import abgeschlossen: %d neue Mitglieder, %d aktualisierte Mitglieder, %d neue Zählpunkte, %d aktualisierte Zählpunkte.', 'eeg-verwaltung'),
                        (int)$import_result['members_created'],
                        (int)$import_result['members_updated'],
                        (int)$import_result['zaehlpunkte_created'],
                        (int)$import_result['zaehlpunkte_updated']
                    );
                    ?>
                </p>
                <?php if (!empty($import_result['errors'])) : ?>
                    <ul>
                        <?php foreach ($import_result['errors'] as $error) : ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('eeg_import_preview'); ?>
            <input type="hidden" name="eeg_import_action" value="preview"/>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="eeg-import-file"><?php esc_html_e('Excel-Datei', 'eeg-verwaltung'); ?></label></th>
                    <td><input type="file" id="eeg-import-file" name="eeg_import_file" accept=".xlsx" required></td>
                </tr>
            </table>
            <?php submit_button(__('Änderungen anzeigen', 'eeg-verwaltung')); ?>
        </form>

        <?php if (is_array($preview)) : ?>
            <hr />
            <h2><?php esc_html_e('Vorschau der Änderungen', 'eeg-verwaltung'); ?></h2>

            <?php if (!empty($preview['errors'])) : ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('Einige Zeilen konnten nicht verarbeitet werden:', 'eeg-verwaltung'); ?></p>
                    <ul>
                        <?php foreach ($preview['errors'] as $error) : ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <p>
                <?php
                printf(
                    esc_html__('Neue Mitglieder: %d, bestehende Mitglieder mit Änderungen: %d, neue Zählpunkte: %d, Zählpunkte mit Änderungen: %d.', 'eeg-verwaltung'),
                    (int)count($preview['new_members']),
                    (int)count($preview['updated_members']),
                    (int)count($preview['new_zaehlpunkte']),
                    (int)count($preview['updated_zaehlpunkte'])
                );
                ?>
            </p>

            <?php if (!empty($preview['new_members'])) : ?>
                <h3><?php esc_html_e('Neue Mitglieder', 'eeg-verwaltung'); ?></h3>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Mitgliedsnummer', 'eeg-verwaltung'); ?></th>
                        <th><?php esc_html_e('Name/Firma', 'eeg-verwaltung'); ?></th>
                        <th><?php esc_html_e('E-Mail', 'eeg-verwaltung'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($preview['new_members'] as $member) : ?>
                        <tr>
                            <td><?php echo esc_html($member['mitgliedsnummer']); ?></td>
                            <td><?php echo esc_html($member['label']); ?></td>
                            <td><?php echo esc_html($member['email']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($preview['updated_members'])) : ?>
                <h3><?php esc_html_e('Bestehende Mitglieder (Überschreibungen)', 'eeg-verwaltung'); ?></h3>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Mitgliedsnummer', 'eeg-verwaltung'); ?></th>
                        <th><?php esc_html_e('Feld', 'eeg-verwaltung'); ?></th>
                        <th><?php esc_html_e('Alt', 'eeg-verwaltung'); ?></th>
                        <th><?php esc_html_e('Neu', 'eeg-verwaltung'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($preview['updated_members'] as $member) : ?>
                        <?php foreach ($member['changes'] as $change) : ?>
                            <tr>
                                <td><?php echo esc_html($member['mitgliedsnummer']); ?></td>
                                <td><?php echo esc_html($change['field_label']); ?></td>
                                <td><?php echo esc_html($change['old']); ?></td>
                                <td><?php echo esc_html($change['new']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($preview['new_zaehlpunkte'])) : ?>
                <h3><?php esc_html_e('Neue Zählpunkte', 'eeg-verwaltung'); ?></h3>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Mitgliedsnummer', 'eeg-verwaltung'); ?></th>
                        <th><?php esc_html_e('Zählpunkt', 'eeg-verwaltung'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($preview['new_zaehlpunkte'] as $zp) : ?>
                        <tr>
                            <td><?php echo esc_html($zp['mitgliedsnummer']); ?></td>
                            <td><?php echo esc_html($zp['label']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($preview['updated_zaehlpunkte'])) : ?>
                <h3><?php esc_html_e('Zählpunkte mit Änderungen', 'eeg-verwaltung'); ?></h3>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Mitgliedsnummer', 'eeg-verwaltung'); ?></th>
                        <th><?php esc_html_e('Zählpunkt', 'eeg-verwaltung'); ?></th>
                        <th><?php esc_html_e('Feld', 'eeg-verwaltung'); ?></th>
                        <th><?php esc_html_e('Alt', 'eeg-verwaltung'); ?></th>
                        <th><?php esc_html_e('Neu', 'eeg-verwaltung'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($preview['updated_zaehlpunkte'] as $change) : ?>
                        <tr>
                            <td><?php echo esc_html($change['mitgliedsnummer']); ?></td>
                            <td><?php echo esc_html($change['zaehlpunkt_label']); ?></td>
                            <td><?php echo esc_html($change['field_label']); ?></td>
                            <td><?php echo esc_html($change['old']); ?></td>
                            <td><?php echo esc_html($change['new']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('eeg_import_apply'); ?>
                <input type="hidden" name="eeg_import_action" value="import"/>
                <input type="hidden" name="eeg_import_payload" value="<?php echo esc_attr($preview['payload']); ?>"/>
                <?php submit_button(__('Import durchführen', 'eeg-verwaltung'), 'primary'); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

function eeg_verw_handle_import_preview()
{
    if (empty($_FILES['eeg_import_file']['tmp_name'])) {
        return new WP_Error('eeg_import_file_missing', __('Bitte eine Excel-Datei auswählen.', 'eeg-verwaltung'));
    }

    $file = $_FILES['eeg_import_file'];
    if (!empty($file['error'])) {
        return new WP_Error('eeg_import_file_error', __('Die Datei konnte nicht hochgeladen werden.', 'eeg-verwaltung'));
    }

    $parsed = eeg_verw_parse_mitglieder_xlsx($file['tmp_name']);
    if (is_wp_error($parsed)) {
        return $parsed;
    }

    return eeg_verw_build_import_preview($parsed);
}

function eeg_verw_handle_import_apply()
{
    $payload = wp_unslash($_POST['eeg_import_payload'] ?? '');
    if ($payload === '') {
        return new WP_Error('eeg_import_payload_missing', __('Keine Importdaten gefunden.', 'eeg-verwaltung'));
    }

    $decoded = json_decode(base64_decode($payload), true);
    if (!is_array($decoded)) {
        return new WP_Error('eeg_import_payload_invalid', __('Importdaten konnten nicht gelesen werden.', 'eeg-verwaltung'));
    }

    return eeg_verw_apply_import($decoded);
}

function eeg_verw_parse_mitglieder_xlsx($file_path)
{
    $rows = eeg_verw_read_xlsx_sheet($file_path, 'Mitglieder');
    if (is_wp_error($rows)) {
        return $rows;
    }

    if (empty($rows)) {
        return new WP_Error('eeg_import_empty', __('Das Arbeitsblatt „Mitglieder“ ist leer.', 'eeg-verwaltung'));
    }

    $header = array_shift($rows);
    $mapping = eeg_verw_build_header_mapping($header);
    if (empty($mapping)) {
        return new WP_Error('eeg_import_header', __('Die Kopfzeile konnte nicht erkannt werden.', 'eeg-verwaltung'));
    }

    $members = [];
    $errors = [];

    foreach ($rows as $row_index => $row) {
        $data = [];
        foreach ($mapping as $col_index => $field) {
            $value = $row[$col_index] ?? '';
            $value = is_string($value) ? trim($value) : $value;
            if (is_numeric($value)) {
                $value = (string)$value;
            }
            $data[$field] = $value;
        }

        $mitgliedsnummer = trim((string)($data['mitgliedsnummer'] ?? ''));
        if ($mitgliedsnummer === '') {
            $errors[] = sprintf(__('Zeile %d: Keine Mitgliedsnummer.', 'eeg-verwaltung'), $row_index + 2);
            continue;
        }

        if (!isset($members[$mitgliedsnummer])) {
            $members[$mitgliedsnummer] = [
                'mitgliedsnummer' => $mitgliedsnummer,
                'name1' => '',
                'name2' => '',
                'titel' => '',
                'status' => '',
                'mitglied_seit' => '',
                'email' => '',
                'telefonnummer' => '',
                'steuernummer' => '',
                'uid' => '',
                'iban' => '',
                'kontoinhaber' => '',
                'zaehlpunkte' => [],
            ];
        }

        $member = &$members[$mitgliedsnummer];
        foreach (['name1', 'name2', 'titel', 'status', 'mitglied_seit', 'email', 'telefonnummer', 'steuernummer', 'uid', 'iban', 'kontoinhaber'] as $field) {
            if (!empty($data[$field])) {
                if ($field === 'mitglied_seit') {
                    $member[$field] = eeg_verw_normalize_date($data[$field]);
                } else {
                    $member[$field] = (string)$data[$field];
                }
            }
        }

        $zaehlpunkt_row = eeg_verw_extract_zaehlpunkt_from_row($data);
        if (!empty($zaehlpunkt_row)) {
            $member['zaehlpunkte'][] = $zaehlpunkt_row;
        }
        unset($member);
    }

    return [
        'members' => $members,
        'errors' => $errors,
    ];
}

function eeg_verw_build_header_mapping($header)
{
    $mapping = [];
    foreach ($header as $index => $label) {
        $normalized = eeg_verw_normalize_header($label);
        $field = eeg_verw_map_header_to_field($normalized);
        if ($field) {
            $mapping[$index] = $field;
        }
    }

    return $mapping;
}

function eeg_verw_map_header_to_field($normalized)
{
    $map = [
        'mit nr' => 'mitgliedsnummer',
        'mitgliedsnummer' => 'mitgliedsnummer',
        'name 1' => 'name1',
        'name 2' => 'name2',
        'titel' => 'titel',
        'status' => 'status',
        'mitglied seit' => 'mitglied_seit',
        'e mail' => 'email',
        'email' => 'email',
        'telefonnummer' => 'telefonnummer',
        'telefon' => 'telefonnummer',
        'steuernr' => 'steuernummer',
        'steuer nr' => 'steuernummer',
        'ust' => 'uid',
        'ust id' => 'uid',
        'iban' => 'iban',
        'kontoinhaber' => 'kontoinhaber',
        'zaehlpunkt' => 'zaehlpunkt',
        'zp status' => 'zp_status',
        'zp nr' => 'zp_nr',
        'zaehlpunktname' => 'zaehlpunktname',
        'registriert' => 'registriert',
        'bezugsrichtung' => 'bezugsrichtung',
        'teilnahme fkt' => 'teilnahme_fkt',
        'wechselrichter nr' => 'wechselrichter_nr',
        'plz' => 'plz',
        'ort' => 'ort',
        'strasse' => 'strasse',
        'hausnummer' => 'hausnummer',
        'aktiviert' => 'aktiviert',
        'deaktiviert' => 'deaktiviert',
        'tarifname' => 'tarifname',
        'umspannwerk' => 'umspannwerk',
    ];

    return $map[$normalized] ?? null;
}

function eeg_verw_extract_zaehlpunkt_from_row($row)
{
    $fields = [
        'zaehlpunkt',
        'zp_status',
        'zp_nr',
        'zaehlpunktname',
        'registriert',
        'bezugsrichtung',
        'teilnahme_fkt',
        'wechselrichter_nr',
        'plz',
        'ort',
        'strasse',
        'hausnummer',
        'aktiviert',
        'deaktiviert',
        'tarifname',
        'umspannwerk',
    ];

    $result = [];
    $has_value = false;
    foreach ($fields as $field) {
        $value = $row[$field] ?? '';
        if (in_array($field, ['registriert', 'aktiviert', 'deaktiviert'], true)) {
            $value = eeg_verw_normalize_date($value);
        }
        $value = is_string($value) ? trim($value) : $value;
        if ($value !== '' && $value !== null) {
            $has_value = true;
        }
        $result[$field] = $value;
    }

    return $has_value ? $result : [];
}

function eeg_verw_build_import_preview($parsed)
{
    $members = $parsed['members'] ?? [];
    $errors = $parsed['errors'] ?? [];

    if (empty($members)) {
        return new WP_Error('eeg_import_empty', __('Keine gültigen Datensätze gefunden.', 'eeg-verwaltung'));
    }

    $mitgliedsnummern = array_keys($members);
    $existing_map = eeg_verw_fetch_existing_members($mitgliedsnummern);
    $existing_zp = eeg_verw_fetch_existing_zaehlpunkte(array_column($existing_map, 'id'));

    $new_members = [];
    $updated_members = [];
    $new_zaehlpunkte = [];
    $updated_zaehlpunkte = [];

    foreach ($members as $mitgliedsnummer => $member) {
        $normalized = eeg_verw_normalize_member_payload($member);
        $existing = $existing_map[$mitgliedsnummer] ?? null;

        if (!$existing) {
            $new_members[] = [
                'mitgliedsnummer' => $mitgliedsnummer,
                'label' => eeg_verw_member_label($normalized),
                'email' => $normalized['email'] ?? '',
            ];
        } else {
            $changes = eeg_verw_detect_member_changes($existing, $normalized);
            if (!empty($changes)) {
                $updated_members[] = [
                    'mitgliedsnummer' => $mitgliedsnummer,
                    'changes' => $changes,
                ];
            }
        }

        if (!empty($normalized['zaehlpunkte'])) {
            $mitglied_id = $existing['id'] ?? null;
            $existing_zp_map = $mitglied_id ? ($existing_zp[$mitglied_id] ?? []) : [];
            foreach ($normalized['zaehlpunkte'] as $zp_row) {
                $key = eeg_verw_zaehlpunkt_key($zp_row);
                if ($key === '') {
                    continue;
                }

                $existing_row = $existing_zp_map[$key] ?? null;
                if (!$existing_row) {
                    $new_zaehlpunkte[] = [
                        'mitgliedsnummer' => $mitgliedsnummer,
                        'label' => eeg_verw_zaehlpunkt_label($zp_row),
                    ];
                } else {
                    $zp_changes = eeg_verw_detect_zaehlpunkt_changes($existing_row, $zp_row);
                    foreach ($zp_changes as $change) {
                        $updated_zaehlpunkte[] = [
                            'mitgliedsnummer' => $mitgliedsnummer,
                            'zaehlpunkt_label' => eeg_verw_zaehlpunkt_label($zp_row),
                            'field_label' => $change['field_label'],
                            'old' => $change['old'],
                            'new' => $change['new'],
                        ];
                    }
                }
            }
        }
    }

    $payload = base64_encode(wp_json_encode($members));

    return [
        'errors' => $errors,
        'new_members' => $new_members,
        'updated_members' => $updated_members,
        'new_zaehlpunkte' => $new_zaehlpunkte,
        'updated_zaehlpunkte' => $updated_zaehlpunkte,
        'payload' => $payload,
    ];
}

function eeg_verw_apply_import($members)
{
    global $wpdb;
    $table = eeg_verw_table_mitglieder();
    $table_zp = eeg_verw_table_zaehlpunkte();

    $mitgliedsnummern = array_keys($members);
    $existing_map = eeg_verw_fetch_existing_members($mitgliedsnummern);
    $existing_zp = eeg_verw_fetch_existing_zaehlpunkte(array_column($existing_map, 'id'));

    $default_mitgliedsart_id = eeg_verw_get_default_mitgliedsart_id();
    $now = current_time('mysql');

    $result = [
        'members_created' => 0,
        'members_updated' => 0,
        'zaehlpunkte_created' => 0,
        'zaehlpunkte_updated' => 0,
        'errors' => [],
    ];

    foreach ($members as $mitgliedsnummer => $member) {
        $normalized = eeg_verw_normalize_member_payload($member);
        $existing = $existing_map[$mitgliedsnummer] ?? null;

        if (!$existing) {
            if (empty($normalized['email'])) {
                $result['errors'][] = sprintf(__('Mitglied %s: Keine E-Mail vorhanden, Import übersprungen.', 'eeg-verwaltung'), $mitgliedsnummer);
                continue;
            }

            $user_id = eeg_verw_find_or_create_user($normalized['email']);
            if (is_wp_error($user_id)) {
                $result['errors'][] = sprintf(__('Mitglied %s: Benutzer konnte nicht erstellt werden: %s', 'eeg-verwaltung'), $mitgliedsnummer, $user_id->get_error_message());
                continue;
            }

            $created_at = $normalized['mitglied_seit'] ? $normalized['mitglied_seit'] . ' 00:00:00' : $now;

            $data = [
                'user_id' => (int)$user_id,
                'mitgliedsnummer' => $mitgliedsnummer,
                'mitgliedsart_id' => $default_mitgliedsart_id,
                'firma' => $normalized['firma'],
                'vorname' => $normalized['vorname'],
                'nachname' => $normalized['nachname'],
                'email' => $normalized['email'],
                'telefonnummer' => $normalized['telefonnummer'],
                'uid' => $normalized['uid'],
                'dokumentenart' => $normalized['dokumentenart'],
                'dokumentennummer' => $normalized['dokumentennummer'],
                'iban' => $normalized['iban'],
                'kontoinhaber' => $normalized['kontoinhaber'],
                'status' => $normalized['status'] ?? 1,
                'aktiv' => $normalized['aktiv'] ?? 1,
                'created_at' => $created_at,
                'updated_at' => $now,
            ];

            $inserted = $wpdb->insert(
                $table,
                $data,
                [
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                ]
            );

            if (!$inserted) {
                $result['errors'][] = sprintf(__('Mitglied %s: Fehler beim Anlegen (%s).', 'eeg-verwaltung'), $mitgliedsnummer, $wpdb->last_error);
                continue;
            }

            $mitglied_id = (int)$wpdb->insert_id;
            $result['members_created']++;
        } else {
            $mitglied_id = (int)$existing['id'];
            $changes = eeg_verw_detect_member_changes($existing, $normalized);
            if (!empty($changes)) {
                $update_data = eeg_verw_build_member_update_data($normalized, $existing);
                if (!empty($update_data)) {
                    $updated = $wpdb->update(
                        $table,
                        $update_data,
                        ['id' => $mitglied_id],
                        eeg_verw_format_member_update($update_data),
                        ['%d']
                    );
                    if ($updated !== false) {
                        $result['members_updated']++;
                    } else {
                        $result['errors'][] = sprintf(__('Mitglied %s: Fehler beim Aktualisieren (%s).', 'eeg-verwaltung'), $mitgliedsnummer, $wpdb->last_error);
                    }
                }
            }
        }

        if (!empty($normalized['zaehlpunkte'])) {
            $existing_zp_map = $existing_zp[$mitglied_id] ?? [];
            foreach ($normalized['zaehlpunkte'] as $zp_row) {
                $key = eeg_verw_zaehlpunkt_key($zp_row);
                if ($key === '') {
                    continue;
                }

                $existing_row = $existing_zp_map[$key] ?? null;
                if ($existing_row) {
                    $update_data = eeg_verw_build_zaehlpunkt_update_data($existing_row, $zp_row);
                    if (!empty($update_data)) {
                        $update_data['updated_at'] = $now;
                        $updated = $wpdb->update(
                            $table_zp,
                            $update_data,
                            ['id' => $existing_row['id']],
                            eeg_verw_format_zaehlpunkt_update($update_data),
                            ['%d']
                        );
                        if ($updated !== false) {
                            $result['zaehlpunkte_updated']++;
                        } else {
                            $result['errors'][] = sprintf(__('Zählpunkt %s: Fehler beim Aktualisieren (%s).', 'eeg-verwaltung'), $key, $wpdb->last_error);
                        }
                    }
                } else {
                    $insert_data = eeg_verw_prepare_zaehlpunkt_insert_data($mitglied_id, $zp_row, $now);
                    $inserted = $wpdb->insert(
                        $table_zp,
                        $insert_data,
                        eeg_verw_format_zaehlpunkt_update($insert_data)
                    );
                    if ($inserted) {
                        $result['zaehlpunkte_created']++;
                    } else {
                        $result['errors'][] = sprintf(__('Zählpunkt %s: Fehler beim Anlegen (%s).', 'eeg-verwaltung'), $key, $wpdb->last_error);
                    }
                }
            }
        }
    }

    return $result;
}

function eeg_verw_fetch_existing_members($mitgliedsnummern)
{
    global $wpdb;
    $table = eeg_verw_table_mitglieder();

    $mitgliedsnummern = array_filter(array_map('trim', $mitgliedsnummern));
    if (empty($mitgliedsnummern)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($mitgliedsnummern), '%s'));
    $sql = "SELECT * FROM $table WHERE mitgliedsnummer IN ($placeholders)";
    $rows = $wpdb->get_results($wpdb->prepare($sql, $mitgliedsnummern), ARRAY_A);

    $map = [];
    foreach ($rows as $row) {
        $map[$row['mitgliedsnummer']] = $row;
    }

    return $map;
}

function eeg_verw_fetch_existing_zaehlpunkte($mitglied_ids)
{
    global $wpdb;
    $table = eeg_verw_table_zaehlpunkte();

    $mitglied_ids = array_filter(array_map('intval', $mitglied_ids));
    if (empty($mitglied_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($mitglied_ids), '%d'));
    $sql = "SELECT * FROM $table WHERE mitglied_id IN ($placeholders)";
    $rows = $wpdb->get_results($wpdb->prepare($sql, $mitglied_ids), ARRAY_A);

    $map = [];
    foreach ($rows as $row) {
        $key = eeg_verw_zaehlpunkt_key($row);
        if ($key === '') {
            continue;
        }
        $map[$row['mitglied_id']][$key] = $row;
    }

    return $map;
}

function eeg_verw_normalize_member_payload($member)
{
    $name1 = trim((string)($member['name1'] ?? ''));
    $name2 = trim((string)($member['name2'] ?? ''));
    $firma = '';
    $vorname = '';
    $nachname = '';

    if ($name2 === '') {
        $firma = $name1;
    } else {
        $vorname = $name1;
        $nachname = $name2;
    }

    $status_raw = strtoupper(trim((string)($member['status'] ?? '')));
    $aktiv = $status_raw === '' ? null : (int)($status_raw === 'ACTIVE');
    $status = $aktiv === null ? null : $aktiv;

    $steuernummer = trim((string)($member['steuernummer'] ?? ''));

    return [
        'mitgliedsnummer' => trim((string)($member['mitgliedsnummer'] ?? '')),
        'firma' => $firma,
        'vorname' => $vorname,
        'nachname' => $nachname,
        'email' => trim((string)($member['email'] ?? '')),
        'telefonnummer' => trim((string)($member['telefonnummer'] ?? '')),
        'uid' => trim((string)($member['uid'] ?? '')),
        'dokumentenart' => $steuernummer !== '' ? 'Steuernummer' : '',
        'dokumentennummer' => $steuernummer,
        'iban' => trim((string)($member['iban'] ?? '')),
        'kontoinhaber' => trim((string)($member['kontoinhaber'] ?? '')),
        'status' => $status,
        'aktiv' => $aktiv,
        'mitglied_seit' => trim((string)($member['mitglied_seit'] ?? '')),
        'zaehlpunkte' => $member['zaehlpunkte'] ?? [],
    ];
}

function eeg_verw_detect_member_changes($existing, $incoming)
{
    $fields = [
        'firma' => __('Firma', 'eeg-verwaltung'),
        'vorname' => __('Vorname', 'eeg-verwaltung'),
        'nachname' => __('Nachname', 'eeg-verwaltung'),
        'email' => __('E-Mail', 'eeg-verwaltung'),
        'telefonnummer' => __('Telefonnummer', 'eeg-verwaltung'),
        'uid' => __('UID', 'eeg-verwaltung'),
        'dokumentennummer' => __('Steuernummer', 'eeg-verwaltung'),
        'iban' => __('IBAN', 'eeg-verwaltung'),
        'kontoinhaber' => __('Kontoinhaber', 'eeg-verwaltung'),
        'aktiv' => __('Aktiv', 'eeg-verwaltung'),
    ];

    $changes = [];
    foreach ($fields as $field => $label) {
        $new_value = $incoming[$field] ?? '';
        if ($new_value === '' || $new_value === null) {
            continue;
        }
        $old_value = $existing[$field] ?? '';
        if ((string)$old_value !== (string)$new_value) {
            $changes[] = [
                'field' => $field,
                'field_label' => $label,
                'old' => $old_value,
                'new' => $new_value,
            ];
        }
    }

    return $changes;
}

function eeg_verw_build_member_update_data($incoming, $existing)
{
    $data = [
        'updated_at' => current_time('mysql'),
    ];

    $fields = ['firma', 'vorname', 'nachname', 'email', 'telefonnummer', 'uid', 'dokumentenart', 'dokumentennummer', 'iban', 'kontoinhaber'];
    foreach ($fields as $field) {
        $value = $incoming[$field] ?? '';
        if ($value === '' || $value === null) {
            continue;
        }
        if ((string)$existing[$field] !== (string)$value) {
            $data[$field] = $value;
        }
    }

    if ($incoming['aktiv'] !== null && (int)$existing['aktiv'] !== (int)$incoming['aktiv']) {
        $data['aktiv'] = (int)$incoming['aktiv'];
        $data['status'] = (int)$incoming['status'];
    }

    return $data;
}

function eeg_verw_format_member_update($data)
{
    $formats = [];
    foreach ($data as $key => $value) {
        if (in_array($key, ['aktiv', 'status'], true)) {
            $formats[] = '%d';
        } else {
            $formats[] = '%s';
        }
    }
    return $formats;
}

function eeg_verw_detect_zaehlpunkt_changes($existing, $incoming)
{
    $labels = [
        'zaehlpunkt' => __('Zählpunkt', 'eeg-verwaltung'),
        'zp_status' => __('Status', 'eeg-verwaltung'),
        'zp_nr' => __('ZP-Nr', 'eeg-verwaltung'),
        'zaehlpunktname' => __('Zählpunktname', 'eeg-verwaltung'),
        'registriert' => __('Registriert', 'eeg-verwaltung'),
        'bezugsrichtung' => __('Bezugsrichtung', 'eeg-verwaltung'),
        'teilnahme_fkt' => __('Teilnahme-FKT', 'eeg-verwaltung'),
        'wechselrichter_nr' => __('Wechselrichter Nr', 'eeg-verwaltung'),
        'plz' => __('PLZ', 'eeg-verwaltung'),
        'ort' => __('Ort', 'eeg-verwaltung'),
        'strasse' => __('Straße', 'eeg-verwaltung'),
        'hausnummer' => __('Hausnummer', 'eeg-verwaltung'),
        'aktiviert' => __('Aktiviert', 'eeg-verwaltung'),
        'deaktiviert' => __('Deaktiviert', 'eeg-verwaltung'),
        'tarifname' => __('Tarifname', 'eeg-verwaltung'),
        'umspannwerk' => __('Umspannwerk', 'eeg-verwaltung'),
    ];

    $changes = [];
    foreach ($labels as $field => $label) {
        $new_value = $incoming[$field] ?? '';
        if ($new_value === '' || $new_value === null) {
            continue;
        }
        $old_value = $existing[$field] ?? '';
        if ((string)$old_value !== (string)$new_value) {
            $changes[] = [
                'field' => $field,
                'field_label' => $label,
                'old' => $old_value,
                'new' => $new_value,
            ];
        }
    }

    return $changes;
}

function eeg_verw_build_zaehlpunkt_update_data($existing, $incoming)
{
    $fields = [
        'zaehlpunkt',
        'zp_status',
        'zp_nr',
        'zaehlpunktname',
        'registriert',
        'bezugsrichtung',
        'teilnahme_fkt',
        'wechselrichter_nr',
        'plz',
        'ort',
        'strasse',
        'hausnummer',
        'aktiviert',
        'deaktiviert',
        'tarifname',
        'umspannwerk',
    ];

    $data = [];
    foreach ($fields as $field) {
        $value = $incoming[$field] ?? '';
        if ($value === '' || $value === null) {
            continue;
        }
        if ((string)$existing[$field] !== (string)$value) {
            $data[$field] = $value;
        }
    }

    return $data;
}

function eeg_verw_prepare_zaehlpunkt_insert_data($mitglied_id, $incoming, $now)
{
    return [
        'mitglied_id' => $mitglied_id,
        'zaehlpunkt' => $incoming['zaehlpunkt'] ?? '',
        'zp_status' => $incoming['zp_status'] ?? '',
        'zp_nr' => $incoming['zp_nr'] ?? '',
        'zaehlpunktname' => $incoming['zaehlpunktname'] ?? '',
        'registriert' => $incoming['registriert'] !== '' ? $incoming['registriert'] : null,
        'bezugsrichtung' => $incoming['bezugsrichtung'] ?? '',
        'teilnahme_fkt' => $incoming['teilnahme_fkt'] ?? '',
        'wechselrichter_nr' => $incoming['wechselrichter_nr'] ?? '',
        'plz' => $incoming['plz'] ?? '',
        'ort' => $incoming['ort'] ?? '',
        'strasse' => $incoming['strasse'] ?? '',
        'hausnummer' => $incoming['hausnummer'] ?? '',
        'aktiviert' => $incoming['aktiviert'] !== '' ? $incoming['aktiviert'] : null,
        'deaktiviert' => $incoming['deaktiviert'] !== '' ? $incoming['deaktiviert'] : null,
        'tarifname' => $incoming['tarifname'] ?? '',
        'umspannwerk' => $incoming['umspannwerk'] ?? '',
        'created_at' => $now,
        'updated_at' => $now,
    ];
}

function eeg_verw_format_zaehlpunkt_update($data)
{
    $formats = [];
    foreach ($data as $key => $value) {
        if ($key === 'mitglied_id') {
            $formats[] = '%d';
        } elseif (in_array($key, ['registriert', 'aktiviert', 'deaktiviert'], true)) {
            $formats[] = '%s';
        } else {
            $formats[] = '%s';
        }
    }
    return $formats;
}

function eeg_verw_zaehlpunkt_key($row)
{
    $zaehlpunkt = trim((string)($row['zaehlpunkt'] ?? ''));
    if ($zaehlpunkt !== '') {
        return 'zp:' . $zaehlpunkt;
    }
    $zp_nr = trim((string)($row['zp_nr'] ?? ''));
    if ($zp_nr !== '') {
        return 'nr:' . $zp_nr;
    }
    return '';
}

function eeg_verw_zaehlpunkt_label($row)
{
    $label = trim((string)($row['zaehlpunkt'] ?? ''));
    if ($label !== '') {
        return $label;
    }
    $label = trim((string)($row['zp_nr'] ?? ''));
    return $label !== '' ? $label : __('(ohne Kennung)', 'eeg-verwaltung');
}

function eeg_verw_member_label($member)
{
    if (!empty($member['firma'])) {
        return $member['firma'];
    }
    $name = trim($member['vorname'] . ' ' . $member['nachname']);
    return $name !== '' ? $name : __('(ohne Name)', 'eeg-verwaltung');
}

function eeg_verw_get_default_mitgliedsart_id()
{
    global $wpdb;
    $table = eeg_verw_table_mitgliedsarten();

    $id = $wpdb->get_var("SELECT id FROM $table WHERE aktiv = 1 ORDER BY sort_order ASC, id ASC LIMIT 1");
    if ($id) {
        return (int)$id;
    }

    return 1;
}

function eeg_verw_find_or_create_user($email)
{
    $email = sanitize_email($email);
    if ($email === '') {
        return new WP_Error('eeg_import_user_email', __('Ungültige E-Mail.', 'eeg-verwaltung'));
    }

    $user_id = username_exists($email);
    if (!$user_id) {
        $user_id = email_exists($email);
    }
    if ($user_id) {
        return (int)$user_id;
    }

    $random_password = wp_generate_password(12, false);

    $role = 'subscriber';
    if (function_exists('wp_roles')) {
        $roles_obj = wp_roles();
        if ($roles_obj && $roles_obj->is_role('mitglied')) {
            $role = 'mitglied';
        }
    }

    $user_id = wp_insert_user([
        'user_login' => sanitize_user($email),
        'user_email' => $email,
        'user_pass' => $random_password,
        'role' => $role,
    ]);

    return $user_id;
}

function eeg_verw_normalize_header($value)
{
    $value = (string)$value;
    $value = preg_replace('/\x{FEFF}/u', '', $value);
    $value = trim(mb_strtolower($value));

    $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $value);
    $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);
    $value = trim(preg_replace('/\s+/', ' ', $value));

    return $value;
}

function eeg_verw_normalize_sheet_name($value)
{
    $normalized = eeg_verw_normalize_header($value);
    return str_replace(' ', '', $normalized);
}

function eeg_verw_normalize_date($value)
{
    if ($value === '' || $value === null) {
        return '';
    }

    if (is_numeric($value)) {
        $days = (int)$value;
        if ($days > 0) {
            $base = new DateTime('1899-12-30');
            $base->modify('+' . $days . ' days');
            return $base->format('Y-m-d');
        }
    }

    $timestamp = strtotime((string)$value);
    if ($timestamp) {
        return gmdate('Y-m-d', $timestamp);
    }

    return trim((string)$value);
}

function eeg_verw_read_xlsx_sheet($file_path, $sheet_name)
{
    if (!class_exists('ZipArchive')) {
        return new WP_Error('eeg_import_zip_missing', __('ZipArchive ist nicht verfügbar.', 'eeg-verwaltung'));
    }

    $zip = new ZipArchive();
    if ($zip->open($file_path) !== true) {
        return new WP_Error('eeg_import_zip_open', __('Die Excel-Datei konnte nicht geöffnet werden.', 'eeg-verwaltung'));
    }

    $workbook_xml = $zip->getFromName('xl/workbook.xml');
    if ($workbook_xml === false) {
        $zip->close();
        return new WP_Error('eeg_import_workbook_missing', __('Workbook-Daten fehlen.', 'eeg-verwaltung'));
    }

    $workbook = simplexml_load_string($workbook_xml);
    if (!$workbook) {
        $zip->close();
        return new WP_Error('eeg_import_workbook_invalid', __('Workbook konnte nicht gelesen werden.', 'eeg-verwaltung'));
    }

    $ns_main = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $ns_r = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    $sheets = $workbook->children($ns_main)->sheets->sheet;
    if (!$sheets) {
        $zip->close();
        return new WP_Error('eeg_import_workbook_invalid', __('Workbook konnte nicht gelesen werden.', 'eeg-verwaltung'));
    }

    $rels_xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($rels_xml === false) {
        $zip->close();
        return new WP_Error('eeg_import_rels_missing', __('Workbook-Beziehungen fehlen.', 'eeg-verwaltung'));
    }
    $rels = simplexml_load_string($rels_xml);
    if (!$rels) {
        $zip->close();
        return new WP_Error('eeg_import_rels_invalid', __('Workbook-Beziehungen konnten nicht gelesen werden.', 'eeg-verwaltung'));
    }
    $ns_pkg = 'http://schemas.openxmlformats.org/package/2006/relationships';
    $relationships = $rels->children($ns_pkg)->Relationship;

    $sheet_target = '';
    $available_sheets = [];
    $normalized_sheet_name = eeg_verw_normalize_sheet_name($sheet_name);
    foreach ($sheets as $sheet) {
        $name = (string)$sheet['name'];
        $available_sheets[] = $name;

        $normalized_name = eeg_verw_normalize_sheet_name($name);
        if ($normalized_name === $normalized_sheet_name) {
            $rid_attrs = $sheet->attributes($ns_r);
            $rel_id = (string)$rid_attrs['id'];
            foreach ($relationships as $rel) {
                if ((string)$rel['Id'] === $rel_id) {
                    $target = (string)$rel['Target'];
                    $sheet_target = 'xl/' . ltrim($target, '/');
                    break 2;
                }
            }
        }
    }

    if ($sheet_target === '' && count($sheets) === 1) {
        $sheet = $sheets[0];
        $rid_attrs = $sheet->attributes($ns_r);
        $rel_id = (string)$rid_attrs['id'];
        foreach ($relationships as $rel) {
            if ((string)$rel['Id'] === $rel_id) {
                $target = (string)$rel['Target'];
                $sheet_target = 'xl/' . ltrim($target, '/');
                break;
            }
        }
    }

    if ($sheet_target === '') {
        $zip->close();
        $available = $available_sheets ? implode(', ', $available_sheets) : __('keine', 'eeg-verwaltung');
        return new WP_Error(
            'eeg_import_sheet_missing',
            sprintf(
                __('Arbeitsblatt „%s“ nicht gefunden. Verfügbare Arbeitsblätter: %s.', 'eeg-verwaltung'),
                $sheet_name,
                $available
            )
        );
    }

    $shared_strings = [];
    $shared_xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($shared_xml !== false) {
        $shared = simplexml_load_string($shared_xml);
        if ($shared && isset($shared->si)) {
            foreach ($shared->si as $si) {
                $shared_strings[] = eeg_verw_shared_string_value($si);
            }
        }
    }

    $sheet_xml = $zip->getFromName($sheet_target);
    $zip->close();

    if ($sheet_xml === false) {
        return new WP_Error('eeg_import_sheet_invalid', __('Arbeitsblatt konnte nicht gelesen werden.', 'eeg-verwaltung'));
    }

    $sheet = simplexml_load_string($sheet_xml);
    if (!$sheet || !isset($sheet->sheetData->row)) {
        return new WP_Error('eeg_import_sheet_invalid', __('Arbeitsblatt enthält keine Daten.', 'eeg-verwaltung'));
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $row_data = [];
        foreach ($row->c as $cell) {
            $cell_ref = (string)$cell['r'];
            $col_index = eeg_verw_column_index_from_cell($cell_ref);
            $value = '';
            $cell_type = (string)$cell['t'];

            if ($cell_type === 's') {
                $idx = (int)$cell->v;
                $value = $shared_strings[$idx] ?? '';
            } elseif ($cell_type === 'inlineStr') {
                $value = (string)$cell->is->t;
            } else {
                $value = (string)$cell->v;
            }

            $row_data[$col_index] = $value;
        }

        if (!empty($row_data)) {
            ksort($row_data);
            $rows[] = $row_data;
        }
    }

    return $rows;
}

function eeg_verw_shared_string_value($si)
{
    if (isset($si->t)) {
        return (string)$si->t;
    }

    $text = '';
    if (isset($si->r)) {
        foreach ($si->r as $run) {
            $text .= (string)$run->t;
        }
    }

    return $text;
}

function eeg_verw_column_index_from_cell($cell_ref)
{
    $letters = preg_replace('/[^A-Z]/', '', strtoupper($cell_ref));
    $index = 0;
    $length = strlen($letters);
    for ($i = 0; $i < $length; $i++) {
        $index = $index * 26 + (ord($letters[$i]) - 64);
    }

    return $index - 1;
}
