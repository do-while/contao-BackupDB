<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package     BackupDB - Database backup
 * @copyright   Softleister 2007-2017
 * @author      Softleister <info@softleister.de> 
 * @licence     LGPL
 */

define('BACKUPDB_VERSION', '4.4');
define('BACKUPDB_BUILD'  , '0');

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
		'callback'   => 'BackupDB\ModuleBackupDB',
		'stylesheet' => 'system/modules/BackupDB/assets/styles.min.css',
	)
));

