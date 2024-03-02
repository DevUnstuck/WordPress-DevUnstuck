<?php
/**
* Multisite Oversight Printer
*   This class is responsible for printing the data from the various components of the report.
 */

namespace Multisite_Oversight;


require __DIR__ . '/../vendor/autoload.php';

class Printer {

    /**
     * Print the report.
     */
    public static function print_report() {
        $report = self::get_report();
        self::print_header();
        self::print_post_types( $report );
        self::print_footer();
    }

    /**
     * Print the report header.
     */
    private static function print_header() {
        echo '<h1>Multisite Oversight Report</h1>';
    }

    /**
     * Print the report footer.
     */
    private static function print_footer() {
        echo '<p>Report generated on ' . date( 'Y-m-d H:i:s' ) . '</p>';
    }

    /**
     * Print the post types.
     *
     * @param array $report The report data.
     */
    private static function print_post_types( array $report ) {
        echo '<h2>Post Types</h2>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Post Type</th>';
        echo '<th>Count</th>';
        echo '<th>Usage</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ( $report['post_types'] as $post_type => $count ) {
            echo '<tr>';
            echo '<td>' . $post_type . '</td>';
            echo '<td>' . $count . '</td>';
            echo '<td>' . self::get_post_type_usage( $post_type ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Get the post type usage.
     *
     * @param string $post_type The post type.
     *
     * @return string The post type usage.
     */
    private static function get_post_type_usage( $post_type ) {
        $usage = '';
        foreach ( self::get_post_type_traces( $post_type ) as $trace ) {
            $usage .= self::get_trace_usage( $trace ) . '<br>';
        }
        return $usage;
    }

    /**
     * Get the post type traces.
     *
     * @param string $post_type The post type.
     *
     * @return array The post type traces.
     */
    private static function get_post_type_traces( $post_type ) {
        $traces = [];
        foreach ( PostTypeAnalyzer::post_type as $trace ) {
            if ( $trace['args'][0] === $post_type ) {
                $traces[] = $trace;
            }
        }
        return $traces;
    }
    
    /**
     * Get the trace usage.
     *
     * @param array $traces The traces captured during previous register_post_type.
     * @return void
     */
    public static function print_post_type_trace( $trace ){
        $plugin_data = \get_plugin_data( $trace["file"] );
        $post_type = $trace["args"][0];
        $plugin_dependency = array(
            "plugin" => $plugin_data["Name"],
            "post_type" => $post_type,
        );
        print_r($plugin_dependency);
    }
    /**
     *  Determine the $plugin_file from the $file_name
     * 
     * @param string $file_name The file name.
     * @return string The plugin file.
     */
    public static function get_plugin_path_from_file( $file_name ){
        $plugin_path = "";
        $plugin_path = str_replace( WP_CONTENT_DIR, "", $file_name );
        $plugin_path = str_replace( "/plugins/", "", $plugin_path );
        $plugin_path = str_replace( "/themes/", "", $plugin_path );
        $plugin_path = explode( "/", $plugin_path );
        $plugin_path = $plugin_path[0];
        return $plugin_path;
    }

}