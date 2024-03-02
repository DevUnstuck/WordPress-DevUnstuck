<?php

namespace Multisite_Oversight;

use Multisite_Oversight\Utils as Utils;
use Multisite_Oversight\Printer as Printer;
use Multisite_Oversight\PluginDataWrapper as PluginDataWrapper;

class PostTypeAnalyzer
{

    // Stored of PHP traces captured while registering post types.
    public static $post_type_traces = array();

    public static $valid_post_types = array();
    
    /**
     * basenames used to map post types to meta capabilities.
     *
     * @var array
     */
    public static $meta_cap_singular_bases = array();

    public function __construct()
    {
        if( ! function_exists('get_plugin_data') ){
            // Need this to get the plugin data. 
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
    }
    public static function wrap_register_post_type($post_type, $args)
    {
    }

}