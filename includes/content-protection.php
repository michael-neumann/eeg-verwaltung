<?php
if (!defined('ABSPATH')) { exit; }
function eeg_verw_content_protection_bootstrap(){
    add_action('add_meta_boxes', 'eeg_verw_add_restrict_metabox');
    add_action('save_post', 'eeg_verw_save_restrict_meta');
    add_action('template_redirect', 'eeg_verw_guard_content');
    add_action('wp_head', 'eeg_verw_restricted_noindex', 1);
    add_filter('the_excerpt', 'eeg_verw_hide_excerpt_for_restricted');
}
function eeg_verw_add_restrict_metabox(){
    add_meta_box('eeg_verw_restrict_box', __('Zugriff','eeg-verwaltung'), 'eeg_verw_restrict_box_cb', ['page','post'], 'side', 'high');
}
function eeg_verw_restrict_box_cb($post){
    $v = (int)get_post_meta($post->ID, '_restrict_to_members', true);
    echo '<label><input type="checkbox" name="eeg_verw_restrict_members" value="1" '.checked($v,1,false).' /> ' . esc_html__('Nur für Mitglieder','eeg-verwaltung') . '</label>';
    echo '<p style="margin-top:8px;color:#666;">' . esc_html__('Nicht-Mitglieder werden zur Login-Seite umgeleitet.','eeg-verwaltung') . '</p>';
    wp_nonce_field('eeg_verw_restrict_meta', 'eeg_verw_restrict_meta_nonce');
}
function eeg_verw_save_restrict_meta($post_id){
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['eeg_verw_restrict_meta_nonce']) || !wp_verify_nonce($_POST['eeg_verw_restrict_meta_nonce'], 'eeg_verw_restrict_meta')) return;
    if (isset($_POST['eeg_verw_restrict_members'])) update_post_meta($post_id, '_restrict_to_members', 1);
    else delete_post_meta($post_id, '_restrict_to_members');
}
function eeg_verw_guard_content(){
    if (is_singular()){
        $post_id = get_queried_object_id();
        if ($post_id && get_post_meta($post_id, '_restrict_to_members', true)){
            if (!is_user_logged_in() || !( current_user_can('access_members_content') || current_user_can('access_verwaltung_content') ) ){
                auth_redirect();
            }
        }
    }
}
function eeg_verw_restricted_noindex(){
    if (is_singular()){
        $post_id = get_queried_object_id();
        if ($post_id && get_post_meta($post_id, '_restrict_to_members', true) && !is_user_logged_in()){
            echo '<meta name="robots" content="noindex, nofollow">' . "\n";
        }
    }
}
function eeg_verw_hide_excerpt_for_restricted($excerpt){
    if (is_admin() || is_singular()) return $excerpt;
    $post_id = get_the_ID();
    if ($post_id && get_post_meta($post_id, '_restrict_to_members', true) && !is_user_logged_in()){
        return __('Bitte anmelden, um den Inhalt zu sehen.','eeg-verwaltung');
    }
    return $excerpt;
}
