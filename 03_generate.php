<?php include dirname(__FILE__) . '/header.php';

try {
    if (!isset($_POST['path'])) {
        throw new RuntimeException('This page has expired');
    }

    $path = rtrim(trim($_POST['path']), '\\/');
    if (!is_dir($path)) {
        throw new RuntimeException('Invalid path');
    }

    if (
        !(
            file_exists("$path/lib.php") &&
            file_exists("$path/db.php") &&
            file_exists("$path/index.php")
        )
    ) {
        throw new RuntimeException(
            'The given path is not a valid AppGini project path'
        );
    }

    if (!is_writable($path . '/hooks')) {
        throw new RuntimeException(
            'The hooks folder of the given path is not writable'
        );
    }

    if (!is_writable($path . '/resources')) {
        throw new RuntimeException(
            'The resources folder of the given path is not writable'
        );
    }
} catch (RuntimeException $e) {
    echo '<br>' . $MyPlugin->error_message($e->getMessage());
    exit();
}
//-------------------------------------------------------------------------------------

$write_to_hooks = $_REQUEST['dont_write_to_hooks'] == 1 ? false : true;
?>
           
<div class="bs-docs-section row">
    <h1 class="page-header">
        <img src="<?php echo $MyPlugin->logo; ?>" style="height: 1em;"> 
        <?php echo $MyPlugin->title; ?>
    </h1>
	<p class="lead">
		<a href="./index.php">Home</a> > 
		<a href="./02_output.php">  Select output folder</a> > Coping Files <?php echo $MyPlugin->name; ?>
	</p>
</div>

<h4>Progress log</h4>

<?php
$MyPlugin->progress_log->add("Output folder: $path", 'text-info');

//coping resources

$MyPlugin->progress_log->ok();
$MyPlugin->progress_log->line();

//admin dest resources
$source = dirname(__FILE__) . '/app-resources/admin/auditLog.php';
$dest = $path . '/admin/auditLog.php';
$MyPlugin->my_copy_file($source, $dest, true);
$source = dirname(__FILE__) . '/app-resources/admin/auditLogTimeLine.php';
$dest = $path . '/admin/auditLogTimeLine.php';
$MyPlugin->my_copy_file($source, $dest, true);

//hooks dest resources
$source = dirname(__FILE__) . '/app-resources/hooks/auditLog_functions.php';
$dest = $path . '/hooks/audit/auditLog_functions.php';
$MyPlugin->my_copy_file($source, $dest, true);
$source = dirname(__FILE__) . '/app-resources/hooks/auditLog_timeline.php';
$dest = $path . '/hooks/audit/auditLog_timeline.php';
$MyPlugin->my_copy_file($source, $dest, true);
$source = dirname(__FILE__) . '/app-resources/dropdown_menu.html';
$dest = $path . '/hooks/audit/dropdown_menu.html';
$MyPlugin->my_copy_file($source, $dest, true);
$source = dirname(__FILE__) . '/app-resources/Clipboard_AppGini_AuditLog_Table.txt';
$dest = $path . '/hooks/audit/Clipboard_AppGini_AuditLog_Table.txt';
$MyPlugin->my_copy_file($source, $dest, true);


$source = dirname(__FILE__) . '/app-resources/button.js';
$dest = $path . '/hooks/audit/button.js';
$MyPlugin->my_copy_file($source, $dest, true);

$source = dirname(__FILE__) . '/app-resources/scripts.php';
$dest = $path . '/hooks/audit/scripts.php';
$MyPlugin->my_copy_file($source, $dest, true);

$MyPlugin->progress_log->line();
$MyPlugin->progress_log->add(
    'Adding New table on current data base',
    'text-info'
);
$sql = file_get_contents(
    dirname(__FILE__) . '/app-resources/audit_tableSQL.sql'
);
if ($sql) {
    $eo = ['silentErrors' => true];
    $res = sql($sql, $eo);
    if ($eo['error'] != '') {
        $MyPlugin->progress_log->add(
            'ERROR: Audit table not created',
            'text-danger spacer'
        );
    } else {
        $MyPlugin->progress_log->add('Audit table ready');
    }
}

$MyPlugin->progress_log->line();
$MyPlugin->progress_log->add('Adding code to hooks', 'text-info');
$MyPlugin->progress_log->add('File: __global.php');
$code = "<?php include('audit/scripts.php');?>";
$file_path = $path . '/hooks/__global.php';

if ($write_to_hooks) {
    $check="include('audit/scripts.php')";
        
    $chk_A = $MyPlugin->check_if_exist_code($file_path, false, $code);
    $chk_B = $MyPlugin->check_if_exist_code($file_path, false, $check);
    if (!($chk_A === $chk_B)) {
        $MyPlugin->progress_log->add("Error adding code into file : {$file_path}.<br>The code or part of code ({$check}) exist in this file, please delete before continue", 'text-danger');
    } else {
        $res = $MyPlugin->add_to_file($file_path, false, $code);
        inspect_result($res, $file_path, $MyPlugin);
    }
} else {
    $code = "include('audit/scripts.php');";
    $MyPlugin->progress_log->add("File: {$file_path}");
    $MyPlugin->progress_log->add('On top of function');
    $MyPlugin->progress_log->add("Install code: {$code}");
    $MyPlugin->progress_log->line();
}

// write to hooks files

$tables = getTableList(true);
foreach ($tables as $tn => $table) {
    $MyPlugin->progress_log->add('Table: ' . $table[0], 'text-info');
    $hook = "{$path}/hooks/{$tn}.php";

    //* Step 4.A
    $function = "{$tn}_init";
    $code = "\$_SESSION ['tablenam'] = \$options->TableName; \$_SESSION ['tableID'] = \$options->PrimaryKey;";
    $check=["\$_SESSION ['tablenam'] = \$options->TableName","\$_SESSION ['tableID'] = \$options->PrimaryKey"];
    $res = hooks_code($hook, $function, $code, $MyPlugin, $write_to_hooks, $check);
    if (!$res) {
        break;
    }

    //* Step 4.B
    $function = "{$tn}_after_insert";
    $code = "table_after_change(\$_SESSION, \$memberInfo, \$data, 'INSERTION');";
    $check ="table_after_change";
    $res = hooks_code($hook, $function, $code, $MyPlugin, $write_to_hooks, $check);
    if (!$res) {
        break;
    }

    //* Step 4.C
    $function = "{$tn}_before_update";
    $code = "table_before_change(\$_SESSION, \$data['selectedID']);";
    $check ="table_before_change";
    $res = hooks_code($hook, $function, $code, $MyPlugin, $write_to_hooks, $check);
    if (!$res) {
        break;
    }

    //* Step 4.D
    $function = "{$tn}_after_update";
    $code = "table_after_change(\$_SESSION, \$memberInfo, \$data, 'UPDATE');";
    $check ="table_after_change";
    $res = hooks_code($hook, $function, $code, $MyPlugin, $write_to_hooks, $check);
    if (!$res) {
        break;
    }

    //* Step 4.E
    $function = "{$tn}_before_delete";
    $code = "table_before_change(\$_SESSION, \$selectedID);";
    $check ="table_before_change";
    $res = hooks_code($hook, $function, $code, $MyPlugin, $write_to_hooks, $check);
    if (!$res) {
        break;
    }

    //* Step 4.F
    $function = "{$tn}_after_delete";
    $code = "table_after_change(\$_SESSION, \$memberInfo, \$selectedID, 'DELETION');";
    $check ="table_after_change";
    $res = hooks_code($hook, $function, $code, $MyPlugin, $write_to_hooks, $check);
    if (!$res) {
        break;
    }
}

echo $MyPlugin->progress_log->show();
?>

<center>
	<a style="margin:20px;" href="index.php" class="btn btn-success btn-lg"><span class="glyphicon glyphicon-home" ></span>   Start page</a>
</center>

<script>	
	$j( function(){

		$j("#progress").height( $j(window).height() - $j("#progress").offset().top - $j(".btn-success").height() - 100 );

		//add resize event
		$j( window ).resize(function() {
		   $j("#progress").height( $j(window).height() - $j("#progress").offset().top - $j(".btn-success").height() - 100 );
		});

	});
</script>

<?php
include dirname(__FILE__) . '/footer.php';

function inspect_result($res, $file_path, &$MyPlugin)
{
    if ($res) {
        $MyPlugin->progress_log->add(
            "Installed code into '{$file_path}'.",
            'text-success spacer'
        );
    } else {
        $error = $MyPlugin->last_error();

        if ($error == 'Code already exists') {
            $MyPlugin->progress_log->add(
                "Skipped installing to '{$file_path}', code is already installed.",
                'text-warning spacer'
            );
        } else {
            $MyPlugin->progress_log->add(
                "Failed to install code '{$file_path}': {$error}",
                'text-danger spacer'
            );
        }
    }
}

function hooks_code($hook, $function, $code, &$MyPlugin, $write_to_hooks=true, $check=false)
{
    if ($write_to_hooks) {
        if ($check) {
            if (!is_array($check)) {
                $check=[$check];
            }
            foreach ($check as $value) {
                $chk_A = $MyPlugin->check_if_exist_code($hook, $function, $code);
                $chk_B = $MyPlugin->check_if_exist_code($hook, $function, $value);
                if (!($chk_A === $chk_B)) {
                    $MyPlugin->progress_log->add("Error on hook file : {$hook}.<br>The code or part of code exist in function <strong>{$function}</strong>.<br>Please delete the calling <strong>{$value}</strong> before continue", 'text-danger');
                    return false;
                }
            }
        }
        $res = $MyPlugin->add_to_hook($hook, $function, $code);
        inspect_result($res, $function, $MyPlugin);
    } else {
        $MyPlugin->progress_log->add("File: {$hook}", 'text-info');
        $MyPlugin->progress_log->add("Hook function: {$function}");
        $MyPlugin->progress_log->add("Install code:  {$code}");
        $MyPlugin->progress_log->line();
    }
    return true;
}
?>
