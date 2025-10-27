<?php
namespace EEG\Verwaltung;
if (!defined('ABSPATH')) { exit; }

final class Loader {
    public static function init(array $modules): void {
        foreach ($modules as $rel) {
            require_once (trailingslashit(constant('EEG_VERW_PATH')) . ltrim($rel, '/'));
        }
        if (function_exists('eeg_verw_roles_bootstrap')) eeg_verw_roles_bootstrap();
        if (function_exists('eeg_verw_db_bootstrap')) eeg_verw_db_bootstrap();
        if (function_exists('eeg_verw_registration_bootstrap')) eeg_verw_registration_bootstrap();
        if (function_exists('eeg_verw_content_protection_bootstrap')) eeg_verw_content_protection_bootstrap();
        if (function_exists('eeg_verw_menu_guard_bootstrap')) eeg_verw_menu_guard_bootstrap();
        if (function_exists('eeg_verw_admin_members_bootstrap')) eeg_verw_admin_members_bootstrap();
        if (function_exists('eeg_verw_downloads_bootstrap')) eeg_verw_downloads_bootstrap();
        if (function_exists('eeg_verw_utils_security_bootstrap')) eeg_verw_utils_security_bootstrap();
    }
}
