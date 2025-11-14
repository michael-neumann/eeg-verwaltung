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

        if (is_array($rows)) {
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

        $mitgliedsnummer = sanitize_text_field($_POST['mitgliedsnummer'] ?? '');
        if ($mitgliedsnummer === '' && function_exists('eeg_verw_get_mitgliedsnummer')) {
            $mitgliedsnummer = (string)eeg_verw_get_mitgliedsnummer(0);
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

        $ok = $wpdb->insert($table, $data, $formats);

        if ($ok) {
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
                            <?php
                            if (is_array($arten)) {
                                foreach ($arten as $art) {
                                    echo '<option value="' . (int)$art['id'] . '"';
                                    selected((int)$row['mitgliedsart_id'], (int)$art['id']);
                                    echo '>' . esc_html($art['bezeichnung']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="firma"><?php esc_html_e('Firma', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="firma" id="firma" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['firma']); ?>"/>
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
                        <label for="uid"><?php esc_html_e('UID', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="uid" id="uid" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['uid']); ?>"/>
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
                    lazy: false,
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
