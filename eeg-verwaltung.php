<?php
/**
 * Plugin Name: EEG Verwaltung
 * Description: Verwaltungssystem für registrierte Benutzer mit eigener Tabelle, Consent-Logging und geschützten Downloads (Pretty-URL).
 * Version: 1.0.0
 * Author: EEG
 * Text Domain: eeg-verwaltung
 */
if (!defined('ABSPATH')) { exit; }

define('EEG_VERW_PATH', plugin_dir_path(__FILE__));
define('EEG_VERW_URL',  plugin_dir_url(__FILE__));
define('EEG_VERW_VER',  '1.0.0');
if (!defined('EEG_VERW_PROTECTED_DIR')) {
    define('EEG_VERW_PROTECTED_DIR', WP_CONTENT_DIR . '/protected-downloads');
}

require_once EEG_VERW_PATH . 'includes/class-loader.php';

register_activation_hook(__FILE__, function(){
    require_once EEG_VERW_PATH . 'includes/roles.php';
    require_once EEG_VERW_PATH . 'includes/db.php';
    eeg_verw_activate_roles();
    eeg_verw_install_db();
    if (!file_exists(EEG_VERW_PROTECTED_DIR)) { wp_mkdir_p(EEG_VERW_PROTECTED_DIR); }
    $ht = EEG_VERW_PROTECTED_DIR . '/.htaccess';
    if (!file_exists($ht)) { @file_put_contents($ht, "Deny from all\n"); }
    require_once EEG_VERW_PATH . 'includes/downloads.php';
    eeg_verw_register_rewrites();
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });

add_action('plugins_loaded', function () {
    load_plugin_textdomain('eeg-verwaltung', false, dirname(plugin_basename(__FILE__)) . '/languages');
    \EEG\Verwaltung\Loader::init([
        'includes/roles.php',
        'includes/db.php',
        'includes/registration.php',
        'includes/content-protection.php',
        'includes/menu-guard.php',
        'includes/admin/Mitgliederliste.php',
        'includes/admin/Mitgliedsarten.php',
        'includes/admin/Einstellungen.php',
        'includes/admin/Import.php',
        'includes/admin/VerwaisteZaehlpunkte.php',
        'includes/downloads.php',
        'includes/utils/security.php',
        'includes/utils/iban_checker.php',
        'includes/meine-daten.php',
    ]);
});


add_action('admin_menu', function () {
    add_menu_page(
        'EEG Verwaltung',
        'EEG',
        'manage_options',
        'eeg-admin',
        'eeg_verw_admin_welcome',
        'dashicons-groups',
        45
    );

    add_submenu_page(
        'eeg-admin',
        'Mitgliederliste',
        'Mitgliederliste',
        'manage_options',
        'eeg-mitgliederliste',
        'eeg_verw_admin_mitglieder_page'
    );

    add_submenu_page(
        'eeg-admin',
        'Mitgliedsarten',
        'Mitgliedsarten',
        'manage_options',
        'eeg-mitgliedsarten',
        'eeg_verw_admin_mitgliedsarten_page'
    );

    add_submenu_page(
        'eeg-admin',
        'Stammdaten-Import',
        'Stammdaten-Import',
        'manage_options',
        'eeg-import',
        'eeg_verw_admin_import_page'
    );

    add_submenu_page(
        'eeg-admin',
        'Einstellungen',
        'Einstellungen',
        'manage_options',
        'eeg-einstellungen',
        'eeg_verw_admin_einstellungen_page'
    );

    $verwaiste_hook = add_submenu_page(
        'eeg-admin',
        'Verwaiste Zählpunkte',
        'Verwaiste Zählpunkte',
        'manage_options',
        'eeg-verwaiste-zaehlpunkte',
        'eeg_verw_admin_verwaiste_zaehlpunkte_page'
    );

    if ($verwaiste_hook) {
        add_action('load-' . $verwaiste_hook, 'eeg_verw_handle_verwaiste_zaehlpunkte_actions');
    }
});

function eeg_verw_admin_welcome() {
    echo '<div class="wrap"><h1>EEG Verwaltung</h1><p>Wähle eine Funktion im Menü.</p></div>';
}

# Wenn ein Benutzer gelöscht wird auch das Mitglied löschen
add_action('deleted_user', function($user_id) {
    global $wpdb;
    $table = eeg_verw_table_mitglieder();
    $mitglied_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE user_id = %d", (int)$user_id));
    if (!empty($mitglied_ids)) {
        eeg_verw_delete_zaehlpunkte_for_mitglieder($mitglied_ids);
    }
    // falls du 1:1-Beziehung hast:
    $wpdb->delete($table, ['user_id' => (int)$user_id], ['%d']);
}, 10, 1);
