<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'Softleister',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'Softleister\BackupDB\BackupDbCommon'   => 'system/modules/BackupDB/classes/BackupDbCommon.php',
	'Softleister\BackupDB\BackupDbRun'      => 'system/modules/BackupDB/classes/BackupDbRun.php',
	'Softleister\BackupDB\BackupWsTemplate' => 'system/modules/BackupDB/classes/BackupWsTemplate.php',
	'Softleister\BackupDB\AutoBackupDb'     => 'system/modules/BackupDB/public/AutoBackupDB.php',

	// Modules
	'Softleister\BackupDB\ModuleBackupDB'   => 'system/modules/BackupDB/modules/ModuleBackupDB.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_backup_db'     => 'system/modules/BackupDB/templates',
));
