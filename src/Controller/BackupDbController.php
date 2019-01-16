<?php

/**
 * @copyright  Softleister 2007-2017
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

namespace Softleister\BackupDbBundle\Controller;

use Softleister\BackupDB\AutoBackupDB;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles the AutoBackup frontend routes.
 *
 * @copyright  Softleister 2007-2017
 * @author     Softleister <info@softleister.de>
 *
 * @Route("/BackupDB", defaults={"_scope" = "frontend", "_token_check" = false})
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

        $controller = new \Softleister\BackupDB\AutoBackupDb();

        return $controller->run();
    }
}
