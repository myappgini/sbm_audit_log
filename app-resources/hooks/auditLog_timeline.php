<?php 

/**
 * @file
 * @version 1.81
 * @author	Olaf Nöhring (https://datenbank-projekt.de)
 * @see 	Thread AuditLog in AppGini Forum: https://forums.appgini.com/phpbb/viewtopic.php?f=4&t=1369
 * 
 * This file contains creates a state of a record from the audit log
 *
 */


// Define the name of your auditor table once for this file:
define("AUDITTABLENAME", 'auditor');

$hooks_dir = dirname(__FILE__);
require("$hooks_dir/../../config.php");

$inc = get_included_files(); if(basename(__FILE__) == basename($inc[0])) exit();	// if file is not included, exit here. src: https://bigprof.com/blog/php/making-sure-a-php-script-is-accessible-only-through-being-included/
	
if (session_status() == PHP_SESSION_NONE) { session_start(); }


/**
* Create table from Array (SQL result)
* 
*/		
function auditor_tableme($result){
	//src: https://stackoverflow.com/questions/12413064/select-and-display-all-fields-from-mysql-table-for-indefinite-amount-of-columns#12413219 id
	$counter = 0;
	$header='';
	$rows='';
    $row_counter = 0;
    
	while ($row = db_fetch_assoc($result)) { 

		//alternate CSS class for every other row
		$row_counter++;
		if (is_int($row_counter / 2)) {
			$extraClass = 'auditor_evenRow';
		} else {
			$extraClass = 'auditor_oddRow';
		}

		if($header==''){
			$header.='<thead><tr class ="auditor_headerRow">'; 
			$rows.='<tr class ="' . $extraClass . '">'; 
			foreach($row as $key => $value){ 
				$header.='<th>'.$key.'</th>'; 
				$rows.='<td>'.$value.'</td>'; 
			} 
			$header.='</tr></thead>'; 
			$rows.='</tr>'; 
		}else{
			$rows.='<tr class ="' . $extraClass . '">'; 
			foreach($row as $value){ 
				$rows .= "<td>".$value."</td>"; 
			} 
			$rows.='</tr>'; 
		}
	} 
	return '<table class="auditor_timeline">'.$header.$rows.'</table>';
}

/**
 * Read all column names from selected table
 */
function auditor_table_fields($state_table){

    $colnames = array();
    $query_fields = sql("SHOW COLUMNS FROM `$state_table`;", $eo);

    // echo "<br><h2>Fields in '" . $state_table ."'</h2>";
    while ($result_fields = db_fetch_assoc($query_fields)) {
        $colnames[] = $result_fields['Field'];
        // echo '<br>'. $result_fields['Field'];
    };

    return $colnames;
}

/** Function looks up all timestamps for records with selected pk from selected table
 * 
 */
function auditor_matching_records($state_table, $state_pk, $state_from, $state_to){
    $sql = "SELECT time_stmp FROM `" . AUDITTABLENAME . "` 
        WHERE 
        table_name = '" . $state_table . "' AND 
        res_id = '" . $state_pk . "' AND
        time_stmp >= '" . $state_from . "' AND
        time_stmp <= '" . $state_to . "'         
         GROUP BY time_stmp ORDER BY time_stmp ASC;";
    $time_stmp = array();
    $query_time_stmp = sql($sql, $eo);

    // echo "<br><h2>Timestamps for pk: $state_pk in '$state_table' between $state_from and $state_to (including)</h2>";
    while ($result_time_stmp = db_fetch_assoc($query_time_stmp)) {
        $time_stmp[] = $result_time_stmp['time_stmp'];
        // echo '<br>'. $result_time_stmp['time_stmp'];
    };

    return $time_stmp;
}

/**
 * gets 
 * a) the latest data for each column before the start date
 * b) the first data for each column after the end date ---------> not implemented (makes no sense to do so!)
 * 
 * @param string    $action    past||future. If 'past', last value before $time_stmp will be searched. If 'future', first value after $time_stmp will be searched. 
 * @param string $time_stmp    timestamp to be used as WHERE condition
 * @param string $state_table   table where the change occured
 * @param string $current_colname   columnname that should be searched
 * @param string $state_pk  primary key of the record
 * @return string $myReturnValue     result of SQL lookup (single value)
 * 
 */
function auditor_getExtraData($action, $time_stmp, $state_table, $current_colname, $state_pk){

    if ($action == 'past'){
        $ordr = ' DESC';
        $compa = ' < ';
    } else {
        $ordr = ' ASC';
        $compa = ' > ';
    }

    $sql = "SELECT NewValue FROM `" . AUDITTABLENAME . "`
        WHERE
            table_name = '" . $state_table . "' AND
            res_id = '" . $state_pk . "' AND
            time_stmp " . $compa . " '"  . $time_stmp . "' AND
            fieldName = '" . $current_colname . "'
        ORDER BY
            time_stmp " . $ordr . "
        LIMIT 1;";
        
    $myReturnValue = sqlValue($sql);
    if ($myReturnValue === FALSE){
        $myReturnValue = FALSE;
    } 
   
    return $myReturnValue;

}


/**
 * Gets all data for selected pk from selected table within timeframe
 */
function auditor_build_timeline($state_table, $state_pk, $state_from, $state_to, $state_html, $state_before, $state_after){
    $myReturnValue = '';
    // $myReturnValue .= "<br><h1>Timeline for pk: $state_pk in '$state_table' between $state_from and $state_to (including)</h1>";
    
    // read all entries from auditor table
    $time_stmp = array();
    $time_stmp = auditor_matching_records($state_table, $state_pk, $state_from, $state_to);    
    $time_stmp_first = reset ($time_stmp);
    $time_stmp_last = end ($time_stmp);
    
    // get table fields
    $colnames = array();
    $colnames = auditor_table_fields($state_table);
    $col_old = array();

    // add some more fields
    $col_timeline = array();
    $col_timeline[] = 'Username';
    $col_timeline[] = 'IP';    
    $col_timeline[] = 'Timestamp';
    $col_timeline[] = 'Changetype';

    $thead = '';
    $trow = '';
    // build table header
    $thead.='<thead><tr class ="auditor_headerRow">'; 
    $thead .= '<th class="auditor_timeline_auto_nr">[#]</th>';
    $thead .= '<th class="auditor_timeline_auto_ts">[Timestamp]</th>';
    foreach($colnames as $current_colname){
        $thead .= '<th>' . $current_colname . '</th>';

        if ($state_before == 1){
            $col_old[$current_colname] = auditor_getExtraData('past', $time_stmp_first, $state_table, $current_colname, $state_pk);
            if ($col_old[$current_colname] === FALSE) {
                $col_old[$current_colname] = NULL;
            }
        }
/* 
        if ($state_after == 1){
            $col_future[$current_colname] = auditor_getExtraData('future', $time_stmp_last, $state_table, $current_colname, $state_pk);
        }
         */
    }
    $thead .= '<th class="auditor_timeline_auto_usr">[Username]</th>';
    $thead .= '<th class="auditor_timeline_auto_ip">[IP]</th>';
    $thead .= '<th class="auditor_timeline_auto_typ">[Changetype]</th>';
    $thead.='</tr></thead>';

    $counter = 0;
    foreach ($time_stmp as $ts){
        $counter++;
        $sql = "SELECT * FROM `" . AUDITTABLENAME . "` WHERE table_name = '" . $state_table . "' AND res_id = '" . $state_pk . "' AND time_stmp = '" .  $ts . "';";
        $result = sql($sql, $eo);

        // handle all fields that were touched in this timestamp        
        $count2 = 0;
        while($row = db_fetch_assoc($result)){        
            $count2 ++;
            $col[$ts]['fieldName'][$count2] = $row['fieldName'];
            if ($state_html == 1){ 
                $col[$ts]['OldValue'][$count2] = strip_tags($row['OldValue']);
                $col[$ts]['NewValue'][$count2] = strip_tags($row['NewValue']);
            } else {
                $col[$ts]['OldValue'][$count2] = $row['OldValue'];
                $col[$ts]['NewValue'][$count2] = $row['NewValue'];      
            }

            // user specific actions of this timestamp
            $col[$ts]['username'] = $row['username'];
            $col[$ts]['ipaddr'] = $row['ipaddr'];
            $col[$ts]['time_stmp'] = $ts;
            $col[$ts]['change_type'] = $row['change_type'];
        }


        //alternate CSS class for every other row
        if (is_int($counter / 2)) {
            $extraClass = 'auditor_evenRow';
        } else {
            $extraClass = 'auditor_oddRow';
        }
        $trow .= '<tr class="' . $extraClass . '">';
        $trow .= '<td class="auditor_timeline_auto_nr">' . $counter . '</td>';
        $trow .= '<td class="auditor_timeline_auto_ts">'. $col[$ts]['time_stmp'] . '</td>';

       

        foreach($colnames as $current_colname){
            $trow_new = '';
            // foreach($col[$ts]['fieldName'] as $key_field => $val_field){
            for ($c2 = 1; $c2 <= $count2; $c2++) {    
                if ($col[$ts]['fieldName'][$c2] == $current_colname){                    
                    $trow_new = '<td class="auditor_timeline_newdata" title="New value is show. Old value of field was: &#13;&#10;' . $col[$ts]['OldValue'][$c2] . '">' . $col[$ts]['NewValue'][$c2] . '</td>';
                    $col_old[$current_colname] = $col[$ts]['NewValue'][$c2];
                }                 
            }

            if ($trow_new != '') {
                //  all ok, use current (new) value in table
            } else {
                if (is_null($col_old[$current_colname])) {
                    $trow_new = '<td class="auditor_timeline_nodata" title="No data available. Column may have been added after auditor logging began or value was entered before logging was enabled for this table and never changed.">&nbsp;</td>';
                } else {
                    $trow_new= '<td class="auditor_timeline_prevdata" title="Value of this field has not changed in this edit. Previous value is shown.">' . $col_old[$current_colname] . '</td>';
                }
            }

            $trow .= $trow_new;

        }

        $trow .= '<td class="auditor_timeline_auto_usr">'. $col[$ts]['username'] . '</td>';
        $trow .= '<td class="auditor_timeline_auto_ip">'. $col[$ts]['ipaddr'] . '</td>';
        $trow .= '<td class="auditor_timeline_auto_typ">'. $col[$ts]['change_type'] . '</td>';
        $trow .= '</tr>';

    }

    if ($counter > 0){
            $fulltable = '<div id="auditor_timle"><table class="auditor_timeline">' . $thead . $trow . '</table></div>';
    } else {
        $fulltable = 'No data available for timeline.';
    }

    $myReturnValue = $fulltable;

    return $myReturnValue;
}


// START Optionen
$state_table = isset($_GET['tabname']) ? makeSafe($_GET['tabname']) : '';
$state_pk = isset($_GET['pk']) ? makeSafe($_GET['pk']) : '';
$state_from = isset($_GET['state_from']) ? makeSafe($_GET['state_from']) : date("Y-m-d 00:00:00", strtotime("-0 days"));
$state_to = isset($_GET['state_to']) ? makeSafe($_GET['state_to']) : date("Y-m-d 00:00:00", strtotime("+1 days"));
$submitted = isset($_GET['submit']) ? makeSafe($_GET['submit']) : 0;
$state_html = isset($_GET['HTML']) ? 1 : 0;
$state_before = isset($_GET['before']) ? 1 : 0;
// $state_after = isset($_GET['after']) ? 1 : 0;
$selectionok = isset($_GET['selectionok']) ? 1 : 0;
// END Optionen
$hint_start = "Initial value ($state_from) is the first occurence of the record with primary key $state_pk in the chosen table '$state_table' in the audit-log.";
$hint_end = "Initial value ($state_to) is the last occurence of the record with the primary key $state_pk in the chosen table '$state_table' in the audit-log.";

$prep_step_max = 4;
?>
<style>

.auditor_timeline_auto_typ,
.auditor_timeline_auto_ip,
.auditor_timeline_auto_usr,
.auditor_timeline_auto_ts,
.auditor_timeline_auto_nr {
    color: #aaa;
}

.auditor_timeline_prevdata{
    color: #999;
}

.auditor_timeline_prevdata:hover {
    /* background-color: #999; */
    opacity: 0.8;
	filter : alpha(opacity=80);
}

.auditor_timeline_nodata {
    cursor: help;
/*
    background-image: url("/hooks/audit/striketrhough1.png");
	background-repeat: no-repeat;
	background-position: center center;
*/

}

.auditor_timeline_nodata {
  position: relative;
}
.auditor_timeline_nodata:before {
  position: absolute;
  content: "";
  left: 0;
  top: 50%;
  right: 0;
  border-top: 1px dashed;
  border-color: inherit;

  -webkit-transform:rotate(-5deg);
  -moz-transform:rotate(-5deg);
  -ms-transform:rotate(-5deg);
  -o-transform:rotate(-5deg);
  transform:rotate(-5deg);
}

.auditor_timeline_prevdata:hover {
    background-color: #fdffa4;
}

.auditor_timeline_newdata {
    cursor: help;
    background-color: #CCFFCC;
}

.auditor_timeline_newdata:hover {
    background-color: #66FF66;
}


/* repeat head on all pages */
thead {display: table-header-group;}

/* repeat foot on all pages */
tfoot {display: table-header-group;}

.auditor_timeline{
	margin-top: 1.5em;
    margin-bottom: 1.5em;
}

.auditor_timeline tr {
	
}

.auditor_timeline th {
	/* text-align: center;  */
	padding: 0.2em;
	font-weight: bold;
}

.auditor_timeline td {
	/* text-align: center; */
	padding: 0.2em;
}

.auditor_timeline tr.auditor_headerRow {
	background-color: #ffffff;
    border-bottom: 1px solid black;
}

.auditor_timeline tr.auditor_oddRow {
	background-color: #ffffff;
}

.auditor_timeline tr.auditor_evenRow {
	background-color: #eeeeee;
}

.auditor_timeline tr:hover{ 
	background-color: #fdffa4;
	/*
	opacity: 0.8;
	filter : alpha(opacity=80);
*/
}

</style>
<h1>Timeline</h1>
<?php
if (($state_table != '') && ($state_pk != '') && ($submitted == 2)) {    
    $divider = '_';

    $state_to_clr = date_format(date_create($state_to),"YmdHis");
    $state_form_clr = date_format(date_create($state_from),"YmdHis");

    $csvtitle = $state_table . $divider . $state_pk . $divider . $state_form_clr . $divider . $state_to_clr . '.csv';

    echo "<h2>PK: <strong>$state_pk</strong> from <strong>$state_table</strong> between <strong>$state_from</strong> and <strong>$state_to</strong></h2> ";    
    // echo '<a href="#" id ="export" role="button"><button id="csvbutton" name="csvbutton_x" type="button" value="1" class="btn btn-default" title="Export as: ' . $csvtitle . '><i class="glyphicon glyphicon-download-alt"></i></button></a>';
    // echo "</p>";
    echo '<p>Columns in square brackets [ and ] are not from the table itself, but extra information for the timeline, automatically generated.<br />
    Cells with <span class="auditor_timeline_newdata">this</span> style have changed at the given timestamp. 
    Cells with <span class="auditor_timeline_prevdata">this</span> style have show the value the field had at the changed of other fields at the given timestamp. 
    Cells with <span class="auditor_timeline_nodata">this</span> style demonstrate that no data is available for the field at the given timestamp.<br />
    Hover over values for more information.<br />
    </p>';
    // generate timeline
    echo auditor_build_timeline($state_table, $state_pk, $state_from, $state_to, $state_html, $state_before, $state_after);

} else {
    
    if ($state_table == '') { $prep_step = 1; }
    if (($state_table != '') && ($state_pk == '')) { $prep_step = 2; }
    if (($state_table != '') && ($state_pk != '') && ($submitted == 1)) { $prep_step = 3; }
    if (($state_table != '') && ($state_pk != '') && ($selectionok == 1) && ($submitted == 1)) { 
        $prep_step = 4; 
        $sql_min = "SELECT `time_stmp` FROM `" . AUDITTABLENAME . "` WHERE `res_id` = '" . $state_pk . "' AND `table_name` = '" . $state_table . "' ORDER BY `time_stmp` LIMIT 1";
        $sql_max = "SELECT `time_stmp` FROM `" . AUDITTABLENAME . "` WHERE `res_id` = '" . $state_pk . "' AND `table_name` = '" . $state_table . "' ORDER BY `time_stmp` DESC LIMIT 1";
        $state_from = sqlValue($sql_min);
        $state_to = sqlValue($sql_max);
        $hint_start = "Initial value ($state_from) is the first occurence of the record with primary key $state_pk in the chosen table '$state_table' in the audit-log.";
        $hint_end = "Initial value ($state_to) is the last occurence of the record with the primary key $state_pk in the chosen table '$state_table' in the audit-log.";
    }
    echo "<p>Preparation, step <strong>$prep_step</strong> of <strong>$prep_step_max</strong></p>";
}

?>
<hr>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="get">
<?php
if ($state_table != '') {
    $table_enable = 'disabled="disabled"';    
    echo '<input type="hidden" name="tabname" value="' . $state_table . '">';
} else {
    $table_enable = '';
}
?>    
    <div class="auditor_tabname">
        <label for="tabname">Select table : </label>
        <select id="tabname" name="tabname" class="form-control" <?php echo $table_enable;?>>
            <?php
            $sql = "SELECT `table_name` FROM `" . AUDITTABLENAME . "` GROUP BY table_name ORDER BY table_name;";
            $result_tabnames = sql($sql, $eo);

            while ($row_tabnames = db_fetch_assoc($result_tabnames)) {

                if ($row_tabnames['table_name'] == $state_table) {
                    $tabname_selected = ' selected="selected"';
                } else {
                    $tabname_selected = '';
                }
                echo '<option value="' . $row_tabnames['table_name'] . '" ' . $tabname_selected . '>' . $row_tabnames['table_name'] . '</option>';
            }
            ?>
        </select> (only tables found in <?php echo AUDITTABLENAME; ?> are shown)
    </div><!-- auditor_tabname -->

    <?php

    if ($state_table != '') {

        if ($state_pk != '') {
            $pk_enable = 'disabled="disabled"';    
            echo '<input type="hidden" name="pk" value="' . $state_pk .'">';
        } else {
            $pk_enable = '';
        }

        ?>
        <div class="auditor_pk">
            <label for="pk">Select record (primary key) from table '<?php echo $state_table; ?>'. Number in () is number of different timestamps of changes in the auditor table for the record. </label>
            <select id="pk" name="pk" class="form-control" <?php echo $pk_enable;?>>
                <?php
                // $sql = "SELECT `res_id` FROM `" . AUDITTABLENAME . "` WHERE `table_name`='" . $state_table . "' GROUP BY res_id ORDER BY Max(id) DESC;";
                $sql = "SELECT res_id,
                Count(DISTINCT time_stmp) AS c                
                FROM `" . AUDITTABLENAME . "` WHERE `table_name`='" . $state_table . "' 
                GROUP BY
                        res_id,
                        table_name
                ORDER BY
                        Max(id) DESC;";

                $result_pk = sql($sql, $eo);
                while ($row_pk = db_fetch_assoc($result_pk)) {

                    if ($row_pk['res_id'] == $state_pk) {
                        $pk_selected = ' selected="selected"';
                    } else {
                        $pk_selected = '';
                    }
                    echo '<option value="' . $row_pk['res_id'] . '" ' . $pk_selected . '>' . $row_pk['res_id'] . ' (' . $row_pk['c'] . ')</option>';
                }
                ?>
            </select>  (order of primary keys list: pk with newest change first)
        </div><!-- auditor_pk -->
        <?php
    }

    if (($state_table != '') && ($state_pk != '')) { 
        
        if (($submitted == 1) || ($submitted == 2)){ 
            // get PK column from table
            /* 
            // method 1
            // src: https://www.tutorialspoint.com/how-do-you-get-whether-a-column-is-a-primary-key-in-mysql
            $sql = "SELECT column_name
                FROM information_schema.columns
                WHERE table_schema =database()
                AND `table_name` = '" . $state_table . "'
                AND column_key= 'PRI'
                ORDER BY `table_name`, ordinal_position;";
            $pk_column = sqlValue($sql);
            */
            // method 2, should work more universal as no permissios to information_schema are needed
            // src: https://www.php.net/manual/en/function.mysql-list-fields.php
            $sql_pk = "SHOW COLUMNS FROM `" . $state_table . "`;";
            $result_pk = sql($sql_pk, $eo);
            while($row_pk = db_fetch_assoc($result_pk)){
                write_log($row_pk['Field'] . ' : ' . $row_pk['Key']);
                if ($row_pk['Key'] == 'PRI'){
                    $pk_column = $row_pk['Field'];                    
                    break;
                }
            }

            $sql_curr_record = "SELECT * FROM `" . $state_table . "` WHERE `" . $pk_column . "` ='" . $state_pk . "';";
            $result_curr_record = sql($sql_curr_record, $eo);
            ?>
            <div class="auditor_confirm">
            <br />
            <hr>
<?php if ($selectionok == 0){
    ?>
            <input type="hidden" name="selectionok" value="1">
            <h3>Confirm selection</h3>
            <p>Check if you selected the correct record (showing current values), if not, please <a href="<?php echo $_SERVER["PHP_SELF"]; ?>">start over</a> (calculated PK column is: <strong><?php echo $pk_column; ?></strong>).</p>
<?php } else {
    ?>
<h3>Current state of selected record</h3>
<?php }
       
            echo auditor_tableme($result_curr_record);        
            ?>
            <hr>
            </div>
        <?php 
        }
        
    }

    if (($state_table != '') && ($state_pk != '') && ($selectionok == 1)) {        
        ?>
       <input type="hidden" name="selectionok" value="1">
        <div class="auditor_dates">
            <label for="state_from">Set starting date/time (YYYY-MM-DD HH:MM:SS): </label>
            <input id="state_from" name="state_from" class="form-control" placeholder="YYYY-MM-DD HH:MM:SS" maxlength="19" size="19" type="text" <?php echo 'value="'. $state_from .'"'; ?> title="<?php echo $hint_start; ?>">

            <label for="state_to">Set end date/time (YYYY-MM-DD HH:MM:SS): </label>
            <input id="state_to" name="state_to" class="form-control" placeholder="YYYY-MM-DD HH:MM:SS" maxlength="19" size="19" type="text" <?php echo 'value="'. $state_to .'"'; ?> title="<?php echo $hint_end; ?>">
        </div><!-- auditor_dates -->

        <div class="auditor_options">
            <label for="HTML">Remove HTML</label>
            <input type="checkbox" id="HTML" name="HTML" value="1" <?php if ($state_html == 1){echo ' checked ';} ?>>
            <br />
            <label for="before">Include latest data before start timestamp (should prevent no-data-cells)</label>
            <input type="checkbox" id="before" name="before" value="1" checked >

        </div><!-- auditor_options -->

        <?php
        $submit_btn_txt = " build timeline";
        $submit_btn_icon = 'ok';
        $submit_btn_value = '2';        
        $submit_btn_tip = 'get the data for the table, record and timeframe';
    } else {
        $submit_btn_txt = " next step";
        $submit_btn_icon = 'play';
        $submit_btn_value = '1';        
        $submit_btn_tip = 'continue to the next step';
    }

    ?>
    <div class="auditor_ActionButtons">
        <button id="submit" name="submit" value="<?php echo $submit_btn_value; ?>" type="submit" class="btn btn-success" title="<?php echo $submit_btn_tip; ?>"><i class="glyphicon glyphicon-<?php echo $submit_btn_icon; ?>"></i> <?php echo  $submit_btn_txt; ?></button>        
        <?php if ($prep_step != 1) {?>
            <button id="cancel" name="cancel" value="1" type="button" class="btn btn-warning" title="Cancel all fields and reload blank page"><a href="<?php echo $_SERVER["PHP_SELF"]; ?>"><i class="glyphicon glyphicon-remove"></i> Start over</a></button>
        <?php } ?>
    </div><!-- auditor_ActionButtons -->

</form>
<div class="clearfix"></div>	