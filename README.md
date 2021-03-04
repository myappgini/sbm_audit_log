# Audit Log for AppGini

## Audit log admin in AppGini project

Adjustments by Olaf Nöhring, 2021-02 (https://datenbank-projekt.de) for v1.77:
- corrected manual installation table_after_change call, using $selectedID instead of $data (which does not exist at this point)
- adjusted function table_after_change to accept $selectedID as direct variable (not array)
- corrected error when inserting a new record and calling table_before_change to document all values

Adjustments by Olaf Nöhring, 2021-02 (https://datenbank-projekt.de) for v1.76:
- Fixed and updated manual installation _init code
- added AppGini Code for Auditor table for easy, quick and correct creation of the auditor table in your AppGini application.
- cooperation with landinialejandro to make code cleaner for use in installation plugin
    
Adjustments by Olaf Nöhring, 2021-01 (https://datenbank-projekt.de) for v1.74:
- Added example screenshots of Audit Log view to docs
- Added hint how to deal with the need for exact position of audit-log calls
    
Adjustments by Olaf Nöhring, 2021-01 (https://datenbank-projekt.de) for v1.73:
- Adjusted docs for more clarity
    
Adjustments by Olaf Nöhring, 2021-01 (https://datenbank-projekt.de) for v1.72:
- Added link to docs for the wonderful plugin extension from landinialejandro which installation a walk in the park. Please see below
- Modified file structure of the zip files that holds the audit log to adjust for use in combination with the plugin.
- Changed formatting of the docs for better readability
- Restructured docs for plugin
- Added hint for auditor tablename to docs

Adjustments by Olaf Nöhring, 2021-01 (https://datenbank-projekt.de) for v1.71:
- removed bug when record is deleted (v1.71)
- added possibility to easily define the name of your auditor table in the beginning of the the files
auditLog_functions.php and /admin/auditLog.php
- changed code in the way that now the order of fields in the database must not match the order of
fields in your AppGini application anymore. In previous versions the order must be the same,
otherwise it would mess up the logging. Now this problem should be solved.
- added a new function Audit_Manually which allows checking for changes on another table and
documenting those (see description below for more information).
- transformed docs to PDF for easier editing
- changed audit_tableSQL.sql to make larger fields for table and fieldnames

Adjustments by Olaf Nöhring, 2019-12 (https://datenbank-projekt.de):
- improved INSERTION: Now, all non-empty fields are written to the auditor table after insert.
Until now, only the new primary key was written. - you can easily use a different table name for teh
auditor. Simply adjust
$audittablename = "auditor";
in function table_after_change in auditLog_functions.php (and the setup sql or course). - changed
auditor table name from Auditor to auditor (script and setup). Note: On Linux systems the
tablenames are case sensitive!

Adjustments by Olaf Nöhring, 2019-06 (https://datenbank-projekt.de):
- Trick: Added in docs. Remove access to Auditor from Admin menu, but use regular AppGini table
instead, so Auditor stays even when application is regenerated.
- Trick: Remove changes from 'application_root/lib.php', instead place code in config.php which
stays in place, even when the application is regenerated.
- Changes to auditLog_functions.php, added , $eo to SQL queries (following vaalonv tip)
- Instead of foreign keys you will see the values the user actually selected (old code to see FKs still in the file). For this to work correct, make sure the ordthe order of the fields in specific table in AppGini!

Only install, go to admin tools area and select Audit from plugins menu.

## Install

go to plugin folder.
dowload audit ZIP pack into plugins folder in your project, and unzip it into audit folder.

[download link](https://github.com//myappgini/sbm_audit_log/archive/main.zip)

or use **git** into your plugin folder:

if you already use git in ypur project add like submodule
```cmd
$ git submodule add https://github.com/myappgini/sbm_audit_log.git audit
```


## Manual Installation

### Attention

**Note 1**
For the Search/Replace it’s recommended to use 'Notepad++' available here: [Notepad++ Home Page](https://notepad-plus-plus.org/)

**Note 2**
We suggest that you wait till your application is ready to go to production before making these changes - although this is NOT essential - (with the proviso that you **BACK UP YOUR FILES FIRST!**)

<a name="note3"></a>
**Note 3**
When it comes to the tedious task of doing the Search/Replace in the Hooks folder, we suggest that you copy the hook files ONLY for the tables you wish to monitor into a separate directory and then BACKUP that directory. This way, you can do it speedily using Notepad++'s Search/Replace facility 'Find in Files' and do them all in just six shots.

### Step 1. Extract the auditlog_files.zip and copy files
The zip file contain only 2 files:
- `auditLog_functions.php` : The functions that allow the audit log to work. Copy this into the /hooks folder of your application
-  `auditLog.php` : A table (filterable and pageable) that will be added to the Admin Menu Options. Copy this into the /admin folder of your application.

### Step 2. Create the Audit Log Table using the audit_tableSQL.sql file provided.
You may want to adjust the auditor tablename before. See above: Custom table name for the auditor table.
Then just run the SQL with your favorite tool (https://www.phpmyadmin.net, https://www.adminer.org).

### Step 3. Essential File Modifications
- 3.1 Include audit-base files: 'application_root/config.php'
Add the following to the bottom of the file.
```php
    if (session_status() == PHP_SESSION_NONE) { session_start(); }	
    $_SESSION['dbase'] = $dbDatabase;
    if (!function_exists('table_before_change')) {	
    	$currDir = dirname(__FILE__);		
    	@require("$currDir/hooks/auditLog_functions.php");
    };
```
- 3.2 Add page to the Admin Menu Options: 'admin/incHeader.php'
Trick (as pictured and described in Advanced Audit Log Table (recommended), above):
If you create a table in AppGini with the name as the Auditor table (i.e. Auditor) and the same fields (case sensitive) as the in the Auditor table, you can build a regular Audit-Table button from AppGini and let user access that with the regular permissions.
Maybe you want to make sure, that noone can change anything in that table.
If you to this, you do not need to make changes in 'admin/incHeader.php' as described now (and thus Auditor will stay in your application even when regenerated.

Do find for:
```html
<a class="navbar-brand" href="pageHome.php">
	<span class="text-warning">
		<i class="glyphicon glyphicon-wrench"></i> 
		Admin Area
	</span>
</a>
```
and replace with:
```html
<a class="navbar-brand" href="pageHome.php">
	<span class="text-warning">
		<i class="glyphicon glyphicon-wrench"></i> 
		Admin Area
	</span>
</a>
<a class="navbar-brand" href="auditLog.php">
	<span class="text-warning-1">
		<i class="glyphicon glyphicon-tasks"></i>
		Audit Log
	</span>
</a>
```
### Step 4. Essential /hooks/folder-files modification
**After you've read [Note 3](#note3), above!**

In the temp folder that contains the files from the hooks-folder for all the tables that you wish to monitor, make the following changes to all these files. Recommended: Do 'find in files'. Code changes/additions are color coded like this.
- A. Do 'find in files' for: (Remember to set the correct directory!)

```init(&$options, $memberInfo, &$args){```

and replace with:
``` 
init(&$options, $memberInfo, &$args){
$_SESSION ['tablenam'] = $options->TableName;
$_SESSION ['tableID'] = $options->PrimaryKey;
```
- B. Do 'find in files' for:

```after_insert($data, $memberInfo, &$args){```

and replace with:
```
after_insert($data, $memberInfo, &$args){
table_after_change ($_SESSION, $memberInfo, $data, 'INSERTION');
```

- C. Do 'find in files' for:

```before_update(&$data, $memberInfo, &$args){```

and replace with:
```
before_update(&$data, $memberInfo, &$args){
table_before_change($_SESSION, $data['selectedID']);
```

- D. Do 'find in files' for:

```after_update($data, $memberInfo, &$args){```

and replace with:
```
after_update($data, $memberInfo, &$args){
table_after_change ($_SESSION, $memberInfo, $data, 'UPDATE');
```

- E. Do 'find in files' for:

```before_delete($selectedID, &$skipChecks, $memberInfo, &$args){```

and replace with:
```
before_delete($selectedID, &$skipChecks, $memberInfo, &$args){
table_before_change($_SESSION, $selectedID);
```

- F. Do 'find in files' for:

```after_delete($selectedID, $memberInfo, &$args){```

and replace with:
```
after_delete($selectedID, $memberInfo, &$args){
table_after_change ($_SESSION, $memberInfo, $selectedID, 'DELETION');
```

Remember to copy the files from the temp directory created in Note 3 back to the original Hooks folder!

AuditLog should now be working! Using this technique will allow you to keep other modifications made to the Hooks folder.


## Use

Select Audit from plugin menu in admin area.

Follow the stepp.

Then next to install needed files and enjoy.
