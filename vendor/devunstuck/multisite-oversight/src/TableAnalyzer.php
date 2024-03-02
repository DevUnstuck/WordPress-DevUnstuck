<?php

namespace Multisite_Oversight;

class TableAnalyzer
{

    const DEFAULT_WP_TABLES = array(
        'commentmeta',
        'comments',
        'links',
        'options',
        'postmeta',
        'posts',
        'term_relationships',
        'term_taxonomy',
        'termmeta',
        'terms',
        'usermeta',
        'users',
        'blogs',
        'blogmeta',
        'site',
        'sitemeta',
        'signups',
        'registration_log',
        'sitecategories'
    );

    public static $tables_analyzed = array();

    /**
     * The singleton pattern class instance.
     */
    public static get_instance(){
        if( !isset( self::$instance ) ){
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Get non-standard database tables.
     * 
     * @return void
     */
    public static function get_non_standard_tables()
    {
        global $wpdb;
        // Strips the wp_ part and save the site id of the table.
        $table_regex = '/^wp_(?<site_id>\d)?_?(?<unique_name_part>.*)/';
        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        $tables = array_column($tables, 0);
        // Parse table names and save the site id and table name.
        foreach ($tables as $table) {
            preg_match($table_regex, $table, $matches);
            // Skip default WP tables.
            if (in_array( $matches['unique_name_part'], self::DEFAULT_WP_TABLES)) {
                continue;
            }
            if( !is_array( self::$tables_analyzed[$matches['unique_name_part']] ) ){
                self::$tables_analyzed[$matches['unique_name_part']] = array();
            }
            // Save the table name and site id.
            self::$tables_analyzed[$matches['unique_name_part']][] = array(
                'fullname' => $table,
                'site_id' => $matches['site_id'],
            );
        }
    }
}