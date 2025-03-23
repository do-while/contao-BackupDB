<?php

/**
 * @copyright  Softleister 2007-2025
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
 * @copyright  Softleister 2007-2025
 * @author     Softleister <info@softleister.de>
 *
 */
#[Route('/BackupDB', name: 'backupdb_', defaults: ['_scope' => 'frontend'])]
class BackupDbController
{
    private $framework;

    public function __construct( ContaoFramework $framework )
    {
        $this->framework = $framework;
    }


    #[Route("/autobackup", name:"autobackup")]
    public function autoBackupAction(): Response
    {
        $this->framework->initialize( );

        $controller = new \Softleister\BackupDB\AutoBackupDb( );

        return $controller->run( );
    }
}
