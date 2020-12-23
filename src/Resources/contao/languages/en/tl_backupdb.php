<?php

/**
 * @copyright  Softleister 2007-2020
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

$GLOBALS['TL_LANG']['tl_backupdb']['download']      = 'Download MySQL Backup';
$GLOBALS['TL_LANG']['tl_backupdb']['startdownload'] = 'Start MySQL Backup';
$GLOBALS['TL_LANG']['tl_backupdb']['database']      = 'Database';

$GLOBALS['TL_LANG']['tl_backupdb']['backupdesc']    = 'The data backup can be used to restore the installation at a later date.';
$GLOBALS['TL_LANG']['tl_backupdb']['backupsetup']   = 'Other parameters can be set under <a href="%s">Settings</a> in the "BackupDB Settings" section.';
$GLOBALS['TL_LANG']['tl_backupdb']['backuplast']    = 'Last backup download on';

$GLOBALS['TL_LANG']['tl_backupdb']['autoinfo']      = 'For an automatic backup call the following URL';
$GLOBALS['TL_LANG']['tl_backupdb']['croninfo']      = 'Time triggerd AutoBackup with cron.';
$GLOBALS['TL_LANG']['tl_backupdb']['cronsetup']     = 'Set up a scheduled backup using the <a href="%s">cron extension</a>.';
$GLOBALS['TL_LANG']['tl_backupdb']['cronlast']      = 'Last AutoBackup on';

$GLOBALS['TL_LANG']['tl_backupdb']['maketpl']       = 'Create website template';
$GLOBALS['TL_LANG']['tl_backupdb']['tpldesc']       = 'Creates a website template for importing into the InstallTool.';
$GLOBALS['TL_LANG']['tl_backupdb']['tplfiles']      = 'Files';
$GLOBALS['TL_LANG']['tl_backupdb']['tplnobackup']   = 'A website template can <strong>NOT</strong> be used as a backup!';
$GLOBALS['TL_LANG']['tl_backupdb']['tplwarning']    = 'When importing in the InstallTool, the installation must be exactly the same. Contao and all extensions must be installed in the same version! You will find the required versions in the created .txt file.';

$GLOBALS['TL_LANG']['tl_backupdb']['tplhead']       = 'Creating website template ...';
$GLOBALS['TL_LANG']['tl_backupdb']['tplentry']      = 'entry';
$GLOBALS['TL_LANG']['tl_backupdb']['tplentriesd']   = 'entries';
$GLOBALS['TL_LANG']['tl_backupdb']['tplresult']     = 'The website template has been completed.';
$GLOBALS['TL_LANG']['tl_backupdb']['tpllegend']     = 'Newly created files';
$GLOBALS['TL_LANG']['tl_backupdb']['tplfooter']     = 'Please read the information in the .txt file.';
$GLOBALS['TL_LANG']['tl_backupdb']['tplbutton']     = 'Back';
