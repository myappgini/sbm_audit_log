<?php

/**
 * @file
 * @version 1.81
 * @author	Olaf Nöhring (https://datenbank-projekt.de), primitive_man (https://forums.appgini.com/phpbb/viewtopic.php?f=4&t=1369), landinialejandro (https://forums.appgini.com/phpbb/memberlist.php?mode=viewprofile&u=8848)
 * @see 	Thread AuditLog in AppGini Forum: https://forums.appgini.com/phpbb/viewtopic.php?f=4&t=1369
 * 
 * This file contains functions for logging changed fields into the auditor table.
 *
*/

// Define the name of your auditor table once for this file:
define("AUDITTABLENAME", 'auditor');


#########################################################
############### AUDIT LOG FUNCTIONS #####################
#########################################################
if (session_status() == PHP_SESSION_NONE) { session_start(); }


/**
 * 
 * Applies AppGini's makeSafe function to all entries of $data, 
 * where $data can be an array, OR a string variable
 * src: https://forums.appgini.com/phpbb/viewtopic.php?f=2&t=4203&p=16537#p16535
 *
 * @param  $data	variable, can be either string OR array
 * @param  bool $is_gpc
 * @return cleaned variable
 *
 */
function Audit_makeSafe_custom($data, $is_gpc = true) {
    return is_array($data) ? array_map(function ($entry) use ($is_gpc) {
        return makeSafe($entry, $is_gpc);
    }, $data) : makeSafe($data, $is_gpc);
}

/**
	* Manual add to Auditor-Table (and/or save old value to session variable)
 * 
 * Example usage:
 * This is useful, in case you have table TA and table TB. TB is a child of TA (holds the foreign key of TA). 
 * The user does something in table TB. Following this change, you do some action to the (parent) record in TA.
 * If you want to add this action (done to a record in TA) also to the audit log, you use this function.
 * 
 * Example setting - you need to place the code in the correct functions! (maybe before_insert, after_insert, before_delete, after_delete):
 * TA has primary key field pkA. User is working in TB with parent record pkA=5 ($valueOfpkA)
 * Now the field TA.LogEdit ($fieldName) should be changed, once something in table TB happens
 * 
 * call hooks/TB _before_update function
 * 		Audit_Manually('SAVEOLD','TA', 'pkA', $valueOfpkA, $fieldName, 0, 0);
 * This will save the current value of TA.LogEdit into a session variable.
 * Then in hooks/TB _before_update function do your stuff to record 5 in TA (example: set the LogEdit to the current time $myNewTimestamp)
 *
 * call hooks/TB _after_update function
 * Then call the function again to make the entry for the changed record
 * 		Audit_Manually('UPDATE','TA', 'pkA', $valueOfpkA, $fieldName, $myNewTimestamp, NULL);
 * 
 * 
 * 
 * @access  public
 * @param   string  $actionType     SAVEOLD to save an old value from the database in a sesssion variable, UPDATE or INSERTION
 * @param   string  $TableName      name of the table where the field change happens and for which it should be documented
 * @param   string	$pkFieldName   	primary key fieldname
 * @param   string  $pkFieldValue	primary key field value of the record that is being changed and recorded* 
 * @param   string  $fieldName  	name of the field that changes in $TableName for which the change should be documented
 * @param   string  $NewValue      	new value of the field $fieldName
 * @param   string  $OldValue      	old value of the field $fieldName. If $actionType is UPDATE and you want to use the valued that has been recorded with SAVEOLD before, you need to set this $OldValue = NULL
 */ 
function Audit_Manually($actionType = 'unknown', $TableName = '', $pkFieldName = '', $pkFieldValue = 0, $fieldName = '', $NewValue = 0, $OldValue = 0){
	//DEBUG
	// write_log("actionType: " . $actionType . "\n TableName: " . $TableName . "\n pkFieldName: " . $pkFieldName . "\n pkFieldValue: " . $pkFieldValue . "\n fieldName: " . $fieldName . " \n NewValue: " . $NewValue ." \n OldValue: ", $OldValue);

	// make sure data can not damage our database
	$actionType = Audit_makeSafe_custom($actionType);
	$TableName = Audit_makeSafe_custom($TableName);
	$pkFieldName = Audit_makeSafe_custom($pkFieldName);
	$pkFieldValue = Audit_makeSafe_custom($pkFieldValue);
	$fieldName = Audit_makeSafe_custom($fieldName);
	$NewValue = Audit_makeSafe_custom($NewValue);
	$OldValue = Audit_makeSafe_custom($OldValue);


	if ($actionType == 'SAVEOLD'){
		$sql = "SELECT `$TableName`.`$fieldName` FROM `$TableName` WHERE `$pkFieldName` = '$pkFieldValue';";
		$result_old_Value = sqlValueC($sql);
		$_SESSION['Audit_Manually'][$TableName][$fieldName][$pkFieldName][$pkFieldValue] = $result_old_Value;
		// write_log("Audit_Manually saveole Value: " .  $result_old_Value);		
	} else {

		if ($actionType == 'INSERTION') {
			$OldValue = '-INSERTED-';
			$doAction = 1;
		}

		if ($actionType == 'UPDATE' || $actionType == 'DELETION'){
			// if the old value was saved before in a session variable and $OldValue = NULL, we use that session variable, otherwise use $OldValue
			if ($_SESSION['Audit_Manually'][$TableName][$fieldName][$pkFieldName][$pkFieldValue] && $OldValue == NULL) {
				// write_log("Audit_Manually using saved: " . $_SESSION['Audit_Manually'][$TableName][$fieldName][$pkFieldName][$pkFieldValue]);
				$OldValue = $_SESSION['Audit_Manually'][$TableName][$fieldName][$pkFieldName][$pkFieldValue];
				unset($_SESSION['Audit_Manually'][$TableName][$fieldName][$pkFieldName][$pkFieldValue]);
			}	

			if ($OldValue != $NewValue) {
				$doAction = 1;
			} else {
				$doAction = 0;
			}			

			// write_log("Audit_Manually 1. doAction: " . $doAction);
		}

		if ($actionType == 'DELETION') {
			$NewValue = '-DELETED-';		
			$doAction = 1;
		}		

		// write_log("Audit_Manually 2. doAction: " . $doAction);
		if ($doAction == 1){
			$memberInfo = getMemberInfo();
			$timeStamp = date('Y-m-d H:i:s');
			$sqlInsert = "INSERT INTO " . AUDITTABLENAME . " (res_id, username, ipaddr, time_stmp, change_type, table_name, fieldName, OldValue, NewValue) VALUES ('" . $pkFieldValue . "', '" . $memberInfo['username'] . "', '" . $memberInfo['IP'] . "','" . $timeStamp. "','" .  $actionType. "','" .  $TableName. "','" .  $fieldName. "','" .  makeSafe($OldValue) . "','" . makeSafe($NewValue) ."')";	// clean OldValue and NewValue again, as they might have changed
		 	$result = sqlValueC($sqlInsert); 
			// write_log("Audit_Manually (result: $result): " . $sqlInsert);
			set_record_owner(AUDITTABLENAME, $pkFieldValue, $memberInfo['username']); // Set/update the owner of given record		
		}

	}
}	

/**
 * Read data from the table 
 * called before_update and again after_update for comparison
 * 
 * @param   string    $action      	  either 'before' OR 'after' -> depending on when you want to get the data. Comparison is made between [before] and [after]
 * @param   string    $TableName      tablename
 * @param   string    $currentID      primary key value of the current record
 * @param   string    $tableID        primary key field on $TableName
 * @return  array  	  associative array with one row as result
 * 
 */
function Audit_getData($action, $TableName, $currentID, $tableID){
	//data comes in clean!

	// as suggested by pbötcher https://forums.appgini.com/phpbb/viewtopic.php?f=4&t=1369&p=10395#p10392
	$fields = get_sql_fields($TableName);
	$from = get_sql_from($TableName);
	$fet = sql("SELECT $fields FROM $from and $tableID = $currentID", $eo);		
	// write_log("SELECT $fields FROM $from and $tableID = $currentID");			
	$result = db_fetch_assoc($fet);
	foreach($result as $colname => $value){ 
		// write_log("1 $colname = $value"); 
		$_SESSION['Audit_data'][$action][$TableName][$currentID][$colname] = $value;
	}

	return $_SESSION['Audit_data'][$action][$TableName][$currentID];
}

/**
 * get table data BEFORE any changes
 * 
 * @param   string    $session      $_SESSION with tablenam and tableID
 * @param   string    $currentID      primary key value of the current record
 * 
 */
function table_before_change($session, $currentID){		
	
	// make sure variables can not damage our database
	$session = Audit_makeSafe_custom($session);
	$currentID = Audit_makeSafe_custom($currentID);

	['tablenam' => $TableName, 'tableID' => $tableID] = $session;
	
	// write_log("before \n Tablename: $TableName,\n ID: $currentID,\n primarykeyfield: $tableID");
	// pull data / write data to session var, $result holds array with current data - for which no further action is needed right now.
	// write_log("3 do before"); 
	$result = Audit_getData('before', $TableName, $currentID, $tableID);
	// write_log("3 do before - FINISHED"); 
	return;
}


/**
 * get table data AFTER any changes, compare with 'before'-values and add to Audit log if something has changed
 * 
 * @param   string    $session      $_SESSION with tablenam and tableID
 * @param   string    $memberInfo   array with username and userIP
 * @param   string    $data      	array with currentID
 * @param   string    $type         type of change. either 'INSERTION', 'DELETION', 'UPDATE'
 * 
 */
function table_after_change ($session, $memberInfo, $data, $type) {

	// make sure variables can not damage our database
	$session = Audit_makeSafe_custom($session);
	$memberInfo = Audit_makeSafe_custom($memberInfo);
	$data = Audit_makeSafe_custom($data);
	$type = Audit_makeSafe_custom($type);
	
	['tablenam' => $TableName, 'tableID' => $tableID] = $session;
	['username' => $username, 'IP' => $userIP] = $memberInfo;
	if (is_array($data)){
		['selectedID' => $currentID] = $data;	
	} else {
		$currentID = $data;
	}

	// write_log("after \n Tablename: $TableName,\n ID: $currentID,\n primarykeyfield: $tableID");

	//if INSERT, then get all initial fieldvalues set by the user and use them as 'old'
	if ($type == 'INSERTION') {
		table_before_change($session, $currentID);
	}

	if ($type != 'DELETION') {
		// pull data / write data to session var, $result holds array with current data. BEFORE and AFTER will be compared after pull is done
		// write_log("3 do after"); 
		$result = Audit_getData('after', $TableName, $currentID, $tableID);
		// write_log("3 do after - FINISHED"); 
	}


	####### COMPARE BEFORE AND AFTER VALUES AND ADD CHANGED DATA ENTRIES TO AUDIT LOG #######
	foreach ($_SESSION['Audit_data']['before'][$TableName][$currentID] as $colname => $value){
		// write_log("--- --- ---"); 
		// write_log("2 $colname = $value"); 
		// write_log("before value: " . $_SESSION['Audit_data']['before'][$TableName][$currentID][$colname]);
		// write_log(" after value: " . $_SESSION['Audit_data']['after'][$TableName][$currentID][$colname]);

		$oldValue = $_SESSION['Audit_data']['before'][$TableName][$currentID][$colname];
		$newValue = $_SESSION['Audit_data']['after'][$TableName][$currentID][$colname];

		if ($type == 'INSERTION') {
			$oldValue = '-INSERTED-';
		}
		if ($type == 'DELETION') {
			$newValue = '-DELETED-';
		}

		$makeEntry = 1;
		if (($oldValue == $newValue)  || (($oldValue == '-INSERTED-') && ($newValue == '')) || (($newValue == '-DELETED-') && ($oldValue == ''))) 
		{ // Do not add entries where a field is empty on INSERT or DELETE, or if field has been empty before and still is empty after update
			$makeEntry = 0;
		}

		if ($makeEntry == 1)
		{ // Adds the INITIAL Values and CHANGED values to the auditlog database table

			switch (true) {
					// START Examples of how to exclude specific fields from being added to the audit Log
				case ($colname == "editDate"):
					break;

				case ($colname == "editedBy"):
					break;

				case ($colname == "id_cnote"):
					break;

				case ($colname == "Note"):
					break;

				case ($colname == "fk_res"):
					break;
					// END of exclusion examples

				default:
					sql("INSERT INTO " . AUDITTABLENAME . " (res_id, username, ipaddr, time_stmp, change_type, table_name, fieldName, OldValue, NewValue) VALUES ('$currentID', '$username','$userIP',NOW(),'$type','$TableName','$colname','" . makeSafe($oldValue) . "','" . makeSafe($newValue) . "')", $eo);	// clean OldValue and NewValue again, as they might have changed
					set_record_owner(AUDITTABLENAME, $currentID, $username); // Set/update the owner of given record
					break;
			}
		}
	}

	unset($_SESSION['Audit_data']['before'][$TableName][$currentID]);
	unset($_SESSION['Audit_data']['after'][$TableName][$currentID]);

	return;
}
?>