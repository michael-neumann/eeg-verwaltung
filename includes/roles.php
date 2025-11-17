<?php
if (!defined('ABSPATH')) { exit; }
function eeg_verw_roles_bootstrap(){}
function eeg_verw_activate_roles(){
    // Beibehalt der Rolle 'mitglied' für Kompatibilität
    add_role('mitglied', __('Mitglied','eeg-verwaltung'), [
        'read' => true,
        'access_members_content' => true,
    ]);
    // Zusätzliches Capability für künftige Umstellung
    $role = get_role('mitglied');
    if ($role && !$role->has_cap('access_verwaltung_content')){
        $role->add_cap('access_verwaltung_content');
    }
}
