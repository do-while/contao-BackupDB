<?php

/**
 * @copyright  Softleister 2007-2017
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

namespace Softleister\BackupDbBundle\Controller;

use Softleister\BackupDbBundle\AutoBackupDB;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the AutoBackup frontend routes.
 *
 * @copyright  Softleister 2007-2017
 * @author     Softleister <info@softleister.de>
 *
 * @Route("/autobackup", defaults={"_scope" = "frontend", "_token_check" = false})
 */
class BackupDbController extends Controller
{
    /**
     * Renders the alerts content.
     *
     * @return Response
     *
     * @Route("/autobackup", name="backupdb_autobackup")
     */
    public function AutoBackupAction()
    {
        $this->container->get('contao.framework')->initialize();

        return 'Läuft';
        
//        $controller = new autoBackupDB();

//        return $controller->run();
    }
}
