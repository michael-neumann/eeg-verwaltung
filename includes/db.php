<?php
if (!defined('ABSPATH')) { exit; }

function eeg_verw_db_bootstrap(){}

function eeg_verw_table_mitglieder(){
    global $wpdb;
    return $wpdb->prefix . 'eeg_mitglieder';
}

function eeg_verw_table_mitglieder_sequence(){
    global $wpdb;
    return $wpdb->prefix . 'eeg_mitglieder_sequence';
}

function eeg_verw_table_mitgliedsarten(){
    global $wpdb;
    return $wpdb->prefix . 'eeg_mitgliedsarten';
}

function eeg_verw_table_einstellungen(){
    global $wpdb;
    return $wpdb->prefix . 'eeg_einstellungen';
}

function eeg_verw_install_db(){
    global $wpdb;
    $table_mitglieder = eeg_verw_table_mitglieder();
    $table_mitglieder_sequence = eeg_verw_table_mitglieder_sequence();
    $table_einstellungen = eeg_verw_table_einstellungen();
    $charset = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Tabelle für Mitglieder
    $sql1 = "CREATE TABLE {$table_mitglieder} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        member_no BIGINT UNSIGNED NOT NULL,
        mitgliedsart_id BIGINT UNSIGNED NOT NULL,
        titel_vor VARCHAR(40) NULL,
        titel_nach VARCHAR(40) NULL,
        vorname VARCHAR(100) NULL,
        nachname VARCHAR(100) NULL,
        company VARCHAR(100) NULL, 
        strasse VARCHAR(100) NULL,
        hausnummer VARCHAR(40) NULL,
        plz VARCHAR(10) NULL,
        ort VARCHAR(190) NULL,
        telefonnummer VARCHAR(40) NULL,
        email VARCHAR(100) NULL,
        uid VARCHAR(40) NULL,
        dokumentenart VARCHAR(40) NULL,
        dokumentennummer VARCHAR(40) NULL,
        iban VARCHAR(40) NULL,
        kontoinhaber VARCHAR(40) NULL,
        consent_at DATETIME NULL,
        consent_ip VARBINARY(16) NULL,
        status TINYINT UNSIGNED NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_member_no (member_no),
        UNIQUE KEY uq_user (user_id),
        KEY idx_status (status)
    ) {$charset};";

    // Sequenz-Tabelle für Mitgliedernummer
    $sql2 = "CREATE TABLE {$table_mitglieder_sequence} (
        counter_key VARCHAR(50) PRIMARY KEY,
        current_value BIGINT UNSIGNED NOT NULL DEFAULT 0
    ) {$charset};";

    // Tabelle für Mitgliedsarten
    $sql3 = "CREATE TABLE {$table_mitglieder_sequence} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        bezeichnung VARCHAR(100) NOT NULL,
        eeg_faktura_tarif_bezug VARCHAR(100) NOT NULL,
        eeg_faktura_tarif_einspeisung VARCHAR(100) NOT NULL
        PRIMARY KEY (id)
    ) {$charset};";

    // Tabelle für EEG Einstellungen
    $sql4 = "CREATE TABLE {$table_einstellungen} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        adresse VARCHAR(100) NULL,
        plz VARCHAR(10) NULL,
        ort VARCHAR(190) NULL,
        telefonnummer VARCHAR(40) NULL,
        email VARCHAR(100) NULL,
        homepage VARCHAR(100) NULL,
        uid VARCHAR(40) NULL,
        steuernummer VARCHAR(40) NULL,
        creditor_id VARCHAR(40) NULL,
        zvr VARCHAR(40) NULL,
        rc_nr VARCHAR(40) NULL,
        gerichtsstand VARCHAR(100) NULL,
        eeg_faktura_benutzer VARCHAR(100) NULL,
        eeg_faktura_passwort VARCHAR(100) NULL,
        
        PRIMARY KEY (id)
    ) {$charset};";

    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
    dbDelta($sql4);

    add_option('eeg_verw_db_version', '1.0');
}

function eeg_verw_get_mitgliedsnummer($user_id){
    global $wpdb;
    $key = 'mitgliedsnummer';
    $table = eeg_verw_table_mitglieder_sequence();

    // atomar: increment in einem Statement
    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO $table (counter_key, current_value)
             VALUES (%s, 1)
             ON DUPLICATE KEY UPDATE current_value = LAST_INSERT_ID(current_value + 1)",
            $key
        )
    );
    return (int) $wpdb->get_var("SELECT LAST_INSERT_ID()");
}
