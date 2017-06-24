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


/**
 * Class BackupDbRun
 */
class BackupDbRun extends \Backend
{
    //-------------------------
    //  Constructor
    //-------------------------
    public function __construct( )
    {
        parent::__construct();                      // Construktor Backend ausführen
        \BackendUser::authenticate();               // Authentifizierung überprüfen
    }

    //-------------------------
    //  Backup ausführen
    //-------------------------
    public function run( )
    {
        @set_time_limit( 600 );
        $user = \BackendUser::getInstance();
        
        $pfad = TL_ROOT . '/' . $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB';
        if( file_exists( $pfad . '/' . BACKUPDB_RUN_LAST ) ) {
            unlink( $pfad . '/' . BACKUPDB_RUN_LAST );          // LastRun-Datei löschen
        }

        header( 'Pragma: public' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Cache-Control: private', false );
        header( 'Content-type: application/octet-stream' );
        header( 'Content-disposition: attachment; filename=Database_' . $GLOBALS['TL_CONFIG']['dbDatabase'] . '_' . date('Y-m-d') . '_' . date('His') . '.sql' );
        header( 'Content-Transfer-Encoding: binary' );

        echo BackupDbCommon::getHeaderInfo( true, 'Saved by User    : ' . $user->username . ' (' . $user->name . ')' );
        $arrBlacklist = BackupDbCommon::get_blacklist( );
        $sqlarray = BackupDbCommon::getFromDB( );

        if( count($sqlarray) == 0 ) {
            print "No tables found in database.";
        }
        else {
            foreach( array_keys($sqlarray) as $table ) {
                echo BackupDbCommon::get_table_structure( $table, $sqlarray[$table] );

                if( !in_array( $table, $arrBlacklist ) ) {              // Blacklisten-Tabellen speichern nur Struktur, keine Daten -> continue
                    BackupDbCommon::get_table_content( $table );        // Dateninhalte ausgeben
                }
            }
        }
        echo "\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n"
           . "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n"
           . "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\r\n"
           . "\r\n# --- End of Backup ---\r\n";                            // Endekennung

        $datei = new \File( $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB/' . BACKUPDB_RUN_LAST );
        $datei->write( date($GLOBALS['TL_CONFIG']['datimFormat']) );
        $datei->close();

		// Update the hash of the target folder
        $objFile = \Dbafs::addResource( $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB/' . BACKUPDB_RUN_LAST );    // Datei in der Dateiverwaltung eintragen
        \Dbafs::updateFolderHashes($strUploadFolder);
    }

}
