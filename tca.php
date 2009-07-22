<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_keyac_cat"] = Array (
	"ctrl" => $TCA["tx_keyac_cat"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,starttime,endtime,fe_group,title,image"
	),
	"feInterface" => $TCA["tx_keyac_cat"]["feInterface"],
	"columns" => Array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_keyac_cat',
				'foreign_table_where' => 'AND tx_keyac_cat.pid=###CURRENT_PID### AND tx_keyac_cat.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"starttime" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.starttime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"default" => "0",
				"checkbox" => "0"
			)
		),
		"endtime" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.endtime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.fe_group",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.xml:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.xml:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"title" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_cat.title",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"image" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_cat.image",		
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],	
				"max_size" => 100,	
				"uploadfolder" => "uploads/tx_keyac",
				"show_thumbs" => 1,	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, title;;;;2-2-2, image;;;;3-3-3")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "starttime, endtime, fe_group")
	)
);



$TCA["tx_keyac_dates"] = Array (
	"ctrl" => $TCA["tx_keyac_dates"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,starttime,endtime,fe_group,startdat,enddat,showtime,title,place,bodytext,infolink,infolink_text,cat"
	),
	"feInterface" => $TCA["tx_keyac_dates"]["feInterface"],
	"columns" => Array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages',-1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_keyac_dates',
				'foreign_table_where' => 'AND tx_keyac_dates.pid=###CURRENT_PID### AND tx_keyac_dates.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (		
			'config' => Array (
				'type' => 'passthrough'
			)
		),
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"starttime" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.starttime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"default" => "0",
				"checkbox" => "0"
			)
		),
		"endtime" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.endtime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.fe_group",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.xml:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.xml:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"startdat" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.startdat",		
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"max" => "20",
				"eval" => "datetime",
				"checkbox" => "0",
				"default" => "0",
			)
		),
		"enddat" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.enddat",		
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"max" => "20",
				"eval" => "datetime",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"showtime" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.showtime",		
			"config" => Array (
				"type" => "check",
			)
		),
		"title" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.title",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "255",	
				"eval" => "required",
			)
		),
		
		'cat' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.cat',
			'config' => Array (
				'type' => 'select',
				'items' => Array(
					
				),
				'MM' => 'tx_keyac_dates_cat_mm',
				'foreign_table' => 'tx_keyac_cat',
				'foreign_table_where' => ' and tx_keyac_cat.title<>"" and tx_keyac_cat.hidden=0 and tx_keyac_cat.deleted=0 order by tx_keyac_cat.title',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			)
		),
		
		
		"place" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.place",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"bodytext" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.bodytext",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
				"wizards" => Array(
					"_PADDING" => 2,
					"RTE" => Array(
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						"script" => "wizard_rte.php",
					),
				),
			)
		),
		"infolink" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.infolink",		
			"config" => Array (
				"type" => "input",
				"size" => "15",
				"max" => "255",
				"checkbox" => "",
				"eval" => "trim",
				"wizards" => Array(
					"_PADDING" => 2,
					"link" => Array(
						"type" => "popup",
						"title" => "Link",
						"icon" => "link_popup.gif",
						"script" => "browse_links.php?mode=wizard",
						"JSopenParams" => "height=300,width=500,status=0,menubar=0,scrollbars=1"
					)
				)
			)
		),
		"infolink_text" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.infolink_text",
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		
		
	),
	"types" => Array (
		#"0" => Array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, startdat, enddat, showtime, title;;;;2-2-2, place;;;;3-3-3, bodytext;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts], infolink, cat")
		"0" => Array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, startdat, enddat, showtime, title;;;;2-2-2, cat, place;;;;3-3-3, bodytext;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|outdent|indent|link|image]:rte_transform[mode=ts], infolink, infolink_text")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "starttime, endtime, fe_group")
	)
);
?>