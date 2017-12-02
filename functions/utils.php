<?php

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) 
{
    echo "Hi there!<br>Do you want to plug me ?<br>";
	echo "If you looking for more about me, you can read at http://osw3.net/wordpress/plugins/please-plug-me/";
    exit;
}


/**
 * Get Plugin Options
 * --
 * @usage WPPPM_getOptions("get_in_touch")
 */
if (!function_exists('WPPPM_getOptions'))
{
    function WPPPM_getOptions( $option_name, $param=null )
    {
        $options = get_option( $option_name );
        return (null == $param) ? $options : $options[ $param ];
    }
} 


/**
 * Slugify
 * --
 * @usage WPPPM_Slugify("Hello WoRlD")
 */
if (!function_exists('WPPPM_Slugify'))
{
    function WPPPM_Slugify( $text, $separator="-" )
    {
        return PPM::slugify( $text, $separator );
    }
} 