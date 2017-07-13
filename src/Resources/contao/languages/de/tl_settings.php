<?php

/**
 * @copyright  Softleister 2007-2017
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */


/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_settings']['backupdb_saveddb']   = array('Datentabellen im Website-Template', 'Tragen Sie hier eine kommagetrennte Liste der Tabellenprefixe oder Tabellen ein, die in dem Website-Template berücksichtigt werden sollen. Alle Tabellen des Contao-Core beginnen mit <em>tl_</em>, für MetaModels ggf. <em>mm_</em>');
$GLOBALS['TL_LANG']['tl_settings']['AutoBackupCount']    = array('Anzahl der Backups bei AutoBackupDB', 'Anzahl der Datensicherungsdateien, die von AutoBackupDB erstellt werden, der Standard ist 3. Das neueste Backup ist immer in der Datei <em>AutoBackupDB-1.sql</em>');
$GLOBALS['TL_LANG']['tl_settings']['WsTemplatePath']     = array('Alternativer Pfad für Website-Templates', 'Standard-Pfad ist <em>templates</em>. Sie können hier ein anderes Verzeichnis angeben, wo die Website-Templates gespeichert werden.');
$GLOBALS['TL_LANG']['tl_settings']['backupdb_blacklist'] = array('Blacklist für Backups', 'Kommagetrennte Liste der Tabellen, für die die Daten nicht mit gesichert werden sollen. Beispielsweise sind <em>tl_lock, tl_log, tl_search, tl_search_index, tl_session, tl_undo, tl_version</em> nicht unbedingt für eine Wiederherstellung notwendig.');
$GLOBALS['TL_LANG']['tl_settings']['backupdb_sendmail']  = array('E-Mail-Benachrichtigung nach AutoBackup', 'Sendet nach erfolgreichem Backup eine Mail an den Systemadministrator.');
$GLOBALS['TL_LANG']['tl_settings']['backupdb_attmail']   = array('AutoBackup-Datei anhängen', 'Hängt die Backupdatei an die Benachrichtigungsmail an.');
$GLOBALS['TL_LANG']['tl_settings']['backupdb_zip']       = array('Backup mit Restoreinfos komprimieren', 'Die ZIP-Datei enthält zusätzlich die Dateien <em>composer.json</em>, <em>composer.lock</em> und ein PHP-Script zur Wiederherstellung der Symlinks.');

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_settings']['backupdb_legend']      = 'BackupDB Einstellungen';
