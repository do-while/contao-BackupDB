<?php

/**
 * @copyright  Softleister 2007-2024
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

namespace Softleister\BackupDB;

use Contao\Backend;
use Contao\Dbafs;
use Contao\ZipWriter;
use Contao\System;
use Contao\File;
use Contao\BackendUser;
use Softleister\BackupDB\BackupDbCommon;

/**
 * Class BackupDbRun
 */
class BackupDbRun extends Backend
{
    //-------------------------
    //  Constructor
    //-------------------------
    public function __construct( )
    {
        parent::__construct();                      	// Construktor Backend ausführen
        // BackendUser::authenticate();                    // Authentifizierung überprüfen

        $user = System::getContainer()->get('security.helper')->getUser();
        if( !$user instanceof BackendUser ) {
            return;
        }
    }

    //-------------------------
    //  Backup ausführen
    //-------------------------
    public static function run( )
    {
        @set_time_limit( 600 );
        $user = BackendUser::getInstance();
        $rootdir = System::getContainer()->getParameter('kernel.project_dir');

        $filepath = System::getContainer()->getParameter('contao.upload_path') . '/AutoBackupDB';
        $pfad = $rootdir . '/' . $filepath;
        if( file_exists( $pfad . '/' . BACKUPDB_RUN_LAST ) ) {
            unlink( $pfad . '/' . BACKUPDB_RUN_LAST );                  // LastRun-Datei löschen
        }

        //--- Datei-Extension festlegen ---
        $ext = '';
        if( isset( $GLOBALS['TL_CONFIG']['backupdb_zip'] ) && ($GLOBALS['TL_CONFIG']['backupdb_zip'] == true) ) {
            $ext = '.zip';
        }

        $tmpdatei = new File( $filepath . '/Database_' . System::getContainer()->get('database_connection')->getParams()['dbname'] . '_' . date('Y-m-d') . '_' . date('His') . '.sql' );        // temporäre Datei erstellen
        $tmpdatei->write( BackupDbCommon::getHeaderInfo( true, 'Saved by User    : ' . $user->username . ' (' . $user->name . ')' ) );

        $arrBlacklist = BackupDbCommon::get_blacklist( );
        $sqlarray = BackupDbCommon::getFromDB( );

        if( count($sqlarray) == 0 ) {
            $tmpdatei->write( 'No tables found in database.' );
        }
        else {
            foreach( array_keys($sqlarray) as $table ) {
                $struktur = BackupDbCommon::get_table_structure( $table, $sqlarray[$table] );
                if( empty( $struktur ) ) continue;                          // keine Tabellenstruktur vorhanden: nächste Tabelle listen

                $tmpdatei->write( $struktur );
                if( in_array( $table, $arrBlacklist ) ) continue;           // Blacklisten-Tabellen speichern nur Struktur, keine Daten -> continue
                BackupDbCommon::get_table_content( $table, $tmpdatei );     // Dateninhalte ausgeben
            }
        }

        $tmpdatei->write( "\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n"
                        . "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n"
                        . "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\r\n"
                        . "SET autocommit = 1;\r\n"
                        . "\r\n# --- End of Backup ---\r\n" );                            // Endekennung
        $tmpdatei->close();
        
        //--- Wenn Komprimierung gewünscht, ZIP erstellen ---
        if( $ext === '.zip' ) {
            $objZip = new ZipWriter( $filepath . '/' . $tmpdatei->name . $ext );
            $objZip->addFile( $filepath . '/' . $tmpdatei->name, $tmpdatei->name );
            $objZip->addFile( 'composer.json' );
            $objZip->addFile( 'composer.lock' );
            $objZip->addFile( 'system/config/localconfig.php', 'localconfig.php' );
            if( file_exists( $rootdir . '/config/config.yml' ) ) $objZip->addFile( 'config/config.yml', 'config.yml' );
            if( file_exists( $rootdir . '/config/parameters.yml' ) ) $objZip->addFile( 'config/parameters.yml', 'parameters.yml' );
            if( file_exists( $rootdir . '/.env' ) ) $objZip->addFile( '.env', '.env' );
            if( file_exists( $rootdir . '/.env.local' ) ) $objZip->addFile( '.env.local', '.env.local' );
            $objZip->addString( BackupDbCommon::get_symlinks(), 'restoreSymlinks.php', time() );    // Symlink-Recovery
            $objZip->close();
        }
        // Timestamp-Datei erstellen
        $datei = new File( System::getContainer()->getParameter('contao.upload_path') . '/AutoBackupDB/' . BACKUPDB_RUN_LAST );
        $datei->write( date($GLOBALS['TL_CONFIG']['datimFormat']) );
        $datei->close();

        // Update the hash of the target folder
        $objFile = Dbafs::addResource( System::getContainer()->getParameter('contao.upload_path') . '/AutoBackupDB/' . BACKUPDB_RUN_LAST );    // Datei in der Dateiverwaltung eintragen
        Dbafs::updateFolderHashes( System::getContainer()->getParameter('contao.upload_path') . '/AutoBackupDB/' );

        //=== Ausgabe der temporären Datei ===
        header( 'Pragma: public' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Cache-Control: private', false );
        header( 'Content-type: application/' . ($ext === '' ? 'octet-stream' : 'zip') );
        header( 'Content-disposition: attachment; filename=' . $tmpdatei->name . $ext );
        header( 'Content-Transfer-Encoding: binary' );

        echo file_get_contents( $rootdir . '/' . $tmpdatei->path . $ext );       // Ausgabe als Download

        if( $ext === '.zip' ) {
            unlink( $rootdir . '/' . $tmpdatei->path . $ext );                   // ZIP-Datei löschen
        }
        $tmpdatei->delete( );
    }
}
