<?php

/**
 * @copyright  Softleister 2007-2021
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

namespace Softleister\BackupDbBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles the AutoBackup frontend routes.
 *
 * @copyright  Softleister 2007-2021
 * @author     Softleister <info@softleister.de>
 *
 * @Route("/BackupDB")
 */
class BackupDbController
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Renders the alerts content.
     *
     * @return Response
     *
     * @Route("/autobackup", name="backupdb_autobackup")
     */
    public function autoBackupAction()
    {
        $this->framework->initialize();

        $controller = new \Softleister\BackupDB\AutoBackupDb();

        return $controller->run();
    }
}
