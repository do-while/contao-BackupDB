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


/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_settings']['backupdb_saveddb']   = array('Data tables in website template', 'Enter a comma-separated list of table prefixes or tables to be included in the website template. All tables of the Contao core start with <em>tl_</em>, catalog tables may have the prefix <em>cat_</em>.');
$GLOBALS['TL_LANG']['tl_settings']['AutoBackupCount']    = array('Number of backups at AutoBackupDB', 'Number of backup files that are created by AutoBackupDB, the default is 3. The most recent backup is always in the file <em>AutoBackupDB-1.sql</em>.');
$GLOBALS['TL_LANG']['tl_settings']['WsTemplatePath']     = array('Alternative path for website templates', 'Default path is <em>templates</em>. You can specify a different directory where the website templates ar stored.');
$GLOBALS['TL_LANG']['tl_settings']['backupdb_blacklist'] = array('Blacklist for backups', 'Comma-separated list of tables for which the data is NOT being backed up. For example <em>tl_lock, tl_log, tl_search, tl_search_index, tl_session, tl_undo, tl_version</em> are not essential for recovery.');
$GLOBALS['TL_LANG']['tl_settings']['backupdb_sendmail']  = array('E-mail notification after AutoBackup', 'Sends a mail after a successful backup to the administrator.');
$GLOBALS['TL_LANG']['tl_settings']['backupdb_attmail']   = array('Append AutoBackup file', 'Append the backup file to the notification e-mail.');
$GLOBALS['TL_LANG']['tl_settings']['backupdb_zip']       = array('Compress backup', 'The ZIP file also contains the <em>composer.json</em> and <em>composer.lock</em> files');

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_settings']['backupdb_legend']      = 'BackupDB settings';

