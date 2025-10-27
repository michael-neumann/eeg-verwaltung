<?php
if (!defined('ABSPATH')) { exit; }
function eeg_verw_menu_guard_bootstrap(){
    add_action('wp_nav_menu_item_custom_fields','eeg_verw_menu_item_field',10,2);
    add_action('wp_update_nav_menu_item','eeg_verw_save_menu_item_field',10,2);
    add_filter('wp_setup_nav_menu_item','eeg_verw_setup_menu_item');
    add_filter('wp_nav_menu_objects','eeg_verw_filter_menu_objects',20);
}
function eeg_verw_menu_item_field($item_id,$item){
    $val = (int)get_post_meta($item_id,'_menu_item_members_only',true);
    ?>
    <p class="field-eeg-members-only description description-wide">
        <label for="edit-menu-item-eeg-members-only-<?php echo esc_attr($item_id); ?>">
            <input type="checkbox" id="edit-menu-item-eeg-members-only-<?php echo esc_attr($item_id); ?>" name="menu-item-eeg-members-only[<?php echo esc_attr($item_id); ?>]" value="1" <?php checked($val,1); ?> />
            <?php echo esc_html__('Nur für Mitglieder','eeg-verwaltung'); ?>
        </label>
    </p>
    <?php
}
function eeg_verw_save_menu_item_field($menu_id,$menu_item_db_id){
    $is_set = isset($_POST['menu-item-eeg-members-only'][$menu_item_db_id]) ? 1 : 0;
    if ($is_set) update_post_meta($menu_item_db_id,'_menu_item_members_only',1);
    else delete_post_meta($menu_item_db_id,'_menu_item_members_only');
}
function eeg_verw_setup_menu_item($menu_item){
    $menu_item->eeg_members_only = (int)get_post_meta($menu_item->ID,'_menu_item_members_only',true);
    return $menu_item;
}
function eeg_verw_filter_menu_objects($items){
    $is_member = ( is_user_logged_in() && ( current_user_can('access_members_content') || current_user_can('access_verwaltung_content') ) );
    if ($is_member) return $items;
    $filtered = [];
    foreach ($items as $item){
        if (!empty($item->eeg_members_only)) continue;
        $filtered[] = $item;
    }
    return $filtered;
}
