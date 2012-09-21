<?php

########################################################################
# Extension Manager/Repository config file for ext "ke_yac".
#
# Auto generated 20-05-2011 13:04
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'YAC - Yet Another Calendar',
	'description' => 'Calendar Plugin with frontend editing, notifications for attendants and more...',
	'category' => 'plugin',
	'author' => 'Andreas Kiefer (kennziffer.com)',
	'author_email' => 'kiefer@kennziffer.com',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => 'www.kennziffer.com GmbH',
	'version' => '2.0.3',
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:89:{s:9:"ChangeLog";s:4:"c9bc";s:10:"README.txt";s:4:"ee2d";s:27:"class.tx_yacevaluations.php";s:4:"7ae3";s:12:"ext_icon.gif";s:4:"e274";s:17:"ext_localconf.php";s:4:"9989";s:14:"ext_tables.php";s:4:"0c02";s:14:"ext_tables.sql";s:4:"44bb";s:15:"flexform_ds.xml";s:4:"65fa";s:22:"icon_tx_kecal2_cat.gif";s:4:"4ad7";s:24:"icon_tx_kecal2_dates.gif";s:4:"475a";s:21:"icon_tx_keyac_cat.gif";s:4:"1572";s:23:"icon_tx_keyac_dates.gif";s:4:"e274";s:13:"locallang.xml";s:4:"ea74";s:16:"locallang_db.xml";s:4:"89e0";s:7:"tca.php";s:4:"bf70";s:14:"doc/manual.sxw";s:4:"33a6";s:19:"doc/wizard_form.dat";s:4:"a45e";s:20:"doc/wizard_form.html";s:4:"c1ec";s:14:"pi1/ce_wiz.gif";s:4:"b879";s:33:"pi1/class.frontend_JScalendar.php";s:4:"817e";s:26:"pi1/class.tx_keyac_pi1.php";s:4:"dcaf";s:34:"pi1/class.tx_keyac_pi1_wizicon.php";s:4:"8254";s:13:"pi1/clear.gif";s:4:"cc11";s:17:"pi1/locallang.xml";s:4:"ae4f";s:21:"pi1/yac-template.html";s:4:"b598";s:19:"pi1/images/back.gif";s:4:"123e";s:19:"pi1/images/cat0.gif";s:4:"8aea";s:19:"pi1/images/cat1.gif";s:4:"e521";s:19:"pi1/images/cat2.gif";s:4:"e267";s:19:"pi1/images/cat3.gif";s:4:"c0a9";s:19:"pi1/images/cat4.gif";s:4:"f310";s:19:"pi1/images/cat5.gif";s:4:"93ad";s:19:"pi1/images/cat6.gif";s:4:"3eff";s:19:"pi1/images/cat7.gif";s:4:"4a5b";s:19:"pi1/images/cat8.gif";s:4:"eab2";s:19:"pi1/images/cat9.gif";s:4:"dbd9";s:20:"pi1/images/clock.gif";s:4:"e1a7";s:30:"pi1/images/horizontal_line.gif";s:4:"96ed";s:19:"pi1/images/next.gif";s:4:"2296";s:22:"pi1/js/mootools-1.2.js";s:4:"2ae2";s:24:"pi1/static/editorcfg.txt";s:4:"3e50";s:20:"pi1/static/setup.txt";s:4:"75ce";s:26:"res/GoogleMapAPI.class.php";s:4:"78c7";s:15:"res/css/yac.css";s:4:"1610";s:18:"res/images/add.png";s:4:"1988";s:23:"res/images/attendee.gif";s:4:"6099";s:23:"res/images/backlink.gif";s:4:"ddf6";s:19:"res/images/cat1.gif";s:4:"df30";s:20:"res/images/cat10.gif";s:4:"cdeb";s:19:"res/images/cat2.gif";s:4:"f821";s:19:"res/images/cat3.gif";s:4:"ca9d";s:19:"res/images/cat4.gif";s:4:"3acc";s:19:"res/images/cat5.gif";s:4:"71cc";s:19:"res/images/cat6.gif";s:4:"332f";s:19:"res/images/cat7.gif";s:4:"1360";s:19:"res/images/cat8.gif";s:4:"b694";s:19:"res/images/cat9.gif";s:4:"9846";s:25:"res/images/catdefault.gif";s:4:"684b";s:23:"res/images/caticons.psd";s:4:"be66";s:27:"res/images/defaultImage.gif";s:4:"fdb3";s:17:"res/images/go.gif";s:4:"75fa";s:23:"res/images/infolink.gif";s:4:"75fa";s:19:"res/images/more.gif";s:4:"092e";s:28:"res/images/myevents-icon.gif";s:4:"30ed";s:19:"res/images/next.gif";s:4:"2296";s:19:"res/images/prev.gif";s:4:"123e";s:34:"res/images/attachmentIcons/avi.gif";s:4:"27bd";s:34:"res/images/attachmentIcons/css.gif";s:4:"4786";s:34:"res/images/attachmentIcons/csv.gif";s:4:"e413";s:38:"res/images/attachmentIcons/default.gif";s:4:"ec6e";s:34:"res/images/attachmentIcons/doc.gif";s:4:"8c62";s:34:"res/images/attachmentIcons/htm.gif";s:4:"54de";s:35:"res/images/attachmentIcons/html.gif";s:4:"3cea";s:33:"res/images/attachmentIcons/js.gif";s:4:"7a5a";s:34:"res/images/attachmentIcons/mov.gif";s:4:"d5e6";s:34:"res/images/attachmentIcons/mp3.gif";s:4:"b37e";s:35:"res/images/attachmentIcons/mpeg.gif";s:4:"15b5";s:34:"res/images/attachmentIcons/mpg.gif";s:4:"15b5";s:34:"res/images/attachmentIcons/pdf.gif";s:4:"5c5f";s:34:"res/images/attachmentIcons/psd.gif";s:4:"4448";s:34:"res/images/attachmentIcons/rtf.gif";s:4:"f660";s:35:"res/images/attachmentIcons/tmpl.gif";s:4:"5114";s:34:"res/images/attachmentIcons/ttf.gif";s:4:"9f93";s:34:"res/images/attachmentIcons/txt.gif";s:4:"d7f9";s:34:"res/images/attachmentIcons/wav.gif";s:4:"6931";s:34:"res/images/attachmentIcons/xls.gif";s:4:"4a22";s:34:"res/images/attachmentIcons/xml.gif";s:4:"2e7b";s:34:"res/images/attachmentIcons/zip.gif";s:4:"5de4";s:22:"res/js/mootools-1.2.js";s:4:"2ae2";}',
);

?>