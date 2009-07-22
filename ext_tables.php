<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$TCA["tx_keyac_cat"] = Array (
	"ctrl" => Array (
		'title' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_cat',		
		'label' => 'title',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE, 
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',	
		'transOrigPointerField' => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		"sortby" => "sorting",	
		"delete" => "deleted",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",	
			"starttime" => "starttime",	
			"endtime" => "endtime",	
			"fe_group" => "fe_group",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_keyac_cat.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, title, image",
	)
);

$TCA["tx_keyac_dates"] = Array (
	"ctrl" => Array (
		'title' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates',		
		'label' => 'title',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE, 
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',	
		'transOrigPointerField' => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		"sortby" => "sorting",	
		"delete" => "deleted",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",	
			"starttime" => "starttime",	
			"endtime" => "endtime",	
			"fe_group" => "fe_group",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_keyac_dates.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, startdat, enddat, showtime, title, place, bodytext, infolink, infolink_text, cat",
	)
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';

// Show FlexForm field in plugin configuration
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

// Configure FlexForm field
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1','FILE:EXT:ke_yac/flexform_ds.xml');


t3lib_extMgm::addPlugin(array('LLL:EXT:ke_yac/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/','Show Calendar');


if (TYPO3_MODE=="BE")	$TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_keyac_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_keyac_pi1_wizicon.php';
?>