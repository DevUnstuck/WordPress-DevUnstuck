<?php

namespace Multisite_Oversight;

use AmpProject\AmpWP\Infrastructure\Plugin;
use Multisite_Oversight\Utils as Utils;
use Multisite_Oversight\Printer as Printer;
use Multisite_Oversight\PostTypeAnalyzer as PostTypeAnalyzer;

class WidgetAnalyzer
{
    public static $widgets_analyzed = array();

    /**
     * Analyze the widgets and store results. 
     *
     * @return void
     */
    public static function crunch_widget_details()
    {
        global $wp_registered_widgets, $sidebars_widgets;

        // List all registered widgets
        foreach ($wp_registered_widgets as $widget_id => $widget) {
            // Get the plugin file that registered the widget.
            $widget_file = Utils::reflect_callable($widget['callback']);
            $provider_plugin = Utils::get_plugin_from_file($widget_file);
            // If no match for the provider plugin is found, use the widget file.
            if( $provider_plugin )
                self::$widgets_analyzed[$widget_id] = new WidgetWrapper( $widget );
            }


        foreach (self::$widgets_analyzed as $widget_id => $widget ) {
            // Which widgets appear in this sidebar?
            // $widgets_in_use = array_intersect( array_keys( $wp_registered_widgets ), $sidebars_widgets[$sidebar_id]);
            if (!empty($widgets_in_use)) {
                // self::$widgets_analyzed[$widget_id]->sidebar = $sidebar_id;
            }
        }
    }

    /**
     * Pair widgets to plugins.
     *
     * @return void
     */
    public static function pair_widgets_to_plugins(){

        // List all registered widgets
        foreach ( self::$widgets_analyzed as $widget_id => $widget) {
            // Get the plugin file that registered the widget.
            $widget_file = Utils::reflect_callable($widget->callback);
            $provider_plugin = Utils::get_plugin_from_file($widget_file);
            if(!$provider_plugin){
                continue;
            }
            $provider_plugin = get_plugin_data( $provider_plugin );
            // need a provider plugin to continue
            if(  WidgetAnalyzer::$widgets_analyzed[$widget_id] ){
                // Match the widget to a plugin.
                PluginDataWrapper::$valid_plugins_wrapped[$provider_plugin['Name']]->widgets_registered[$widget_id] = $widget;
            }
        }

    }
}