<?php
include_once( 'kernel/shop/classes/ezshopfunctions.php' );
include_once( 'kernel/common/template.php' );
include_once( 'kernel/classes/ezredirectmanager.php' );
include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
include_once( eZExtension::baseDirectory() . '/' . nameFromPath( __FILE__ ) . '/classes/ezxISO3166.php' );
include_once( eZExtension::baseDirectory() . '/' . nameFromPath( __FILE__ ) . '/classes/ezxISO939.php' );
include_once( eZExtension::baseDirectory() . '/' . nameFromPath( __FILE__ ) . '/classes/ezxregion.php' );
$module =& $Params['Module'];
$http = eZHTTPTool::instance();

$tpl = templateInit();

$regionini = eZINI::instance( 'region.ini' );
$contentini = eZINI::instance( "content.ini");
$regions = $regionini->groups();
$settings = $regions['Settings'];
unset( $regions['Settings'] );

$Result['content'] = '';

$LayoutStyle = 'index';
$layoutINI = eZINI::instance( 'layout.ini' );
$regiondata = ezxRegion::getRegionData();

$redirect = true;
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
elseif ( array_key_exists( 'preferred_region', $regiondata ) )
{
    $selection = $regiondata['preferred_region'];
    $redirect = false;
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

if ( $redirect === false and $settings['AutomaticRedirect'] == 'enabled' )
{
    $redirect = true;
}

$found = false;

include_once( 'kernel/classes/ezsiteaccess.php');
$oldaccess = $GLOBALS['eZCurrentAccess'];
$accesslist = eZSiteAccess::siteAccessList();
if ( $selection )
{
    foreach( $accesslist as $access )
    {
        if (  $access['name'] and $access['name'] == $selection )
        {
            $accessNew = $GLOBALS['eZCurrentAccess'];
            $accessNew['name'] = $access['name'];

            if ( $accessNew['type'] == EZ_ACCESS_TYPE_URI )
            {
                eZSys::clearAccessPath();
            }
            changeAccess( $accessNew );

            // Load the siteaccess extensions
            eZExtension::activateExtensions( 'access' );

            // Change content object default language
            unset( $GLOBALS['eZContentObjectDefaultLanguage'] );
            eZContentObject::clearCache();

            eZContentLanguage::expireCache();
            break;
        }
    }
}
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
eZDebug::writeDebug( $selection ,'Selection');
if ( $selection and $redirect )
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
            return $module->setExitStatus( eZModule::STATUS_RERUN );
        }
    }
}

if ( $layoutINI->hasVariable( $LayoutStyle, 'PageLayout' ) )
    $Result['pagelayout'] = $layoutINI->variable( $LayoutStyle, 'PageLayout' );
else
    $Result['pagelayout'] = 'pagelayout.tpl';

if ( strpos( $url, 'region/index' ) !== false )
{
    $tpl->setVariable( 'nocookie', 1 );
}
else
    $tpl->setVariable( 'nocookie', 0 );
$tpl->setVariable('URL', $url );

$tpl->setVariable('preferred_region', $regiondata['preferred_region'] );
$tpl->setVariable('preferred_languages', $regiondata['preferred_languages'] );
$tpl->setVariable('preferred_regions', $regiondata['preferred_regions'] );
$tpl->setVariable('regions', $regions );
$Result['content'] = $tpl->fetch( "design:region/index.tpl" );
$node = eZContentObjectTreeNode::fetch( $contentini->variable( 'NodeSettings', 'RootNode') );
if ( $node )
    $Result['path'] = array( array( 'url' => false,
                        'text' => $node->attribute( 'name' ) . ' - Region Selector' ) );

?>
