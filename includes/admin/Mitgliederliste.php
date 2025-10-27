<?php
if (!defined('ABSPATH')) { exit; }

function eeg_verw_admin_mitgliederliste_bootstrap(){
    add_action('admin_menu', function(){
        add_users_page(
            __('Mitgliederliste','eeg-verwaltung'),
            __('Mitgliederliste','eeg-verwaltung'),
            'list_users',
            'eeg-Mitgliederliste',
            'eeg_verw_render_mgmt'
        );
    });
}

function eeg_verw_render_mgmt(){
    if (!current_user_can('list_users')) wp_die('Kein Zugriff.');
    global $wpdb;
    $table = eeg_verw_table_mitglieder();

    if (isset($_POST['eeg_verw_mgmt_nonce']) && wp_verify_nonce($_POST['eeg_verw_mgmt_nonce'], 'eeg_verw_mgmt')){
        $uid = intval($_POST['user_id'] ?? 0);
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $company = sanitize_text_field($_POST['company'] ?? '');
        $custom = sanitize_text_field($_POST['eeg_custom_field'] ?? '');
        if ($uid && current_user_can('edit_user', $uid)){
            $wpdb->update($table, [
                'phone' => $phone,
                'company' => $company,
                'custom_field' => $custom,
                'updated_at' => current_time('mysql'),
            ], ['user_id' => $uid], ['%s','%s','%s','%s'], ['%d']);
            update_user_meta($uid, 'eeg_custom_field', $custom);
            update_user_meta($uid, 'phone', $phone);
            update_user_meta($uid, 'company', $company);
            echo '<div class="notice notice-success"><p>'.esc_html__('Gespeichert.','eeg-verwaltung').'</p></div>';
        }
    }

    $rows = $wpdb->get_results("SELECT m.*, u.user_email, u.display_name 
                                FROM {$table} m 
                                INNER JOIN {$wpdb->users} u ON u.ID = m.user_id 
                                ORDER BY u.display_name ASC");

    echo '<div class="wrap"><h1>'.esc_html__('Verwaltungsliste','eeg-verwaltung').'</h1>';
    if (!$rows){
        echo '<p>'.esc_html__('Keine Einträge gefunden.','eeg-verwaltung').'</p></div>';
        return;
    }
    echo '<table class="widefat striped"><thead><tr>
            <th>ID</th><th>'.esc_html__('Nr.','eeg-verwaltung').'</th>
            <th>'.esc_html__('Name','eeg-verwaltung').'</th><th>E-Mail</th>
            <th>'.esc_html__('Telefon','eeg-verwaltung').'</th><th>'.esc_html__('Firma','eeg-verwaltung').'</th>
            <th>'.esc_html__('Custom Field','eeg-verwaltung').'</th>
            <th>'.esc_html__('Consent','eeg-verwaltung').'</th>
            <th>'.esc_html__('Aktionen','eeg-verwaltung').'</th>
          </tr></thead><tbody>';
    foreach ($rows as $r){
        $cons = $r->consent_at ? '✓ ' . esc_html($r->consent_at) : '✗';
        echo '<tr>';
        echo '<td>'.intval($r->user_id).'</td>';
        echo '<td>'.esc_html($r->member_no).'</td>';
        echo '<td>'.esc_html($r->display_name).'</td>';
        echo '<td><a href="mailto:'.esc_attr($r->user_email).'">'.esc_html($r->user_email).'</a></td>';
        echo '<td>
                <form method="post" style="display:flex;gap:6px;align-items:center;">
                    '.wp_nonce_field('eeg_verw_mgmt','eeg_verw_mgmt_nonce',true,false).'
                    <input type="hidden" name="user_id" value="'.intval($r->user_id).'">
                    <input type="text" name="phone" value="'.esc_attr($r->phone).'" size="12"/>
              </td>';
        echo '<td><input formmethod="post" type="text" name="company" value="'.esc_attr($r->company).'" size="14"/></td>';
        echo '<td><input formmethod="post" type="text" name="eeg_custom_field" value="'.esc_attr($r->custom_field).'" size="16"/></td>';
        echo '<td>'.$cons.'</td>';
        echo '<td><button class="button button-primary" type="submit">'.esc_html__('Speichern','eeg-verwaltung').'</button></td>';
        echo '</form>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
