<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_aobedemouser_accounts'] = Array (
	'ctrl' => $TCA['tx_aobedemouser_accounts']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,username,email,privacy'
	),
	'feInterface' => $TCA['tx_aobedemouser_accounts']['feInterface'],
	'columns' => Array (
		'hidden' => Array (		
			'exclude' => 1,	
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'username' => Array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ao_bedemouser/locallang_db.php:tx_aobedemouser_accounts.username',		
			'config' => Array (
				'type' => 'input',	
				'size' => '30',	
				'max' => '20',	
				'eval' => 'required,trim',
			)
		),
		'email' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:ao_bedemouser/locallang_db.php:tx_aobedemouser_accounts.email',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'max' => '100',
				'eval' => 'required,trim',
			)
		),
		'privacy' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:ao_bedemouser/locallang_db.php:tx_aobedemouser_accounts.privacy',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'hidden;;1;;1-1-1, username, email, privacy')
	),
	'palettes' => Array (
		'1' => Array('showitem' => '')
	)
);
?>
