<?php
if (!defined('ABSPATH')) {
    exit;
}
function eeg_verw_menu_guard_bootstrap()
{
    add_action('wp_nav_menu_item_custom_fields', 'eeg_verw_menu_item_field', 10, 2);
    add_action('wp_update_nav_menu_item', 'eeg_verw_save_menu_item_field', 10, 2);
    add_filter('wp_setup_nav_menu_item', 'eeg_verw_setup_menu_item');
    add_filter('wp_nav_menu_objects', 'eeg_verw_filter_menu_objects', 20);
}

function eeg_verw_menu_item_field($item_id, $item)
{
    $val = (int)get_post_meta($item_id, '_menu_item_members_only', true);
    ?>
    <p class="field-eeg-members-only description description-wide">
        <label for="edit-menu-item-eeg-members-only-<?php echo esc_attr($item_id); ?>">
            <input type="checkbox" id="edit-menu-item-eeg-members-only-<?php echo esc_attr($item_id); ?>"
                   name="menu-item-eeg-members-only[<?php echo esc_attr($item_id); ?>]"
                   value="1" <?php checked($val, 1); ?> />
            <?php echo esc_html__('Nur für Mitglieder', 'eeg-verwaltung'); ?>
        </label>
    </p>
    <?php
}

function eeg_verw_save_menu_item_field($menu_id, $menu_item_db_id)
{
    $is_set = isset($_POST['menu-item-eeg-members-only'][$menu_item_db_id]) ? 1 : 0;
    if ($is_set) update_post_meta($menu_item_db_id, '_menu_item_members_only', 1);
    else delete_post_meta($menu_item_db_id, '_menu_item_members_only');
}

function eeg_verw_setup_menu_item($menu_item)
{
    $menu_item->eeg_members_only = (int)get_post_meta($menu_item->ID, '_menu_item_members_only', true);
    return $menu_item;
}

function eeg_verw_filter_menu_objects($items)
{
    $is_member = (is_user_logged_in() && (current_user_can('access_members_content') || current_user_can('access_verwaltung_content')));
    if ($is_member) return $items;
    $filtered = [];
    foreach ($items as $item) {
        if (!empty($item->eeg_members_only)) continue;
        $filtered[] = $item;
    }
    return $filtered;
}


/**
 * Login-Seite: Logo, URL und Linktext ändern
 */
add_action('login_enqueue_scripts', function () {
    ?>
    <style type="text/css">
        #login h1 a {
            background-image: url('<?php echo esc_url( wp_get_attachment_url(15) ); ?>') !important;
            width: 300px;
            height: 80px;
            background-size: contain;
        }
    </style>
    <?php
});


// URL des Login-Logos ändern
add_filter('login_headerurl', function () {
    return home_url();  // Link auf die Startseite
});

// Linktext (ersetzt Tooltip) - moderner Hook
add_filter('login_headertext', function () {
    return get_bloginfo('name'); // Seitentitel
});


// Backend-Zugriff für Rolle "mitglied" sperren und auf Startseite umleiten
add_action('admin_init', function () {

    // Nicht im Ajax blocken
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    // aktuellen Benutzer holen
    $user = wp_get_current_user();
    if (!$user) {
        return;
    }

    // Rollen prüfen – hier "mitglied" und "subscriber" als Beispiel
    $rollen = (array)$user->roles;

    if (in_array('mitglied', $rollen, true) || in_array('subscriber', $rollen, true)) {
        // wenn im Backend (wp-admin) -> umleiten
        if (is_admin()) {
            wp_redirect(home_url('/')); // oder z.B. home_url('/mitgliederbereich/')
            exit;
        }
    }
});


// Admin-Bar für Mitglieder-Rolle ausblenden (Frontend + Backend)
add_action('after_setup_theme', function () {
    $user = wp_get_current_user();
    if (!$user) {
        return;
    }

    $rollen = (array)$user->roles;

    if (in_array('mitglied', $rollen, true) || in_array('subscriber', $rollen, true)) {
        show_admin_bar(false);
    }
});


/**
 * Navigation: Menüpunkte nach Login-Status ein-/ausblenden
 * - Links mit Klasse "only-logged-in" nur für eingeloggte Benutzer
 * - Links mit Klasse "only-logged-out" nur für nicht eingeloggte Benutzer
 */
add_filter('render_block', function ($block_content, $block) {

    // Im Admin-Editor nichts ausblenden, damit du alles bearbeiten kannst
    if (is_admin()) {
        return $block_content;
    }

    // Nur Navigation-Link-Blöcke interessieren uns
    if (
            !isset($block['blockName']) ||
            $block['blockName'] !== 'core/navigation-link'
    ) {
        return $block_content;
    }

    $classes = '';
    if (isset($block['attrs']['className'])) {
        $classes = $block['attrs']['className'];
    }

    // Nur für eingeloggte Benutzer anzeigen
    if (strpos($classes, 'only-logged-in') !== false && !is_user_logged_in()) {
        return ''; // komplett ausblenden
    }

    // Nur für ausgeloggte Benutzer anzeigen
    if (strpos($classes, 'only-logged-out') !== false && is_user_logged_in()) {
        return ''; // komplett ausblenden
    }

    return $block_content;

}, 10, 2);

