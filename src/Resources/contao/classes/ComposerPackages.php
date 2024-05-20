<?php

/**
 * @copyright  Softleister 2007-2024
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */

namespace Softleister\BackupDB;

/**
 * Composer Packages, 
 * handles composer.json and composer.lock
 * 
 * @author     Glen Langer (BugBuster)
 *
 */
class ComposerPackages
{
    private $path    = '';
    private $arrJson = array();
    private $arrLock = array();
    
    /**
     * @return the $path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return the $arrJson
     */
    public function getArrJson()
    {
        return $this->arrJson;
    }

    /**
     * @return the $arrLock
     */
    public function getArrLock()
    {
        return $this->arrLock;
    }

    /**
     */
    public function __construct($path = false)
    {
        $this->path = $path;
    }
    
    public function parseComposerJson($file = 'composer.json')
    {
        if (false === file_exists($this->path .'/'. $file))
        {
            return false;
        }
        
        $file = file_get_contents($this->path .'/'. $file);
        $this->arrJson = json_decode($file, true);
        
        return true;
    }
    
    public function parseComposerLock($file = 'composer.lock')
    {
        if (false === file_exists($this->path .'/'. $file))
        {
            return false;
        }
        
        $file = file_get_contents($this->path .'/'. $file);
        $this->arrLock = json_decode($file, true);
        
        return true;
    }
    
    public function getPackages($arrExclude = array())
    {
        $bundels      = array();
        $arrInstalled = $this->arrLock['packages'];
        $arrRequired  = $this->arrJson['require'];
        
        foreach ($arrInstalled as $key => $value) 
        {
            if ( array_key_exists($value['name'], $arrRequired) 
                     && !in_array($value['name'], $arrExclude) 
               ) 
            {
                $bundels[$value['name']] = $value['version'];
            }
        }
        
        return $bundels;
    }
    
}

