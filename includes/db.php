<?php
if (!defined('ABSPATH')) { exit; }

function eeg_verw_db_bootstrap(){
    $current = get_option('eeg_verw_db_version');
    if (!$current || version_compare($current, '1.8', '<')) {
        eeg_verw_install_db();
        update_option('eeg_verw_db_version', '1.8');
    }
}

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

function eeg_verw_table_zaehlpunkte(){
    global $wpdb;
    return $wpdb->prefix . 'eeg_zaehlpunkte';
}

function eeg_verw_table_einstellungen(){
    global $wpdb;
    return $wpdb->prefix . 'eeg_einstellungen';
}

function eeg_verw_delete_zaehlpunkte_for_mitglieder($mitglied_ids)
{
    global $wpdb;
    $table = eeg_verw_table_zaehlpunkte();

    if (!is_array($mitglied_ids)) {
        $mitglied_ids = [$mitglied_ids];
    }

    $mitglied_ids = array_map('absint', $mitglied_ids);
    $mitglied_ids = array_filter($mitglied_ids);

    if (empty($mitglied_ids)) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($mitglied_ids), '%d'));
    $sql = "DELETE FROM {$table} WHERE mitglied_id IN ({$placeholders})";
    $wpdb->query($wpdb->prepare($sql, $mitglied_ids));
}

function eeg_verw_install_db(){
    global $wpdb;
    $table_mitglieder = eeg_verw_table_mitglieder();
    $table_mitglieder_sequence = eeg_verw_table_mitglieder_sequence();
    $table_mitgliedsarten = eeg_verw_table_mitgliedsarten();
    $table_zaehlpunkte = eeg_verw_table_zaehlpunkte();
    $table_einstellungen = eeg_verw_table_einstellungen();
    $charset = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Tabelle für Mitglieder
    $sql1 = "CREATE TABLE {$table_mitglieder} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        mitgliedsnummer VARCHAR(40) NOT NULL,
        mitgliedsart_id BIGINT UNSIGNED NOT NULL,
        vorname VARCHAR(100) NULL,
        nachname VARCHAR(100) NULL,
        firma VARCHAR(100) NULL, 
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
        consent_ip VARCHAR(45) NULL,
        status TINYINT UNSIGNED NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        aktiv TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE KEY uq_mitgliedsnummer (mitgliedsnummer),
        UNIQUE KEY uq_email (email),
        UNIQUE KEY uq_user (user_id),
        KEY idx_status (status)
    ) {$charset};";

    // Sequenz-Tabelle für Mitgliedernummer
    $sql2 = "CREATE TABLE {$table_mitglieder_sequence} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) {$charset};";

    // Tabelle für Mitgliedsarten
    $sql3 = "CREATE TABLE {$table_mitgliedsarten} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        bezeichnung VARCHAR(100) NOT NULL,
        eeg_faktura_tarif_bezug VARCHAR(100) NOT NULL,
        eeg_faktura_tarif_einspeisung VARCHAR(100) NOT NULL,
        uid_pflicht TINYINT(1) NOT NULL DEFAULT 0,
        firmenname_pflicht TINYINT(1) NOT NULL DEFAULT 0,
        aktiv TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        KEY aktiv (aktiv),
        KEY bezeichnung (bezeichnung)
    ) {$charset};";

    // Tabelle für Zählpunkte
    $sql4 = "CREATE TABLE {$table_zaehlpunkte} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        mitglied_id BIGINT UNSIGNED NOT NULL,
        zaehlpunkt VARCHAR(50) NULL,
        zp_status VARCHAR(50) NULL,
        zp_nr VARCHAR(100) NULL,
        zaehlpunktname VARCHAR(190) NULL,
        registriert DATE NULL,
        bezugsrichtung VARCHAR(100) NULL,
        teilnahme_fkt VARCHAR(100) NULL,
        wechselrichter_nr VARCHAR(100) NULL,
        plz VARCHAR(10) NULL,
        ort VARCHAR(190) NULL,
        strasse VARCHAR(190) NULL,
        hausnummer VARCHAR(40) NULL,
        aktiviert DATE NULL,
        deaktiviert DATE NULL,
        tarifname VARCHAR(190) NULL,
        umspannwerk VARCHAR(190) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_mitglied (mitglied_id),
        KEY idx_zaehlpunkt (zaehlpunkt),
        KEY idx_zp_nr (zp_nr)
    ) {$charset};";

    // Tabelle für EEG Einstellungen
    $sql5 = "CREATE TABLE {$table_einstellungen} (
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
        laenge_mitgliedsnummer INT UNSIGNED NULL,
        PRIMARY KEY (id)
    ) {$charset};";

    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
    dbDelta($sql4);
    dbDelta($sql5);

    add_option('eeg_verw_db_version', '1.8');
}


function eeg_verw_get_einstellungen_defaults() {
    return [
        'name' => '',
        'adresse' => '',
        'plz' => '',
        'ort' => '',
        'telefonnummer' => '',
        'email' => '',
        'homepage' => '',
        'uid' => '',
        'steuernummer' => '',
        'creditor_id' => '',
        'zvr' => '',
        'rc_nr' => '',
        'gerichtsstand' => '',
        'eeg_faktura_benutzer' => '',
        'eeg_faktura_passwort' => '',
        'laenge_mitgliedsnummer' => null,
    ];
}

function eeg_verw_get_einstellungen() {
    global $wpdb;
    $table = eeg_verw_table_einstellungen();
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A);
    $defaults = eeg_verw_get_einstellungen_defaults();

    if (empty($rows)) {
        $formats = [];
        foreach ($defaults as $key => $value) {
            $formats[] = $key === 'laenge_mitgliedsnummer' ? '%d' : '%s';
        }
        $wpdb->insert($table, $defaults, $formats);
        $insert_id = (int)$wpdb->insert_id;
        return array_merge(['id' => $insert_id], $defaults);
    }

    $row = $rows[0];
    if (count($rows) > 1) {
        $ids = array_slice(array_column($rows, 'id'), 1);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE id IN ({$placeholders})", $ids));
    }

    return array_merge($defaults, $row);
}

function eeg_verw_update_einstellungen(array $data) {
    global $wpdb;
    $table = eeg_verw_table_einstellungen();
    $existing = eeg_verw_get_einstellungen();
    $id = isset($existing['id']) ? (int)$existing['id'] : 0;
    $defaults = eeg_verw_get_einstellungen_defaults();

    $payload = [];
    foreach ($defaults as $key => $default) {
        if (array_key_exists($key, $data)) {
            $payload[$key] = $data[$key];
        }
    }

    $formats = [];
    foreach ($payload as $key => $value) {
        $formats[] = $key === 'laenge_mitgliedsnummer' ? '%d' : '%s';
    }

    if ($id) {
        $wpdb->update($table, $payload, ['id' => $id], $formats, ['%d']);
        return $id;
    }

    $wpdb->insert($table, $payload, $formats);
    return (int)$wpdb->insert_id;
}

function eeg_verw_get_mitgliedsnummer_length() {
    $settings = eeg_verw_get_einstellungen();
    $length = isset($settings['laenge_mitgliedsnummer']) ? (int)$settings['laenge_mitgliedsnummer'] : 0;
    return max(0, $length);
}

function eeg_verw_format_mitgliedsnummer($mitgliedsnummer) {
    $nummer = trim((string)$mitgliedsnummer);
    if ($nummer === '') {
        return '';
    }

    $length = eeg_verw_get_mitgliedsnummer_length();
    if ($length > 0 && strlen($nummer) < $length) {
        return str_pad($nummer, $length, '0', STR_PAD_LEFT);
    }

    return $nummer;
}


function eeg_verw_get_mitgliedsnummer(){
    global $wpdb;
    $table = eeg_verw_table_mitglieder_sequence();
    $wpdb->query( "INSERT INTO {$table} () VALUES ()" );
    $nummer = (string)$wpdb->insert_id;
    return eeg_verw_format_mitgliedsnummer($nummer);
}
