<?php

########################################################################
# Extension Manager/Repository config file for ext "ke_yac".
#
# Auto generated 22-09-2012 02:00
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
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => 'www.kennziffer.com GmbH',
	'version' => '3.0.0',
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
	'_md5_values_when_last_written' => 'a:102:{s:9:"ChangeLog";s:4:"5d36";s:27:"class.tx_yacevaluations.php";s:4:"7ae3";s:12:"ext_icon.gif";s:4:"e274";s:17:"ext_localconf.php";s:4:"9989";s:14:"ext_tables.php";s:4:"0c02";s:14:"ext_tables.sql";s:4:"44bb";s:15:"flexform_ds.xml";s:4:"cfe3";s:22:"icon_tx_kecal2_cat.gif";s:4:"4ad7";s:24:"icon_tx_kecal2_dates.gif";s:4:"475a";s:21:"icon_tx_keyac_cat.gif";s:4:"1572";s:23:"icon_tx_keyac_dates.gif";s:4:"e274";s:13:"locallang.xml";s:4:"ea74";s:16:"locallang_db.xml";s:4:"7433";s:10:"README.txt";s:4:"ee2d";s:7:"tca.php";s:4:"bf70";s:14:"doc/manual.sxw";s:4:"fe14";s:19:"doc/wizard_form.dat";s:4:"a45e";s:20:"doc/wizard_form.html";s:4:"c1ec";s:14:"pi1/ce_wiz.gif";s:4:"b879";s:26:"pi1/class.tx_keyac_pi1.php";s:4:"a195";s:34:"pi1/class.tx_keyac_pi1_wizicon.php";s:4:"8185";s:13:"pi1/clear.gif";s:4:"cc11";s:17:"pi1/locallang.xml";s:4:"f6c4";s:24:"pi1/static/editorcfg.txt";s:4:"3e50";s:20:"pi1/static/setup.txt";s:4:"5cb2";s:21:"res/yac-template.html";s:4:"8583";s:29:"res/css/yac-calendar-list.css";s:4:"3160";s:22:"res/css/yac-detail.css";s:4:"5043";s:23:"res/css/yac-general.css";s:4:"4bf4";s:24:"res/css/yac-myevents.css";s:4:"fc4b";s:22:"res/css/yac-teaser.css";s:4:"c47e";s:18:"res/images/add.png";s:4:"1988";s:23:"res/images/attendee.gif";s:4:"6099";s:38:"res/images/attendees-icon-listview.png";s:4:"9e5f";s:23:"res/images/backlink.gif";s:4:"ddf6";s:27:"res/images/defaultImage.gif";s:4:"fdb3";s:21:"res/images/delete.png";s:4:"6846";s:17:"res/images/go.gif";s:4:"75fa";s:23:"res/images/infolink.gif";s:4:"75fa";s:19:"res/images/more.gif";s:4:"092e";s:28:"res/images/myevents-icon.gif";s:4:"30ed";s:19:"res/images/next.gif";s:4:"2296";s:23:"res/images/nextlink.gif";s:4:"75fa";s:19:"res/images/prev.gif";s:4:"123e";s:23:"res/images/prevlink.gif";s:4:"f144";s:34:"res/images/attachmentIcons/avi.gif";s:4:"27bd";s:34:"res/images/attachmentIcons/css.gif";s:4:"4786";s:34:"res/images/attachmentIcons/csv.gif";s:4:"e413";s:38:"res/images/attachmentIcons/default.gif";s:4:"ec6e";s:34:"res/images/attachmentIcons/doc.gif";s:4:"8c62";s:34:"res/images/attachmentIcons/htm.gif";s:4:"54de";s:35:"res/images/attachmentIcons/html.gif";s:4:"3cea";s:33:"res/images/attachmentIcons/js.gif";s:4:"7a5a";s:34:"res/images/attachmentIcons/mov.gif";s:4:"d5e6";s:34:"res/images/attachmentIcons/mp3.gif";s:4:"b37e";s:35:"res/images/attachmentIcons/mpeg.gif";s:4:"15b5";s:34:"res/images/attachmentIcons/mpg.gif";s:4:"15b5";s:34:"res/images/attachmentIcons/pdf.gif";s:4:"5c5f";s:34:"res/images/attachmentIcons/psd.gif";s:4:"4448";s:34:"res/images/attachmentIcons/rtf.gif";s:4:"f660";s:35:"res/images/attachmentIcons/tmpl.gif";s:4:"5114";s:34:"res/images/attachmentIcons/ttf.gif";s:4:"9f93";s:34:"res/images/attachmentIcons/txt.gif";s:4:"d7f9";s:34:"res/images/attachmentIcons/wav.gif";s:4:"6931";s:34:"res/images/attachmentIcons/xls.gif";s:4:"4a22";s:34:"res/images/attachmentIcons/xml.gif";s:4:"2e7b";s:34:"res/images/attachmentIcons/zip.gif";s:4:"5de4";s:33:"res/images/categoryIcons/cat1.gif";s:4:"df30";s:34:"res/images/categoryIcons/cat10.gif";s:4:"cdeb";s:33:"res/images/categoryIcons/cat2.gif";s:4:"f821";s:33:"res/images/categoryIcons/cat3.gif";s:4:"ca9d";s:33:"res/images/categoryIcons/cat4.gif";s:4:"3acc";s:33:"res/images/categoryIcons/cat5.gif";s:4:"71cc";s:33:"res/images/categoryIcons/cat6.gif";s:4:"332f";s:33:"res/images/categoryIcons/cat7.gif";s:4:"1360";s:33:"res/images/categoryIcons/cat8.gif";s:4:"b694";s:33:"res/images/categoryIcons/cat9.gif";s:4:"9846";s:39:"res/images/categoryIcons/catdefault.gif";s:4:"684b";s:26:"res/js/jquery-1.8.2.min.js";s:4:"cfa9";s:36:"res/js/jquery-ui-timepicker-addon.js";s:4:"2044";s:27:"res/js/ke_yac_datepicker.js";s:4:"5a47";s:22:"res/js/ke_yac_gmaps.js";s:4:"24bd";s:25:"res/js/ke_yac_tooltips.js";s:4:"e7fc";s:38:"res/js/jquery-qtip/jquery.qtip.min.css";s:4:"cf93";s:37:"res/js/jquery-qtip/jquery.qtip.min.js";s:4:"d086";s:61:"res/js/jquery-ui/css/ui-lightness/jquery-ui-1.8.23.custom.css";s:4:"16bb";s:82:"res/js/jquery-ui/css/ui-lightness/images/ui-bg_diagonals-thick_18_b81900_40x40.png";s:4:"95f9";s:82:"res/js/jquery-ui/css/ui-lightness/images/ui-bg_diagonals-thick_20_666666_40x40.png";s:4:"f040";s:72:"res/js/jquery-ui/css/ui-lightness/images/ui-bg_flat_10_000000_40x100.png";s:4:"c18c";s:73:"res/js/jquery-ui/css/ui-lightness/images/ui-bg_glass_100_f6f6f6_1x400.png";s:4:"5f18";s:73:"res/js/jquery-ui/css/ui-lightness/images/ui-bg_glass_100_fdf5ce_1x400.png";s:4:"d26e";s:72:"res/js/jquery-ui/css/ui-lightness/images/ui-bg_glass_65_ffffff_1x400.png";s:4:"e5a8";s:79:"res/js/jquery-ui/css/ui-lightness/images/ui-bg_gloss-wave_35_f6a828_500x100.png";s:4:"58d2";s:82:"res/js/jquery-ui/css/ui-lightness/images/ui-bg_highlight-soft_100_eeeeee_1x100.png";s:4:"384c";s:81:"res/js/jquery-ui/css/ui-lightness/images/ui-bg_highlight-soft_75_ffe45c_1x100.png";s:4:"b806";s:68:"res/js/jquery-ui/css/ui-lightness/images/ui-icons_222222_256x240.png";s:4:"ebe6";s:68:"res/js/jquery-ui/css/ui-lightness/images/ui-icons_228ef1_256x240.png";s:4:"79f4";s:68:"res/js/jquery-ui/css/ui-lightness/images/ui-icons_ef8c08_256x240.png";s:4:"ef9a";s:68:"res/js/jquery-ui/css/ui-lightness/images/ui-icons_ffd27a_256x240.png";s:4:"ab8c";s:68:"res/js/jquery-ui/css/ui-lightness/images/ui-icons_ffffff_256x240.png";s:4:"342b";s:39:"res/js/jquery-ui/js/jquery-1.8.0.min.js";s:4:"cd8b";s:50:"res/js/jquery-ui/js/jquery-ui-1.8.23.custom.min.js";s:4:"5007";}',
);

?>