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
 * Run in a custom namespace, so the class can be replaced
 */
namespace Softleister\BackupDB;

use Softleister\BackupDB\BackupDbCommon;

//-------------------------------------------------------------------
// AutoBackupDB.php Backup Contao-Datenbank mit Cron-Job
//
// Copyright (c) 2007-2017 by Softleister
//
// Der Cron-Job nimmt diese Datei als Include-Datei für CronController.php
// aktueller Pfad bei Ausführung: system/modules/cron
//
//-------------------------------------------------------------------
//  Systeminitialisierung, wenn direkt aufgerufen
//-------------------------------------------------------------------

/**
 * Set the script name
 */
if( !defined('TL_SCRIPT') ) {
    define('TL_SCRIPT', 'system/modules/BackupDB/public/AutoBackupDB.php');
}


if( !defined('TL_MODE') ) {
    define('TL_MODE', 'BE');

    // search the initialize.php // Danke an xtra
    $dir = dirname( $_SERVER['SCRIPT_FILENAME'] );

    while( ($dir != '.') && ($dir != '/') && !is_file($dir . '/system/initialize.php') ) {
        $dir = dirname( $dir );
    }

    if( !is_file( $dir . '/system/initialize.php' ) ) {
        echo 'Could not find initialize.php, where is Contao?';
        exit;
    }

    require_once( $dir . '/system/initialize.php' );
    define('DIRECT_CALL', 1 );
}

//-------------------------------------------------------------------
//  Backend um die Backup-Funktionen erweitern
//-------------------------------------------------------------------
class AutoBackupDb extends \Backend             // Datenbank ist bereits geöffnet
{
    //-------------------------
    //  Constructor
    //-------------------------
    public function __construct( )
    {
        parent::__construct();                      // Construktor Backend ausführen
        $user = \BackendUser::getInstance();        // Backend-User
        $user->authenticate();
    }

    //-------------------------
    //  Backup ausführen
    //-------------------------
    public function run( )
    {
        @set_time_limit( 600 );
        \System::loadLanguageFile('tl_backupdb');                   // Sprachenfiles laden

        //--- alten Zeitstempel löschen ---
        $pfad = TL_ROOT . '/' . $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB';
        if( file_exists( $pfad . '/' . BACKUPDB_CRON_LAST ) ) {
            unlink( $pfad . '/' . BACKUPDB_CRON_LAST );             // LastRun-Datei löschen
        }

        echo 'Starting BackupDB ...<br>';

        //--- Datei-Extension festlegen ---
        $ext = '.sql';
        if( isset( $GLOBALS['TL_CONFIG']['backupdb_zip'] ) && ($GLOBALS['TL_CONFIG']['backupdb_zip'] == true) ) {
            $ext = '.zip';
        }

        //--- alte Backups aufrutschen ---  Anzahl einstellbar 29.3.2009 Softleister, über localconfig 07.05.2011
        $anzBackup = 3;
        if( isset( $GLOBALS['BACKUPDB']['AutoBackupCount'] ) && is_int($GLOBALS['BACKUPDB']['AutoBackupCount']) ) {
            $anzBackup = $GLOBALS['BACKUPDB']['AutoBackupCount'];
        }
        if( isset( $GLOBALS['TL_CONFIG']['AutoBackupCount'] ) && is_int($GLOBALS['TL_CONFIG']['AutoBackupCount']) ) {
            $anzBackup = $GLOBALS['TL_CONFIG']['AutoBackupCount'];
        }

        if( file_exists( $pfad . '/AutoBackupDB-' . $anzBackup . $ext ) ) {
            unlink( $pfad . '/AutoBackupDB-' . $anzBackup . $ext );
        }
        for( ; $anzBackup > 1; $anzBackup-- ) {
            if( file_exists( $pfad . '/AutoBackupDB-' . ($anzBackup-1) . $ext ) ) {
                rename( $pfad . '/AutoBackupDB-' . ($anzBackup-1) . $ext, $pfad . '/AutoBackupDB-' . $anzBackup . $ext );
            }
        }

        //--- wenn alte Backupdatei existiert: löschen ---
        if( file_exists( $pfad . '/AutoBackupDB-1.sql' ) ) {
            unlink( $pfad . '/AutoBackupDB-1.sql' );
        }

        //--- neue Datei AutoBackupDB-1.sql ---
        $datei = new \File( $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB/AutoBackupDB-1.sql' );
        $from = defined( 'DIRECT_CALL' ) ? 'Saved            : by direct call from IP ' . $this->Environment->ip : 'Saved by Cron';
        $datei->write( BackupDbCommon::getHeaderInfo( true, $from ) );

        $arrBlacklist = BackupDbCommon::get_blacklist( );
        $sqlarray = BackupDbCommon::getFromDB( );

        if( count($sqlarray) == 0 ) {
            $datei->write( 'No tables found in database.' );
        }
        else {
            foreach( array_keys($sqlarray) as $table ) {
                $datei->write( BackupDbCommon::get_table_structure( $table, $sqlarray[$table] ) );

                if( in_array( $table, $arrBlacklist ) ) continue;      // Blacklisten-Tabellen speichern nur Struktur, keine Daten -> continue
                BackupDbCommon::get_table_content( $table, $datei );   // Dateninhalte in Datei schreiben
            }
        }
        $datei->write( "\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n"
                     . "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n"
                     . "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\r\n"
                     . "\r\n# --- End of Backup ---\r\n" );           // Endekennung
        $datei->close();

        echo 'End of Backup<br>';

        //--- Wenn Komprimierung gewünscht, ZIP erstellen ---
        if( $ext === '.zip' ) {
            $objZip = new \ZipWriter( $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB/AutoBackupDB-1.zip' );
            $objZip->addFile( $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB/AutoBackupDB-1.sql' );
            $objZip->addFile( 'composer.json' );
            $objZip->addFile( 'composer.lock' );
            $objZip->addString( BackupDbCommon::get_symlinks(), 'restoreSymlinks.php', time() );    // Symlink-Recovery
            $objZip->close();
            unlink( $pfad . '/AutoBackupDB-1.sql' );
        }
        $objFile = \Dbafs::addResource( $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB/AutoBackupDB-1' . $ext );    // Datei in der Dateiverwaltung eintragen

        //--- Mail-Benachrichtigung ---
        if( isset( $GLOBALS['TL_CONFIG']['backupdb_sendmail'] ) && ($GLOBALS['TL_CONFIG']['backupdb_sendmail'] == true) ) {
            $objEmail = new \Email();
            $objEmail->from = $GLOBALS['TL_CONFIG']['adminEmail'];
            $objEmail->subject = 'AutoBackupDB ' . \Environment::get('host') . ' (' . $GLOBALS['TL_CONFIG']['websiteTitle'] . ')';
            $objEmail->text = BackupDbCommon::getHeaderInfo( false, $from );

            if( isset( $GLOBALS['TL_CONFIG']['backupdb_attmail'] ) && ($GLOBALS['TL_CONFIG']['backupdb_attmail'] == true) ) {
                $objEmail->attachFile( $pfad . '/AutoBackupDB-1' . $ext, 'application/octet-stream' );
            }

            $objEmail->sendTo( $GLOBALS['TL_CONFIG']['adminEmail'] );
        }

        $datei = new \File( $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB/' . BACKUPDB_CRON_LAST );
        $datei->write( date($GLOBALS['TL_CONFIG']['datimFormat']) );
        $datei->close();

        // Update the hash of the target folder
        $objFile = \Dbafs::addResource( $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB/' . BACKUPDB_CRON_LAST );    // Datei in der Dateiverwaltung eintragen
        \Dbafs::updateFolderHashes( $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB/' );
    }

}

//-------------------------------------------------------------------
//  Programmstart
//-------------------------------------------------------------------
$objBackupDB = new AutoBackupDB( );
$objBackupDB->run( );

//-------------------------------------------------------------------
