<?php
include_once( 'kernel/shop/classes/ezshopfunctions.php' );
include_once( 'kernel/common/template.php' );
include_once( 'kernel/classes/ezredirectmanager.php' );
include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
include_once( eZExtension::baseDirectory() . '/' . nameFromPath( __FILE__ ) . '/classes/ezxISO3166.php' );
include_once( eZExtension::baseDirectory() . '/' . nameFromPath( __FILE__ ) . '/classes/ezxISO939.php' );
$module =& $Params['Module'];
$http =& eZHTTPTool::instance();

$tpl = templateInit();

$regionini = eZINI::instance( 'region.ini' );
$contentini  = eZINI::instance( "content.ini");
$regions = $regionini->groups();
$settings = $regions['Settings'];
unset( $regions['Settings'] );

$Result['content'] = '';

$LayoutStyle = 'index';
$layoutINI = eZINI::instance( 'layout.ini' );

if ( $Params['siteaccess'] == 'select' )
{
    $selection = false;
}
elseif ( $Params['siteaccess'] ) 
{
    $selection = $Params['siteaccess'];
}
elseif ( $http->hasPostVariable( 'region' ) )
{
    if ( $settings['UseCookie'] == 'enabled' )
    {
        setcookie("EZREGION", $http->postVariable( 'region' ), time()+3600*24*365 , '/' );
    }
    $selection = $http->postVariable( 'region' );
}
elseif ( array_key_exists( 'EZREGION', $_COOKIE ) )
{
    $selection = $_COOKIE['EZREGION'];
}
else
    $selection = false;

if ( $http->hasGetVariable( 'URL' ) and $http->getVariable( 'URL' ) )
{
    $url = $http->getVariable( 'URL' );
}
elseif ( $http->hasPostVariable( 'URL' ) and $http->postVariable( 'URL' ) )
{
    $url = $http->postVariable( 'URL' );
}
else
    $url = false;

$found = false;

include_once( 'kernel/classes/ezsiteaccess.php');
$oldaccess = $GLOBALS['eZCurrentAccess'];
$accesslist = eZSiteAccess::siteAccessList();
if ( !$selection )
{
    foreach( $accesslist as $access )
    {
    if (  $access['name'] and $access['name'] == $url and !$selection )
    {
        $url = false;
        $access = changeAccess( $access );
        $GLOBALS['eZCurrentAccess'] =& $access;
        $found = true;
        $selection = $access['name'];
            break;
        }
    }
}

if ( $selection )
{
    if ( $regionini->hasVariable( $selection, "Country" ) )
        $country = $regionini->variable( $selection, "Country" );
    if ( $regionini->hasVariable( $selection, "Currency" ) )
        $preferredCurrency = $regionini->variable( $selection, "Currency" );
    if ( $country )
        eZShopFunctions::setPreferredUserCountry( $country );

    if ( $preferredCurrency )
        eZShopFunctions::setPreferredCurrencyCode( $preferredCurrency );

    if ( $_GET["URI"] )
    {
        return $module->redirectTo( $_GET["URI"], false );
    }
    else
    {
        if ( !$found )
        {
            foreach( $accesslist as $access )
            {
                if ( $access['name'] == $regions[$selection]['Siteaccess'] )
                {
                    $access = changeAccess( $access );
                    $GLOBALS['eZCurrentAccess'] =& $access;
                    $found = true;
                    break;
                }
            }
        }

        if ( ( $found and $oldaccess['name'] != $access['name'] ) or ( $found and !$url ) )
        {
            if ( $found )
                $accesspath= '/' . $access['name'];
            if ( $access and !$url )
                return eZHTTPTool::redirect( $accesspath . "/content/view/full/" . $contentini->variable( 'NodeSettings', 'RootNode') );
            else
            {
                if ( strpos( $url, '/' ) === 0 )
                    return eZHTTPTool::redirect( $accesspath . $url );
                else
                    return eZHTTPTool::redirect( $accesspath . '/' . $url );
            }
        }
        else
        {
            if( !$url )
                $url='/';
            $Result['rerun_uri'] = $url;
            return $module->setExitStatus( EZ_MODULE_STATUS_RERUN );
        }
    }
}

if ( $layoutINI->hasVariable( $LayoutStyle, 'PageLayout' ) )
    $Result['pagelayout'] = $layoutINI->variable( $LayoutStyle, 'PageLayout' );
else
    $Result['pagelayout'] = 'pagelayout.tpl';

$ini = eZINI::instance();
$regionini = eZINI::instance( 'region.ini' );
$regions = $regionini->groups();

$ccode = ezxISO3166::preferredCountry();

$lcode = ezxISO936::preferredLanguages();

$regions_keys = array_keys( $regions );

$preferred_regions = array();
foreach ( $regions as $key => $region )
{
    if ( $ccode and strpos( $key, '_' . $ccode) !== false )
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

if ( !$preferred_region )
{
    $keys = array_keys( $preferred_regions );
    $preferred_region = $keys[0];
}

if ( !$preferred_region )
{
    $keys = array_keys( $preferred_languages );
    $preferred_region = $keys[0];
}

if ( !$preferred_region )
{
    eZDebug::writeError( 'No proper region has been found', 'Extension Region' );
}



if ( strpos( $url, 'region/index' ) !== false )
{
    $tpl->setVariable( 'nocookie', 1 );
}
else
    $tpl->setVariable( 'nocookie', 0 );
$tpl->setVariable('URL', $url );

$tpl->setVariable('preferred_region', $preferred_region );
$tpl->setVariable('preferred_languages', $preferred_languages );
$tpl->setVariable('preferred_regions', $preferred_regions );
$tpl->setVariable('regions', $regions );
$Result['content'] = $tpl->fetch( "design:region/index.tpl" );
$node = eZContentObjectTreeNode::fetch( $contentini->variable( 'NodeSettings', 'RootNode') );
if ( $node )
    $Result['path'] = array( array( 'url' => false,
                        'text' => $node->attribute( 'name' ) . ' - Region Selector' ) );

?>