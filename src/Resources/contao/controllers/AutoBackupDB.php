<?php

/**
 * @copyright  Softleister 2007-2024
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see        https://github.com/do-while/contao-BackupDB
 */

namespace Softleister\BackupDB;

use Contao\File;
use Contao\Dbafs;
use Contao\Email;
use Contao\Input;
use Contao\Config;
use Contao\System;
use Contao\ZipWriter;
use Contao\Environment;
use Softleister\BackupDB\BackupDbCommon;
use Symfony\Component\HttpFoundation\Response;

//-------------------------------------------------------------------
// AutoBackupDB.php Backup Contao-Datenbank mittels Cron-Job
//
// Copyright (c) 2007-2021 by Softleister
//-------------------------------------------------------------------

class AutoBackupDb
{
    //-------------------------
    //  Backup ausführen
    //-------------------------
    public function run( )
    {
        // Spamming-Schutz
        if( !empty( Config::get('backupdb_var') ) ) {
            if( Input::get( Config::get('backupdb_var') ) === null ) {
                die( 'You cannot access this file directly!' );             // Variable nicht vorhanden => NULL
            }                                                               // Variable leer            => ''
        }

        @set_time_limit( 600 );
        $uploadPath = System::getContainer()->getParameter('contao.upload_path');
        $rootdir = System::getContainer()->getParameter('kernel.project_dir');

        //--- alten Zeitstempel löschen ---
        $pfad = System::getContainer()->getParameter('kernel.project_dir') . '/' . $uploadPath . '/AutoBackupDB';
        if( file_exists( $pfad . '/' . BACKUPDB_CRON_LAST ) ) {
            unlink( $pfad . '/' . BACKUPDB_CRON_LAST );             // LastRun-Datei löschen
        }

        $result = 'Starting BackupDB ...<br>';

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
        $datei = new File( $uploadPath . '/AutoBackupDB/AutoBackupDB-1.sql' );
        $from = defined( 'DIRECT_CALL' ) ? 'Saved            : by direct call from IP ' . Environment::get('ip') : 'Saved by Cron';
        $datei->write( BackupDbCommon::getHeaderInfo( true, $from ) );

        $arrBlacklist = BackupDbCommon::get_blacklist( );
        $sqlarray = BackupDbCommon::getFromDB( );

        if( count($sqlarray) == 0 ) {
            $datei->write( 'No tables found in database.' );
        }
        else {
            foreach( array_keys($sqlarray) as $table ) {
                $struktur = BackupDbCommon::get_table_structure( $table, $sqlarray[$table] );
                if( empty( $struktur ) ) continue;                          // keine Tabellenstruktur vorhanden: nächste Tabelle listen

                $datei->write( $struktur );
                if( in_array( $table, $arrBlacklist ) ) continue;           // Blacklisten-Tabellen speichern nur Struktur, keine Daten -> continue
                BackupDbCommon::get_table_content( $table, $datei );        // Dateninhalte in Datei schreiben
            }
        }
        $datei->write( "\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n"
                     . "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n"
                     . "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\r\n"
                     . "SET autocommit = 1;\r\n"
                     . "\r\n# --- End of Backup ---\r\n" );                 // Endekennung
        $datei->close();

        $result .= 'End of Backup<br>';

        //--- Wenn Komprimierung gewünscht, ZIP erstellen ---
        if( $ext === '.zip' ) {
            $objZip = new ZipWriter( $uploadPath . '/AutoBackupDB/AutoBackupDB-1.zip' );
            $objZip->addFile( $uploadPath . '/AutoBackupDB/AutoBackupDB-1.sql', 'AutoBackupDB-1.sql' );
            $objZip->addFile( 'composer.json' );
            $objZip->addFile( 'composer.lock' );
            $objZip->addFile( 'system/config/localconfig.php', 'localconfig.php' );
            if( file_exists( $rootdir . '/config/config.yml' ) ) $objZip->addFile( 'config/config.yml', 'config.yml' );
            if( file_exists( $rootdir . '/config/parameters.yml' ) ) $objZip->addFile( 'config/parameters.yml', 'parameters.yml' );
            if( file_exists( $rootdir . '/.env' ) ) $objZip->addFile( '.env', '.env' );
            if( file_exists( $rootdir . '/.env.local' ) ) $objZip->addFile( '.env.local', '.env.local' );
            $objZip->addString( BackupDbCommon::get_symlinks(), 'restoreSymlinks.php', time() );    // Symlink-Recovery
            $objZip->close();
            unlink( $pfad . '/AutoBackupDB-1.sql' );
        }
        $objFile = Dbafs::addResource( $uploadPath . '/AutoBackupDB/AutoBackupDB-1' . $ext );    // Datei in der Dateiverwaltung eintragen

        //--- Mail-Benachrichtigung ---
        if( isset( $GLOBALS['TL_CONFIG']['backupdb_sendmail'] ) && ($GLOBALS['TL_CONFIG']['backupdb_sendmail'] == true) ) {
            $objEmail = new Email();
            $objEmail->from = $GLOBALS['TL_CONFIG']['adminEmail'];
            $objEmail->subject = 'AutoBackupDB ' . Environment::get('host');
            $objEmail->text = BackupDbCommon::getHeaderInfo( false, $from );

            if( isset( $GLOBALS['TL_CONFIG']['backupdb_attmail'] ) && ($GLOBALS['TL_CONFIG']['backupdb_attmail'] == true) ) {
                $objEmail->attachFile( $pfad . '/AutoBackupDB-1' . $ext, 'application/octet-stream' );
            }

            $objEmail->sendTo( $GLOBALS['TL_CONFIG']['adminEmail'] );
        }

        $datei = new File( $uploadPath . '/AutoBackupDB/' . BACKUPDB_CRON_LAST );
        $datei->write( date($GLOBALS['TL_CONFIG']['datimFormat']) );
        $datei->close();

        // Update the hash of the target folder
        $objFile = Dbafs::addResource( $uploadPath . '/AutoBackupDB/' . BACKUPDB_CRON_LAST );    // Datei in der Dateiverwaltung eintragen
        Dbafs::updateFolderHashes( $uploadPath . '/AutoBackupDB/' );

        return new Response( $result );
    }
}

//-------------------------------------------------------------------
