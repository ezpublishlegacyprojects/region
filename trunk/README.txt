Setup

edit your config.php and add those lines of code. This will execute and request router after the ezc have been loaded. 

The first parameter of ezxRegion::load will define an array of siteaccesses that are excluded from the routing.

NOTE: In earlier setup the work has been covert by mod_rewrite over .htacces (see htaccess_append.txt)

<?php
if( php_sapi_name() != 'cli' )
{
    function RegionOnLoad( $className )
    {
        if ( !defined( 'EZP_ROUTER_EXECUTION' ) and class_exists( 'ezcUrl' )  )
        {
            define( 'EZP_ROUTER_EXECUTION', true );
            ezxRegion::load( array( 'ezwebin_site_admin' ) );
        }
    }
    spl_autoload_register( 'RegionOnLoad' );
}
?>
