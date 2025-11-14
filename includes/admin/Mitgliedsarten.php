<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin-Seite: Mitgliedsarten (CRUD + Aktiv-Toggle)
 * - nutzt admin-post.php (POST) für Toggle, damit keine "headers already sent"-Warnungen entstehen
 * - sortiert: aktive zuerst, dann nach Priorität (sort_order ASC), dann Bezeichnung
 * - zeigt "Ja/Nein" für Aktiv-Spalte
 */

function eeg_verw_admin_mitgliedsarten_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Nicht erlaubt.');
    }

    global $wpdb;
    $table = eeg_verw_table_mitgliedsarten(); // Helper-Funktion in deinen DB-Utilities

    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';
    $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';

    // ===== CREATE / UPDATE =====
    if (in_array($action, ['create', 'update'], true) && wp_verify_nonce($nonce, 'eeg_ms_save')) {
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $sort_order = isset($_POST['sort_order']) ? max(0, (int)$_POST['sort_order']) : 0;
        $bez = isset($_POST['bezeichnung']) ? sanitize_text_field($_POST['bezeichnung']) : '';
        $tarB = isset($_POST['eeg_faktura_tarif_bezug']) ? sanitize_text_field($_POST['eeg_faktura_tarif_bezug']) : '';
        $tarE = isset($_POST['eeg_faktura_tarif_einspeisung']) ? sanitize_text_field($_POST['eeg_faktura_tarif_einspeisung']) : '';
        $uid_pflicht = isset($_POST['uid_pflicht']) ? 1 : 0;
        $firmenname_pflicht = isset($_POST['firmenname_pflicht']) ? 1 : 0;
        $aktiv = isset($_POST['aktiv']) ? 1 : 0;

        $data = [
                'sort_order' => $sort_order,
                'bezeichnung' => $bez,
                'eeg_faktura_tarif_bezug' => $tarB,
                'eeg_faktura_tarif_einspeisung' => $tarE,
                'uid_pflicht' => $uid_pflicht,
                'firmenname_pflicht' => $firmenname_pflicht,
                'aktiv' => $aktiv,
        ];
        $format = ['%d', '%s', '%s', '%s', '%d', '%d', '%d'];

        if ($action === 'create') {
            $wpdb->insert($table, $data, $format);
            echo '<div class="updated notice"><p>Mitgliedsart angelegt.</p></div>';
        } else {
            $wpdb->update($table, $data, ['id' => $id], $format, ['%d']);
            echo '<div class="updated notice"><p>Mitgliedsart aktualisiert.</p></div>';
        }
    }

    // ===== DELETE =====
    if ($action === 'delete' && wp_verify_nonce($nonce, 'eeg_ms_delete')) {
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id) {
            $wpdb->delete($table, ['id' => $id], ['%d']);
            echo '<div class="updated notice"><p>Mitgliedsart gelöscht.</p></div>';
        }
    }

    // Datensatz für „Bearbeiten“
    $edit_item = null;
    if ($action === 'edit') {
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($id) {
            $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        }
    }

    // Liste (Aktive zuerst, dann nach Priorität und Name)
    $items = $wpdb->get_results("
        SELECT *
        FROM {$table}
        ORDER BY aktiv DESC, sort_order ASC, bezeichnung ASC
    ");
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Mitgliedsarten</h1>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'eeg-mitgliedsarten', 'action' => 'new'], admin_url('admin.php'))); ?>"
           class="page-title-action">Neu</a>
        <hr class="wp-header-end"/>

        <?php if ($action === 'new' || $action === 'edit'): ?>
            <?php $is_edit = ($action === 'edit' && $edit_item); ?>
            <h2><?php echo $is_edit ? 'Mitgliedsart bearbeiten' : 'Neue Mitgliedsart'; ?></h2>

            <form method="post">
                <?php wp_nonce_field('eeg_ms_save'); ?>
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$edit_item->id; ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th><label for="sort_order">Priorität</label></th>
                        <td>
                            <label>
                                <input name="sort_order" type="number" id="sort_order" class="small-text" min="0"
                                       step="1"
                                       value="<?php echo esc_attr($is_edit ? (int)$edit_item->sort_order : 0); ?>">
                                Sortierung in der Anzeige (kleiner = weiter oben)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bezeichnung">Bezeichnung *</label></th>
                        <td>
                            <input name="bezeichnung" type="text" id="bezeichnung" class="regular-text" required
                                   value="<?php echo esc_attr($is_edit ? $edit_item->bezeichnung : ''); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="eeg_faktura_tarif_bezug">Faktura Tarif Bezug</label></th>
                        <td>
                            <label>
                                <input name="eeg_faktura_tarif_bezug" type="text" id="eeg_faktura_tarif_bezug"
                                       class="regular-text"
                                       value="<?php echo esc_attr($is_edit ? $edit_item->eeg_faktura_tarif_bezug : ''); ?>">
                                Name des Bezugstarifs in der EEG Faktura
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="eeg_faktura_tarif_einspeisung">Faktura Tarif Einspeisung</label></th>
                        <td>
                            <label>
                                <input name="eeg_faktura_tarif_einspeisung" type="text"
                                       id="eeg_faktura_tarif_einspeisung" class="regular-text"
                                       value="<?php echo esc_attr($is_edit ? $edit_item->eeg_faktura_tarif_einspeisung : ''); ?>">
                                Name des Einspeisetarifs in der EEG Faktura
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="uid_pflicht">UID Plficht</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="uid_pflicht"
                                       id="uid_pflicht" <?php checked($is_edit ? (int)$edit_item->uid_pflicht : 1, 1); ?>>
                                sichtbar und verpflichtend
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="firmenname_pflicht">Firmenname Plficht</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="firmenname_pflicht"
                                       id="firmenname_pflicht" <?php checked($is_edit ? (int)$edit_item->firmenname_pflicht : 1, 1); ?>>
                                sichtbar und verpflichtend
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="aktiv">Aktiv</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="aktiv"
                                       id="aktiv" <?php checked($is_edit ? (int)$edit_item->aktiv : 1, 1); ?>>
                                sichtbar/auswählbar
                            </label>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php submit_button($is_edit ? 'Speichern' : 'Anlegen'); ?>
                <a class="button button-secondary"
                   href="<?php echo esc_url(admin_url('admin.php?page=eeg-mitgliedsarten')); ?>">Abbrechen</a>
            </form>

        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Priorität</th>
                    <th>Bezeichnung</th>
                    <th>Tarif Bezug</th>
                    <th>Tarif Einspeisung</th>
                    <th>UID Pflicht</th>
                    <th>Firmenname Pflicht</th>
                    <th>Aktiv</th>
                    <th style="width:220px;">Aktionen</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($items): foreach ($items as $row): ?>
                    <tr>
                        <td><?php echo (int)$row->id; ?></td>
                        <td><?php echo (int)$row->sort_order; ?></td>
                        <td><?php echo esc_html($row->bezeichnung); ?></td>
                        <td><?php echo esc_html($row->eeg_faktura_tarif_bezug); ?></td>
                        <td><?php echo esc_html($row->eeg_faktura_tarif_einspeisung); ?></td>
                        <td><?php echo $row->uid_pflicht ? 'Ja' : 'Nein'; ?></td>
                        <td><?php echo $row->firmenname_pflicht ? 'Ja' : 'Nein'; ?></td>
                        <td><?php echo $row->aktiv ? 'Ja' : 'Nein'; ?></td>
                        <td>
                            <!-- Toggle Aktiv via admin-post.php (POST) -->
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                  style="display:inline; margin-right:6px;">
                                <?php wp_nonce_field('eeg_ms_toggle'); ?>
                                <input type="hidden" name="action" value="eeg_toggle_mitgliedsart">
                                <input type="hidden" name="id" value="<?php echo (int)$row->id; ?>">
                                <button class="button button-small" type="submit">
                                    <?php echo $row->aktiv ? 'Deaktivieren' : 'Aktivieren'; ?>
                                </button>
                            </form>

                            <a class="button button-small"
                               href="<?php echo esc_url(add_query_arg(['page' => 'eeg-mitgliedsarten', 'action' => 'edit', 'id' => $row->id], admin_url('admin.php'))); ?>">Bearbeiten</a>

                            <a class="button button-small button-link-delete"
                               href="<?php echo wp_nonce_url(add_query_arg(['page' => 'eeg-mitgliedsarten', 'action' => 'delete', 'id' => $row->id], admin_url('admin.php')), 'eeg_ms_delete'); ?>"
                               onclick="return confirm('Diese Mitgliedsart wirklich löschen?');">
                                Löschen
                            </a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="7">Keine Einträge gefunden.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * POST-Handler: Toggle "aktiv" für Mitgliedsarten
 * Wird vor dem Seiten-Output ausgeführt → kein "headers already sent".
 */
add_action('admin_post_eeg_toggle_mitgliedsart', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Nicht erlaubt.');
    }
    check_admin_referer('eeg_ms_toggle');

    global $wpdb;
    $table = eeg_verw_table_mitgliedsarten();
    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

    if ($id) {
        $current = (int)$wpdb->get_var($wpdb->prepare("SELECT aktiv FROM {$table} WHERE id = %d", $id));
        $wpdb->update($table, ['aktiv' => $current ? 0 : 1], ['id' => $id], ['%d'], ['%d']);
    }

    // Zurück zur Liste
    wp_safe_redirect(admin_url('admin.php?page=eeg-mitgliedsarten'));
    exit;
});
