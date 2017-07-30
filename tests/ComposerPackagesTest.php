<?php

/**
 * @copyright  Softleister 2007-2017
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

require_once 'src/Resources/contao/classes/ComposerPackages.php';

use Softleister\BackupDB\ComposerPackages;


/**
 * ComposerPackages test case.
 * 
 * @author     Glen Langer (BugBuster)
 * 
 */
class ComposerPackagesTest extends PHPUnit_Framework_TestCase
{

    /**
     *
     * @var ComposerPackages
     */
    private $composerPackages;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->composerPackages = new ComposerPackages(__DIR__ .'/demofiles');
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->composerPackages = null;
        
        parent::tearDown();
    }

    /**
     * Constructs the test case.
     */
    public function __construct()
    {
        // TODO Auto-generated constructor
    }

    /**
     * Tests ComposerPackages->__construct()
     */
    public function test__construct()
    {
        $this->composerPackages->__construct('tests/demofiles');
        $this->assertEquals('tests/demofiles',$this->composerPackages->getPath());
    }

    /**
     * Tests ComposerPackages->parseComposerJson()
     */
    public function testParseComposerJson()
    {
        $this->composerPackages->parseComposerJson();
        $arrJson = $this->composerPackages->getArrJson();
        $this->assertCount(16, $arrJson['require']);
    }

    /**
     * Tests ComposerPackages->parseComposerLock()
     */
    public function testParseComposerLock()
    {
        $this->composerPackages->parseComposerLock();
        $arrLock = $this->composerPackages->getArrLock();
        $this->assertCount(123, $arrLock['packages']);
    }

    /**
     * Tests ComposerPackages->getPackages()
     */
    public function testGetPackages()
    {
        $this->composerPackages->parseComposerJson();
        $this->composerPackages->parseComposerLock();
         
        /* $bundles = array('name' => 'version') */
        $bundles = $this->composerPackages->getPackages();
        $this->assertCount(15, $bundles);

        $arrExclude = ['contao/calendar-bundle',
                       'contao/comments-bundle',
                       'contao/faq-bundle',
                       'contao/listing-bundle',
                       'contao/manager-bundle', 
                       'contao/news-bundle', 
                       'contao/newsletter-bundle'
                      ];
        $bundles = $this->composerPackages->getPackages($arrExclude);
        $this->assertCount(8, $bundles);
    }
}

