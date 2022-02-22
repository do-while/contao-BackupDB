<?php

/**
 * @copyright  Softleister 2007-2021
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
array_insert($GLOBALS['BE_MOD']['system'], -1, array
(
	'BackupDB' => array (
		'callback'   => 'Softleister\BackupDB\ModuleBackupDB',
		'stylesheet' => 'bundles/softleisterbackupdb/styles.min.css',
	)
));
