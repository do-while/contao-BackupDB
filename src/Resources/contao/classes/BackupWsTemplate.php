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
use Contao\File;
use Contao\StringUtil;
use Contao\BackendUser;
use Contao\Environment;
use Contao\System;
use Softleister\BackupDB\BackupDbCommon;


//-------------------------------------------------------------------
//  Backend um die WsTemplate-Funktionen erweitern
//-------------------------------------------------------------------
class BackupWsTemplate extends Backend
{
    //-------------------------
    //  Constructor
    //-------------------------
    public function __construct( )
    {
        parent::__construct();                      // Construktor Backend ausführen
        // BackendUser::authenticate();                // Authentifizierung überprüfen

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
        $rootdir = System::getContainer()->getParameter('kernel.project_dir');      // TL_ROOT
        System::loadLanguageFile('tl_backupdb');                                    // Modultexte laden

        $user     = BackendUser::getInstance();                                     // Backend-User

        $filename = Environment::get('host');                                                                   // Dateiname = Domainname
        if( isset($GLOBALS['TL_CONFIG']['websiteTitle']) ) $filename = $GLOBALS['TL_CONFIG']['websiteTitle'];   // IF( Exiat WbsiteTitle ) Dateiname für Template-Dateien
        $filename = StringUtil::generateAlias( $filename );                                                     // Dateiname = Alias für Template-Dateien

        $arrExclude = Array (                       // Diese Datenbank-Tabellen gehören nicht in ein WS-Template
                                'tl_cache',
                                'tl_cron',
                                'tl_lock',
                                'tl_log',
                                'tl_runonce',
                                'tl_search',
                                'tl_search_index',
                                'tl_session',
                                'tl_undo',
                                'tl_version'
                            );

        $arrResults = array();

        $headertext  = "#================================================================================\r\n";
        $headertext .= "# Website-Template : " . $filename . ".sql\r\n";
        $headertext .= BackupDbCommon::getHeaderInfo( false, 'Saved by User    : ' . $user->username . ' (' . $user->name . ')' );

        //--- Zielverzeichnis für Website-Templates ---
        $zielVerz = 'templates';
        if( isset( $GLOBALS['BACKUPDB']['WsTemplatePath'] ) && is_dir($rootdir . '/' . trim($GLOBALS['BACKUPDB']['WsTemplatePath'], '/')) ) {
            $zielVerz = trim($GLOBALS['BACKUPDB']['WsTemplatePath'], '/');
        }
        if( isset( $GLOBALS['TL_CONFIG']['WsTemplatePath'] ) && is_dir($rootdir . '/' . trim($GLOBALS['TL_CONFIG']['WsTemplatePath'], '/')) && !empty(trim($GLOBALS['TL_CONFIG']['WsTemplatePath'])) ) {
            $zielVerz = trim($GLOBALS['TL_CONFIG']['WsTemplatePath'], '/');
        }

        $tempdir = '/system/tmp/';                  // temporäre Datei anlegen, dann wird das vorige Template nur überschrieben, wenn die runtime ausreicht
        $fileSQL = $filename . '.sql';              // Datenbank-Datei
        $fileTXT = $filename . '.txt';              // Info-Datei
        $fileSTR = $filename . '.structure';        // Struktur-Datei

        $datei = new File( $tempdir . $fileSQL );
        $datei->write( $headertext );
        $datei->write( 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";' . "\r\n"
                     . "\r\n"
                     . "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n"
                     . "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n"
                     . "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n"
                     . "/*!40101 SET NAMES utf8 */;\r\n" );

        $arrSavedDB = array( 'tl_' );               // Default, Verhalten wie in den Vorversionen von BackupDB
        if( isset( $GLOBALS['TL_CONFIG']['backupdb_saveddb'] ) && !empty(trim($GLOBALS['TL_CONFIG']['backupdb_saveddb'])) ) {
            $arrSavedDB = StringUtil::trimsplit( ',', strtolower($GLOBALS['TL_CONFIG']['backupdb_saveddb']) );
        }

        $arrBlacklist = BackupDbCommon::get_blacklist( );
        $sqlarray = BackupDbCommon::getFromDB( );

        $arrEntries = array( );
        if( count($sqlarray) == 0 ) {
            $datei->write( "\r\nNo tables found in database." );
        }
        else {
            foreach( array_keys($sqlarray) as $table ) {
                if( in_array( $table, $arrExclude ) ) continue;             // Exclude-Tabellen überspringen
                if( in_array( $table, $arrBlacklist ) ) continue;           // Blacklisten-Tabellen überspringen

                $found = false;
                for( $i=0; $i<count($arrSavedDB); $i++ ) {
                    if( (strlen($arrSavedDB[$i]) <= strlen($table)) && ($arrSavedDB[$i] === substr($table, 0, strlen($arrSavedDB[$i]))) ) {
                        $found = true;
                    }
                }
                if( !$found ) continue;                                     // nur die angegebenen Datentabellen sichern

                $arrEntries[] = BackupDbCommon::get_table_content( $table, $datei, true );  // Dateninhalte in Datei schreiben
            }
        }
        $arrResults['entries'] = $arrEntries;

        $datei->write( "\r\nSET autocommit = 1;\r\n\r\n# --- End of Backup ---" );      // Endekennung
        $datei->close();

        $datei = new File( $tempdir . $fileSTR );                   // Strukturdatei öffnen
        $datei->write( BackupDbCommon::getHeaderInfo( true, 'Saved by User    : ' . $user->username . ' (' . $user->name . ')' ));

        $sqlarray = BackupDbCommon::getFromDB( );
        if( count($sqlarray) == 0 ) {
            $datei->write( 'No tables found in database.' );
        }
        else {
            foreach( array_keys($sqlarray) as $table ) {
                $datei->write( BackupDbCommon::get_table_structure($table, $sqlarray[$table]) );
            }
        }
        $datei->write( "\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n"
                      ."/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n"
                      ."/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\r\n"
                      . "SET autocommit = 1;\r\n"                         // Endekennung
                      ."\r\n# --- End of Backup ---\r\n" );                            // Endekennung
        $datei->close();

        $datei = new File( $tempdir . $fileTXT );                   // Textdatei öffnen
        $datei->write( $headertext );
        $datei->close();

        //--- alte Dateien löschen, neue ans Ziel verschieben ---
        if( file_exists($rootdir . '/' . $zielVerz . '/' . $fileSQL) ) {     // Delete old files if exist
            unlink($rootdir . '/' . $zielVerz . '/' . $fileSQL);
        }
        if( file_exists($rootdir . '/' . $zielVerz . '/' . $fileSTR) ) {
            unlink($rootdir . '/' . $zielVerz . '/' . $fileSTR);
        }
        if( file_exists($rootdir . '/' . $zielVerz . '/' . $fileTXT) ) {
            unlink($rootdir . '/' . $zielVerz . '/' . $fileTXT);
        }
        rename( $rootdir . $tempdir . $fileSQL, $rootdir . '/' . $zielVerz . '/' . $fileSQL );        // Move new files
        rename( $rootdir . $tempdir . $fileSTR, $rootdir . '/' . $zielVerz . '/' . $fileSTR );
        rename( $rootdir . $tempdir . $fileTXT, $rootdir . '/' . $zielVerz . '/' . $fileTXT );

        //--- Ergebnisausgabe ---
        $arrResults['header']['text']    = $GLOBALS['TL_LANG']['tl_backupdb']['tplhead'];
        $arrResults['entry']['text']     = array( $GLOBALS['TL_LANG']['tl_backupdb']['tplentry'], $GLOBALS['TL_LANG']['tl_backupdb']['tplentriesd'] );

        $arrResults['footer']['result']  = $GLOBALS['TL_LANG']['tl_backupdb']['tplresult'];
        $arrResults['footer']['legend']  = $GLOBALS['TL_LANG']['tl_backupdb']['tpllegend'];
        $arrResults['footer']['fileSQL'] = '/' . $zielVerz . '/' . $fileSQL;
        $arrResults['footer']['fileTXT'] = '/' . $zielVerz . '/' . $fileTXT;
        $arrResults['footer']['fileSTR'] = '/' . $zielVerz . '/' . $fileSTR;
        $arrResults['footer']['text']    = $GLOBALS['TL_LANG']['tl_backupdb']['tplfooter'];
        $arrResults['footer']['button']  = $GLOBALS['TL_LANG']['tl_backupdb']['tplbutton'];

        return $arrResults;
    }

}
