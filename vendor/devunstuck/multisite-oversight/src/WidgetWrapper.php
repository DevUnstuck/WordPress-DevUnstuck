<?php

namespace Multisite_Oversight;

use Multisite_Oversight\Utils as Utils;
use Multisite_Oversight\Printer as Printer;

/**
 * Wrapper for a registered instance of WP_Widget.
 */
class WidgetWrapper
{
    public $sidebar;

    public function __construct( $widget ){  
        // Copy all the properties from the widget object to this object.
        foreach( $widget as $key => $value ){
            $this->$key = $value;
        } 
    }

}