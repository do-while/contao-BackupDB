<?php

/**
 * @copyright  Softleister 2007-2024
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Softleister\BackupDB;

use Contao\BackendModule;
use Contao\System;
use Contao\Input;
use Contao\BackendTemplate;
use Contao\Environment;
use Contao\StringUtil;
use Contao\Config;
use Contao\Database;
use Composer\InstalledVersions;


/**
 * Class ModuleBackupDB
 */
class ModuleBackupDB extends BackendModule
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_backup_db';


    /**
     * Function compile
     */
    protected function compile()
    {
        System::loadLanguageFile('tl_backupdb');
        $rootDir      = System::getContainer()->getParameter('kernel.project_dir');
        $refererId    = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
        $requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        switch( Input::get('act') ) {
            case 'backup':          BackupDbRun::run( );
                                    die( );               // Beenden, da Download rausgegangen ist

            case 'webtemplate':     $objTemplate = new BackendTemplate('be_backupdb_wstpl');
                                    $this->Template = $objTemplate;
                                    $this->Template->arrResults = BackupWsTemplate::run( );
                                    $this->Template->back = 'contao?do=BackupDB&ref' . $refererId;
                                    $this->Template->backupdb_version = 'BackupDB Version ' . InstalledVersions::getPrettyVersion('do-while/contao-backupdb-bundle');
                                    return;
        }

        //--- Zielverzeichnis für Website-Templates ---
        $zielVerz = 'templates';
        if( isset( $GLOBALS['BACKUPDB']['WsTemplatePath'] ) && is_dir( $rootDir . '/' . trim($GLOBALS['BACKUPDB']['WsTemplatePath'], '/') ) ) {
            $zielVerz = trim($GLOBALS['BACKUPDB']['WsTemplatePath'], '/');
        }
        if( isset( $GLOBALS['TL_CONFIG']['WsTemplatePath'] ) && is_dir( $rootDir . '/' . trim($GLOBALS['TL_CONFIG']['WsTemplatePath'], '/') ) && !empty(trim($GLOBALS['TL_CONFIG']['WsTemplatePath'])) ) {
            $zielVerz = trim($GLOBALS['TL_CONFIG']['WsTemplatePath'], '/');
        }

        $filename = Environment::get('host');                                                                   // Dateiname = Domainname
        if( isset($GLOBALS['TL_CONFIG']['websiteTitle']) ) $filename = $GLOBALS['TL_CONFIG']['websiteTitle'];   // IF( Exiat WbsiteTitle ) Dateiname für Template-Dateien
        $filename = StringUtil::generateAlias( $filename );                                                     // Dateiname = Alias für Template-Dateien

        $this->Template->ws_template_sqlfile = $zielVerz.'/' . $filename . '.sql';
        $this->Template->ws_template_txtfile = $zielVerz.'/' . $filename . '.txt';
        $this->Template->ws_template_strfile = $zielVerz.'/' . $filename . '.structure';


        $this->Template->settingslink    = $settingslink = 'contao?do=settings&amp;ref=' . $refererId;
        $this->Template->cronlink        = $cronlink     = 'contao?do=cron&amp;ref=' . $refererId;
        $this->Template->backuplink      = 'contao?do=BackupDB&amp;act=backup&amp;rt=' . $requestToken . '&amp;ref=' . $refererId;
        $this->Template->webtemplatelink = 'contao?do=BackupDB&amp;act=webtemplate&amp;rt=' . $requestToken . '&amp;ref=' . $refererId;

        $this->Template->database = System::getContainer()->get('database_connection')->getParams()['dbname'];
        $autoinfo = Environment::get('url') . '/BackupDB/autobackup' . (empty(Config::get('backupdb_var')) ? '' : '?' . Config::get('backupdb_var'));
        $this->Template->texte    = array(
                                        'download'      => $GLOBALS['TL_LANG']['tl_backupdb']['download'],
                                        'startdownload' => $GLOBALS['TL_LANG']['tl_backupdb']['startdownload'],
                                        'database'      => $GLOBALS['TL_LANG']['tl_backupdb']['database'] . ': ',

                                        'backupdesc'    => $GLOBALS['TL_LANG']['tl_backupdb']['backupdesc'],
                                        'backupsetup'   => sprintf( $GLOBALS['TL_LANG']['tl_backupdb']['backupsetup'], $settingslink ),
                                        'backuplast'    => $GLOBALS['TL_LANG']['tl_backupdb']['backuplast'] . ': ',

                                        'autoinfo'      => $GLOBALS['TL_LANG']['tl_backupdb']['autoinfo'] . ': <strong><a href="' . $autoinfo . '" target="_blank">' . $autoinfo . '</a></strong>',
                                        'croninfo'      => $GLOBALS['TL_LANG']['tl_backupdb']['croninfo'],
                                        'cronsetup'     => sprintf( $GLOBALS['TL_LANG']['tl_backupdb']['cronsetup'], $cronlink ),
                                        'cronlast'      => $GLOBALS['TL_LANG']['tl_backupdb']['cronlast'] . ': ',

                                        'maketpl'       => $GLOBALS['TL_LANG']['tl_backupdb']['maketpl'],
                                        'tpldesc'       => $GLOBALS['TL_LANG']['tl_backupdb']['tpldesc'],
                                        'tplfiles'      => $GLOBALS['TL_LANG']['tl_backupdb']['tplfiles'] . ':',
                                        'tplnobackup'   => $GLOBALS['TL_LANG']['tl_backupdb']['tplnobackup'],
                                        'tplwarning'    => $GLOBALS['TL_LANG']['tl_backupdb']['tplwarning']
                                    );

        //--- CRON Erweiterung einbeziehen, wenn vorhanden ---
        $this->Template->ws_cron = $this->checkCronExt();               // übergibt 0 (kein Cron), 1 (Cron ohne Job), 2 (Cron, Job inaktiv) oder 3 (Cron, Job aktiv)
        if( (Input::get('op') == 'cron') && ($this->Template->ws_cron == 1) ) {
            $sql = "INSERT INTO `tl_cron` "
                  ."(`id`, `tstamp`, `lastrun`, `nextrun`, `scheduled`, `title`, `job`, `t_minute`, `t_hour`, `t_dom`, `t_month`, `t_dow`, `runonce`, `enabled`, `logging`) "
                  ."VALUES ( 0, " . time() . ", 0, 0, 0, 'AutoBackupDB', 'system/modules/BackupDB/AutoBackupDB.php', '0', '2', '*', '*', '*', '', '', '1')";
            $this->Database->execute( $sql );                           // inaktiven Cronjob eintragen
            $this->reload();
        }

        //--- Letzte Backups ---
        $pfad = $rootDir . '/' . System::getContainer()->getParameter('contao.upload_path') . '/AutoBackupDB/';
        $this->Template->lastrun = file_exists( $pfad . BACKUPDB_RUN_LAST ) ? file_get_contents( $pfad . BACKUPDB_RUN_LAST ) : '--.--.---- --:--';
        $this->Template->lastcron = file_exists( $pfad . BACKUPDB_CRON_LAST ) ? file_get_contents( $pfad . BACKUPDB_CRON_LAST ) : '--.--.---- --:--';

        //--- Footer ---
        $this->Template->backupdb_icons = 'Icons from <a href="https://icons8.com" target="_blank">Icons8</a> (<a href="https://creativecommons.org/licenses/by-nd/3.0/" target="_blank">CC BY-ND 3.0</a>)';
        $this->Template->backupdb_version = '<a href="https://github.com/do-while/contao-BackupDB" target="_blank">BackupDB Version ' . InstalledVersions::getPrettyVersion('do-while/contao-backupdb-bundle') . '</a>';
    }


    /**
     * Function check cron job
     */
    public function checkCronExt( )
    {
        $result = 0;                                    // kein Cron
        $objDB = Database::getInstance();

        if( $objDB->tableExists('tl_crontab') ) {
            $result = 1;                                // Cron vorhanden, kein Job

            $objJob = $objDB->execute("SELECT * FROM tl_crontab WHERE job='system/modules/BackupDB/public/AutoBackupDB.php' LIMIT 1");
            if( $objJob->next() ) {                     // IF( Job vorhanden )
                $result = $objJob->enabled ? 3 : 2;     //   Job AKTIV(3) oder Job INAKTIV(2)
            }
        }
        return $result;
    }

}
