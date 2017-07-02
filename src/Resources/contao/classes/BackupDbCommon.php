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


//-------------------------------------------------------------------
//  Backup Contao-Datenbank
//-------------------------------------------------------------------
class BackupDbCommon extends \Backend
{
    //---------------------------------------
    // Extension-Versions-Info
    //---------------------------------------
    public static function getVersionInfo( $ver )
    {
        $version = floor( $ver / 10000000 ) . '.';              // Hauptversion
        $version .= floor(($ver % 10000000) / 10000) . '.';     // Subversion
        $version .= floor(($ver % 10000) / 10 );                // Build
        switch( $ver % 10 ) {
            case 0: $version .= ' alpha1';  break;
            case 1: $version .= ' alpha2';  break;
            case 2: $version .= ' alpha3';  break;
            case 3: $version .= ' beta1';   break;
            case 4: $version .= ' beta2';   break;
            case 5: $version .= ' beta3';   break;
            case 6: $version .= ' rc1';     break;
            case 7: $version .= ' rc2';     break;
            case 8: $version .= ' rc3';     break;
            case 9: $version .= ' stable';  break;
        }

        return $version;
    }


    //---------------------------------------
    // Extension-Versions-Info
    //---------------------------------------
    public static function getHeaderInfo( $sql_mode, $savedby = 'Saved by Cron' )
    {
        $objDB = \Database::getInstance();

        $arrExtcludeExt30 = array (         // Module aus der Standard-Installation >= 3.0
                                "calendar", "comments", "core", "devtools", "faq", "listing",
                                "news", "newsletter", "repository", "BackupDB"
                            );
        $instExt = array();

        $result = "#================================================================================\r\n"
                . "# Contao-Website   : " . $GLOBALS['TL_CONFIG']['websiteTitle'] . "\r\n"
                . "# Contao-Database  : " . $GLOBALS['TL_CONFIG']['dbDatabase'] . "\r\n"
                . "# " . $savedby . "\r\n"
                . "# Time stamp       : " . date( "Y-m-d" ) . " at " . date( "H:i:s" ) . "\r\n"
                . "#\r\n"
                . "# Contao Extension : BackupDB, Version " . BACKUPDB_VERSION . '.' . BACKUPDB_BUILD . "\r\n"
                . "# Copyright        : Softleister (www.softleister.de)\r\n"
                . "# Licence          : LGPL\r\n"
                . "#\r\n"
                . "# Visit Contao project page https://contao.org for more information\r\n"
                . "#\r\n"

                //--- Installierte Module unter /system/modules auflisten ---
                . "#-----------------------------------------------------\r\n"
                . "# Contao Version " . VERSION . "." . BUILD . "\r\n"
                . "# The following modules must be installed:\r\n"
                . "#-----------------------------------------------------\r\n";

        //--- über ER2 installierte Erweiterungen ---
        if( $objDB->tableExists('tl_repository_installs') ) {

            $sql = "SELECT i.extension, i.version, i.build, f.filename FROM tl_repository_installs as i, tl_repository_instfiles as f WHERE i.id=f.pid AND LEFT(f.filename,15)='system/modules/' GROUP BY i.extension";
            $objData = $objDB->executeUncached( $sql );

            while( $objData->next() ) {
                if( $objData->extension === 'BackupDB' ) continue;          // BackupDB für Restore nicht notwendig

                $result .= '#   - ' . sprintf('%-28s', $objData->extension) . ': Version ' . BackupDbCommon::getVersionInfo( $objData->version ) . ', Build ' . $objData->build . "\r\n";
                $arrfile = explode('/', $objData->filename );
                $instExt[] = strtolower( $arrfile[2] );
            }
        }

        $modullist = '';
        $handle = opendir( TL_ROOT . '/system/modules' );
        while( ($file = readdir( $handle )) !== false ) {
            if( substr( $file, 0, 1 ) == "." ) continue;                                // . und .. ausblenden

            if( isset($instExt) && in_array( strtolower($file), $instExt ) ) continue;  // keine Files, die schon im Repository stehen
            if( in_array($file, $arrExtcludeExt30) ) continue;                          // Core-Erweiterungen ausblenden

            $modullist .= "#   - $file\r\n";
        }
        closedir( $handle );

        if( $modullist != '' ) {
            $result .= $modullist;
        }
        else {
            if( !count($instExt) ) {
                $result .= "#   == none ==\r\n";
            }
        }

        $result .= "#\r\n"
                . "#================================================================================\r\n"
                . "\r\n";
        if( $sql_mode ) {
            $result .= 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";' . "\r\n"
                    . 'SET time_zone = "+00:00";' . "\r\n"
                    . "\r\n"
                    . "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n"
                    . "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n"
                    . "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n"
                    . "/*!40101 SET NAMES utf8 */;\r\n";
        }

        return $result;
    }


    //------------------------------------------------
    //  Erzeugt die Strukturdefinitionen
    //------------------------------------------------
    public static function getFromDB( )
    {
        $result = array();
        $objDB = \Database::getInstance( );

        $tables = $objDB->listTables( null, true );
        if( empty($tables) ) {
            return $result;
        }

        foreach( $tables as $table ) {
            $keys = array();
            $fields = $objDB->listFields($table, true);

            foreach( $fields as $field ) {
                $name = $field['name'];
                $field['name'] = '`' . $field['name'] . '`';

                if( in_array(strtolower($field['type']), array('text', 'tinytext', 'mediumtext', 'longtext')) && isset($fields[$name]) ) {
                    $keys[$name] = 'FULLTEXT ';
                }

                //--- Tabellenspalten definieren ---
                if( $field['type'] != 'index' ) {
                    unset($field['index']);

                    // Field type
                    if( isset($field['length']) && ($field['length'] != '') ) {
                        $field['type'] .= '(' . $field['length'] . ((isset($field['precision']) && $field['precision'] != '') ? ',' . $field['precision'] : '') . ')';

                        unset( $field['length'] );
                        unset( $field['precision'] );
                    }

                    // Variant collation
                    if( ($field['collation'] != '') && ($field['collation'] != $GLOBALS['TL_CONFIG']['dbCollation']) ) {
                        $field['collation'] = 'COLLATE ' . $field['collation'];
                    }
                    else {
                        unset( $field['collation'] );
                    }

                    // Default values
                    if( in_array(strtolower($field['type']), array('text', 'tinytext', 'mediumtext', 'longtext', 'blob', 'tinyblob', 'mediumblob', 'longblob')) || stristr($field['extra'], 'auto_increment') || ($field['default'] === null) || (strtolower($field['null']) == 'null') ) {
                        unset( $field['default'] );
                    }
                    // Date/time constants (see #5089)
                    else if( in_array(strtolower($field['default']), array('current_date', 'current_time', 'current_timestamp')) ) {
                        $field['default'] = "default " . $field['default'];
                    }
                    // Everything else
                    else {
                        $field['default'] = "default '" . $field['default'] . "'";
                    }

                    unset( $field['origtype'] );
                    $result[$table]['TABLE_FIELDS'][$name] = trim( implode( ' ', $field ) );
                }

                //--- Index-Einträge ---
                if( isset($field['index']) && !empty($field['index_fields']) ) {
                    $index_fields = implode( '`, `', $field['index_fields'] );

                    switch( $field['index'] ) {
                        case 'UNIQUE':  if( $name == 'PRIMARY' ) {
                                            $result[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'PRIMARY KEY  (`'.$index_fields.'`)';
                                        }
                                        else {
                                            $result[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'UNIQUE KEY `'.$name.'` (`'.$index_fields.'`)';
                                        }
                                        break;

                        default:        $result[$table]['TABLE_CREATE_DEFINITIONS'][$name] = (isset($keys[$name]) ? $keys[$name] : '') . 'KEY `'.$name.'` (`'.$index_fields.'`)';
                                        break;
                    }
                    unset( $field['index_fields'] );
                    unset( $field['index'] );
                }
            }
        }

        // Table status
        $objStatus = $objDB->execute( 'SHOW TABLE STATUS' );
        while( $zeile = $objStatus->fetchAssoc() ) {
            $result[$zeile['Name']]['TABLE_OPTIONS'] = ' ENGINE=' . $zeile['Engine'] . ' DEFAULT CHARSET=' . substr($zeile['Collation'], 0, strpos($zeile['Collation'],"_"));

            if( $zeile['Auto_increment'] != '' ) {
                $result[$zeile['Name']]['TABLE_OPTIONS'] .= ' AUTO_INCREMENT=' . $zeile['Auto_increment'];
            }
        }

        return $result;
    }


    //---------------------------------------
    // Erzeut Struktur der Datentabelle
    //---------------------------------------
    public static function get_table_structure( $table, $tablespec )
    {
        $result = "\r\n"
                . "#---------------------------------------------------------\r\n"
                . "# Table structure for table '$table'\r\n"
                . "#---------------------------------------------------------\r\n";

        $result .= "CREATE TABLE `" . $table . "` (\n  " . implode(",\n  ", $tablespec['TABLE_FIELDS']) . (count($tablespec['TABLE_CREATE_DEFINITIONS']) ? ',' : '') . "\n";

        if( is_array( $tablespec['TABLE_CREATE_DEFINITIONS'] ) ) {                     // Bugfix 29.3.2009 Softleister
            $result .= "  " . implode( ",\n  ", $tablespec['TABLE_CREATE_DEFINITIONS'] ) . "\n";
        }
        $result .= ")" . $tablespec['TABLE_OPTIONS'] . ";\r\n\r\n";

        return $result;
    }


    //------------------------------------------------
    //  Erzeut INSERT-Zeilen mit den Datenbankdaten
    //------------------------------------------------
    public static function get_table_content( $table, $datei=NULL, $sitetemplate=false )
    {
        $objDB = \Database::getInstance();

        $objData = $objDB->executeUncached( "SELECT * FROM $table" );

        $fields = $objDB->listFields( $table );                  // Liste der Felder lesen
        $fieldlist = '';
        $arrEntry = array( );
        if( $sitetemplate ) {
            $fieldlist = ' (';
            foreach( $fields as $field ) {
                if( $field['type'] != 'index' ) {
                    $fieldlist .= '`' . $field['name'] . '`, ';
                }
            }
            $fieldlist = substr( $fieldlist, 0, -2 ) . ')';         // letztes ", " abschneiden

            $arrEntry = array( $table, $objData->numRows );
        }

        $noentries = $objData->numRows ? '' : ' - no entries';
        if( $datei == NULL ) {
            echo "\r\n"
               . "#\r\n"
               . "# Dumping data for table '$table'" . $noentries . "\r\n"
               . "#\r\n\r\n";
        }
        else {
            $datei->write( "\r\n"
                         . "#\r\n"
                         . "# Dumping data for table '$table'" . $noentries . "\r\n"
                         . "#\r\n\r\n" );
        }

        while( $row = $objData->fetchRow() ) {
            $insert_data = 'INSERT INTO `' . $table . '`' . $fieldlist . ' VALUES (';
            $i = 0;                         // Fields[0]['type']
            foreach( $row as $field_data ) {
                if( !isset( $field_data ) ) {
                    $insert_data .= " NULL,";
                }
                else if( $field_data != "" ) {
                    switch( strtolower($fields[$i]['type']) ) {
                        case 'blob':
                        case 'binary':
                        case 'varbinary':
                        case 'tinyblob':
                        case 'mediumblob':
                        case 'longblob':    $insert_data .= " 0x";      // Auftackt für HEX-Darstellung
                                            $insert_data .= bin2hex($field_data);
                                            $insert_data .= ",";        // Abschuß
                                            break;

                        case 'smallint':
                        case 'int':         $insert_data .= " $field_data,";
                                            break;

                        case 'text':
                        case 'mediumtext':  if( strpos( $field_data, "'" ) != false ) {  // ist im Text ein Hochkomma vorhanden, wird der Text in HEX-Darstellung gesichert
                                                $insert_data .= " 0x" . bin2hex($field_data) . ",";
                                                break;
                                            }
                                            // else: weiter mit default

                        default:            $insert_data .= " '" . str_replace( array("\\", "'", "\r", "\n"), array("\\\\", "\\'", "\\r", "\\n"), $field_data )."',";
                                            break;
                    }
                }
                else
                    $insert_data .= " '',";

                $i++;                               // Next Field
            }
            $insert_data = trim( $insert_data, ',' );
            $insert_data .= " )";
            if( $datei == NULL ) {
                echo "$insert_data;\r\n";           // Zeile ausgeben
            }
            else {
                $datei->write( "$insert_data;\r\n" );
            }
        }

        return $arrEntry;
    }


    //------------------------------------------------
    //  get_blacklist: Liefert die eingestellte Blacklist zurück
    //------------------------------------------------
    public static function get_blacklist( )
    {
        $arrBlacklist = array();                // Default: alle Datentabellen werden gesichert

        if( isset( $GLOBALS['TL_CONFIG']['backupdb_blacklist'] ) && (trim($GLOBALS['TL_CONFIG']['backupdb_blacklist']) != '') ) {
            $arrBlacklist = trimsplit(',', strtolower($GLOBALS['TL_CONFIG']['backupdb_blacklist']));
        }

        return $arrBlacklist;
    }


    //---------------------------------------
}
