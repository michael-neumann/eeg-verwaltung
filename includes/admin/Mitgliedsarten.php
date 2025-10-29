<?php
if (!defined('ABSPATH')) exit;

function eeg_verw_admin_mitgliedsarten_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Nicht erlaubt.');
    }

    global $wpdb;
    $table = eeg_verw_table_mitgliedsarten();

    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';
    $nonce  = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';

    // CREATE / UPDATE
    if (in_array($action, ['create','update'], true) && wp_verify_nonce($nonce, 'eeg_ms_save')) {
        $id   = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $bez  = isset($_POST['bezeichnung']) ? sanitize_text_field($_POST['bezeichnung']) : '';
        $tarB = isset($_POST['eeg_faktura_tarif_bezug']) ? sanitize_text_field($_POST['eeg_faktura_tarif_bezug']) : '';
        $tarE = isset($_POST['eeg_faktura_tarif_einspeisung']) ? sanitize_text_field($_POST['eeg_faktura_tarif_einspeisung']) : '';

        $data = [
            'bezeichnung' => $bez,
            'eeg_faktura_tarif_bezug' => $tarB,
            'eeg_faktura_tarif_einspeisung' => $tarE,
        ];
        $format = ['%s','%s','%s'];

        if ($action === 'create') {
            $wpdb->insert($table, $data, $format);
            echo '<div class="updated notice"><p>Mitgliedsart angelegt.</p></div>';
        } else {
            $wpdb->update($table, $data, ['id' => $id], $format, ['%d']);
            echo '<div class="updated notice"><p>Mitgliedsart aktualisiert.</p></div>';
        }
    }

    // DELETE
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

    // Liste
    $items = $wpdb->get_results("SELECT * FROM {$table} ORDER BY bezeichnung ASC");
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Mitgliedsarten</h1>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'eeg-mitgliedsarten', 'action' => 'new'])); ?>" class="page-title-action">Neu</a>
        <hr class="wp-header-end" />

        <?php if ($action === 'new' || $action === 'edit'): ?>
            <?php $is_edit = ($action === 'edit' && $edit_item); ?>
            <h2><?php echo $is_edit ? 'Mitgliedsart bearbeiten' : 'Neue Mitgliedsart'; ?></h2>

            <form method="post">
                <?php wp_nonce_field('eeg_ms_save'); ?>
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?php echo (int) $edit_item->id; ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th><label for="bezeichnung">Bezeichnung *</label></th>
                        <td><input name="bezeichnung" type="text" id="bezeichnung" class="regular-text" required value="<?php echo esc_attr($is_edit ? $edit_item->bezeichnung : ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="eeg_faktura_tarif_bezug">Faktura Tarif Bezug</label></th>
                        <td><input name="eeg_faktura_tarif_bezug" type="text" id="eeg_faktura_tarif_bezug" class="regular-text" value="<?php echo esc_attr($is_edit ? $edit_item->eeg_faktura_tarif_bezug : ''); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="eeg_faktura_tarif_einspeisung">Faktura Tarif Einspeisung</label></th>
                        <td><input name="eeg_faktura_tarif_einspeisung" type="text" id="eeg_faktura_tarif_einspeisung" class="regular-text" value="<?php echo esc_attr($is_edit ? $edit_item->eeg_faktura_tarif_einspeisung : ''); ?>"></td>
                    </tr>
                    </tbody>
                </table>

                <?php submit_button($is_edit ? 'Speichern' : 'Anlegen'); ?>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=eeg-mitgliedsarten')); ?>">Abbrechen</a>
            </form>

        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th style="width:70px;">ID</th>
                    <th>Bezeichnung</th>
                    <th>Tarif Bezug</th>
                    <th>Tarif Einspeisung</th>
                    <th style="width:180px;">Aktionen</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($items): foreach ($items as $row): ?>
                    <tr>
                        <td><?php echo (int)$row->id; ?></td>
                        <td><?php echo esc_html($row->bezeichnung); ?></td>
                        <td><?php echo esc_html($row->eeg_faktura_tarif_bezug); ?></td>
                        <td><?php echo esc_html($row->eeg_faktura_tarif_einspeisung); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page'=>'eeg-mitgliedsarten','action'=>'edit','id'=>$row->id])); ?>">Bearbeiten</a>
                            <a class="button button-small button-link-delete" href="<?php echo wp_nonce_url(add_query_arg(['page'=>'eeg-mitgliedsarten','action'=>'delete','id'=>$row->id]), 'eeg_ms_delete'); ?>" onclick="return confirm('Diese Mitgliedsart wirklich löschen?');">Löschen</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5">Keine Einträge gefunden.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}