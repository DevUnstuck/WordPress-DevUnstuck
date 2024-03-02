<?php
/*
Plugin Name: Multisite Oversight
Description: Multisite plugin and theme usage reporting tool.
Version:     0.1
Author:      Manny Adumbire
Author URI:  http://example.com
*/

// Load WordPress.
// require '../../wp-load.php';
// require '../../wp-blog-header.php';

require __DIR__ . '/vendor/autoload.php';

use \Multisite_Oversight\PostTypeAnalyzer as PostTypeAnalyzer;
use \Multisite_Oversight\PluginDataWrapper as PluginDataWrapper;
use \Multisite_Oversight\CapabilitiesAnalyzer as CapabilitiesAnalyzer;
use Multisite_Oversight\PostTypeWrapper;
use \Multisite_Oversight\Printer;

$mo_analyze = new PostTypeAnalyzer();

// Clone the register_post_type function.
runkit7_function_copy( 'add_role', 'add_role_copy' );

// Analyze the post types.
if ( is_callable( 'add_role' ) ) {
	/**
	 * This function runs to initiate roles.
	 * It wraps the 'add_role' stores the stack data for later analysis.
	 */
	$wrap_fn = function ( $role, $display_name, $capabilities = array() ) {
		// Call the original/cloned register_post_type function.
		add_role_copy( $role, $display_name, $capabilities = array() );
		// Get the PHP trace for this function call, we should only need the top-most stack frame.
		$trace_add_role                          = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 1 );
		$trace_add_role                          = $trace_add_role[0];
		PluginDataWrapper::$role_plugin_traces[] = $trace_add_role;
	};
	// Replace the register_post_type function with the wrapper function.
	runkit7_function_redefine( 'add_role', $wrap_fn );
}

// Clone the register_post_type function.
runkit7_function_copy( 'register_post_type', 'register_post_type_copy' );
// Analyze the post types.
if ( is_callable( 'register_post_type' ) ) {
	/**
	 * The function runs before/while plugins are being registered.
	 * It wraps the register_post_type function and stores the post type data for later analysis.
	 */
	$wrap_fn = function ( $post_type, $args ) {
		// Call the original/cloned register_post_type function.
		register_post_type_copy( $post_type, $args );
		if ( $args['map_meta_cap'] === true ) {
			if ( is_array( $args['capability_type'] ) && ! empty( $args['capability_type'][0] ) ) {
				// Get the capabilities basename.
				PostTypeAnalyzer::$meta_cap_singular_bases[] = $args['capability_type'][0];
			} elseif ( ! empty( $args['capability_type'] ) ) {
				PostTypeAnalyzer::$meta_cap_singular_bases[] = $args['capability_type'];
			}
		}
		// Get the PHP trace for this function call, we should only need the top-most stack frame.
		$trace_post_type = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 1 );
		$trace_post_type = $trace_post_type[0];
		// Check if this post type is a native or "built-in" post_type.
		$is_builtin = $trace_post_type['args'][1]['_builtin'] ?? false;
		if ( $is_builtin ) {
			return;
		}
		PostTypeAnalyzer::$post_type_traces[] = $trace_post_type;
	};

	// Replace the register_post_type function with the wrapper function.
	runkit7_function_redefine( 'register_post_type', $wrap_fn );
}

// Clone the register_post_type function.
runkit7_function_copy( 'register_post_type', 'register_post_type_copy' );

// Add the post types to the wrapper.


/**
 * Compile initial lists of active plugins and and their directories.
 */
// Prepare the plugin data wrapper to store plugin data.
add_action( 'init', '\Multisite_Oversight\PluginDataWrapper::init', 10 );
// Add valid plugins to the wrapper.
add_action( 'init', '\Multisite_Oversight\PluginDataWrapper::set_valid_plugins', 9001 );
// Match valid plugins to direcotries.
add_action( 'init', '\Multisite_Oversight\PluginDataWrapper::set_active_plugin_dirs', 9002 );


// Run After init

// Initilize the post type analyzer and add the post types to the wrapper.
// add_action('init', '\Multisite_Oversight\PostTypeAnalyzer::set_post_types', 9002 );
// Initilize the widget analyzer and add the widgets to the wrapper.
// add_action('init', '\Multisite_Oversight\WidgetAnalyzer::crunch_widget_details', 9003);
// Initilize the table analyzer and add the non-standard tables to its store.
add_action( 'init', '\Multisite_Oversight\TableAnalyzer::get_non_standard_tables', 9999 );
// Initilize the capabilities analyzer and add the non-standard tables to its store.
// add_action('init', '\Multisite_Oversight\CapabilitiesAnalyzer::get_non_standard_capabilities', 9999);

// Process (supposed) already accumulated data, merging it into the appropriate plugin wrappers.
// add_action('init', '\Multisite_Oversight\PluginDataWrapper::pair_post_types_to_plugins', 9999);
// add_action('init', '\Multisite_Oversight\PluginDataWrapper::pair_widgets_to_plugins', 9999);
// add_action('init', '\Multisite_Oversight\PluginDataWrapper::pair_shortcodes_to_plugins', 9999);
// add_action('init', '\Multisite_Oversight\PluginDataWrapper::pair_roles_to_plugins', 9999);
// TODO reduce resource usage by only running this on the admin page.
add_action( 'init', '\Multisite_Oversight\PluginDataWrapper::pair_tables_to_plugins', 9999 );
