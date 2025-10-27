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
        'includes/downloads.php',
        'includes/utils/security.php',
    ]);
});
