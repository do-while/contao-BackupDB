<?php

/**
 * @copyright  Softleister 2007-2017
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Softleister\BackupDB;


/**
 * Class ModuleBackupDB
 */
class ModuleBackupDB extends \BackendModule
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
        \System::loadLanguageFile('tl_backupdb');

        switch( \Input::get('act') ) {
            case 'backup':          BackupDbRun::run( );
                                    die( );               // Beenden, da Download rausgegangen ist

            case 'webtemplate':     $objTemplate = new \BackendTemplate('be_backupdb_wstpl');
                                    $this->Template = $objTemplate;
                                    $this->Template->arrResults = BackupWsTemplate::run( );
                                    $this->Template->back = 'contao/main.php?do=BackupDB&ref' . TL_REFERER_ID;
                                    $this->Template->backupdb_version = 'BackupDB Version ' . BACKUPDB_VERSION . '.' . BACKUPDB_BUILD;
                                    return;
        }

        //--- Zielverzeichnis für Website-Templates ---
        $zielVerz = 'templates';
        if( isset( $GLOBALS['BACKUPDB']['WsTemplatePath'] ) && is_dir(TL_ROOT.'/'.trim($GLOBALS['BACKUPDB']['WsTemplatePath'], '/')) ) {
            $zielVerz = trim($GLOBALS['BACKUPDB']['WsTemplatePath'], '/');
        }
        if( isset( $GLOBALS['TL_CONFIG']['WsTemplatePath'] ) && is_dir(TL_ROOT.'/'.trim($GLOBALS['TL_CONFIG']['WsTemplatePath'], '/')) && (trim($GLOBALS['TL_CONFIG']['WsTemplatePath']) != '') ) {
            $zielVerz = trim($GLOBALS['TL_CONFIG']['WsTemplatePath'], '/');
        }

        $filename = \Environment::get('host');                                                                  // Dateiname = Domainname
        if( isset($GLOBALS['TL_CONFIG']['websiteTitle']) ) $filename = $GLOBALS['TL_CONFIG']['websiteTitle'];   // IF( Exiat WbsiteTitle ) Dateiname für Template-Dateien
        $filename = \StringUtil::generateAlias( $filename );                                                    // Dateiname = Alias für Template-Dateien

        $this->Template->ws_template_sqlfile = $zielVerz.'/' . $filename . '.sql';
        $this->Template->ws_template_txtfile = $zielVerz.'/' . $filename . '.txt';
        $this->Template->ws_template_strfile = $zielVerz.'/' . $filename . '.structure';


        $this->Template->settingslink    = $settingslink = 'contao/main.php?do=settings&amp;ref=' . TL_REFERER_ID;
        $this->Template->cronlink        = $cronlink     = 'contao/main.php?do=cron&amp;ref=' . TL_REFERER_ID;
        $this->Template->backuplink      = 'contao/main.php?do=BackupDB&amp;act=backup&amp;rt=' . REQUEST_TOKEN . '&amp;ref=' . TL_REFERER_ID;
        $this->Template->webtemplatelink = 'contao/main.php?do=BackupDB&amp;act=webtemplate&amp;rt=' . REQUEST_TOKEN . '&amp;ref=' . TL_REFERER_ID;

        $this->Template->database = $GLOBALS['TL_CONFIG']['dbDatabase'];
        $this->Template->texte    = array(
                                        'download'      => $GLOBALS['TL_LANG']['tl_backupdb']['download'],
                                        'startdownload' => $GLOBALS['TL_LANG']['tl_backupdb']['startdownload'],
                                        'database'      => $GLOBALS['TL_LANG']['tl_backupdb']['database'] . ': ',

                                        'backupdesc'    => $GLOBALS['TL_LANG']['tl_backupdb']['backupdesc'],
                                        'backupsetup'   => sprintf( $GLOBALS['TL_LANG']['tl_backupdb']['backupsetup'], $settingslink ),
                                        'backuplast'    => $GLOBALS['TL_LANG']['tl_backupdb']['backuplast'] . ': ',

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
        if( ($this->Input->get('op') == 'cron') && ($this->checkCron() == 1) ) {
            $sql = "INSERT INTO `tl_cron` "
                  ."(`id`, `tstamp`, `lastrun`, `nextrun`, `scheduled`, `title`, `job`, `t_minute`, `t_hour`, `t_dom`, `t_month`, `t_dow`, `runonce`, `enabled`, `logging`) "
                  ."VALUES ( 0, " . time() . ", 0, 0, 0, 'AutoBackupDB', 'system/modules/BackupDB/AutoBackupDB.php', '0', '2', '*', '*', '*', '', '', '1')";
            $this->Database->execute( $sql );                           // inaktiven Cronjob eintragen
            $this->reload();
        }

        //--- Letzte Backups ---
        $pfad = TL_ROOT . '/' . $GLOBALS['TL_CONFIG']['uploadPath'] . '/AutoBackupDB/';
        $this->Template->lastrun = file_exists($pfad . BACKUPDB_RUN_LAST) ? file_get_contents($pfad . BACKUPDB_RUN_LAST) : '--.--.---- --:--';
        $this->Template->lastcron = file_exists($pfad . BACKUPDB_CRON_LAST) ? file_get_contents($pfad . BACKUPDB_CRON_LAST) : '--.--.---- --:--';

        //--- Footer ---
        $this->Template->backupdb_icons = 'Icons from <a href="https://icons8.com" target="_blank">Icons8</a> (<a href="https://creativecommons.org/licenses/by-nd/3.0/" target="_blank">CC BY-ND 3.0</a>)';
        $this->Template->backupdb_version = '<a href="https://github.com/do-while/contao-BackupDB" target="_blank">BackupDB Version ' . BACKUPDB_VERSION . '.' . BACKUPDB_BUILD . '</a>';
    }


    /**
     * Function check cron job
     */
    public function checkCronExt( )
    {
        $result = 0;                                    // kein Cron
        $objDB = \Database::getInstance();

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
