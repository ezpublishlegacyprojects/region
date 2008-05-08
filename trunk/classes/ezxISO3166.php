<?php
class ezxISO3166
{
    var $address;
    function ezxISO3166( $address = null )
    {
        if ( !$address )
    	   $this->address = $_SERVER['REMOTE_ADDR'];
    	else
    	   $this->address = $address;
    }
    function getALLfromIP()
    {
        // this sprintf() wrapper is needed, because the PHP long is signed by default
        $ipnum = sprintf("%u", ip2long( $this->address ));
        $query = "SELECT cc, cn FROM ezx_i2c_ip NATURAL JOIN ezx_i2c_cc WHERE ${ipnum} BETWEEN start AND end";
        $db =& eZDB::instance();
        $result = $db->arrayQuery( $query );
        if ( isset( $result[0] )  )
            return $result[0];
    }
    function getCCfromIP()
    {
        $data = $this->getALLfromIP();
        if( isset( $data['cc'] ) ) 
            return $data['cc'];
        else
            return false;
    }
    function getCOUNTRYfromIP()
    {
        $data = $this->getALLfromIP();
        if( isset( $data['cn'] ) ) 
        {
            return $data['cn'];
        }
        else
            return false;
    }
    function getCCfromNAME( $name )
    {
        $ip2country = new ip2country( gethostbyname( $name ) );
        return $ip2country->getCCfromIP();
    }
    function getCOUNTRYfromNAME( $name )
    {
        $ip2country = new ip2country( gethostbyname( $name ) );
        return $ip2country->getCOUNTRYfromIP();

    }
    function getCountryCodeFromAccess( $accessname )
    {
    	$list = split( '[_-]',  $accessname, 2 );
    	return $list[0];
    }
    static function getPrimaryLocales( $Code = null, $exceptCurrent = true )
    {
        $regionini = eZINI::instance( 'region.ini' );
        $list = split( '[_-]',  $Code, 2 );
        $regionini = eZINI::instance( 'region.ini' );
        $regions = $regionini->groups();
        $locales = array();
        foreach ( $regions as $key => $region )
        {
            $list2 = split( '[_-]',  $key, 2 );
            if ( !isset( $locales[$list2[1]] )  )
            {
                /* TODO $exceptCurrent
                if ( $exceptCurrent and ( $Code != $region['Siteaccess'] ) )
                {

                }
                elseif( $exceptCurrent === false )
                {

                }
                */
                $region['code'] = $list2[0] . '-' . $list2[1];
                if ( $region['code'] != '*-*' )
                {
                    $region['possible_languagecodes'] = array();
                    array_push( $region['possible_languagecodes'], $list2[0] . '-' . $list2[1] );
                    array_push( $region['possible_languagecodes'], $list2[0] );
                }
                else
                {
                    $region['possible_languagecodes'] = array();
                    array_push( $region['possible_languagecodes'], $region['Siteaccess'] );

                    $extralang = $regionini->variable( '*_*', 'AdditionalLanguageList' );
                    foreach ( $extralang  as $lang )
                    {
                        array_push( $region['possible_languagecodes'], $lang );
                    }
                }    
                $locales[$list2[1]] = $region;
            }
        }
        return $locales;
    }
    static function getLanguagesFromLocalCode( $Code, $exceptCurrent = true )
    {
        $list = split( '[_-]',  $Code, 2 );
        $regionini = eZINI::instance( 'region.ini' );
        $regions = $regionini->groups();
        $languages = array();
        foreach ( $regions as $key => $region )
        {
            $list2 = split( '[_-]', $key, 2 );
            if ( $list[1] == $list2[1]  )
            {
                if ( $exceptCurrent and ( $Code != $region['Siteaccess'] ) )
                {
                    $languages[$region['Siteaccess']] = $region;
                }
                elseif( $exceptCurrent === false )
                {
                    $languages[$region['Siteaccess']] = $region;
                }
            }
                
        }
        return $languages;
    }
    function countries()
    {
    	$regionini = eZINI::instance( 'region.ini' );
        $regions = $regionini->groups();

        $counties = array();
        foreach ( $regions as $key => $region )
        {
            $list = split( '[_-]',  $key, 2 );
            if ( isset( $list[1] ) )
                $counties[$list[1]] = $list[1];
        }
        return $counties;
    }
    function preferredCountry()
    {
    	$ip = new ezxISO3166();
        $code = $ip->getCCfromIP();
        $countries = ezxISO3166::countries();
        if ( in_array( $code, $countries ) )
            return $code;
        else if( $code )
            return true;
        else
            return false;
    }
}
?>
