<?php
/** 
 *
 * @author>
 * @version 1.0
 * @since 1.0
 * @access public
 */


	// c o n f i g  &  i n i t
	require_once __DIR__."/../config/config.php";
	require_once __DIR__."/../_lib/sess.php";
	// Register the Composer autoloader...
	require __DIR__."/../vendor/autoload.php";

	// c l a s s   l i b r a r y
	require_once __DIR__."/../_lib/class/backend.php";
	require_once __DIR__."/../_lib/class/mysqlPDO.php";
	require_once __DIR__."/../_lib/class/workspace.php";
	require_once __DIR__."/../_lib/class/expeditieDto.php";
	require_once __DIR__."/../_lib/class/tarif.php";
	require_once __DIR__."/../_lib/class/tarifDet.php";
	require_once __DIR__."/../_lib/class/tarifG.php";
	// s t a r t   W o r k s p a c e M o d u l e
	$workspaceModule = new Workspace($config);
?>