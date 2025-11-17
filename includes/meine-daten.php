<?php

function myplugin_person_field_shortcode($atts)
{
    global $wpdb;
    $table_m = eeg_verw_table_mitglieder();
    $table_a = eeg_verw_table_mitgliedsarten();
    $atts = shortcode_atts(['field' => '',], $atts, 'person_field');
    if (empty($atts['field'])) {
        return '';
    }
    $user_id = get_current_user_id();
    if (!$user_id) {
        return '';
    }
    $data = $wpdb->get_row($wpdb->prepare("SELECT m.*, a.bezeichnung AS mitgliedsart FROM {$table_m} m LEFT JOIN {$table_a} a ON a.id = m.mitgliedsart_id WHERE m.user_id = %d", $user_id), ARRAY_A);
    $value=null;
    if ($data) {
        if (isset($data[$atts['field']])) {
            $value = $data[$atts['field']];
        }
    }
    if ($value === null) {
        return '';
    }
    return esc_html($value);
}

add_shortcode('person_field', 'myplugin_person_field_shortcode');

function myplugin_if_person_field_shortcode($atts, $content = '')
{
    global $wpdb;

    $table_m = eeg_verw_table_mitglieder();
    $table_a = eeg_verw_table_mitgliedsarten();

    // Attribute: welches Feld, und ob auf "nicht leer" geprüft werden soll
    $atts = shortcode_atts(
        [
            'field' => '',
            'notempty' => '',
        ],
        $atts,
        'if_person_field'
    );

    if (empty($atts['field'])) {
        return '';
    }

    $user_id = get_current_user_id();

    if (!$user_id) {
        return '';
    }

    // Datensatz holen (wie in deinem anderen Shortcode)
    $data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT m.*, a.bezeichnung AS mitgliedsart
             FROM {$table_m} m
             LEFT JOIN {$table_a} a ON a.id = m.mitgliedsart_id
             WHERE m.user_id = %d
             LIMIT 1",
            $user_id
        ),
        ARRAY_A
    );

    if (!$data) {
        return '';
    }

    if (!array_key_exists($atts['field'], $data)) {
        return '';
    }

    $value = $data[$atts['field']];

    // Wenn notempty gesetzt ist, und Feld leer => nichts anzeigen
    if ($atts['notempty']) {
        if (empty($value)) {
            return '';
        }
    }

    // Inhalt zwischen [if_person_field]...[/if_person_field] rendern
    // und darin enthaltene Shortcodes mit ausführen
    return do_shortcode($content);
}

add_shortcode('if_person_field', 'myplugin_if_person_field_shortcode');

