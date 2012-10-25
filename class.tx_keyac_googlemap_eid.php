<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Andreas Kiefer <kiefer@kennziffer.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_keyacgooglemap_eid extends tslib_pibase {

	/*
	 * function main
	 */
	function main() {
		tslib_eidtools::connectDB(); //Connect to database
		
		$recordUid = intval(t3lib_div::_GET('yacuid'));
		$yacRecord = $this->getYACRecord($recordUid);

		if (!is_array($yacRecord)) {
			// die with error message
			die ('No Data available');
		}
		// yac must be loaded for google maps function
		if (!t3lib_extMgm::isLoaded('ke_yac')) {
			// die with error message
			die ('EXT KE_YAC NOT LOADED!');
		} else {

			// check if record uid is set
			if (!$recordUid) {
				// die with error message if no uid set
				die ('NO UID SET!');
			} else {
				
				$content .= '
					<style type="text/css">
						body{margin:0; padding:0; font: 12px Arial, Helvetica, sans-serif;}
					</style>';
				
				// include jQuery
				$content .= '<script type="text/javascript" scr="http://code.jquery.com/jquery.min.js"></script>'."\n";
				
				// include gMaps Api v3
				$content .= '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>'."\n";
				
				// make YAC instance
				require_once(t3lib_extMgm::extPath('ke_yac').'pi1/class.tx_keyac_pi1.php');
				$yac = t3lib_div::makeInstance('tx_keyac_pi1');

				// inline js for gmaps
				$mapZoom = $yacRecord['googlemap_zoom'] > 0 ? $yacRecord['googlemap_zoom'] : 12;
				$infoContent = $yac->getFieldContent('gmaps_htmladdress', $yacRecord);	
				$inlineJS = '
				var mapAddress = "'.$yac->getFieldContent('gmaps_address', $yacRecord).'";
				var mapZoom = '.$mapZoom.';
				var infocontent = "'.$infoContent.'";';
				$content .= '	<script type="text/javascript">'.
				$inlineJS.'
				var map;
				var geocoder = new google.maps.Geocoder();
				var latlng;
				function gmap_init() {
					geocoder.geocode({ \'address\': mapAddress}, function(results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							var myOptions = {
								zoom: mapZoom,
								center: results[0].geometry.location,
								mapTypeId: google.maps.MapTypeId.ROADMAP
							};
							var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
							marker = new google.maps.Marker({
								position: results[0].geometry.location,
								map: map,
								draggable: false
							});
							var infowindow = new google.maps.InfoWindow({
								content: infocontent,
								maxWidth: 400
							  });
							  google.maps.event.addListener(marker, \'click\', function() {
								infowindow.open(map,marker);
							  });
						}
					});
				}

				gmap_init();

				</script>'."\n";
				
				$content .= '<div id="map_canvas" style="width:800px; height: 500px;">';
			}
			
			
		}
		echo $content;
		
	}
	
	/*
	 * function getYACRecord
	 * @param $uid int
	 */
	function getYACRecord($uid) {
		$fields = 'uid,address,zip,city,googlemap_zoom,location';
		$table = 'tx_keyac_dates';
		$where = 'uid="'.intval($uid).' AND hidden=0 AND deleted=0" ';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='1');
		$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	}
  
}

$output = t3lib_div::makeInstance('tx_keyacgooglemap_eid');
$output->main();