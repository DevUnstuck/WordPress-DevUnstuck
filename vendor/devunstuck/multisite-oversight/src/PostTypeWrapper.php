<?php

namespace Multisite_Oversight;

use Multisite_Oversight\Utils as Utils;
use Multisite_Oversight\Printer as Printer;
use Multisite_Oversight\PluginDataWrapper as PluginDataWrapper;

class PostTypeWrapper
{
    public function __construct( $post_type ){
        $post_type_obj = get_post_type_object( $post_type);
        // Copy all the properties from the post type object to this object.
        foreach( $post_type_obj as $key => $value ){
            $this->$key = $value;
        }
    }

}
