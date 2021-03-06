<?php
/**
 * @file
 * @version 1.93, 2021-03-05
 * @author	Olaf Nöhring (https://datenbank-projekt.de), primitive_man (https://forums.appgini.com/phpbb/viewtopic.php?f=4&t=1369), landinialejandro (https://forums.appgini.com/phpbb/memberlist.php?mode=viewprofile&u=8848)
 * @see 	Thread AuditLog in AppGini Forum: https://forums.appgini.com/phpbb/viewtopic.php?f=4&t=1369
 * 
 * This file is part of the AuditLog extension for AppGini
 *
 * This file created the timeline in the admin area
 *
*/
	
	$currDir=dirname(__FILE__);
	require("$currDir/incCommon.php");
	include("$currDir/incHeader.php");
	
	include("$currDir/../hooks/audit/auditLog_timeline.php");


	include("$currDir/incFooter.php");
?>