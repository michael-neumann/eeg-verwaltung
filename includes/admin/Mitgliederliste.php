<?php
if (!defined('ABSPATH')) {
    exit;
}

// Sicherstellen, dass WP_List_Table verfügbar ist
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


/**
 * List Table
 */
class EEG_Verw_Mitglieder_List_Table extends WP_List_Table
{
    private $items_total = 0;

    public function __construct()
    {
        parent::__construct([
                'singular' => 'mitglied',
                'plural' => 'mitglieder',
                'ajax' => false,
        ]);
    }

    protected function get_primary_column_name()
    {
        return 'name';
    }

    public function get_columns()
    {
        return [
                'cb' => '',
                'mitgliedsnummer' => __('Mitgliedsnummer', 'eeg-verwaltung'),
                'mitgliedsart' => __('Mitgliedsart', 'eeg-verwaltung'),
                'name' => __('Name', 'eeg-verwaltung'),
                'zaehlpunkte' => __('Zählpunkte', 'eeg-verwaltung'),
                'adresse' => __('Adresse', 'eeg-verwaltung'),
                'ort' => __('Ort', 'eeg-verwaltung'),
                'telefonnummer' => __('Telefon', 'eeg-verwaltung'),
                'email' => __('E-Mail', 'eeg-verwaltung'),
                'aktiv' => __('Status', 'eeg-verwaltung'),
                'created_at' => __('Angelegt', 'eeg-verwaltung'),
        ];
    }

    protected function get_sortable_columns()
    {
        return [
                'mitgliedsnummer' => ['mitgliedsnummer', false],
                'name' => ['name', false],
                'mitgliedsart' => ['mitgliedsart', false],
                'email' => ['email', false],
                'ort' => ['ort', false],
                'created_at' => ['created_at', false],
        ];
    }

    protected function column_cb($item)
    {
        return '<input type="checkbox" name="ids[]" value="' . (int)$item['id'] . '" />';
    }

    public function get_bulk_actions()
    {
        $actions = [];
        $actions['bulk_activate'] = __('Aktivieren', 'eeg-verwaltung');
        $actions['bulk_deactivate'] = __('Deaktivieren', 'eeg-verwaltung');
        $actions['bulk_delete'] = __('Löschen', 'eeg-verwaltung');
        return $actions;
    }

    protected function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'mitgliedsnummer':
            case 'email':
            case 'telefonnummer':
            case 'adresse':
            case 'name':
            case 'ort':
                return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
            case 'mitgliedsart':
                if (isset($item['mitgliedsart'])) {
                    return esc_html($item['mitgliedsart']);
                }
                return '—';
            case 'zaehlpunkte':
                $status_counts = $item['zaehlpunkte_status_counts'] ?? [];
                if (empty($status_counts)) {
                    return '—';
                }

                $parts = [];
                foreach ($status_counts as $status => $count) {
                    if ((int)$count <= 0) {
                        continue;
                    }
                    $label = $status !== '' ? $status : esc_html__('Ohne Status', 'eeg-verwaltung');
                    $parts[] = sprintf('%s: %d', esc_html($label), (int)$count);
                }

                if (empty($parts)) {
                    return '—';
                }

                return implode(' / ', $parts);
            case 'aktiv':
                if (function_exists('eeg_verw_badge_aktiv')) {
                    return eeg_verw_badge_aktiv($item['aktiv']);
                }
                if ((int)$item['aktiv']) {
                    return __('Aktiv', 'eeg-verwaltung');
                }
                return __('Inaktiv', 'eeg-verwaltung');
            case 'created_at':
                if (!empty($item['created_at'])) {
                    $ts = strtotime($item['created_at']);
                    if ($ts) {
                        return esc_html(date_i18n(get_option('date_format'), $ts));
                    }
                }
                return '—';
            default:
                return '';
        }
    }

    protected function column_mitgliedsnummer($item)
    {
        $edit_url = add_query_arg(
                [
                        'page' => 'eeg-mitgliederliste',
                        'action' => 'edit',
                        'id' => $item['id'],
                ],
                admin_url('admin.php')
        );

        $toggle_action = 'activate';
        $toggle_label = __('Aktivieren', 'eeg-verwaltung');
        if ((int)$item['aktiv'] === 1) {
            $toggle_action = 'deactivate';
            $toggle_label = __('Deaktivieren', 'eeg-verwaltung');
        }

        $nonce_toggle = wp_create_nonce('eeg_mitglied_toggle_' . $item['id']);
        $nonce_delete = wp_create_nonce('eeg_mitglied_delete_' . $item['id']);

        $actions = [];

        $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($edit_url),
                esc_html__('Bearbeiten', 'eeg-verwaltung')
        );

        $actions['toggle'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(add_query_arg(
                        [
                                'page' => 'eeg-mitgliederliste',
                                'action' => $toggle_action,
                                'id' => $item['id'],
                                '_wpnonce' => $nonce_toggle,
                        ],
                        admin_url('admin.php')
                )),
                esc_html($toggle_label)
        );

        $actions['delete'] = sprintf(
                '<a href="%s" onclick="return confirm(%s);">%s</a>',
                esc_url(add_query_arg(
                        [
                                'page' => 'eeg-mitgliederliste',
                                'action' => 'delete',
                                'id' => $item['id'],
                                '_wpnonce' => $nonce_delete,
                        ],
                        admin_url('admin.php')
                )),
                wp_json_encode(__('Wirklich löschen?', 'eeg-verwaltung')),
                esc_html__('Löschen', 'eeg-verwaltung')
        );

        $title = '<a href="' . esc_url($edit_url) . '"><strong>' . esc_html($item['mitgliedsnummer']) . '</strong></a>';

        return $title . $this->row_actions($actions);
    }

    protected function get_views()
    {
        $base = add_query_arg(['page' => 'eeg-mitgliederliste'], admin_url('admin.php'));
        $current = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'all';
        $counts = $this->counts_by_status();

        $views = [];

        // Alle
        $class_all = '';
        if ($current === 'all') {
            $class_all = ' class="current"';
        }
        $views['all'] =
                '<a href="' . esc_url($base) . '"' . $class_all . '>' .
                sprintf(__('Alle (%d)', 'eeg-verwaltung'), $counts['all']) .
                '</a>';

        // Aktiv
        $url_active = add_query_arg('status', 'active', $base);
        $class_act = '';
        if ($current === 'active') {
            $class_act = ' class="current"';
        }
        $views['active'] =
                '<a href="' . esc_url($url_active) . '"' . $class_act . '>' .
                sprintf(__('Aktiv (%d)', 'eeg-verwaltung'), $counts['active']) .
                '</a>';

        // Inaktiv
        $url_inactive = add_query_arg('status', 'inactive', $base);
        $class_inact = '';
        if ($current === 'inactive') {
            $class_inact = ' class="current"';
        }
        $views['inactive'] =
                '<a href="' . esc_url($url_inactive) . '"' . $class_inact . '>' .
                sprintf(__('Inaktiv (%d)', 'eeg-verwaltung'), $counts['inactive']) .
                '</a>';

        return $views;
    }

    private function counts_by_status(): array
    {
        global $wpdb;
        $table_m = eeg_verw_table_mitglieder();

        $all = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table_m}");
        $active = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table_m} WHERE aktiv = 1");
        $inactive = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table_m} WHERE aktiv = 0");

        return [
                'all' => $all,
                'active' => $active,
                'inactive' => $inactive,
        ];
    }

    public function prepare_items()
    {
        global $wpdb;

        $table_m = eeg_verw_table_mitglieder();
        $table_a = eeg_verw_table_mitgliedsarten();
        $table_zp = eeg_verw_table_zaehlpunkte();

        $per_page = 20;
        $current_page = max(1, $this->get_pagenum());
        $offset = ($current_page - 1) * $per_page;

        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'created_at';
        $order = 'DESC';
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') {
            $order = 'ASC';
        }

        $order_map = [
                'mitgliedsnummer' => 'm.mitgliedsnummer',
                'name' => 'm.nachname, m.vorname',
                'mitgliedsart' => 'a.bezeichnung',
                'email' => 'm.email',
                'ort' => 'm.ort',
                'created_at' => 'm.created_at',
        ];
        $order_by_sql = 'm.created_at';
        if (isset($order_map[$orderby])) {
            $order_by_sql = $order_map[$orderby];
        }

        $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'all';
        $search = '';
        if (isset($_REQUEST['s'])) {
            $search = trim(wp_unslash($_REQUEST['s']));
        }

        $where = [];
        $args = [];

        if ($status === 'active') {
            $where[] = 'm.aktiv = %d';
            $args[] = 1;
        } elseif ($status === 'inactive') {
            $where[] = 'm.aktiv = %d';
            $args[] = 0;
        }

        if ($search !== '') {
            $where[] = '(m.vorname LIKE %s OR m.nachname LIKE %s OR m.email LIKE %s OR m.mitgliedsnummer LIKE %s OR m.ort LIKE %s OR m.strasse LIKE %s)';
            $like = '%' . $wpdb->esc_like($search) . '%';
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
        }

        $where_sql = '';
        if (!empty($where)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where);
        }

        $sql_count = "SELECT COUNT(*) 
                      FROM {$table_m} m 
                      LEFT JOIN {$table_a} a ON a.id = m.mitgliedsart_id 
                      {$where_sql}";

        if (!empty($args)) {
            $total = $wpdb->get_var($wpdb->prepare($sql_count, $args));
        } else {
            $total = $wpdb->get_var($sql_count);
        }

        $this->items_total = (int)$total;

        $sql_list = "
            SELECT 
                m.id,
                m.mitgliedsnummer,
                CONCAT_WS(' ', m.vorname, m.nachname) AS name,
                a.bezeichnung AS mitgliedsart,
                m.email,
                m.telefonnummer,
                CONCAT_WS(' ', m.strasse, m.hausnummer) AS adresse,
                m.ort,
                m.aktiv,
                m.created_at
            FROM {$table_m} m
            LEFT JOIN {$table_a} a ON a.id = m.mitgliedsart_id
            {$where_sql}
            ORDER BY {$order_by_sql} {$order}
            LIMIT %d OFFSET %d
        ";

        $list_args = $args;
        $list_args[] = $per_page;
        $list_args[] = $offset;

        $query = $wpdb->prepare($sql_list, $list_args);
        $rows = $wpdb->get_results($query, ARRAY_A);

        $status_counts = [];
        if (!empty($rows)) {
            $ids = array_map('intval', array_column($rows, 'id'));
            $ids = array_values(array_filter($ids));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                $status_sql = "
                    SELECT mitglied_id, zp_status, COUNT(*) AS status_count
                    FROM {$table_zp}
                    WHERE mitglied_id IN ({$placeholders})
                    GROUP BY mitglied_id, zp_status
                ";
                $status_rows = $wpdb->get_results($wpdb->prepare($status_sql, $ids), ARRAY_A);
                foreach ($status_rows as $status_row) {
                    $mitglied_id = (int)$status_row['mitglied_id'];
                    $status = isset($status_row['zp_status']) ? (string)$status_row['zp_status'] : '';
                    $status_counts[$mitglied_id][trim($status)] = (int)$status_row['status_count'];
                }
            }
        }

        if (is_array($rows)) {
            foreach ($rows as &$row) {
                $row_id = (int)$row['id'];
                $row['zaehlpunkte_status_counts'] = $status_counts[$row_id] ?? [];
            }
            unset($row);
            $this->items = $rows;
        } else {
            $this->items = [];
        }

        $total_pages = 1;
        if ($this->items_total > 0) {
            $total_pages = (int)ceil($this->items_total / $per_page);
        }

        $this->set_pagination_args([
                'total_items' => $this->items_total,
                'per_page' => $per_page,
                'total_pages' => $total_pages,
        ]);

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable, $this->get_primary_column_name()];
    }
}

function eeg_verw_sanitize_zaehlpunkt_field($value)
{
    if ($value === null) {
        return '';
    }
    return sanitize_text_field(wp_unslash($value));
}

function eeg_verw_prepare_zaehlpunkte_from_request($raw_rows)
{
    $rows = [];
    if (!is_array($raw_rows)) {
        return $rows;
    }

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

    foreach ($raw_rows as $raw_row) {
        if (!is_array($raw_row)) {
            continue;
        }

        $row = [];
        $has_value = false;
        foreach ($fields as $field) {
            $value = '';
            if (array_key_exists($field, $raw_row)) {
                $value = eeg_verw_sanitize_zaehlpunkt_field($raw_row[$field]);
            }
            if ($value !== '') {
                $has_value = true;
            }
            $row[$field] = $value;
        }

        if ($has_value) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function eeg_verw_save_mitglied_zaehlpunkte($mitglied_id, $rows)
{
    global $wpdb;
    $table_zp = eeg_verw_table_zaehlpunkte();

    $mitglied_id = (int)$mitglied_id;
    if ($mitglied_id <= 0) {
        return;
    }

    $wpdb->delete($table_zp, ['mitglied_id' => $mitglied_id], ['%d']);

    if (empty($rows)) {
        return;
    }

    $now = current_time('mysql');
    foreach ($rows as $row) {
        $data = [
                'mitglied_id' => $mitglied_id,
                'zaehlpunkt' => $row['zaehlpunkt'],
                'zp_status' => $row['zp_status'],
                'zp_nr' => $row['zp_nr'],
                'zaehlpunktname' => $row['zaehlpunktname'],
                'registriert' => $row['registriert'] !== '' ? $row['registriert'] : null,
                'bezugsrichtung' => $row['bezugsrichtung'],
                'teilnahme_fkt' => $row['teilnahme_fkt'],
                'wechselrichter_nr' => $row['wechselrichter_nr'],
                'plz' => $row['plz'],
                'ort' => $row['ort'],
                'strasse' => $row['strasse'],
                'hausnummer' => $row['hausnummer'],
                'aktiviert' => $row['aktiviert'] !== '' ? $row['aktiviert'] : null,
                'deaktiviert' => $row['deaktiviert'] !== '' ? $row['deaktiviert'] : null,
                'tarifname' => $row['tarifname'],
                'umspannwerk' => $row['umspannwerk'],
                'created_at' => $now,
                'updated_at' => $now,
        ];

        $wpdb->insert(
                $table_zp,
                $data,
                [
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
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                ]
        );
    }
}

/**
 * Aktionen (vor Ausgabe) verarbeiten
 */
function eeg_verw_handle_mitglieder_actions()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $action = '';
    if (isset($_GET['action'])) {
        $action = sanitize_key($_GET['action']);
    }
    $id = 0;
    if (isset($_GET['id'])) {
        $id = absint($_GET['id']);
    }

    if (empty($action) && isset($_POST['action']) && $_POST['action'] !== '-1') {
        $action = sanitize_key($_POST['action']);
    }
    if (empty($action) && isset($_POST['action2']) && $_POST['action2'] !== '-1') {
        $action = sanitize_key($_POST['action2']);
    }

    global $wpdb;
    $table = eeg_verw_table_mitglieder();

    // Single toggle/delete
    if (!empty($id) && in_array($action, ['activate', 'deactivate', 'delete'], true)) {
        if ($action === 'activate' || $action === 'deactivate') {
            check_admin_referer('eeg_mitglied_toggle_' . $id);
            $flag = 0;
            if ($action === 'activate') {
                $flag = 1;
            }
            $wpdb->update(
                    $table,
                    [
                            'aktiv' => $flag,
                            'updated_at' => current_time('mysql'),
                    ],
                    ['id' => $id],
                    ['%d', '%s'],
                    ['%d']
            );
            if ($flag === 1) {
                add_settings_error(
                        'eeg_mitglieder',
                        'updated',
                        __('Mitglied aktiviert.', 'eeg-verwaltung'),
                        'updated'
                );
            } else {
                add_settings_error(
                        'eeg_mitglieder',
                        'updated',
                        __('Mitglied deaktiviert.', 'eeg-verwaltung'),
                        'updated'
                );
            }
        } elseif ($action === 'delete') {
            check_admin_referer('eeg_mitglied_delete_' . $id);
            $wpdb->delete($table, ['id' => $id], ['%d']);
            add_settings_error(
                    'eeg_mitglieder',
                    'deleted',
                    __('Mitglied gelöscht.', 'eeg-verwaltung'),
                    'updated'
            );
        }

        $redirect = remove_query_arg(['action', 'id', '_wpnonce']);
        wp_safe_redirect($redirect);
        exit;
    }

    // Bulk
    if (!empty($action) && in_array($action, ['bulk_activate', 'bulk_deactivate', 'bulk_delete'], true) && !empty($_POST['ids'])) {
        check_admin_referer('bulk-mitglieder');

        $ids = array_map('absint', (array)$_POST['ids']);
        $ids = array_filter($ids);

        if (!empty($ids)) {
            $in_placeholder = implode(',', array_fill(0, count($ids), '%d'));

            if ($action === 'bulk_activate' || $action === 'bulk_deactivate') {
                $flag = 0;
                if ($action === 'bulk_activate') {
                    $flag = 1;
                }
                $sql = "UPDATE {$table} SET aktiv = %d, updated_at = %s WHERE id IN ({$in_placeholder})";
                $args = [$flag, current_time('mysql')];
                $args = array_merge($args, $ids);
                $wpdb->query($wpdb->prepare($sql, $args));

                if ($flag === 1) {
                    add_settings_error(
                            'eeg_mitglieder',
                            'updated',
                            __('Mitglieder aktiviert.', 'eeg-verwaltung'),
                            'updated'
                    );
                } else {
                    add_settings_error(
                            'eeg_mitglieder',
                            'updated',
                            __('Mitglieder deaktiviert.', 'eeg-verwaltung'),
                            'updated'
                    );
                }
            } elseif ($action === 'bulk_delete') {
                $sql = "DELETE FROM {$table} WHERE id IN ({$in_placeholder})";
                $wpdb->query($wpdb->prepare($sql, $ids));

                add_settings_error(
                        'eeg_mitglieder',
                        'deleted',
                        __('Mitglieder gelöscht.', 'eeg-verwaltung'),
                        'updated'
                );
            }
        }

        $redirect = remove_query_arg(['action', '_wpnonce', 'ids']);
        wp_safe_redirect($redirect);
        exit;
    }

    // Save (Create/Update)
    if (isset($_POST['eeg_action']) && $_POST['eeg_action'] === 'save') {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nicht erlaubt.', 'eeg-verwaltung'));
        }

        check_admin_referer('eeg_mitglied_edit');

        $edit_id = 0;
        if (isset($_POST['id'])) {
            $edit_id = absint($_POST['id']);
        }
        $mitgliedsart_id = 0;
        if (isset($_POST['mitgliedsart_id'])) {
            $mitgliedsart_id = absint($_POST['mitgliedsart_id']);
        }

        // Basisfelder
        $firma = sanitize_text_field($_POST['firma'] ?? '');
        $vorname = sanitize_text_field($_POST['vorname'] ?? '');
        $nachname = sanitize_text_field($_POST['nachname'] ?? '');
        $strasse = sanitize_text_field($_POST['strasse'] ?? '');
        $hausnummer = sanitize_text_field($_POST['hausnummer'] ?? '');
        $plz = sanitize_text_field($_POST['plz'] ?? '');
        $ort = sanitize_text_field($_POST['ort'] ?? '');
        $telefonnummer = sanitize_text_field($_POST['telefonnummer'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        $uid = sanitize_text_field($_POST['uid'] ?? '');
        $dokumentenart = sanitize_text_field($_POST['dokumentenart'] ?? '');
        $dokumentennummer = sanitize_text_field($_POST['dokumentennummer'] ?? '');
        $iban = sanitize_text_field($_POST['iban'] ?? '');
        $kontoinhaber = sanitize_text_field($_POST['kontoinhaber'] ?? '');
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $aktiv = isset($_POST['aktiv']) ? 1 : 0;
        $zaehlpunkte_rows = eeg_verw_prepare_zaehlpunkte_from_request($_POST['zaehlpunkte'] ?? []);

        // IBAN prüfen
        if ($iban !== '') {
            $iban_clean = preg_replace('/[^A-Za-z0-9]/', '', $iban);

            if (!eeg_verw_check_iban($iban_clean)) {
                add_settings_error(
                        'eeg_mitglieder',
                        'iban_invalid',
                        __('Der eingegebene IBAN ist nicht gültig!', 'eeg-verwaltung'),
                        'error'
                );
                $_GET['action'] = 'edit';
                $_GET['id'] = $edit_id;
                return;
            }

            $iban = strtoupper($iban_clean);
        }

        // Email prüfen
        $exists_email = 0;
        if ($email !== '') {
            $exists_email = $wpdb->get_var(
                    $wpdb->prepare(
                            "SELECT COUNT(*) FROM $table WHERE email = %s AND id != %d",
                            $email,
                            (int)$edit_id
                    )
            );
        }

        if ($exists_email) {
            add_settings_error(
                    'eeg_mitglieder',
                    'duplicate_email',
                    __('Diese E-Mail-Adresse existiert bereits!', 'eeg-verwaltung'),
                    'error'
            );
            $_GET['action'] = 'edit';
            $_GET['id'] = $edit_id;
            return;
        }

        $mitgliedsnummer = sanitize_text_field($_POST['mitgliedsnummer'] ?? '');
        if ($mitgliedsnummer === '' && function_exists('eeg_verw_get_mitgliedsnummer')) {
            $mitgliedsnummer = (string)eeg_verw_get_mitgliedsnummer(0);
        }
        if (function_exists('eeg_verw_format_mitgliedsnummer')) {
            $mitgliedsnummer = eeg_verw_format_mitgliedsnummer($mitgliedsnummer);
        }

        $data = [
                'mitgliedsart_id' => $mitgliedsart_id,
                'firma' => $firma,
                'vorname' => $vorname,
                'nachname' => $nachname,
                'strasse' => $strasse,
                'hausnummer' => $hausnummer,
                'plz' => $plz,
                'ort' => $ort,
                'email' => $email,
                'telefonnummer' => $telefonnummer,
                'uid' => $uid,
                'dokumentenart' => $dokumentenart,
                'dokumentennummer' => $dokumentennummer,
                'iban' => $iban,
                'kontoinhaber' => $kontoinhaber,
                'status' => $status,
                'aktiv' => $aktiv,
                'updated_at' => current_time('mysql'),
        ];

        $format_map = [
                'mitgliedsart_id' => '%d',
                'firma' => '%s',
                'vorname' => '%s',
                'nachname' => '%s',
                'strasse' => '%s',
                'hausnummer' => '%s',
                'plz' => '%s',
                'ort' => '%s',
                'email' => '%s',
                'telefonnummer' => '%s',
                'uid' => '%s',
                'dokumentenart' => '%s',
                'dokumentennummer' => '%s',
                'iban' => '%s',
                'kontoinhaber' => '%s',
                'status' => '%d',
                'aktiv' => '%d',
                'updated_at' => '%s',
                'mitgliedsnummer' => '%s',
                'created_at' => '%s',
                'user_id' => '%d',
        ];

        $formats = [];
        foreach ($data as $k => $v) {
            if (isset($format_map[$k])) {
                $formats[] = $format_map[$k];
            } else {
                $formats[] = '%s';
            }
        }

        if ($mitgliedsnummer !== '') {
            $data['mitgliedsnummer'] = $mitgliedsnummer;
            $formats[] = '%s';
        }

        // UPDATE
        if (!empty($edit_id)) {
            $ok = $wpdb->update($table, $data, ['id' => $edit_id], $formats, ['%d']);

            if ($ok !== false) {
                eeg_verw_save_mitglied_zaehlpunkte($edit_id, $zaehlpunkte_rows);
                add_settings_error(
                        'eeg_mitglieder',
                        'updated',
                        __('Mitglied aktualisiert.', 'eeg-verwaltung'),
                        'updated'
                );
            } else {
                add_settings_error(
                        'eeg_mitglieder',
                        'error',
                        __('Aktualisierung fehlgeschlagen: ', 'eeg-verwaltung') . $wpdb->last_error,
                        'error'
                );
            }

            $redirect = add_query_arg(['page' => 'eeg-mitgliederliste'], admin_url('admin.php'));
            wp_safe_redirect($redirect);
            exit;
        }

        // INSERT inkl. WP-User
        $data['created_at'] = current_time('mysql');
        $formats[] = '%s';

        // User anlegen / finden
        $user_id = 0;
        if (!empty($email)) {
            $user_id = username_exists($email);
            if (!$user_id) {
                $user_id = email_exists($email);
            }
            if (!$user_id) {
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
            }
        }

        if (is_wp_error($user_id)) {
            add_settings_error(
                    'eeg_mitglieder',
                    'user_error',
                    __('WP-User konnte nicht erstellt werden: ', 'eeg-verwaltung') . $user_id->get_error_message(),
                    'error'
            );
            $_GET['action'] = 'edit';
            $_GET['id'] = 0;
            return;
        }

        if (!empty($user_id)) {
            $data['user_id'] = (int)$user_id;
            $formats[] = '%d';
        }

        // User-ID prüfen
        $exists_uid = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id)
        );

        if ($exists_uid) {
            add_settings_error(
                    'eeg_mitglieder',
                    'duplicate_user',
                    __('Dieser Benutzer ist bereits verknüpft.', 'eeg-verwaltung'),
                    'error'
            );
            $_GET['action'] = 'edit';
            $_GET['id'] = $edit_id;
            return;
        }

        $ok = $wpdb->insert($table, $data, $formats);

        if ($ok) {
            $mitglied_id = (int)$wpdb->insert_id;
            eeg_verw_save_mitglied_zaehlpunkte($mitglied_id, $zaehlpunkte_rows);
            add_settings_error(
                    'eeg_mitglieder',
                    'created',
                    __('Mitglied angelegt.', 'eeg-verwaltung'),
                    'updated'
            );
            $redirect = add_query_arg(['page' => 'eeg-mitgliederliste'], admin_url('admin.php'));
            wp_safe_redirect($redirect);
            exit;
        }

        add_settings_error(
                'eeg_mitglieder',
                'error',
                __('Anlage fehlgeschlagen: ', 'eeg-verwaltung') . $wpdb->last_error,
                'error'
        );
        $_GET['action'] = 'edit';
        $_GET['id'] = 0;
    }
}

add_action('admin_init', 'eeg_verw_handle_mitglieder_actions');

/**
 * Formular (Create/Edit)
 */
function eeg_verw_render_mitglied_form($id = 0)
{
    if (!current_user_can('manage_options')) {
        wp_die(__('Nicht erlaubt.', 'eeg-verwaltung'));
    }

    global $wpdb;
    $table_m = eeg_verw_table_mitglieder();
    $table_a = eeg_verw_table_mitgliedsarten();
    $table_zp = eeg_verw_table_zaehlpunkte();

    $row = [
            'id' => 0,
            'mitgliedsnummer' => '',
            'mitgliedsart_id' => 0,
            'firma' => '',
            'vorname' => '',
            'nachname' => '',
            'strasse' => '',
            'hausnummer' => '',
            'plz' => '',
            'ort' => '',
            'telefonnummer' => '',
            'email' => '',
            'uid' => '',
            'dokumentenart' => '',
            'dokumentennummer' => '',
            'iban' => '',
            'kontoinhaber' => '',
            'status' => 1,
            'aktiv' => 1,
    ];

    if (!empty($id)) {
        $found = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_m} WHERE id = %d", $id), ARRAY_A);
        if (is_array($found)) {
            $row = array_merge($row, $found);
        }
    }

    $zaehlpunkte = [];
    if (!empty($id)) {
        $zaehlpunkte = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM {$table_zp} WHERE mitglied_id = %d ORDER BY id ASC", $id),
                ARRAY_A
        );
    }
    if (empty($zaehlpunkte)) {
        $zaehlpunkte = [
                [
                        'zaehlpunkt' => '',
                        'zp_status' => '',
                        'zp_nr' => '',
                        'zaehlpunktname' => '',
                        'registriert' => '',
                        'bezugsrichtung' => '',
                        'teilnahme_fkt' => '',
                        'wechselrichter_nr' => '',
                        'plz' => '',
                        'ort' => '',
                        'strasse' => '',
                        'hausnummer' => '',
                        'aktiviert' => '',
                        'deaktiviert' => '',
                        'tarifname' => '',
                        'umspannwerk' => '',
                ],
        ];
    }

    if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['eeg_action'])
            && $_POST['eeg_action'] === 'save'
    ) {
        // einfache Liste der Formularfelder, die zurückgeschrieben werden sollen
        $fields = [
                'mitgliedsnummer',
                'mitgliedsart_id',
                'firma',
                'vorname',
                'nachname',
                'strasse',
                'hausnummer',
                'plz',
                'ort',
                'telefonnummer',
                'email',
                'uid',
                'dokumentenart',
                'dokumentennummer',
                'iban',
                'kontoinhaber',
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'mitgliedsart_id') {
                    $row[$field] = (int)$_POST[$field];
                } else {
                    $row[$field] = sanitize_text_field(wp_unslash($_POST[$field]));
                }
            }
        }

        // Checkbox/Status-Felder
        if (isset($_POST['status'])) {
            $row['status'] = (int)$_POST['status'];
        }

        $row['aktiv'] = isset($_POST['aktiv']) ? 1 : 0;
    }

    $arten = $wpdb->get_results(
            "SELECT id, bezeichnung, uid_pflicht, firmenname_pflicht FROM {$table_a} WHERE aktiv = 1 ORDER BY sort_order ASC",
            ARRAY_A
    );

    if (!empty($id)) {
        $title = __('Mitglied bearbeiten', 'eeg-verwaltung');
    } else {
        $title = __('Neues Mitglied', 'eeg-verwaltung');
    }

    $list_url = add_query_arg(['page' => 'eeg-mitgliederliste'], admin_url('admin.php'));
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
                        <?php esc_html_e('Zählpunkte', 'eeg-verwaltung'); ?>
                    </th>
                    <td>
                        <table class="widefat striped" id="eeg-zaehlpunkte-table">
                            <thead>
                            <tr>
                                <th><?php esc_html_e('Zählpunkt', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('ZP-Status', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('ZpNr.', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Zählpunktname', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Registriert', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Bezugsrichtung', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Teilnahme Fkt.', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('WechselrichterNr.', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('PLZ', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Ort', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Straße', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('HausNr.', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Aktiviert', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Deaktiviert', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Zp. Tarifname', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Umspannwerk', 'eeg-verwaltung'); ?></th>
                                <th><?php esc_html_e('Aktion', 'eeg-verwaltung'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($zaehlpunkte as $index => $zaehlpunkt_row) : ?>
                                <tr>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][zaehlpunkt]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['zaehlpunkt'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][zp_status]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['zp_status'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][zp_nr]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['zp_nr'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][zaehlpunktname]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['zaehlpunktname'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <input type="date"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][registriert]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['registriert'] ?? ''); ?>"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][bezugsrichtung]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['bezugsrichtung'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][teilnahme_fkt]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['teilnahme_fkt'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][wechselrichter_nr]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['wechselrichter_nr'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][plz]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['plz'] ?? ''); ?>"
                                               class="small-text"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][ort]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['ort'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][strasse]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['strasse'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][hausnummer]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['hausnummer'] ?? ''); ?>"
                                               class="small-text"/>
                                    </td>
                                    <td>
                                        <input type="date"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][aktiviert]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['aktiviert'] ?? ''); ?>"/>
                                    </td>
                                    <td>
                                        <input type="date"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][deaktiviert]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['deaktiviert'] ?? ''); ?>"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][tarifname]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['tarifname'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="zaehlpunkte[<?php echo (int)$index; ?>][umspannwerk]"
                                               value="<?php echo esc_attr($zaehlpunkt_row['umspannwerk'] ?? ''); ?>"
                                               class="regular-text"/>
                                    </td>
                                    <td>
                                        <button type="button" class="button eeg-remove-zaehlpunkt">
                                            <?php esc_html_e('Entfernen', 'eeg-verwaltung'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p>
                            <button type="button" class="button" id="eeg-add-zaehlpunkt">
                                <?php esc_html_e('Zählpunkt hinzufügen', 'eeg-verwaltung'); ?>
                            </button>
                        </p>
                        <script type="text/html" id="eeg-zaehlpunkt-template">
                            <tr>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][zaehlpunkt]" class="regular-text"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][zp_status]" class="regular-text"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][zp_nr]" class="regular-text"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][zaehlpunktname]" class="regular-text"/></td>
                                <td><input type="date" name="zaehlpunkte[__INDEX__][registriert]"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][bezugsrichtung]" class="regular-text"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][teilnahme_fkt]" class="regular-text"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][wechselrichter_nr]" class="regular-text"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][plz]" class="small-text"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][ort]" class="regular-text"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][strasse]" class="regular-text"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][hausnummer]" class="small-text"/></td>
                                <td><input type="date" name="zaehlpunkte[__INDEX__][aktiviert]"/></td>
                                <td><input type="date" name="zaehlpunkte[__INDEX__][deaktiviert]"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][tarifname]" class="regular-text"/></td>
                                <td><input type="text" name="zaehlpunkte[__INDEX__][umspannwerk]" class="regular-text"/></td>
                                <td>
                                    <button type="button" class="button eeg-remove-zaehlpunkt">
                                        <?php esc_html_e('Entfernen', 'eeg-verwaltung'); ?>
                                    </button>
                                </td>
                            </tr>
                        </script>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var table = document.getElementById('eeg-zaehlpunkte-table');
            var addButton = document.getElementById('eeg-add-zaehlpunkt');
            var template = document.getElementById('eeg-zaehlpunkt-template');

            if (!table || !addButton || !template) {
                return;
            }

            var tbody = table.querySelector('tbody');
            if (!tbody) {
                return;
            }

            var index = tbody.children.length;

            function addRow() {
                var html = template.innerHTML.replace(/__INDEX__/g, index);
                var wrapper = document.createElement('tbody');
                wrapper.innerHTML = html.trim();
                var row = wrapper.firstElementChild;
                if (row) {
                    tbody.appendChild(row);
                    index += 1;
                }
            }

            addButton.addEventListener('click', function () {
                addRow();
            });

            tbody.addEventListener('click', function (event) {
                var target = event.target;
                if (target && target.classList.contains('eeg-remove-zaehlpunkt')) {
                    event.preventDefault();
                    var row = target.closest('tr');
                    if (row) {
                        row.remove();
                    }
                }
            });
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

    <?php
}

/**
 * Admin-Seite (Liste + Form)
 * (interne Funktion, von Menü/Loader aufgerufen)
 */
function eeg_verw_mitglieder_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('Nicht erlaubt.', 'eeg-verwaltung'));
    }

    $action = '';
    if (isset($_GET['action'])) {
        $action = sanitize_key($_GET['action']);
    }
    $id = 0;
    if (isset($_GET['id'])) {
        $id = absint($_GET['id']);
    }

    if ($action === 'edit') {
        eeg_verw_render_mitglied_form($id);
        return;
    }

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php esc_html_e('Mitglieder', 'eeg-verwaltung'); ?>
        </h1>
        <a href="<?php echo esc_url(add_query_arg(
                ['page' => 'eeg-mitgliederliste', 'action' => 'edit', 'id' => 0],
                admin_url('admin.php')
        )); ?>" class="page-title-action">
            <?php esc_html_e('Neu', 'eeg-verwaltung'); ?>
        </a>
        <hr class="wp-header-end"/>

        <?php settings_errors('eeg_mitglieder'); ?>

        <form method="post">
            <?php
            $list = new EEG_Verw_Mitglieder_List_Table();
            $list->prepare_items();

            $views = $list->get_views();
            if (is_array($views) && !empty($views)) {
                echo '<ul class="subsubsub">';
                $i = 0;
                $count = count($views);
                foreach ($views as $html) {
                    echo '<li>' . $html;
                    if ($i < $count - 1) {
                        echo ' | ';
                    }
                    echo '</li>';
                    $i++;
                }
                echo '</ul>';
            }

            $list->search_box(__('Suchen', 'eeg-verwaltung'), 'eeg-mitglieder');

            $list->display();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Wrapper für den im Menü registrierten Callback-Namen
 * (Fehlermeldung: eeg_verw_admin_mitglieder_page not found)
 */
function eeg_verw_admin_mitglieder_page()
{
    eeg_verw_mitglieder_page();
}
