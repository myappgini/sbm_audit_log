<?php 
if (defined('ADMIN_AREA')){
    ?>
    <script src="../hooks/audit/button.js"></script>
    <?php
}

if (!function_exists('table_before_change')) {
    if (session_status() == PHP_SESSION_NONE) { session_start(); }
    $_SESSION['dbase'] = config('dbDatabase');
    $thisDir = dirname(__FILE__);
    @require("$thisDir/auditLog_functions.php");
}


?>