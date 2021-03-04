<?php

	// Define the name of your auditor table once for this file:
	define("AUDITTABLENAME", 'auditor');



	$currDir=dirname(__FILE__);
	require("$currDir/incCommon.php");
	include("$currDir/incHeader.php");
	
	if (isset($_SESSION ['auditLogBackup'])){
	$thisMessage = "Log File Backed Up";
	echo "<script type='text/javascript'>alert('$thisMessage');</script>";
	unset($_SESSION['auditLogBackup']);
	}


	$numMembers=sqlValue("select count(1) from ". AUDITTABLENAME);
	if(!$numMembers){
		echo "<div class=\"status\">No matching results found.</div>";
		$noResults=TRUE;
		$page=1;
	}else{
		$noResults=FALSE;
	}

	$page=intval($_GET['page']);
	if($page<1){
		$page=1;
	}elseif($page>ceil($numMembers/$adminConfig['recordsPerPage']) && !$noResults){
		redirect("admin/auditLog.php?page=".ceil($numMembers/$adminConfig['recordsPerPage']));
	}

	$start=($page-1)*$adminConfig['recordsPerPage'];

?>

<style>
input {
  width:100%;
  height: 26px;
  font-size: 18px;
  padding:2px;
  border:0;
}

table {
  border-collapse: collapse;
  width: 100%;
}

th {
	background: #0086cc;
	color: white;
}
th, td {
  padding: 0.5rem;
  text-align: centre;
  border: 1px solid #ccc;
}
tbody tr:nth-child(odd) {
  background: #eee;
}

tbody tr:nth-child(odd):hover {
  background: red;
  color: white;
}

tbody tr:hover {
  background: red;
  color: white;
}

tfoot tr {
    background: #b3e5ff;
	
}

tfoot tr:hover {
  
}

</style>
<section class="container">
<div class="page-header"><h1>Audit Log</h1>

</div>
<input type="search" class="light-table-filter" data-table="order-table" placeholder="Type Here to Filter Data">
<table class="table table-striped order-table" width="100%" cellspacing="0" id="t01">
<thead>
	<tr>
				<b>
				<th>ID</th>
				<th>User Name</th>
				<th>IP Address</th> 
				<th>TimeStamp</th>
				<th>Change Type</th>
				<th>Table</th> 
				<th>Field</th>
				<th>Previous Value</th> 
				<th>New Value</th>
				</b>
	</tr>
</thead>
<tbody>
<?php

	
	$res=sql("SELECT `username`,`ipaddr`, `time_stmp`, `change_type`, `table_name`, `fieldName`, `OldValue`, `NewValue`, `id` FROM `" . AUDITTABLENAME . "` order by `time_stmp` desc limit $start, ".$adminConfig['recordsPerPage'], $eo);
	
	
	while($row=db_fetch_row($res))	{
?>
		<tr>
				<td class="tdCaptionCell"><?php echo $row[8]; ?></td>
				<td class="tdCaptionCell"><?php echo $row[0]; ?></td>
				<td class="tdCaptionCell"><?php echo $row[1]; ?></td>
				<td class="tdCaptionCell"><?php echo date('d/m/Y H:i:s',strtotime($row[2])); ?></td>
				<td class="tdCaptionCell"><?php echo $row[3]; ?></td>
				<td class="tdCaptionCell"><?php echo $row[4]; ?></td>
				<td class="tdCaptionCell"><?php echo $row[5]; ?></td>
				<td class="tdCaptionCell"><?php echo $row[6]; ?></td>
				<td class="tdCaptionCell"><?php echo $row[7]; ?></td>
		</tr>
<?php
									}
?>
</tbody>
<tfoot>
<tr>
				<td align="left" class="tdFooter">
					<input type="button" onClick="window.location='auditLog.php?searchMembers=<?php echo $searchHTML; ?>&groupID=<?php echo $groupID; ?>&status=<?php echo $status; ?>&searchField=<?php echo $searchField; ?>&page=<?php echo ($page>1 ? $page-1 : 1); ?>';" value="Previous">
				</td>
				<td align="center" class="tdFooter">
					
				</td>
				<td align="center" class="tdFooter">
					<?php echo "<b>Displaying Audit Log Records ".($start+1)." to ".($start+db_num_rows($res))." of $numMembers</b>"; ?>
				</td>
					<td align="center" class="tdFooter">
					
				</td>
					<td align="center" class="tdFooter">
					
				</td>
					<td align="center" class="tdFooter">
					
				</td>
					<td align="center" class="tdFooter">
					
				</td>
					<td align="center" class="tdFooter">
					
				</td>
				<td align="right" class="tdFooter">
					<input type="button" onClick="window.location='auditLog.php?searchMembers=<?php echo $searchHTML; ?>&groupID=<?php echo $groupID; ?>&status=<?php echo $status; ?>&searchField=<?php echo $searchField; ?>&page=<?php echo ($page<ceil($numMembers/$adminConfig['membersPerPage']) ? $page+1 : ceil($numMembers/$adminConfig['membersPerPage'])); ?>';" value="Next">
				</td>
</tr>		
</tfoot>
</table>
</section>

<script type="text/javascript">
(function(document) {
    'use strict';

    var LightTableFilter = (function(Arr) {

        var _input;

        function _onInputEvent(e) {
            _input = e.target;
            var tables = document.getElementsByClassName(_input.getAttribute('data-table'));
            Arr.forEach.call(tables, function(table) {
                Arr.forEach.call(table.tBodies, function(tbody) {
                    Arr.forEach.call(tbody.rows, _filter);
                });
            });
        }

        function _filter(row) {
            var text = row.textContent.toLowerCase(), val = _input.value.toLowerCase();
            row.style.display = text.indexOf(val) === -1 ? 'none' : 'table-row';
        }

        return {
            init: function() {
                var inputs = document.getElementsByClassName('light-table-filter');
                Arr.forEach.call(inputs, function(input) {
                    input.oninput = _onInputEvent;
                });
            }
        };
    })(Array.prototype);

    document.addEventListener('readystatechange', function() {
        if (document.readyState === 'complete') {
            LightTableFilter.init();
        }
    });

})(document);
</script>

<?php
	include("$currDir/incFooter.php");
?>