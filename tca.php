<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_keyac_cat'] = array (
	'ctrl' => $TCA['tx_keyac_cat']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,hidden,starttime,endtime,fe_group,title,image'
	),
	'feInterface' => $TCA['tx_keyac_cat']['feInterface'],
	'columns' => array (
		't3ver_label' => array (		
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '30',
			)
		),
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_keyac_cat',
				'foreign_table_where' => 'AND tx_keyac_cat.pid=###CURRENT_PID### AND tx_keyac_cat.sys_language_uid IN (-1,0)',
			)
		),
		'l10n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'starttime' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0',
				'range'    => array (
					'upper' => mktime(3, 14, 7, 1, 19, 2038),
					'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
				)
			)
		),
		'fe_group' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
			'config'  => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
					array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'title' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_cat.title',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, title;;;;2-2-2, image;;;;3-3-3')
	),
	'palettes' => array (
		'1' => array('showitem' => 'starttime, endtime, fe_group')
	)
);



$TCA['tx_keyac_dates'] = array (
	'ctrl' => $TCA['tx_keyac_dates']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,hidden,starttime,endtime,fe_group,startdat,enddat,showtime,private,title,location,address,city,zip,teaser,bodytext,infolink,cat,owner,attendees,images,attachments,googlemap_zoom'
	),
	'feInterface' => $TCA['tx_keyac_dates']['feInterface'],
	'columns' => array (
		't3ver_label' => array (		
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '30',
			)
		),
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_keyac_dates',
				'foreign_table_where' => 'AND tx_keyac_dates.pid=###CURRENT_PID### AND tx_keyac_dates.sys_language_uid IN (-1,0)',
			)
		),
		'l10n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'starttime' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0',
				'range'    => array (
					'upper' => mktime(3, 14, 7, 1, 19, 2038),
					'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
				)
			)
		),
		'fe_group' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
			'config'  => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
					array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'startdat' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.startdat',		
			'config' => array (
				'type'     => 'input',
				'size'     => '12',
				'max'      => '20',
				'eval'     => 'datetime',
				'checkbox' => '0',
				'default'  => '0'
			)
		),
		'enddat' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.enddat',		
			'config' => array (
				'type'     => 'input',
				'size'     => '12',
				'max'      => '20',
				'eval'     => 'datetime',
				'checkbox' => '0',
				'default'  => '0'
			)
		),
		'showtime' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.showtime',		
			'config' => array (
				'type' => 'check',
			)
		),
		'title' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.title',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'max' => '255',	
				'eval' => 'required',
			)
		),
		'location' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.location',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'address' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.address',		
			'config' => array (
				'type' => 'text',
				'cols' => '30',	
				'rows' => '5',
			)
		),
		'city' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.city',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'zip' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.zip',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'teaser' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.teaser',		
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 2,
					'RTE' => array(
						'notNewRecords' => 1,
						'RTEonly'       => 1,
						'type'          => 'script',
						'title'         => 'Full screen Rich Text Editing|Formatteret redigering i hele vinduet',
						'icon'          => 'wizard_rte2.gif',
						'script'        => 'wizard_rte.php',
					),
				),
			)
		),
		'bodytext' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.bodytext',		
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 2,
					'RTE' => array(
						'notNewRecords' => 1,
						'RTEonly'       => 1,
						'type'          => 'script',
						'title'         => 'Full screen Rich Text Editing|Formatteret redigering i hele vinduet',
						'icon'          => 'wizard_rte2.gif',
						'script'        => 'wizard_rte.php',
					),
				),
			)
		),
		'infolink' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.infolink',		
			'config' => array (
				'type'     => 'input',
				'size'     => '15',
				'max'      => '255',
				'checkbox' => '',
				'eval'     => 'trim',
				'wizards'  => array(
					'_PADDING' => 2,
					'link'     => array(
						'type'         => 'popup',
						'title'        => 'Link',
						'icon'         => 'link_popup.gif',
						'script'       => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)
			)
		),
		'cat' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.cat',		
			'config' => array (
				'type' => 'group',	
				'internal_type' => 'db',	
				'allowed' => 'tx_keyac_cat',	
				'size' => 3,	
				'minitems' => 0,
				'maxitems' => 99,	
				"MM" => "tx_keyac_dates_cat_mm",
			)
		),
		'owner' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.owner',		
			'config' => array (
				'type' => 'group',	
				'internal_type' => 'db',	
				'allowed' => 'fe_users',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'attendees' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.attendees',		
			'config' => array (
				'type' => 'group',	
				'internal_type' => 'db',	
				'allowed' => 'fe_users',	
				'size' => 10,	
				'minitems' => 0,
				'maxitems' => 100,	
				"MM" => "tx_keyac_dates_attendees_mm",
			)
		),
		'images' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.images',		
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'gif,png,jpeg,jpg',	
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],	
				'uploadfolder' => 'uploads/tx_keyac',
				'show_thumbs' => 1,	
				'size' => 3,	
				'minitems' => 0,
				'maxitems' => 20,
			)
		),
		'attachments' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.attachments',		
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => '',	
				'disallowed' => 'php,php3',	
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],	
				'uploadfolder' => 'uploads/tx_keyac',
				'size' => 3,	
				'minitems' => 0,
				'maxitems' => 100,
			)
		),
		'googlemap_zoom' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.googlemap_zoom',		
			'config' => array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array (
					'upper' => '17',
					'lower' => '1'
				),
				'default' => 0
			)
		),
        'private' => array (		
			'exclude' => 1,		
			'label' => 'LLL:EXT:ke_yac/locallang_db.xml:tx_keyac_dates.private',		
			'config' => array (
				'type' => 'check',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, startdat, enddat, showtime, private, title;;;;2-2-2, location;;;;3-3-3, address, city, zip, teaser;;;richtext[]:rte_transform[mode=ts], bodytext;;;richtext[]:rte_transform[mode=ts], infolink, cat, owner, attendees, images, attachments, googlemap_zoom')
	),
	'palettes' => array (
		'1' => array('showitem' => 'starttime, endtime, fe_group')
	)
);
?>