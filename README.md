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
    
    

Versions 1.71
Adjustments by Olaf Nöhring, 2021-01 (https://datenbank-projekt.de):
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

## Use

Select Audit from plugin menu in admin area.

Follow the stepp.

Then next to install needed files and enjoy.
