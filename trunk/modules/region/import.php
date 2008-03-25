<?php
include_once( 'kernel/shop/classes/ezshopfunctions.php' );
include_once( 'kernel/common/template.php' );
include_once( eZExtension::baseDirectory() . '/' . nameFromPath( __FILE__ ) . '/modules/region/ip2country.php' );

$module =& $Params['Module'];
$http =& eZHTTPTool::instance();

        $tpl =& templateInit();

        $db =& eZDB::instance();
        $db->begin();

/** STEP 1
        $db->query( "DROP TABLE IF EXISTS ip2country" );

        $db->query( "CREATE TABLE ip2country (
  start_ip CHAR(15) NOT NULL,
  end_ip CHAR(15) NOT NULL,
  start INT UNSIGNED NOT NULL,
  end INT UNSIGNED NOT NULL,
  cc CHAR(2) NOT NULL,
  cn VARCHAR(50) NOT NULL
)" );

        $db->query( "CREATE TABLE ezx_i2c_cc (
  ci TINYINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  cc CHAR(2) NOT NULL,
  cn VARCHAR(50) NOT NULL
)" );
        $db->query( "CREATE TABLE ezx_i2c_ip (
  start INT UNSIGNED NOT NULL,
  end INT UNSIGNED NOT NULL,
  ci TINYINT UNSIGNED NOT NULL
);" );
*/

/** STEP 2

"C:\Program Files (x86)\MySQL\MySQL Server 5.0\bin\mysqlimport.exe"  --fields-terminated-by=, --fields-optionally-enclosed-by=\" --lines-terminated-by=\n --host=localhost db_mobotix38 "C:\workspace\mobotix\extension\mobotix\modules\region\ip2country.csv"

 */

/** STEP 3 
$db->query( "TRUNCATE ezx_i2c_cc" );
$db->query( "INSERT INTO ezx_i2c_cc SELECT DISTINCT NULL,cc,cn FROM ip2country;" );
$db->query( "TRUNCATE ezx_i2c_ip" );
$db->query( "INSERT INTO ezx_i2c_ip SELECT start,end,ci FROM ip2country NATURAL JOIN ezx_i2c_cc;" );
*/
/** STEP 4 
$db->query( "DROP TABLE IF EXISTS ip2country" );

*/
        $db->commit();

$Result = array();
#$Result['content'] = $tpl->fetch( "design:region/import.tpl" );

$ip2c = new ip2country();
$Result['content'] = $ip2c->getCOUNTRYfromIP();
$Result['path'] = array( array( 'url' => false,
                        'text' => 'IP to country import' ) );

?>