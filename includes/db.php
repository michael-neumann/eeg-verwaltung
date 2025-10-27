<?php
if (!defined('ABSPATH')) { exit; }

function eeg_verw_db_bootstrap(){}

function eeg_verw_table(){
    global $wpdb;
    // Bewusst alter Tabellenname für nahtlose Migration
    return $wpdb->prefix . 'eeg_members';
}

function eeg_verw_install_db(){
    global $wpdb;
    $table = eeg_verw_table();
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        member_no VARCHAR(32) NOT NULL,
        company VARCHAR(120) NULL,
        phone VARCHAR(40) NULL,
        custom_field VARCHAR(190) NULL,
        consent_at DATETIME NULL,
        consent_ip VARBINARY(16) NULL,
        status TINYINT UNSIGNED NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_member_no (member_no),
        UNIQUE KEY uq_user (user_id),
        KEY idx_status (status),
        KEY idx_company (company)
    ) {$charset};";
    dbDelta($sql);
    add_option('eeg_verw_db_version', '1.0');
}

function eeg_verw_generate_member_no($user_id){
    $base = date('Y') . str_pad((string)$user_id, 6, '0', STR_PAD_LEFT);
    $sum = 0; for ($i=0;$i<strlen($base);$i++){ $sum += ord($base[$i]); }
    $chk = strtoupper(dechex($sum % 4096));
    return $base . '-' . $chk;
}
