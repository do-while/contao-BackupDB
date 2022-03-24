<?php

/**
 * @copyright  Softleister 2007-2021
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Softleister\BackupDB;

use Softleister\BackupDB\ComposerPackages;


//-------------------------------------------------------------------
//  Backup Contao-Datenbank
//-------------------------------------------------------------------
class BackupDbCommon extends \Backend
{
    // Variable für Symlink-Array
    protected static $arrSymlinks;
    
    //Core-Module + backupdb herausnehmen
    protected static $arrExclude = ['contao/calendar-bundle',
                                    'contao/comments-bundle',
                                    'contao/faq-bundle',
                                    'contao/listing-bundle',
                                    'contao/manager-bundle',
                                    'contao/news-bundle',
                                    'contao/newsletter-bundle',
                                    'do-while/contao-backupdb-bundle'
                                   ];

    //---------------------------------------
    // Extension-Versions-Info
    //---------------------------------------
    public static function getHeaderInfo( $sql_mode, $savedby = 'Saved by Cron' )
    {
        $objDB = \Contao\Database::getInstance();

        $instExt = array();
        $bundles = array();

        $result = "#================================================================================\r\n"
                . "# Contao-Website   : " . (isset($GLOBALS['TL_CONFIG']['websiteTitle']) ? $GLOBALS['TL_CONFIG']['websiteTitle'] : \Contao\Environment::get('host')) . "\r\n"
                . "# Contao-Database  : " . $GLOBALS['TL_CONFIG']['dbDatabase'] . "\r\n"
                . "# " . $savedby . "\r\n"
                . "# Time stamp       : " . date( "Y-m-d" ) . " at " . date( "H:i:s" ) . "\r\n"
                . "#\r\n"
                . "# Contao Extension : BackupDbBundle, Version " . \Contao\System::getContainer()->getParameter('kernel.packages')['do-while/contao-backupdb-bundle'] . "\r\n"
                . "# Copyright        : Softleister (www.softleister.de)\r\n"
                . "# Licence          : LGPL\r\n"
                . "#\r\n"
                . "# Visit https://github.com/do-while/contao-BackupDB for more information\r\n"
                . "#\r\n"

                //--- Installierte Pakete auflisten ---
                . "#-----------------------------------------------------\r\n"
                . "# If you save the backup in ZIP file, a file restoreSymlinks.php\r\n"
                . "# is also in the ZIP. See the file for more information\r\n"
                . "#-----------------------------------------------------\r\n"
                . "# Contao Version " . VERSION . "." . BUILD . "\r\n"
                . "# The following packages must be installed:\r\n"
                . "#\r\n";

        //--- installierte Pakete ---
        $rootDir = \Contao\System::getContainer()->getParameter('kernel.project_dir');  // TL_ROOT
        $objComposerPackages = new ComposerPackages($rootDir);
        
        if (true === $objComposerPackages->parseComposerJson() &&
            true === $objComposerPackages->parseComposerLock()
           )
        {
            // $bundles: array('name' => 'version')
            $bundles = $objComposerPackages->getPackages(self::$arrExclude);
        }
        ksort( $bundles );                                                   // sortieren nach name (key)

        if( empty( $bundles ) ) {
          $result .= "#   == none ==\r\n";
        }
        else {
          foreach( $bundles as $ext => $ver ) 
              $result .= "#   - $ext : $ver\r\n";
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
        $objDB = \Contao\Database::getInstance( );

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
                        $field['type'] .= '(' . $field['length'] . ((isset($field['precision']) && !empty($field['precision'])) ? ',' . $field['precision'] : '') . ')';

                        unset( $field['length'] );
                        unset( $field['precision'] );
                    }

                    // Variant collation
                    if( !empty($field['collation']) && ($field['collation'] !== $GLOBALS['TL_CONFIG']['dbCollation']) ) {
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
                    $index_fields = '`' . implode( '`, `', $field['index_fields'] ) . '`';
                    $index_fields = str_replace(array('(', ')`'), array('`(', ')'), $index_fields );

                    switch( $field['index'] ) {
                        case 'UNIQUE':  if( $name === 'PRIMARY' ) {
                                            $result[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'PRIMARY KEY  ('.$index_fields.')';
                                        }
                                        else {
                                            $result[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'UNIQUE KEY `'.$name.'` ('.$index_fields.')';
                                        }
                                        break;

                        default:        $result[$table]['TABLE_CREATE_DEFINITIONS'][$name] = (isset($keys[$name]) ? $keys[$name] : '') . 'KEY `'.$name.'` ('.$index_fields.')';
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

            if( ($zeile['Engine'] === 'InnoDB') && ($zeile['Row_format'] === 'Dynamic') ) {
                $result[$zeile['Name']]['TABLE_OPTIONS'] .= ' COLLATE=' . $zeile['Collation'] . ' ROW_FORMAT=DYNAMIC';
            }

            if( !empty($zeile['Auto_increment']) ) {
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

        $result .= "CREATE TABLE `" . $table . "` (\n  " . implode(",\n  ", $tablespec['TABLE_FIELDS']) . (count($tablespec['TABLE_CREATE_DEFINITIONS'] ?? []) ? ',' : '') . "\n";

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
        $objDB = \Contao\Database::getInstance();

        $objData = $objDB->executeUncached( "SELECT * FROM $table" );

        $fields = $objDB->listFields( $table );                  // Liste der Felder lesen
        $fieldlist = '';
        $arrEntry = array( );
        if( $sitetemplate ) {
            $fieldlist = ' (';
            foreach( $fields as $field ) {
                if( $field['type'] !== 'index' ) {
                    $fieldlist .= '`' . $field['name'] . '`, ';
                }
            }
            $fieldlist = substr( $fieldlist, 0, -2 ) . ')';         // letztes ", " abschneiden

            $arrEntry = array( $table, $objData->numRows );
        }

        $noentries = $objData->numRows ? '' : ' - no entries';
        if( $datei === null ) {
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
                else if( !empty($field_data) ) {
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
                        case 'mediumtext':  if( strpos( $field_data, "'" ) !== false ) {  // ist im Text ein Hochkomma vorhanden, wird der Text in HEX-Darstellung gesichert
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
            if( $datei === null ) {
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

        if( isset( $GLOBALS['TL_CONFIG']['backupdb_blacklist'] ) && !empty(trim($GLOBALS['TL_CONFIG']['backupdb_blacklist'])) ) {
            $arrBlacklist = trimsplit(',', strtolower($GLOBALS['TL_CONFIG']['backupdb_blacklist']));
        }

        return $arrBlacklist;
    }


    //------------------------------------------------
    //  get_symlinks: Symlinks suchen und Datei erstellen
    //------------------------------------------------
    public static function get_symlinks( )
    {
        Self::$arrSymlinks = array();               // leeres Array
        $url = TL_ROOT . '/';

        Self::iterateDir( $url );                   // Symlinks suchen
        asort( Self::$arrSymlinks );                // alphabetisch sortieren

        $links = array();
        foreach( Self::$arrSymlinks as $link ) {
            $links[] = Self::getLinkData( $link );
        }

        $script = "<?php\n\n"
                . "// This file is part of a backup, included in the zip archive.\n"
                . "// Place the restoreSymlinks.php in the web directory of your\n"
                . "// contao 4 and call http://domain.tld/restoreSymlinks.php\n"
                . "// After running script clear symfony-cache, e.g. with Contao Manager\n\n"
                . '$arrSymlinks = unserialize(\'' . serialize( $links ) . "');\n\n"
                . "// Check current position\n"
                . 'if( !is_dir( "../web" ) || !file_exists( "./app.php" ) ) {' . "\n"
                . "\t" . 'die( "The file is not in the correct directory" );' . "\n"
                . "}\n\n"
                . "// detect OS\n"
                . '$windows = strtoupper(substr(PHP_OS, 0, 3)) === "WIN";      // Windows or not' . "\n\n"
                . "// get absolute path to contao\n"
                . '$rootpath = getcwd();' . "\n"
                . '$rootpath = substr( $rootpath, 0 , strlen($rootpath) - 3 );' . "\n\n"
                . "// Restore the symlinks\n"
                . '$errors = 0;' . "\n"
                . '$counter = 0;' . "\n"
                . 'foreach( $arrSymlinks as $link ) {' . "\n"
                . "\t// get linkpath\n"
                . "\t" . '$l = $rootpath . $link["link"];                         // absolute address of symlink' . "\n"
                . "\t" . 'if( $windows ) $l = str_replace( "/", "\\\\", $l );       // for windows change slashes' . "\n\n"
                . "\t// get targetpath\n"
                . "\t" . 'if( $windows ) {' ."\n"
                . "\t\t" . '$t = str_replace( "/", "\\\\", $rootpath . $link["target"] );' . "\n"
                . "\t}\n"
                . "\telse {\n"
                . "\t\t" . '$t = $link["target"];' . "\n"
                . "\t\t" . 'for( $i = 0; $i < $link["depth"]; $i++ ) {' . "\n"
                . "\t\t\t" . '$t = "../" . $t;' . "\n"
                . "\t\t}\n"
                . "\t}\n\n"
                . "\t// check if link seem to be a directory\n"
                . "\t" . 'if( file_exists( $l ) && !is_link( $l ) ) {' . "\n"
                . "\t\t" . 'rename( $l, $l . ".removed" );                      // rename directory or file' . "\n"
                . "\t}\n\n"
                . "\t// no action, if link is a symlink\n"
                . "\t" . 'if( is_link( $l ) ) continue;' . "\n\n"
                . "\t// set new symlink\n"
                . "\t" . '$counter++;' . "\n"
                . "\t" . 'if( !symlink( $t, $l ) ) {' . "\n"
                . "\t\t" . 'echo "Symlink failed: " . $l . "<br>";' . "\n"
                . "\t\t" . '$errors++;' . "\n"
                . "\t}\n"
                . "}\n\n"
                . 'echo "Program terminated with " . $errors . " errors, " . $counter . " new symlinks<br><br>PLEASE DELETE THE SCRIPT FROM THE DIRECTORY NOW!<br>CLEAR THE SYMFONY-CACHE, e.g. with Contao Manager<br>";' . "\n\n";

        return $script;
    }


    //------------------------------------------------
    //  iterateDir: rekusives Suchen nach Symlinks
    //------------------------------------------------
    public static function iterateDir( $startPath )
    {
        foreach( new \DirectoryIterator( $startPath ) as $objItem ) {
            if( $objItem->isDot( ) ) {
                continue;
            }
            if( $objItem->isLink( ) ) {
                self::$arrSymlinks[] = $objItem->getPath( ) . '/' . $objItem->getFilename( );
                continue;
            }
            if( $objItem->isDir( ) ) {
                self::iterateDir( $objItem->getPathname( ) );
                continue;
            }
        }
    }

    //------------------------------------------------
    //  iterateDir: rekusives Suchen nach Symlinks
    //------------------------------------------------
    public static function getLinkData( $link )
    {
        $root = str_replace('\\', '/', TL_ROOT) . '/';
        $sym = substr( str_replace('\\', '/', $link), strlen($root) );
        $target = str_replace('\\', '/', readlink( $link ) );
        
        if( substr($target, 0, strlen($root)) === $root ) {         // absolute path
            $target = substr($target, strlen($root));
            $depth = count(explode('/', $sym)) - 1;
        }
        else {                                                      // relative path
            $depth = 0;
            while( substr($target, 0, 3) === '../' ) {
                $target = substr($target, 3);
                $depth++;
            }
        }

        return array( 'link'=>$sym, 'target'=>trim($target, '/'), 'depth'=>$depth );        
    }
                                 
    //---------------------------------------
}
