<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Badge für Aktiv/Inaktiv
 */
function eeg_verw_badge_aktiv($aktiv)
{
    $is_active = (int)$aktiv === 1;
    $label = $is_active ? __('Aktiv', 'eeg-verwaltung') : __('Inaktiv', 'eeg-verwaltung');
    $class = $is_active ? 'eeg-badge green' : 'eeg-badge gray';
    return '<span class="' . esc_attr($class) . '">' . esc_html($label) . '</span>';
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

    /** Wichtig: damit WP weiß, welche Spalte die "Hauptspalte" ist */
    protected function get_primary_column_name()
    {
        return 'name';
    }

    public function get_columns()
    {
        return [
                'cb' => '<input type="checkbox" />',
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
        return sprintf('<input type="checkbox" name="ids[]" value="%d" />', $item['id']);
    }

    public function get_bulk_actions()
    {
        return [
                'bulk_activate' => __('Aktivieren', 'eeg-verwaltung'),
                'bulk_deactivate' => __('Deaktivieren', 'eeg-verwaltung'),
                'bulk_delete' => __('Löschen', 'eeg-verwaltung'),
        ];
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
                return esc_html($item[$column_name] ?? '');
            case 'mitgliedsart':
                return esc_html($item['mitgliedsart'] ?? '—');
            case 'aktiv':
                return eeg_verw_badge_aktiv($item['aktiv']);
            case 'created_at':
                $ts = !empty($item['created_at']) ? strtotime($item['created_at']) : 0;
                return $ts ? esc_html(date_i18n(get_option('date_format'), $ts)) : '—';
            default:
                return '';
        }
    }

    protected function column_mitgliedsnummer($item)
    {
        $edit_url = add_query_arg([
                'page' => 'eeg-mitgliederliste',
                'action' => 'edit',
                'id' => $item['id'],
        ], admin_url('admin.php'));

        $toggle_action = ((int)$item['aktiv'] === 1) ? 'deactivate' : 'activate';
        $toggle_label = ((int)$item['aktiv'] === 1) ? __('Deaktivieren', 'eeg-verwaltung') : __('Aktivieren', 'eeg-verwaltung');

        $nonce_toggle = wp_create_nonce('eeg_mitglied_toggle_' . $item['id']);
        $nonce_delete = wp_create_nonce('eeg_mitglied_delete_' . $item['id']);

        $actions = [
                'edit' => sprintf('<a href="%s">%s</a>', esc_url($edit_url), esc_html__('Bearbeiten', 'eeg-verwaltung')),
                'toggle' => sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(add_query_arg([
                                'page' => 'eeg-mitgliederliste',
                                'action' => $toggle_action,
                                'id' => $item['id'],
                                '_wpnonce' => $nonce_toggle,
                        ], admin_url('admin.php'))),
                        esc_html($toggle_label)
                ),
                'delete' => sprintf(
                        '<a class="submitdelete" href="%s" onclick="return confirm(\'%s\')">%s</a>',
                        esc_url(add_query_arg([
                                'page' => 'eeg-mitgliederliste',
                                'action' => 'delete',
                                'id' => $item['id'],
                                '_wpnonce' => $nonce_delete,
                        ], admin_url('admin.php'))),
                        esc_attr__('Wirklich löschen?', 'eeg-verwaltung'),
                        esc_html__('Löschen', 'eeg-verwaltung')
                ),
        ];

        $title = sprintf('<strong><a href="%s">%s</a></strong>', esc_url($edit_url), esc_html($item['mitgliedsnummer']));
        return $title . $this->row_actions($actions);
    }

    protected function get_views()
    {
        $base = add_query_arg(['page' => 'eeg-mitgliederliste'], admin_url('admin.php'));
        $current = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'all';
        $counts = $this->counts_by_status();

        $views = [];
        $views['all'] = sprintf(
                '<a href="%s" class="%s">%s</a>',
                esc_url($base),
                $current === 'all' ? 'current' : '',
                sprintf(__('Alle <span class="count">(%d)</span>'), $counts['all'])
        );
        $views['active'] = sprintf(
                '<a href="%s" class="%s">%s</a>',
                esc_url(add_query_arg('status', 'active', $base)),
                $current === 'active' ? 'current' : '',
                sprintf(__('Aktiv <span class="count">(%d)</span>'), $counts['active'])
        );
        $views['inactive'] = sprintf(
                '<a href="%s" class="%s">%s</a>',
                esc_url(add_query_arg('status', 'inactive', $base)),
                $current === 'inactive' ? 'current' : '',
                sprintf(__('Inaktiv <span class="count">(%d)</span>'), $counts['inactive'])
        );

        return $views;
    }

    private function counts_by_status()
    {
        global $wpdb;
        $table_m = eeg_verw_table_mitglieder();

        $all = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table_m}");
        $active = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table_m} WHERE aktiv = 1");
        $inactive = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table_m} WHERE aktiv = 0");

        return ['all' => $all, 'active' => $active, 'inactive' => $inactive];
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
        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

        $order_map = [
                'mitgliedsnummer' => 'm.mitgliedsnummer',
                'name' => 'm.nachname, m.vorname',
                'mitgliedsart' => 'a.bezeichnung',
                'email' => 'm.email',
                'ort' => 'm.ort',
                'created_at' => 'm.created_at',
        ];
        $order_by_sql = isset($order_map[$orderby]) ? $order_map[$orderby] : 'm.created_at';

        $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'all';
        $search = isset($_REQUEST['s']) ? trim(wp_unslash($_REQUEST['s'])) : '';

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
            $where[] = '(m.vorname LIKE %s OR m.nachname LIKE %s OR m.email LIKE %s OR m.mitgliedsnummer LIKE %s)';
            $like = '%' . $wpdb->esc_like($search) . '%';
            array_push($args, $like, $like, $like, $like);
        }

        $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql_count = "
            SELECT COUNT(*)
            FROM {$table_m} m
            LEFT JOIN {$table_a} a ON a.id = m.mitgliedsart_id
            {$where_sql}
        ";
        $total = $args ? $wpdb->get_var($wpdb->prepare($sql_count, $args)) : $wpdb->get_var($sql_count);
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
        $list_args = array_merge($args, [$per_page, $offset]);
        $query = $wpdb->prepare($sql_list, $list_args);
        $rows = $wpdb->get_results($query, ARRAY_A);

        $this->items = $rows ?: [];

        $this->set_pagination_args([
                'total_items' => $this->items_total,
                'per_page' => $per_page,
                'total_pages' => max(1, ceil($this->items_total / $per_page)),
        ]);

        // 🔧 entscheidend, damit die Tabelle wirklich rendert:
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
    if (!current_user_can('manage_options')) return;

    $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
    $id = isset($_GET['id']) ? absint($_GET['id']) : 0;

    // Bulk action Ermittlung
    if (!$action && isset($_POST['action']) && $_POST['action'] !== '-1') {
        $action = sanitize_key($_POST['action']);
    }
    if (!$action && isset($_POST['action2']) && $_POST['action2'] !== '-1') {
        $action = sanitize_key($_POST['action2']);
    }

    global $wpdb;
    $table = eeg_verw_table_mitglieder();

    // --- Single toggle/delete ---
    if ($id && in_array($action, ['activate', 'deactivate', 'delete'], true)) {
        switch ($action) {
            case 'activate':
            case 'deactivate':
                check_admin_referer('eeg_mitglied_toggle_' . $id);
                $flag = ($action === 'activate') ? 1 : 0;
                $wpdb->update($table, ['aktiv' => $flag, 'updated_at' => current_time('mysql')], ['id' => $id], ['%d', '%s'], ['%d']);
                add_settings_error('eeg_mitglieder', 'updated',
                        $flag ? __('Mitglied aktiviert.', 'eeg-verwaltung') : __('Mitglied deaktiviert.', 'eeg-verwaltung'),
                        'updated'
                );
                break;

            case 'delete':
                check_admin_referer('eeg_mitglied_delete_' . $id);
                $wpdb->delete($table, ['id' => $id], ['%d']);
                add_settings_error('eeg_mitglieder', 'deleted', __('Mitglied gelöscht.', 'eeg-verwaltung'), 'updated');
                break;
        }

        $redirect = remove_query_arg(['action', 'id', '_wpnonce']);
        wp_safe_redirect($redirect);
        exit;
    }

    // --- Bulk ---
    if (!empty($action) && in_array($action, ['bulk_activate', 'bulk_deactivate', 'bulk_delete'], true) && !empty($_POST['ids'])) {
        // Korrekte WP_List_Table Bulk-Nonce
        check_admin_referer('bulk-mitglieder');

        $ids = array_map('absint', (array)$_POST['ids']);
        $ids = array_filter($ids);

        if ($ids) {
            $in_placeholder = implode(',', array_fill(0, count($ids), '%d'));

            if ($action === 'bulk_activate' || $action === 'bulk_deactivate') {
                $flag = ($action === 'bulk_activate') ? 1 : 0;
                $sql = "UPDATE {$table} SET aktiv = %d, updated_at = %s WHERE id IN ($in_placeholder)";
                $args = array_merge([$flag, current_time('mysql')], $ids);
                $wpdb->query($wpdb->prepare($sql, $args));
                add_settings_error('eeg_mitglieder', 'updated',
                        $flag ? __('Mitglieder aktiviert.', 'eeg-verwaltung') : __('Mitglieder deaktiviert.', 'eeg-verwaltung'),
                        'updated'
                );
            } elseif ($action === 'bulk_delete') {
                $sql = "DELETE FROM {$table} WHERE id IN ($in_placeholder)";
                $wpdb->query($wpdb->prepare($sql, $ids));
                add_settings_error('eeg_mitglieder', 'deleted', __('Mitglieder gelöscht.', 'eeg-verwaltung'), 'updated');
            }
        }

        $redirect = remove_query_arg(['action', '_wpnonce', 'ids']);
        wp_safe_redirect($redirect);
        exit;
    }

    // --- Save (Create/Update) ---
    if (isset($_POST['eeg_action']) && $_POST['eeg_action'] === 'save') {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nicht erlaubt.', 'eeg-verwaltung'));
        }
        check_admin_referer('eeg_mitglied_edit');

        $edit_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $mitgliedsart_id = isset($_POST['mitgliedsart_id']) ? absint($_POST['mitgliedsart_id']) : 0;
        $firma = sanitize_text_field($_POST['firma'] ?? '');
        $vorname = sanitize_text_field($_POST['vorname'] ?? '');
        $nachname = sanitize_text_field($_POST['nachname'] ?? '');
        $strasse = sanitize_text_field($_POST['strasse'] ?? '');
        $hausnummer = sanitize_text_field($_POST['hausnummer'] ?? '');
        $ort = sanitize_text_field($_POST['ort'] ?? '');
        $telefonnummer = sanitize_text_field($_POST['telefonnummer'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');


        $aktiv = isset($_POST['aktiv']) ? 1 : 0;

        // Mitgliedsnummer: manuell oder via Generator (falls vorhanden)
        $mitgliedsnummer = sanitize_text_field($_POST['mitgliedsnummer'] ?? '');
        if ($mitgliedsnummer === '' && function_exists('eeg_verw_get_mitgliedsnummer')) {
            // user_id unbekannt -> 0; passe an, falls du user_id ziehen willst
            $mitgliedsnummer = (string)eeg_verw_get_mitgliedsnummer(0);
        }

        $data = [
                'mitgliedsart_id' => $mitgliedsart_id,
                'firma' => $firma,
                'vorname' => $vorname,
                'nachname' => $nachname,
                'strasse' => $strasse,
                'hausnummer' => $hausnummer,
                'ort' => $ort,
                'email' => $email,
                'telefonnummer' => $telefonnummer,
                'aktiv' => $aktiv,
                'updated_at' => current_time('mysql'),
        ];
        $format_map = [
                'mitgliedsart_id' => '%d',
                'firma' => '%s',
                'vorname' => '%s',
                'nachname' => '%s',
                'strasse' => '%s',
                'hausnummer' => '%s',   // alphanumerisch (z.B. '12a') → %s
                'ort' => '%s',
                'email' => '%s',
                'telefonnummer' => '%s',
                'aktiv' => '%d',
                'updated_at' => '%s',
                'mitgliedsnummer' => '%s',
                'created_at' => '%s',
                'user_id' => '%d',
        ];

        // Formate passend zur Reihenfolge der $data-Keys erzeugen:
        $formats = [];
        foreach (array_keys($data) as $k) {
            $formats[] = $format_map[$k] ?? '%s';
        }

        if ($mitgliedsnummer !== '') {
            $data['mitgliedsnummer'] = $mitgliedsnummer;
            $formats[] = '%s';
        }

        if ($edit_id) {
            $ok = $wpdb->update($table, $data, ['id' => $edit_id], $formats, ['%d']);
            if ($ok !== false) {
                add_settings_error('eeg_mitglieder', 'updated', __('Mitglied aktualisiert.', 'eeg-verwaltung'), 'updated');
            } else {
                add_settings_error('eeg_mitglieder', 'error', __('Aktualisierung fehlgeschlagen: ', 'eeg-verwaltung') . $wpdb->last_error, 'error');
            }
            $redirect = add_query_arg(['page' => 'eeg-mitgliederliste'], admin_url('admin.php'));
            wp_safe_redirect($redirect);
            exit;
        } else {
            $data['created_at'] = current_time('mysql');
            $formats[] = '%s';


// Wenn keine user_id vorhanden ist, neuen WP-User erstellen
            $user_id = username_exists($email);
            if (!$user_id && !email_exists($email)) {
                $random_password = wp_generate_password(12, false);
                $user_id = wp_insert_user([
                        'user_login' => sanitize_user($email),
                        'user_email' => $email,
                        'user_pass' => $random_password,
                        'role' => 'mitglied',
                ]);
            }

            if (is_wp_error($user_id)) {
                // Fehler beim Anlegen des WP-Users
                add_settings_error('eeg_mitglieder', 'user_error', 'WP-User konnte nicht erstellt werden: ' . $user_id->get_error_message(), 'error');
            } else {
                $data['user_id'] = (int)$user_id;
                $formats[] = '%d';
            }

            $data['created_at'] = current_time('mysql');
            // ggf. $data['user_id'] = (int) $user_id;

            $formats = [];
            foreach (array_keys($data) as $k) {
                $formats[] = $format_map[$k] ?? '%s';
            }

            $ok = $wpdb->insert($table, $data, $formats);
            if ($ok) {
                add_settings_error('eeg_mitglieder', 'created', __('Mitglied angelegt.', 'eeg-verwaltung'), 'updated');
                $redirect = add_query_arg(['page' => 'eeg-mitgliederliste'], admin_url('admin.php'));
                wp_safe_redirect($redirect);
                exit;
            } else {
                add_settings_error('eeg_mitglieder', 'error', __('Anlage fehlgeschlagen: ', 'eeg-verwaltung') . $wpdb->last_error, 'error');
                // bei Fehler wieder ins Formular fallen lassen
                $_GET['action'] = 'edit';
                $_GET['id'] = 0;
            }
        }
    }
}

add_action('admin_init', 'eeg_verw_handle_mitglieder_actions');

/**
 * Formular-Renderer (Create/Edit)
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
            'vorname' => '',
            'nachname' => '',
            'email' => '',
            'telefonnummer' => '',
            'ort' => '',
            'mitgliedsart_id' => 0,
            'aktiv' => 1,
    ];

    if ($id) {
        $found = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_m} WHERE id = %d", $id), ARRAY_A);
        if ($found) $row = array_merge($row, $found);
    }

    // Mitgliedsarten für Dropdown
    $arten = $wpdb->get_results("SELECT id, bezeichnung FROM {$table_a} ORDER BY bezeichnung ASC", ARRAY_A);

    $title = $id ? __('Mitglied bearbeiten', 'eeg-verwaltung') : __('Neues Mitglied', 'eeg-verwaltung');
    ?>
    <div class="wrap eeg-wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'eeg-mitgliederliste'], admin_url('admin.php'))); ?>"
           class="page-title-action">
            <?php esc_html_e('Zurück zur Liste', 'eeg-verwaltung'); ?>
        </a>
        <hr class="wp-header-end">

        <?php settings_errors('eeg_mitglieder'); ?>

        <form method="post"
              action="<?php echo esc_url(add_query_arg(['page' => 'eeg-mitgliederliste'], admin_url('admin.php'))); ?>">
            <?php wp_nonce_field('eeg_mitglied_edit'); ?>
            <input type="hidden" name="eeg_action" value="save"/>
            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>"/>

            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row"><label
                                for="mitgliedsnummer"><?php esc_html_e('Mitgliedsnummer', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <input name="mitgliedsnummer" id="mitgliedsnummer" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['mitgliedsnummer']); ?>"/>
                        <p class="description"><?php esc_html_e('Leer lassen, wenn automatisch vergeben werden soll.', 'eeg-verwaltung'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label
                                for="mitgliedsart_id"><?php esc_html_e('Mitgliedsart', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td>
                        <select name="mitgliedsart_id" id="mitgliedsart_id">
                            <option value="0">—</option>
                            <?php foreach ($arten as $a): ?>
                                <option value="<?php echo (int)$a['id']; ?>" <?php selected((int)$row['mitgliedsart_id'], (int)$a['id']); ?>>
                                    <?php echo esc_html($a['bezeichnung']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="firma"><?php esc_html_e('Firmenname', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="firma" id="firma" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['firma']); ?>" required/></td>
                </tr>
                <tr>
                    <th scope="row"><label for="vorname"><?php esc_html_e('Vorname', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="vorname" id="vorname" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['vorname']); ?>" required/></td>
                </tr>
                <tr>
                    <th scope="row"><label for="nachname"><?php esc_html_e('Nachname', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td><input name="nachname" id="nachname" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['nachname']); ?>" required/></td>
                </tr>
                <tr>
                    <th scope="row"><label for="strasse"><?php esc_html_e('Straße', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="strasse" id="strasse" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['strasse']); ?>"/></td>
                </tr>
                <tr>
                    <th scope="row"><label for="hausnummer"><?php esc_html_e('Hausnummer', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td><input name="hausnummer" id="hausnummer" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['hausnummer']); ?>"/></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ort"><?php esc_html_e('Ort', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="ort" id="ort" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['ort']); ?>"/></td>
                </tr>
                <tr>
                    <th scope="row"><label for="email"><?php esc_html_e('E-Mail', 'eeg-verwaltung'); ?></label></th>
                    <td><input name="email" id="email" type="email" class="regular-text"
                               value="<?php echo esc_attr($row['email']); ?>"/></td>
                </tr>
                <tr>
                    <th scope="row"><label for="telefonnummer"><?php esc_html_e('Telefon', 'eeg-verwaltung'); ?></label>
                    </th>
                    <td><input name="telefonnummer" id="telefonnummer" type="text" class="regular-text"
                               value="<?php echo esc_attr($row['telefonnummer']); ?>"/></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Status', 'eeg-verwaltung'); ?></th>
                    <td>
                        <label><input type="checkbox" name="aktiv"
                                      value="1" <?php checked((int)$row['aktiv'], 1); ?> /> <?php esc_html_e('Aktiv', 'eeg-verwaltung'); ?>
                        </label>
                    </td>
                </tr>
                </tbody>
            </table>

            <?php submit_button($id ? __('Speichern', 'eeg-verwaltung') : __('Anlegen', 'eeg-verwaltung')); ?>
        </form>
    </div>

    <style>
        .eeg-badge {
            display: inline-block;
            padding: .15rem .45rem;
            border-radius: 999px;
            font-size: 11px;
            line-height: 1;
            font-weight: 600
        }

        .eeg-badge.green {
            background: #e6f4ea;
            color: #137333
        }

        .eeg-badge.gray {
            background: #edf2f7;
            color: #4a5568
        }

        .eeg-wrap .tablenav.top {
            margin-top: 12px
        }
    </style>
    <?php
}

/**
 * Admin-Seite rendern
 */
function eeg_verw_admin_mitglieder_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('Nicht erlaubt.', 'eeg-verwaltung'));
    }

    $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';

    // Branch: Edit/Create Formular
    if ($action === 'edit') {
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        eeg_verw_render_mitglied_form($id);
        return;
    }

    // Default: Liste
    $list = new EEG_Verw_Mitglieder_List_Table();
    $list->prepare_items();
    ?>
    <div class="wrap eeg-wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('Mitglieder', 'eeg-verwaltung'); ?></h1>
        <a href="<?php echo esc_url(add_query_arg(['page' => 'eeg-mitgliederliste', 'action' => 'edit'], admin_url('admin.php'))); ?>"
           class="page-title-action">
            <?php esc_html_e('Neu hinzufügen', 'eeg-verwaltung'); ?>
        </a>
        <hr class="wp-header-end">

        <?php settings_errors('eeg_mitglieder'); ?>

        <form method="get">
            <input type="hidden" name="page" value="eeg-mitgliederliste"/>
            <?php
            $list->views();
            $list->search_box(__('Suchen', 'eeg-verwaltung'), 'eeg-mitglieder');
            $list->display();
            ?>
        </form>
    </div>
    <style>
        .eeg-badge {
            display: inline-block;
            padding: .15rem .45rem;
            border-radius: 999px;
            font-size: 11px;
            line-height: 1;
            font-weight: 600
        }

        .eeg-badge.green {
            background: #e6f4ea;
            color: #137333
        }

        .eeg-badge.gray {
            background: #edf2f7;
            color: #4a5568
        }

        .eeg-wrap .tablenav.top {
            margin-top: 12px
        }
    </style>
    <?php
}
