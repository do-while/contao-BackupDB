<?php

/**
 * @copyright  Softleister 2007-2024
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

define('BACKUPDB_RUN_LAST',  'RunBackupDB.last');
define('BACKUPDB_CRON_LAST', 'AutoBackupDB.last');


/**
 * -------------------------------------------------------------------------
 * BACK END MODULES
 * -------------------------------------------------------------------------
 */
$GLOBALS['BE_MOD']['system']['BackupDB'] = array
(
    'callback'   => 'Softleister\BackupDB\ModuleBackupDB',
    'stylesheet' => 'bundles/softleisterbackupdb/styles.min.css',
);
