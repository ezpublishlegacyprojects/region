Region extension
===============================================================================
Author: Björn Dieding, http://www.xrow.com

The region extension redirects a user to a siteaccess based on his ip address.
This can be done in the background (the ip address is checked in the database)
automatically, or the user can choose a country siteaccess in a specific
pagelayout.

This extension includes GeoLite data created by MaxMind, available from
http://maxmind.com/

The geo ip database is taken from
http://geolite.maxmind.com/download/geoip/database/GeoIPCountryCSV.zip

Project Homepage:
http://projects.ez.no/region

Setup
===============================================================================
- install extension
- run php bin/php/ezpgenerateautoloads.php
- activate the extension in your site.ini.append
- edit settings/region.ini
- clear ini cache

eZ Publish 4.0.x:
Include the rewrite rules from htaccess_append.txt
You need to exclude your admin siteaccess from rewriting

From eZ Publish 4.1.x:
Edit your config.php and add those lines of code. This will execute
and request router after the ezc have been loaded.

The first parameter of ezxRegion::load will define an array of siteaccesses
that are excluded from the routing.

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
