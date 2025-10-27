<?php
if (!defined('ABSPATH')) { exit; }
function eeg_verw_register_rewrites(){
    add_rewrite_rule('^verwaltung/download/([^/]+)$', 'index.php?eeg_verw_download=$matches[1]', 'top');
    add_rewrite_tag('%eeg_verw_download%', '([^&]+)');
}
add_action('init', 'eeg_verw_register_rewrites');

function eeg_verw_downloads_bootstrap(){
    add_action('template_redirect', 'eeg_verw_handle_download_query');
}
function eeg_verw_handle_download_query(){
    $file = get_query_var('eeg_verw_download');
    if (!$file){ return; }
    if (!is_user_logged_in() || !( current_user_can('access_members_content') || current_user_can('access_verwaltung_content') ) ){ auth_redirect(); }
    $file = wp_unslash($file);
    $file = str_replace(['..','\\','/'], '', $file);
    $path = trailingslashit(EEG_VERW_PROTECTED_DIR) . $file;
    if (!$file || !file_exists($path) || !is_readable($path)){
        status_header(404); wp_die(__('Datei nicht gefunden.','eeg-verwaltung'));
    }
    $mime = wp_check_filetype($path);
    header('Content-Type: ' . (!empty($mime['type']) ? $mime['type'] : 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    @readfile($path);
    exit;
}
function eeg_verw_download_url($filename){
    $filename = ltrim($filename, '/');
    return home_url('/verwaltung/download/' . rawurlencode($filename));
}
