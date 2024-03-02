<?php

if (!class_exists('WP_CLI')) {
    return;
}

// Get an instance of Multisite_Oversight class
$multisite_oversight = new Multisite_Oversight();

/**
 * Group of commands to manage Multisite Oversight
 */
class Multisite_Oversight_Commands {

    /**
     * @var Multisite_Oversight
     */
    private $multisite_oversight;

    public function __construct(Multisite_Oversight $multisite_oversight) {
        $this->multisite_oversight = $multisite_oversight;
    }

    /**
     * Outputs plugin dependencies
     *
     * ## OPTIONS
     *
     * ## EXAMPLES
     *
     * wp multisite_oversight plugin_dependencies
     */
    public function plugin_dependencies() {
        $dependencies = $this->multisite_oversight->get_plugin_dependencies();
        WP_CLI::line(json_encode($dependencies));
    }

    // Add similar commands for last_update_and_compatibility, custom_widgets_and_shortcodes_usage, admin_menu_items, registered_post_types_and_taxonomies, and database_impact

}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('multisite_oversight', 'Multisite_Oversight_Commands');
}
