<?php

class ezxRegion
{

    /**
     * Returns Region information for the current user/ip...
     *
     * @return array Returns an array with keys
     */
    static function load( $SessionName, $redirectRoot = false, $url_excludes = array() )
    {
        if ( eZSys::isShellExecution() )
        {
            return;
        }
        if ( self::isBot() )
        {
            return;
        }
        $urlCfg = new ezcUrlConfiguration( );
        $urlCfg->script = 'index.php';
        $url = new ezcUrl( ezcUrlTools::getCurrentUrl(), $urlCfg );
        $params = $url->getParams();

        $url_excludes = array_merge( $url_excludes, eZINI::instance( 'region.ini' )->variable( 'Settings', 'URLExcludes' ) );
        # Checking for excluded URLs
        $current_url = implode( '/', $params );
        if ( count( $url_excludes ) > 0 )
        {
            foreach ( $url_excludes as $exclude )
            {
                if ( preg_match( '#^([^/]*/){0,1}' . $exclude . '#', $current_url ) )
                {
                     return;
                }
            }
        }

        if ( !is_array( $SessionName ) && $SessionName == '' )
        {
           $SessionName = 'eZSESSID';
        }
        $foundSessionName = false;        
        if ( is_array( $SessionName ) )
        {
            foreach ( $SessionName as $name )
            {
                foreach ( $_COOKIE as $cookieName => $cookieValue)
                {
                    if ( strpos( $cookieName, $SessionName ) !== false )
                    {
                        $foundSessionName = true;
                    }
                }

                if ( $foundSessionName )
                {
                    if ( $redirectRoot and array_key_exists( 'EZREGION', $_COOKIE ) and is_array( $params ) && count( $params ) == 0 and file_exists( 'settings/siteaccess/' . $_COOKIE['EZREGION'] ) )
                    {
                        $redirectWithCookie = true;
                    }
                    else
                    {
                        return;
                    }
                }
            }
        }
        else
        {
			foreach ( $_COOKIE as $cookieName => $cookieValue)
			{
				if ( strpos( $cookieName, $SessionName ) !== false )
				{
					$foundSessionName = true;
				}
			}
	  
      	    if ( $foundSessionName )
            {
                if ( $redirectRoot and array_key_exists( 'EZREGION', $_COOKIE ) and is_array( $params ) && count( $params ) == 0 and file_exists( 'settings/siteaccess/' . $_COOKIE['EZREGION'] ) )
                {
                    $redirectWithCookie = true;
                }
                else
                {
                    return;
                }
            }
        }
        
        if ( isset( $params[0] ) and file_exists( 'settings/siteaccess/' . $params[0] ) )
        {
            $siteaccess = $params[0];
            if ( array_key_exists( 'EZREGION', $_COOKIE ) and $_COOKIE['EZREGION'] === $siteaccess )
            {
                return;
            }
        }
        else
        {
            if ( isset( $redirectWithCookie ) && $redirectWithCookie === true )
            {
                $siteaccess = $_COOKIE['EZREGION'];
            }
            else
            {
                $siteaccess = false;
            }
        }
        
		if ( isset( $params[0] ) and $params[0] == 'ezinfo' and isset( $params[1] ) and $params[1] == 'is_alive' )    
        { 
            return;
        }
        if ( ( isset( $params[0] ) and $params[0] == 'region' and $params[1] == 'index' ) or ( $siteaccess and isset( $params[1] ) and $params[1] == 'region' and isset( $params[1] ) and $params[2] == 'index' ) )
        {
            return;
        }
        if ( $siteaccess )
        {
            $paramnew = array( 
                $siteaccess , 
                'region' , 
                'index' , 
                $siteaccess 
            );
        }
        else
        {
            $paramnew = array( 
                'region' , 
                'index' 
            );
        }
        $query = $url->getQuery();
        $params = $url->path;
        if ( $siteaccess )
        {
            array_shift( $params );
        }
        
        if ( count( $params ) )
        {
            $query['URL'] = join( '/', $params );
        }
        setcookie( "COOKIETEST", 1, time() + 3600 * 24 * 365, '/' );
        $query['COOKIETEST'] = 1;
        
        $url->setQuery( $query );
        $url->params = $paramnew;
        header( 'Location: ' . $url->buildUrl() );
        eZExecution::cleanExit();
    }

    static function isBot()
    {
        $bot_list = array( 
            "Teoma" , 
            "alexa" , 
            "froogle" , 
            "Gigabot" , 
            "inktomi" , 
            "looksmart" , 
            "URL_Spider_SQL" , 
            "Firefly" , 
            "NationalDirectory" , 
            "Ask Jeeves" , 
            "TECNOSEEK" , 
            "InfoSeek" , 
            "WebFindBot" , 
            "girafabot" , 
            "crawler" , 
            "www.galaxy.com" , 
            "Googlebot" , 
            "Scooter" , 
            "Slurp" , 
            "msnbot" , 
            "appie" , 
            "FAST" , 
            'Slurp' , 
            'CazoodleBot' , 
            'msnbot' , 
            'InfoPath' , 
            'Baiduspider' , 
            "WebBug" , 
            "Spade" , 
            "ZyBorg" , 
            "rabaz" , 
            "Baiduspider" , 
            "Feedfetcher-Google" , 
            "TechnoratiSnoop" , 
            "Rankivabot" , 
            "Mediapartners-Google" , 
            "Sogou web spider" , 
            "WebAlta Crawler" 
        );
        
        if ( preg_match( "/" . join( '|', $bot_list ) . "/", $_SERVER['HTTP_USER_AGENT'] ) )
        {
            return true;
        }
        return false;
    }

    /**
     * Returns Region information for the current user/ip...
     *
     * @return array Returns an array with keys
     */
    static function getRegionData( $address = null )
    {
        eZDebug::writeDebug( 'Starting...', 'ezxRegion::getRegionData()' );
        $regionini = eZINI::instance( 'region.ini' );
        $regions = $regionini->groups();
        unset( $regions['Settings'] );
        $ccode = ezxISO3166::preferredCountry( $address );
        $lcode = ezxISO936::preferredLanguages();
        $regions_keys = array_keys( $regions );
        $preferred_regions = array();
        
        foreach ( $regions as $key => $region )
        {
            if ( $ccode and strpos( $key, '_' . $ccode ) !== false )
                $preferred_regions[$key] = $region;
        }
        
        $langs = array_keys( $lcode );
        $preferred_languages = array();
        foreach ( $regions as $key => $region )
        {
            foreach ( $langs as $lang )
            {
                if ( strpos( $key, $lang . '_' ) !== false )
                {
                    $preferred_languages[$key] = $region;
                    break;
                }
            }
        }
        $preferred_region = false;
        foreach ( $langs as $lang )
        {
            if ( in_array( $lang . '_' . $ccode, $regions_keys ) )
            {
                $preferred_region = $lang . '_' . $ccode;
                break;
            }
        }
        if ( ! $preferred_region )
        {
            $keys = array_keys( $preferred_regions );
            $preferred_region = $keys[0];
        }
        if ( ! $preferred_region )
        {
            $keys = array_keys( $preferred_languages );
            $preferred_region = $keys[0];
        }
        if ( ! $preferred_region )
        {
            eZDebug::writeError( 'No proper region has been found', 'ezxRegion::getRegionData()' );
            return false;
        }
        return array( 
            'preferred_region' => $preferred_region , 
            'preferred_languages' => $preferred_languages , 
            'preferred_regions' => $preferred_regions 
        );
    }
}
