<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::allowTableOnStandardPages('tx_aobedemouser_accounts');


t3lib_extMgm::addToInsertRecords('tx_aobedemouser_accounts');

$TCA['tx_aobedemouser_accounts'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:ao_bedemouser/locallang_db.php:tx_aobedemouser_accounts',		
		'label' => 'username',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'crdate',	
		'delete' => 'deleted',	
		'enablecolumns' => Array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_aobedemouser_accounts.gif",
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden, username, email, privacy',
	)
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(Array('LLL:EXT:ao_bedemouser/locallang_db.php:tt_content.list_type', $_EXTKEY.'_pi1'),'list_type');


if (TYPO3_MODE=='BE')	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_aobedemouser_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_aobedemouser_pi1_wizicon.php';
?>
