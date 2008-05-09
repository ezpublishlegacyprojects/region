<?
class ezxRegion
{

    /**
     * Returns Region information for the current user/ip...
     *
     * @return array Returns an array with keys
     */
    static function getRegionData()
    {
        $regionini = eZINI::instance( 'region.ini' );
        $regions = $regionini->groups();
        unset( $regions['Settings'] );
        $ccode = ezxISO3166::preferredCountry();
        $lcode = ezxISO936::preferredLanguages();
        $regions_keys = array_keys( $regions );
        $preferred_regions = array( 
        );
        
        foreach ( $regions as $key => $region )
        {
            if ( $ccode and strpos( $key, '_' . $ccode ) !== false )
                $preferred_regions[$key] = $region;
        }

        $langs = array_keys( $lcode );
        $preferred_languages = array( 
        );
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
            'preferred_region' => $preferred_region,
            'preferred_languages' => $preferred_languages, 
            'preferred_regions' => $preferred_regions 
        );
    }
}
