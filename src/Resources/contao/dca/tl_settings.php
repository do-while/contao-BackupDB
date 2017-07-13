<?php

/**
 * @copyright  Softleister 2007-2017
 * @author     Softleister <info@softleister.de>
 * @package    BackupDB - Database backup
 * @license    LGPL
 * @see	       https://github.com/do-while/contao-BackupDB
 */


/**
 * System configuration
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['backupdb_saveddb'] = array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['backupdb_saveddb'],
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50')
		);

$GLOBALS['TL_DCA']['tl_settings']['fields']['AutoBackupCount'] = array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['AutoBackupCount'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50')
		);
		
$GLOBALS['TL_DCA']['tl_settings']['fields']['WsTemplatePath'] = array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['WsTemplatePath'],
			'inputType'               => 'text',
			'eval'                    => array('nospace'=>'true', 'trailingSlash'=>false, 'tl_class'=>'w50')
		);

$GLOBALS['TL_DCA']['tl_settings']['fields']['backupdb_blacklist'] = array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['backupdb_blacklist'],
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'long')
		);

$GLOBALS['TL_DCA']['tl_settings']['fields']['backupdb_sendmail'] = array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['backupdb_sendmail'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 clr')
		);

$GLOBALS['TL_DCA']['tl_settings']['fields']['backupdb_attmail'] = array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['backupdb_attmail'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
		);

$GLOBALS['TL_DCA']['tl_settings']['fields']['backupdb_zip'] = array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['backupdb_zip'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12')
		);


/**
 * Modify palette
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{backupdb_legend:hide},backupdb_blacklist,backupdb_saveddb,WsTemplatePath,backupdb_zip,AutoBackupCount,backupdb_sendmail,backupdb_attmail';
