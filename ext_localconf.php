<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_keyac_cat=1
');

// register class for additional evaluations
$TYPO3_CONF_VARS['SC_OPTIONS']['tce']['formevals']['tx_yacevaluations'] = 'EXT:ke_yac/class.tx_yacevaluations.php';

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_keyac_pi1.php', '_pi1', 'list_type', 0);


t3lib_extMgm::addTypoScript($_EXTKEY,'setup','
	tt_content.shortcut.20.0.conf.tx_keyac_dates = < plugin.'.t3lib_extMgm::getCN($_EXTKEY).'_pi1
	tt_content.shortcut.20.0.conf.tx_keyac_dates.CMD = singleView
',43);




?>