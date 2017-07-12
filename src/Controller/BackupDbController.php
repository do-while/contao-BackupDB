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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the AutoBackup front end routes.
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
     * @Route("/autobackup", name="backupdb_AutoBackupDb")
     */
    public function AutoBackupAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new FrontendVisitors();

        return $controller->run();
    }
}
