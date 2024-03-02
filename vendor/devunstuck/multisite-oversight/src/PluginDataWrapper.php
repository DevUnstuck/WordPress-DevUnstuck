<?php

namespace Multisite_Oversight;

use Multisite_Oversight\Utils as Utils;
use Multisite_Oversight\PostTypeAnalyzer as PostTypeAnalyzer;
use Multisite_Oversight\PostTypeWrapper as PostTypeWrapper;
use Multisite_Oversight\WidgetAnalyzer as WidgetAnalyzer;
use Multisite_Oversight\TableAnalyzer as TableAnalyzer;


if (!function_exists('get_plugin_data')) {
    // Need this to get the plugin data. 
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// Use with data returned from get_plugin_data and wp_get_active_network_plugins
class PluginDataWrapper
{
    /**
     * All plugin directories.
     *
     * @var array
     */
    public static $all_plugin_dirs;

    /**
     * Plugins as reported by WordPress.
     *
     * @var array
     */
    public static $valid_plugins = array();
    public static $valid_plugin_dirs  = array();

    /**
     * The plugins that are currently loaded.
     *
     * @var array[PluginDataWrapper]
     */
    public static $valid_plugins_wrapped = array();
    public static $valid_role_plugin_dirs = array();
    public static $role_plugin_traces = array();
    public static $role_cap_traces = array();
    private $name;
    private $pluginuri;
    private $version;
    private $description;
    private $author;
    private $authoruri;
    private $textdomain;
    private $domainpath;
    private $network;
    private $requireswp;
    private $requiresphp;
    private $updateuri;
    private $title;
    private $authorname;

    /**
     * The post types registered by this plugin. 
     *
     * @var array
     */
    public $post_types_registered = array();

    /**
     * The widgets registered by this plugin. 
     *
     * @var array
     */
    public $widgets_registered = array();

    /**
     * The blocks registered by this plugin. 
     *
     * @var array
     */
    public $blocks_registered;

    /**
     * The database tables created by this plugin.
     * 
     * @var array
     */
    public $tables_created = array();

    /**
     * The postmeta used by this plugin.
     * 
     * @var array
     */
    public $postmeta_used;

    /**
     * The options used by this plugin.
     * 
     * @var array
     */
    public $options_used;

    /**
     * Custom capabilities registered by this plugin.
     * 
     * @var array
     */
    public $custom_capabilities_registered;

    /**
     * The custom capabilities used by this plugin.
     */
    public $custom_capabilities_used;

    /**
     * Custom roles added by this plugin.
     * 
     * @var array
     */
    public $custom_roles_added;

    /**
     * Custom roles used by this plugin.
     * 
     * @var array
     */
    public $custom_roles_used;

    /**
     * The taxonomies registered by this plugin.
     * 
     * @var array
     */
    public $taxonomies_registered;

    /**
     * The shortcodes registered by this plugin.
     *
     * @var array
     */
    public $shortcodes_registered;

    /**
     * The cron jobs registered by this plugin.
     * 
     * @var array
     */
    public $cron_jobs_registered = array();

    /**
     * The menus registered by this plugin.
     *
     * @var array
     */
    public $menus_registered;

    /**
     * The actions registered by this plugin.
     */
    public $actions_registered;

    /**
     * The filters registered by this plugin.
     */
    public $filters_registered;

    /**
     * Other plugins that this plugin depends on.
     * 
     * @var array
     */
    public $plugin_dependencies;

    /** 
     * Perform resource intensive tasks on init.
     * Set directories to plugins, and post types.
     */
    public static function init()
    {
        // Get all plugin directories.
        $all_plugin_dirs = shell_exec('find ' . WP_CONTENT_DIR . '/plugins -mindepth 1 -maxdepth 1 -type d');
        self::$all_plugin_dirs = explode("\n", $all_plugin_dirs);
    }

    /**
     * Takes an array of plugin data and wraps it in a PluginDataWrapper object.
     * 
     * @param array $plugin_data
     */
    public function __construct($plugin_data)
    {
        $this->name = $plugin_data['Name'];
        $this->pluginuri = $plugin_data['PluginURI'];
        $this->version = $plugin_data['Version'];
        $this->description = $plugin_data['Description'];
        $this->author = $plugin_data['Author'];
        $this->authoruri = $plugin_data['AuthorURI'];
        $this->textdomain = $plugin_data['TextDomain'];
        $this->domainpath = $plugin_data['DomainPath'];
        $this->network = $plugin_data['Network'];
        $this->requireswp = $plugin_data['RequiresWP'];
        $this->requiresphp = $plugin_data['RequiresPHP'];
        $this->updateuri = $plugin_data['UpdateURI'];
        $this->title = $plugin_data['Title'];
        $this->authorname = $plugin_data['AuthorName'];
    }

    public static function set_valid_plugin($plugin_data)
    {
        $plugin_wrapped = new self($plugin_data);
        self::$valid_plugins["{$plugin_data['Name']}"] = $plugin_data;
        self::$valid_plugins_wrapped["{$plugin_data['Name']}"] = $plugin_wrapped;
    }

    /**
     * Get plugins that are currently loaded by WordPress. Should be called on or after 'init'.
     *
     * @return void
     */
    public static function set_valid_plugins()
    {
        // Get network activate plugins.
        $network_plugins = wp_get_active_network_plugins();
        // Get currently site active plugins.
        $sites_plugins = wp_get_active_and_valid_plugins();
        $valid_plugins  = array_merge($network_plugins, $sites_plugins);
        foreach ($valid_plugins as $valid_plugin) {
            $plugin_data = get_plugin_data($valid_plugin);
            self::set_valid_plugin($plugin_data);
        }
    }
    /**
     * Set the plugin directories of any plugins that are active.
     *
     * @return void
     */
    public static function set_active_plugin_dirs()
    {
        // Get network activate plugins.
        $network_plugins = wp_get_active_network_plugins();
        // Get currently site active plugins.
        $sites_plugins = wp_get_active_and_valid_plugins();
        $valid_plugins  = array_merge($network_plugins, $sites_plugins);
        $valid_plugin_dirs = array();
        foreach ($valid_plugins as $valid_plugin) {
            $plugin_root = preg_replace('@' . WP_PLUGIN_DIR . '@', '', $valid_plugin);
            // Get only the plugin directory name (leading slash creates an empty first array item).
            $plugin_dir = explode('/', $plugin_root)[1];
            // save the plugin directory name as the key and the plugin main file as the value.
            $valid_plugin_dirs[$plugin_dir] = $valid_plugin;
        }
        self::$valid_plugin_dirs = $valid_plugin_dirs;
    }

    public function set_registered_post_type($post_type)
    {
        // TODO get list of published posts for each post type.
        $this->post_types_registered[$post_type] = array(
            'pulished_posts' => array(),
            'num_published_posts' => 0,
        );
    }
    public function set_roles_added($role)
    {
        // TODO get list of users with this role.
        $this->custom_roles_added[$role] = array(
            'users' => array(),
            'num_users' => 0,
        );
    }

    /**
     * Pair post type to the plugins that registered them.
     *
     * @return void
     */
    public static function pair_post_types_to_plugins()
    {
        foreach (PostTypeAnalyzer::$post_type_traces as $trace) {
            $plugin = (Utils::get_plugin_from_file($trace['file']));
            if (!$plugin) {
                continue;
            }
            $plugin_data = get_plugin_data($plugin);
            $plugin = self::$valid_plugins_wrapped[$plugin_data['Name']];
            if ($plugin) {
                $plugin->set_registered_post_type($trace['args'][0]);
            }
        }
        //TODO count the number of published posts on each subsite for each post type.
    }

    /**
     * Pair widgets to plugins.
     *
     * @return void
     */
    public static function pair_widgets_to_plugins()
    {

        // List all registered widgets
        foreach (WidgetAnalyzer::$widgets_analyzed as $widget_id => $widget) {
            // Get the plugin file that registered the widget.
            $widget_file = Utils::reflect_callable($widget->callback);
            $provider_plugin = Utils::get_plugin_from_file($widget_file);
            if (!$provider_plugin) {
                continue;
            }
            $provider_plugin = get_plugin_data($provider_plugin);
            // need a provider plugin to continue
            if (WidgetAnalyzer::$widgets_analyzed[$widget_id]) {
                // Match the widget to a plugin.
                self::$valid_plugins_wrapped[$provider_plugin['Name']]->widgets_registered[$widget_id] = $widget;
            }
        }
    }

    /**
     * Pair tables to plugins.
     * These tables are not created by WordPress core, and might be created by the plugin, theme or dependency.
     *
     * @return void
     */
    public static function pair_tables_to_plugins()
    {
        
        foreach (TableAnalyzer::$tables_analyzed as $table => $table_data) {
            $plugin_matches = array();
            // Remove empties.
            $file_matches = self::grep_plugin_dirs($table);
            $file_matches = array_filter($file_matches);
            if (!$file_matches) {
                continue;
            }
            // match each table referencing file to a plugin.
            foreach ($file_matches as $match) {
                $plugin = (Utils::get_plugin_from_file($match));
                if ($plugin) {
                    $plugin_matches[] = $plugin;
                }
            }

            // Multiple plugins can reference the same table, so we need to add the table to each plugin that uses it.
            foreach ($plugin_matches as $plugin_file) {
                $plugin_data = get_plugin_data($plugin_file);
                $plugin = self::$valid_plugins_wrapped[$plugin_data['Name']];
                // Has the plugin been processed yet?
                if ($plugin) {
                    $plugin->tables_created[$table] = TableAnalyzer::$tables_analyzed[$table];
                }
            }
        }
    }

    /**
     * Pair shortcodes to plugins.
     *
     * @return void
     */
    public static function pair_shortcodes_to_plugins()
    {
        global $shortcode_tags;
        foreach ($shortcode_tags as $shortcode => $callback) {
            $plugin_file = Utils::reflect_callable($callback);
            $provider_plugin = Utils::get_plugin_from_file($plugin_file);
            if (!$provider_plugin) {
                continue;
            }
            $provider_plugin = get_plugin_data($provider_plugin);
            // need a provider plugin to continue
            if (self::$valid_plugins_wrapped[$provider_plugin['Name']]) {
                // TODO get list of posts that use this shortcode.
                // Match the widget to a plugin.
                self::$valid_plugins_wrapped[$provider_plugin['Name']]->shortcodes_registered[$shortcode] = array(
                    'callback' => $callback,
                    'posts' => array(),
                    'num_posts' => 0,
                );
            }
        }
    }

    /**
     * Pair the custom capabilities to the plugins that registered them.
     */
    public static function pair_capabilities_to_plugins()
    {
        global $wp_roles;
        // Capabilities keyed by the singular base if one was found.
        $singular_base_to_meta_cap = array();
        $singular_bases = PostTypeAnalyzer::$meta_cap_singular_bases;
        $singular_bases = array_unique($singular_bases);
        // remove the default WP post types
        $singular_bases = preg_grep('/post|page|attachment|revision|nav_menu_item|custom_css|customize_changeset|oembed_cache|user_request|wp_block/', $singular_bases, PREG_GREP_INVERT);
        $singular_bases_regex = implode('|', $singular_bases);

        // TODO make regex more comprehensive.
        $wp_capabilities_regex = 'add_(role|cap)|capability_type|map_meta_cap';
        $maybe_capabilities_files = Utils::get_files_using_wp_fns(WP_PLUGIN_DIR, $wp_capabilities_regex);
        // merge and dedupe the capabilities arrays on each role.
        $all_capabilities = array_unique(array_merge(array_column($wp_roles->roles, 'capabilities')));
        if (is_array($all_capabilities) && !empty($all_capabilities)) {
            $all_capabilities = array_keys($all_capabilities[0]);
        } else {
            // No capabilities found.
            return false;
        }
        // remove the default WP capabilities.
        $wp_default_caps = Utils::get_wp_default_caps();
        $all_capabilities = array_diff($all_capabilities, $wp_default_caps);
        // Strip the meta_cap singular bases from the capabilities.
        foreach ($all_capabilities as $key => $capability) {
            // Check if the capability contains a singular base( meaning it's likey a meta capability) and strip it.
            preg_match("/{$singular_bases_regex}/", $capability, $matches);
            if (!empty($matches)) {
                $matches = array_filter($matches);
                // Add the meta capability with the matched singular base.
                $singular_base_to_meta_cap[$capability] = $matches[0];
            } else {
                // No singular base found, so add the capability as is.
                $singular_base_to_meta_cap[$capability] = $capability;
            }
        }
        foreach ($singular_base_to_meta_cap as $capability => $maybe_singular_base) {
            // search for the hook name in the plugin files.
            $hook_file = self::search_files_for_regex($maybe_capabilities_files, $maybe_singular_base);
            if (!$hook_file) {
                continue;
            }
            // todo performance concern.
            // only use the first matched result returned from search. 
            $hook_plugin = utils::get_plugin_from_file($hook_file[0]);
            if (!$hook_plugin) {
                continue;
            }
            $provider_plugin = get_plugin_data($hook_plugin);
            // need a provider plugin to continue
            if (self::$valid_plugins_wrapped[$provider_plugin['Name']]) {
                if (!self::$valid_plugins_wrapped[$provider_plugin['Name']]->custom_capabilities_registered) {
                    self::$valid_plugins_wrapped[$provider_plugin['Name']]->custom_capabilities_registered = array();
                }
                // match the cron job to a plugin.
                self::$valid_plugins_wrapped[$provider_plugin['Name']]->custom_capabilities_registered[$capability] = $capability;
            }
        }
    }

    /**
     * Pair the custom roles to the plugins that registered them.
     */
    public static function pair_roles_to_plugins()
    {
        // Use the actual roles loaded for comparison
        global $wp_roles;
        $wp_default_roles_regex = '/administrator|editor|author|contributor|subscriber/';
        $all_roles = array_keys($wp_roles->roles);
        // Filter out the default WP roles.
        $custom_roles = preg_grep($wp_default_roles_regex, $all_roles, PREG_GREP_INVERT);
        $wp_role_regex = 'add_role';
        $maybe_capabilities_files = Utils::get_files_using_wp_fns(WP_PLUGIN_DIR, $wp_role_regex);
        // TODO make regex more comprehensive.
        foreach ($custom_roles  as $role) {
            $hook_file = self::search_files_for_regex($maybe_capabilities_files, $role);
            // only use the first matched result returned from search. 
            $hook_plugin = utils::get_plugin_from_file($hook_file[0]);
            if ($hook_plugin) {
                $provider_plugin = get_plugin_data($hook_plugin);
                self::$valid_plugins_wrapped[$provider_plugin['Name']]->set_roles_added($role);
            }
        }
    }

    /**
     * Pair the taxonomies to the plugins that registered them.
     */
    public static function pair_taxonomies_to_plugins()
    {
    }

    /**
     * Pair the cron jobs to the plugins that registered them.
     */
    public static function pair_cron_jobs_to_plugins()
    {
        global $wpdb;
        $tables = array();
        $tables = Utils::get_subsite_tables_by_suffix('options');
        $cron_sql = Utils::select_columns_from_tables(array('option_value'), $tables, 'option_name = "cron"');
        $cron_jobs = $wpdb->get_results($cron_sql);
        $wp_cron_fns_regex = 'wp_(next_scheduled|schedule_event|schedule_single_event|schedule_event|schedule_single_event|schedule_recurring_event)';
        $maybe_cron_files = Utils::get_files_using_wp_fns(WP_PLUGIN_DIR, $wp_cron_fns_regex);
        // Process results from the cron table.
        foreach ($cron_jobs as &$cron) {
            $cron->option_value = unserialize($cron->option_value);
            $cron->option_value = array_filter($cron->option_value, 'is_array');
            // Set the site id so we can sort the cron jobs by site.
            $site_id = explode('_', $cron->option_name)[1];
            $site_id = is_numeric($site_id) ? $site_id : 1;
            $cron->site_id = $site_id;
            // Loop the cron job schedules/timings 
            foreach ($cron->option_value as $hooks) {
                if (!is_array($hooks)) {
                    continue;
                }
                foreach ($hooks as $hook => $job) {
                    // Search for the hook name in the plugin files.
                    $hook_file = self::search_files_for_regex($maybe_cron_files, $hook);
                    if (!$hook_file) {
                        continue;
                    }
                    // TODO Performance concern.
                    // Only use the first matched result returned from search. 
                    $hook_plugin = Utils::get_plugin_from_file($hook_file[0]);
                    if (!$hook_plugin) {
                        continue;
                    }
                    $provider_plugin = get_plugin_data($hook_plugin);
                    // need a provider plugin to continue
                    if (self::$valid_plugins_wrapped[$provider_plugin['Name']]) {
                        if (!self::$valid_plugins_wrapped[$provider_plugin['Name']]->cron_jobs_registered[$cron->$site_id]) {
                            self::$valid_plugins_wrapped[$provider_plugin['Name']]->cron_jobs_registered[$cron->$site_id] = array();
                        }
                        // Match the cron job to a plugin.
                        self::$valid_plugins_wrapped[$provider_plugin['Name']]->cron_jobs_registered[$cron->$site_id][$hook] = $job;
                    }
                }
            }
        }
        return;
    }

    /**
     * Pair the menus to the plugins that registered them.
     */
    public static function pair_menus_to_plugins()
    {
    }

    // Search all files for a string
    public static function search_files_for_regex($files, $search)
    {
        // Search for the first matched file.
        $hide_errors = ' 2>/dev/null';
        $shell_cmd = '(rg -m 1 -lF ' . $search . ' ' . implode(' ', $files) . ' | xargs -I{} sh -c \'realpath "{}" && kill $PPID\')  ' . $hide_errors;
        $file_matches[] = shell_exec($shell_cmd);
        $file_matches = array_filter($file_matches);
        return $file_matches;
    }
    // Search all plugin directories for a string
    public static function grep_plugin_dirs($search)
    {
        foreach (self::$all_plugin_dirs as $plugin_dir) {
            // Search for the first matched file that contains the unique part of the table name in this plugin directory.
            $shell_cmd = 'cd ' . $plugin_dir . ' && (rg -m 1 -lF ' . $search . '  $( find . -type f -regex ".*.php" ) | xargs -I{} sh -c \'realpath "{}" && kill $PPID\')  2>/dev/null';
            $file_matches[] = shell_exec($shell_cmd);
        }
        // TODO fix rg matchign more than one.
        $file_matches = array_filter($file_matches);
        return $file_matches;
    }

    /**
     * Get the plugin main file from a given file name.
     */
    public static function get_plugin_from_file($file_name)
    {
        foreach (self::$valid_plugin_dirs as $plugin_dir => $valid_plugin) {
            // if the file name contains the plugin directory name
            if (strpos($file_name, $plugin_dir) !== false) {
                // return the plugin main file.
                return $valid_plugin;
            }
        }
        return false;
    }
}
