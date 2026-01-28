<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class EEG_Verw_Verwaiste_Zaehlpunkte_List_Table extends WP_List_Table
{
    private $items_total = 0;

    public function __construct()
    {
        parent::__construct([
                'singular' => 'zaehlpunkt',
                'plural' => 'zaehlpunkte',
                'ajax' => false,
        ]);
    }

    protected function get_primary_column_name()
    {
        return 'zaehlpunkt';
    }

    public function get_columns()
    {
        return [
                'cb' => '',
                'zaehlpunkt' => __('Zählpunkt', 'eeg-verwaltung'),
                'zp_nr' => __('ZP-Nr.', 'eeg-verwaltung'),
                'zaehlpunktname' => __('Zählpunktname', 'eeg-verwaltung'),
                'mitglied_id' => __('Mitglied-ID', 'eeg-verwaltung'),
                'ort' => __('Ort', 'eeg-verwaltung'),
                'created_at' => __('Angelegt', 'eeg-verwaltung'),
        ];
    }

    protected function get_sortable_columns()
    {
        return [
                'zaehlpunkt' => ['zaehlpunkt', false],
                'zp_nr' => ['zp_nr', false],
                'zaehlpunktname' => ['zaehlpunktname', false],
                'mitglied_id' => ['mitglied_id', false],
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
        return [
                'bulk_delete' => __('Löschen', 'eeg-verwaltung'),
        ];
    }

    protected function column_zaehlpunkt($item)
    {
        $nonce_delete = wp_create_nonce('eeg_zp_orphan_delete_' . $item['id']);
        $delete_url = add_query_arg(
                [
                        'page' => 'eeg-verwaiste-zaehlpunkte',
                        'action' => 'delete',
                        'id' => $item['id'],
                        '_wpnonce' => $nonce_delete,
                ],
                admin_url('admin.php')
        );

        $label = $item['zaehlpunkt'] !== '' ? $item['zaehlpunkt'] : __('(ohne Zählpunkt)', 'eeg-verwaltung');

        $actions = [];
        $actions['delete'] = sprintf(
                '<a href="%s" onclick="return confirm(%s);">%s</a>',
                esc_url($delete_url),
                wp_json_encode(__('Wirklich löschen?', 'eeg-verwaltung')),
                esc_html__('Löschen', 'eeg-verwaltung')
        );

        return sprintf(
                '%1$s %2$s',
                esc_html($label),
                $this->row_actions($actions)
        );
    }

    protected function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'zp_nr':
            case 'zaehlpunktname':
            case 'mitglied_id':
            case 'ort':
                return isset($item[$column_name]) && $item[$column_name] !== '' ? esc_html($item[$column_name]) : '—';
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

    public function prepare_items()
    {
        global $wpdb;

        $table_zp = eeg_verw_table_zaehlpunkte();
        $table_m = eeg_verw_table_mitglieder();

        $per_page = 20;
        $current_page = max(1, $this->get_pagenum());
        $offset = ($current_page - 1) * $per_page;

        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'created_at';
        $order = 'DESC';
        if (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') {
            $order = 'ASC';
        }

        $order_map = [
                'zaehlpunkt' => 'zp.zaehlpunkt',
                'zp_nr' => 'zp.zp_nr',
                'zaehlpunktname' => 'zp.zaehlpunktname',
                'mitglied_id' => 'zp.mitglied_id',
                'ort' => 'zp.ort',
                'created_at' => 'zp.created_at',
        ];

        $order_by_sql = 'zp.created_at';
        if (isset($order_map[$orderby])) {
            $order_by_sql = $order_map[$orderby];
        }

        $search = '';
        if (isset($_REQUEST['s'])) {
            $search = trim(wp_unslash($_REQUEST['s']));
        }

        $where = ['m.id IS NULL'];
        $args = [];

        if ($search !== '') {
            $where[] = '(zp.zaehlpunkt LIKE %s OR zp.zp_nr LIKE %s OR zp.zaehlpunktname LIKE %s OR zp.ort LIKE %s)';
            $like = '%' . $wpdb->esc_like($search) . '%';
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where);

        $sql_count = "SELECT COUNT(*) FROM {$table_zp} zp LEFT JOIN {$table_m} m ON m.id = zp.mitglied_id {$where_sql}";
        if (!empty($args)) {
            $total = $wpdb->get_var($wpdb->prepare($sql_count, $args));
        } else {
            $total = $wpdb->get_var($sql_count);
        }

        $this->items_total = (int)$total;

        $sql_list = "
            SELECT zp.*
            FROM {$table_zp} zp
            LEFT JOIN {$table_m} m ON m.id = zp.mitglied_id
            {$where_sql}
            ORDER BY {$order_by_sql} {$order}
            LIMIT %d OFFSET %d
        ";

        $list_args = $args;
        $list_args[] = $per_page;
        $list_args[] = $offset;

        $rows = $wpdb->get_results($wpdb->prepare($sql_list, $list_args), ARRAY_A);
        $this->items = is_array($rows) ? $rows : [];

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

    public function no_items()
    {
        esc_html_e('Keine verwaisten Zählpunkte gefunden.', 'eeg-verwaltung');
    }
}

function eeg_verw_handle_verwaiste_zaehlpunkte_actions()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['page']) || $_GET['page'] !== 'eeg-verwaiste-zaehlpunkte') {
        return;
    }

    $action = '';
    if (isset($_GET['action'])) {
        $action = sanitize_key($_GET['action']);
    }

    if (empty($action) && isset($_POST['action']) && $_POST['action'] !== '-1') {
        $action = sanitize_key($_POST['action']);
    }
    if (empty($action) && isset($_POST['action2']) && $_POST['action2'] !== '-1') {
        $action = sanitize_key($_POST['action2']);
    }

    $id = 0;
    if (isset($_GET['id'])) {
        $id = absint($_GET['id']);
    }

    global $wpdb;
    $table = eeg_verw_table_zaehlpunkte();

    if (!empty($id) && $action === 'delete') {
        check_admin_referer('eeg_zp_orphan_delete_' . $id);
        $wpdb->delete($table, ['id' => $id], ['%d']);

        add_settings_error(
                'eeg_zaehlpunkte',
                'deleted',
                __('Zählpunkt gelöscht.', 'eeg-verwaltung'),
                'updated'
        );

        $redirect = remove_query_arg(['action', 'id', '_wpnonce']);
        wp_safe_redirect($redirect);
        exit;
    }

    if (!empty($action) && $action === 'bulk_delete' && !empty($_POST['ids'])) {
        check_admin_referer('bulk-zaehlpunkte');

        $ids = array_map('absint', (array)$_POST['ids']);
        $ids = array_filter($ids);

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $sql = "DELETE FROM {$table} WHERE id IN ({$placeholders})";
            $wpdb->query($wpdb->prepare($sql, $ids));

            add_settings_error(
                    'eeg_zaehlpunkte',
                    'deleted',
                    __('Zählpunkte gelöscht.', 'eeg-verwaltung'),
                    'updated'
            );
        }

        $redirect = remove_query_arg(['action', 'action2', '_wpnonce', 'ids']);
        wp_safe_redirect($redirect);
        exit;
    }
}
add_action('admin_init', 'eeg_verw_handle_verwaiste_zaehlpunkte_actions');

function eeg_verw_admin_verwaiste_zaehlpunkte_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('Nicht erlaubt.', 'eeg-verwaltung'));
    }

    $list = new EEG_Verw_Verwaiste_Zaehlpunkte_List_Table();
    $list->prepare_items();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Verwaiste Zählpunkte', 'eeg-verwaltung'); ?></h1>
        <?php settings_errors('eeg_zaehlpunkte'); ?>

        <form method="post">
            <?php
            wp_nonce_field('bulk-zaehlpunkte');
            $list->search_box(__('Suchen', 'eeg-verwaltung'), 'eeg-zaehlpunkte');
            $list->display();
            ?>
        </form>
    </div>
    <?php
}
