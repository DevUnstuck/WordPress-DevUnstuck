<?php

namespace Multisite_Oversight;
use Multisite_Oversight\PluginDataWrapper as PluginDataWrapper;

class Utils
{

    /**
     * Perform an action on all sites in a multisite network.
     *
     * @param callable $callback Function to call for each site. Accepts one parameter: $blog_id.
     */
    public static function for_each_site(callable $callback)
    {
        global $wpdb;

        // Get all blog ids
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            try {
                call_user_func($callback, $blog_id);
            } finally {
                restore_current_blog();
            }
        }
    }

    /**
     * Get the plugin main file from a given file name.
     */
    public static function get_plugin_from_file($file_name)
    {
        foreach ( PluginDataWrapper::$valid_plugin_dirs as $plugin_dir => $valid_plugin) {
            // if the file name contains the plugin directory name
            if (strpos($file_name, $plugin_dir) !== false) {
                // return the plugin main file.
                return $valid_plugin;
            }
        }
        return false;
    }

    /**
     * Given any file within a plugin directory, determine the plugin main file.
     *
     * @param string $file
     * @return void
     */
    public static function determine_plugin_from_file( $file ){
    }

    /**
     * Given a callabe, return the reflection object.
     *
     * @param callable $callable
     * @return void
     */
    public static function reflect_callable($callable)
    {
        if (is_string($callable) && function_exists($callable)) {
            // It's a function
            $reflection = new \ReflectionFunction($callable);
        } elseif( is_string($callable) && str_contains($callable, '::') ){
            // It's a static method.
            $callable = explode('::', $callable);
            $reflection = new \ReflectionMethod( $callable[0], $callable[1] );
        } elseif (is_array($callable) && count($callable) === 2) {
            // It's a method
            if (
                (is_string($callable[0]) && class_exists($callable[0]) && method_exists($callable[0], $callable[1]))
                || (is_object($callable[0]) && method_exists($callable[0], $callable[1]))
            ) {
                $reflection = new \ReflectionMethod($callable[0], $callable[1]);
            }
        } elseif (is_object($callable) && method_exists($callable, '__invoke')) {
            // It's an object with an __invoke method
            $reflection = new \ReflectionMethod($callable, '__invoke');
        } else {             
            error_log('Invalid callable provided');
            // throw new \InvalidArgumentException('Invalid callable provided');
        }

        return $reflection ? $reflection->getFileName() : false;
    }

    /**
     * Get all subsite tablenames for a given suffix.
     * 
     * @param string $suffix The suffix of the table name.
     */
    public static function get_subsite_tables_by_suffix($suffix)
    {
        global $wpdb;
        $table_names = array();

        if (is_multisite()) {

            // Fetch all blog IDs.
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

            // Create an array to hold the table names.
            $table_names = array();

            // Generate the table names.
            foreach ($blog_ids as $blog_id) {
                // For main site (blog_id is 1), the table is 'wp_options'.
                if ($blog_id == 1) {
                    $table_names[$blog_id] = $wpdb->prefix . $suffix;
                }
                // For other sites, the table is 'wp_x_options' where x is the blog_id.
                else {
                    $table_names[$blog_id] = $wpdb->prefix . $blog_id . '_' . $suffix;
                }
            }
            return $table_names;
        } else {
            // Stick to the multisite paradigm.
            return array( 1 => $wpdb->prefix . $suffix );
        }
        // TODO ensure that the table exists.

    }

    /**
     * SELECT multiple columns for multiple tables UNION ALL.
     * 
     * @var array $columns The columns to select.
     */
    public static function select_columns_from_tables($columns, $tables, $where = null)
    {
        global $wpdb;
        $sql = '';
        $i = 0;
        $columns = implode(', ', $columns);
        foreach ($tables as $table) {
            $sql .= "SELECT \"{$table}\", $columns FROM $table";
            if ($where) {
                $sql .= " WHERE $where";
            }
            if ($i < count($tables) - 1) {
                $sql .= " UNION ALL ";
            }
            $i++;
        }
        return $sql;
    }

    /** 
     * Search all php plugin files for references to cron schedules functions to get the filenames
     */
    public static function get_files_using_wp_fns( $plugin_path, $wp_fns_regex , $get_all = false)
    {
        // Use rigrep to search all php files for references to cron schedules functions.
        $command = "rg --files-with-matches \"{$wp_fns_regex}\" --type php {$plugin_path}";
        $output = shell_exec($command);
        $output = explode("\n", $output);
        // Remove empty lines.dfadf
        $output = array_filter($output);
        return $output;
    }

    /**
     * Get WP default roles.
     */
    public static function get_wp_default_caps(){
        // Need the schema file that contains references to add_cap.
        $schema_file = ABSPATH . 'wp-admin/includes/schema.php';
        $schema_file_contents = file_get_contents($schema_file);
        // Get the lines that contain add_cap.
        preg_match_all("%add_cap[('\s]*(?<cap>[a-zA-Z0-9_]*)[('\s]*%", $schema_file_contents, $matches);
        if( is_array( $matches ) && isset( $matches['cap'] ) ){
            return $matches['cap'];
        }
    }
}