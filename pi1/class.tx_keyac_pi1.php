<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2010 Andreas Kiefer <kiefer@kennziffer.com>
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

// needed for checking filenames when uploading files
require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');


/**
 * Plugin 'Show Calendar' for the 'ke_yac' extension.
 *
 * @author	Andreas Kiefer <kiefer@kennziffer.com>
 * @package	TYPO3
 * @subpackage	tx_keyac
 */
class tx_keyac_pi1 extends tslib_pibase {

	var $prefixId = 'tx_keyac_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_keyac_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'ke_yac';	// The extension key.
	// var $pi_checkCHash = TRUE;
	var $uploadFolder = "uploads/tx_keyac/";
	var $maxFiles = 3;


	/**
	 * Main method of your PlugIn
	 *
	 * @param	string		$content: The content of the PlugIn
	 * @param	array		$conf: The PlugIn Configuration
	 * @return	The content that should be displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexform(); // Init and get the flexform data of the plugin
		$this->lcObj=t3lib_div::makeInstance('tslib_cObj');
		$this->internal['results_at_a_time'] = 100;
		
		// GET FLEXFORM DATA
		$this->pi_initPIflexForm();
		$piFlexForm = $this->cObj->data['pi_flexform'];
		if (is_array($piFlexForm['data'])) {
			foreach ( $piFlexForm['data'] as $sheet => $data ) {
				foreach ( $data as $lang => $value ) {
					foreach ( $value as $key => $val ) {
						$this->ffdata[$key] = $this->pi_getFFvalue($piFlexForm, $key, $sheet);
					}
				}
			}
		}
		
		// set user_int if fe editing is enabled
		/*
		if ($this->ffdata['enableFrontendEditing']) {
			$this->pi_checkCHash = false;
			$this->pi_USER_INT_obj = 1;
		} else {
			$this->pi_checkCHash = true;
			$this->pi_USER_INT_obj = 0;
		}
		t3lib_div::debug($this->pi_USER_INT_obj,'user_int???');
		*/
		
		// DB DEBUG
 		//$GLOBALS['TYPO3_DB']->debugOutput = true;

		// starting point
		$pages = $this->cObj->data['pages'] ? $this->cObj->data['pages'] : ( $this->conf['dataPids'] ? $this->conf['dataPids'] : $GLOBALS['TSFE']->id);
		$this->pids = $this->pi_getPidList($pages,$this->cObj->data['recursive']);

		// Include HTML Template
		$this->templateFile = $this->ffdata['templateFile'] ? $this->uploadFolder.$this->ffdata['templateFile'] : $this->conf['templateFile'];
		$this->templateCode = $this->cObj->fileResource($this->templateFile);

		// Include CSS File
		$cssfile = $this->conf['cssFile'] ? $this->conf['cssFile'] : t3lib_extMgm::siteRelPath($this->extKey).'res/css/yac.css';
		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId.'_css'] = '<link rel="stylesheet" type="text/css" href="'.$cssfile.'" />';

		// get Format Strings from FF or TS
		$this->formatStringWithTime = $this->ffdata['strftimeFormatStringWithTime'] ? $this->ffdata['strftimeFormatStringWithTime'] : $this->conf['strftimeFormatStringWithTime'];
		$this->formatStringWithoutTime = $this->ffdata['strftimeFormatStringWithoutTime'] ? $this->ffdata['strftimeFormatStringWithoutTime'] : $this->conf['strftimeFormatStringWithoutTime'];
		$this->formatTime = $this->ffdata['strftimeFormatTime'] ? $this->ffdata['strftimeFormatTime'] : $this->conf['strftimeFormatTime'];

		// Duration until fadeout for tooltips
		$this->tooltipDuration = $this->ffdata['tooltipDuration'] ? $this->ffdata['tooltipDuration'] : $this->conf['tooltipDuration'];

		// Listview PID
		$this->listviewPid = $this->ffdata['listviewPid'] ? $this->ffdata['listviewPid'] : $this->conf['listviewPid'];
		if (!$this->listviewPid) $this->listviewPid = $GLOBALS['TSFE']->page['uid'];

		// Singleview PID
		$this->singleviewPid = $this->ffdata['singleviewPid'] ? $this->ffdata['singleviewPid'] : $this->conf['singleviewPid'];
		if (!$this->singleviewPid) $this->singleviewPid = $GLOBALS['TSFE']->page['uid'];
		
		// get the plugin-mode from flexforms
		$mode_selector = $this->ffdata['mode_selector'];
		// Overwrite Mode if teaser is set in TS
		if ($this->conf['mode'] == 'TEASER') $mode_selector = 1;

		// generate backlink icon
		unset($imageConf);
		$imageConf['file'] = t3lib_extMgm::siteRelPath($this->extKey).'res/images/backlink.gif';
		$imageConf['altText'] = $this->pi_getLL('back');
		$this->backlinkIcon=$this->cObj->IMAGE($imageConf);


		// get Content corresponding to mode
		switch($mode_selector) {

			// TEASER-VIEW
			case "1":
				$content = $this->teaserView();
				break;

			// MY EVENTS
			case "2":
				$content = $this->myEventsView();
				break;

			// DETAIL VIEW
			case "4":
				$this->piVars['showUid'] = $this->ffdata['singleDateUid'];
				$content = $this->singleView($this->ffdata['singleDateUid']);
				break;


			// CALENDAR / LIST / SINGLE
			case "0":
			// CALENDAR / LIST (no singleview - e.g. for calendar view as teaser - ignores "showUid")
			case "3":
			default:
				
				// unset showUid if this mode was selected
				if ($mode_selector == '3') {
					unset($this->piVars['showUid']);
				}
				
				// =========================
				// -------- action handling --------------
				// =========================
				
				// create new record
				if ($this->piVars['action'] == 'create') {
					$this->initDate2Cal();
					// find new startdat and enddat for event
					if ($this->piVars['submitcreatefind']) $content = $this->findEventTime();
					else if ($this->piVars['submitcreate'] || $this->piVars['submitcreateignore']) $content = $this->evaluateCreateData();
					else $content = $this->showForm();
					return $this->pi_wrapInBaseClass($content);
				}
				
				// edit event
				if ($this->piVars['action'] == 'edit' && $GLOBALS['TSFE']->loginUser) {
					
					$this->initDate2Cal();
					// find new startdat and enddat for event
					if ($this->piVars['submiteditfind']) $content = $this->findEventTime();
					else if ($this->piVars['submitedit'] || $this->piVars['submiteditignore']) $content = $this->evaluateEditData(intval($this->piVars['showUid']));
					else $content = $this->showForm(intval($this->piVars['showUid']));
					return $this->pi_wrapInBaseClass($content);
				}
				
				// attend
				if ($this->piVars['action'] == 'attend' && $GLOBALS['TSFE']->loginUser) {
					$this->setUserAsAttendant(intval($this->piVars['showUid']), $GLOBALS['TSFE']->fe_user->user['uid']);
					// clear page cache
					$this->clearPageCache($GLOBALS['TSFE']->id);
				}
				
				// delete attendance
				if ($this->piVars['action'] == 'delattendance' && $GLOBALS['TSFE']->loginUser) {
					$this->deleteUserAsAttendant(intval($this->piVars['showUid']),$GLOBALS['TSFE']->fe_user->user['uid']);
					// clear page cache
					$this->clearPageCache($GLOBALS['TSFE']->id);
				}

				// invite other users
				if ($this->piVars['action'] == 'invite' && $GLOBALS['TSFE']->loginUser) {
					if ($this->piVars['submitinvite']) {
						// automatically set invited persons as attendee 
						if ($this->piVars['invitation_mode'] == 'set') {
							foreach ($this->piVars['user'] as $invUser => $value) {
								$this->setUserAsAttendant(intval($this->piVars['showUid']), $invUser, false);
							}
						}
						$content = $this->processInviteData();
					}
					else $content = $this->showInviteForm();
					return $this->pi_wrapInBaseClass($content);
				}

				// move event
				if ($this->piVars['action'] == 'move' && $GLOBALS['TSFE']->loginUser) {
					$this->initDate2Cal();
					if ($this->piVars['submitmove']) $content = $this->processMoveData();
					else $content = $this->showMoveForm();
					return $this->pi_wrapInBaseClass($content);
				}

				// delete the event
				if ($this->piVars['action'] == 'delete' && $GLOBALS['TSFE']->loginUser) {
					if ($this->piVars['submitdeleteyes']) $content = $this->processDelete(intval($this->piVars['showUid']));
					else if ($this->piVars['submitdeleteno']) {
						$this->loadJS();
						$content.=$this->getCalendarView();
					}
					else $content = $this->showDeleteForm(intval($this->piVars['showUid']));
					return $this->pi_wrapInBaseClass($content);
				}

				if ($this->piVars['showCal']=="") $this->piVars['showCal']=1;

				// single view if event is chosen
				if ($this->piVars['showUid']) {
					$content.=$this->singleView(intval($this->piVars['showUid']));
				}

				// if month and year for viewing the cal are chosen
				else if ($this->piVars['month'] && $this->piVars['year']) {
					$this->loadJS();
					$content.=$this->getCalendarView(intval($this->piVars['month']),intval($this->piVars['year']));
				} else {
					// show current month if nothing is set
					$this->loadJS();
					$content.=$this->getCalendarView();
				}
				break;
		}
		return $this->pi_wrapInBaseClass($content);
	}

		
	function initDate2Cal() {
		
		// process only if date2cal is loaded
		if (t3lib_extMgm::isLoaded('date2cal')) {
		
			// include jscalendar api
			include_once(t3lib_extMgm::siteRelPath('date2cal') . '/src/class.jscalendar.php');
			
			// init jscalendar class
			$this->JSCalendar = JSCalendar::getInstance();
			
			// datetime format (default: time)
			$format = '%d.%m.%Y';
			$this->JSCalendar->setDateFormat(true, $format);
			
			// set options
			$this->JSCalendar->setConfigOption('firstDay', true);
			$this->JSCalendar->setLanguage($this->extConfig['lang']);
			
			// get initialisation code of the calendar
			if (($jsCode = $this->JSCalendar->getMainJS()) != '') {
				$GLOBALS['TSFE']->additionalHeaderData['date2cal'] = $jsCode;
			}
		}
		
	}

	function loadJS() {
		// Load Javascript Library (Mootools)
		// only if listview is shown
		if ($this->conf['useJS']) {
			$mootoolsJS = t3lib_extMgm::siteRelPath($this->extKey).'res/js/mootools-1.2.js';
			#$mootoolsJS = t3lib_extMgm::siteRelPath($this->extKey).'res/js/mootoolsv1.11.js';
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= '<script src="'.$mootoolsJS.'" type="text/javascript"></script>';
		}

		// use simple javascript for tooltips?
		if ($this->conf['showTooltipsWithoutMootools']) {
			$jsSwitcher = "
				<script type='text/javascript'>
				<!--
					function hideEl(elname) {
						el = document.getElementById(elname);
						el.style.display = 'none';
					}
					function showEl(elname) {
					  el = document.getElementById(elname);
					  el.style.display = 'inline';
					  el.style.opacity = '100';
					}
				-->
				</script>";

			// load javascript (minify if typo3 > 4.1)
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['compat_version'] > 4.1) $GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= t3lib_div::minifyJavaScript($jsSwitcher);
			else $GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= $jsSwitcher;
		}


	}

	/**
	/*
	* show calendars and other elements of this view
	*/
	function getCalendarView($month=0,$year=0) {
		
		// show passed events? 
		$this->sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey);
		if ($this->piVars['showpassedchange']) {
			$this->sessionData['showpassed'] = $this->piVars['showpassed'];
			$GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey, $this->sessionData);
			$GLOBALS['TSFE']->storeSessionData();
			unset($this->piVars['showpassedchange']);
			unset($this->piVars['showpassed']);
		}
		#debug($this->sessionData,'session');
		
		// use rows and columns values from ff or conf?
		$this->calRows = $this->ffdata['rows'] ? $this->ffdata['rows'] : $this->conf['rows'];
		$this->calColumns = $this->ffdata['columns'] ? $this->ffdata['columns'] : $this->conf['columns'];
		$this->cals = $this->calRows * $this->calColumns;

		// if no month is set -> get current
		if (!$month && !$year) {
			$current = time();
			$cur_month = intval(date('m',$current));
			$cur_year= intval(date('Y',$current));
		}
		// use given month otherwise
		else {
			$cur_month = $month;
			$cur_year = $year;
		}

		// Show months navigation row ?
		$showMonthsNavigation = isset($this->ffdata['showMonthsNavigation']) ? $this->ffdata['showMonthsNavigation'] : $this->conf['showMonthsNavigation'];
		if ($showMonthsNavigation > 0) $monthsNav = $this->getMonthsNavigation($showMonthsNavigation, $cur_month, $cur_year);
		else $monthsNav = '';

		// navigation arrow "back"
		$prev_arrow = $this->getNavArrow('prev', $cur_month, $cur_year);

		// Generate calendars starting from given month
		$calendarsContent = '';
		$i=0;
		// run through number of rows
		for ($row=0; $row < $this->calRows; $row++) {

			// run through number of cols for every row
			for ($col=0; $col < $this->calColumns; $col++) {

				// which month has to be shown?
				$show_month = ($row * $this->calColumns) + $col + $cur_month;
				$show_year = $cur_year;
				if ($show_month > 12) {
					$show_month -= 12;
					$show_year += 1;
				}
				// get dates for current month from db
				$datesarray = $this->getDBData($show_month,$show_year);
				// generate calendar content
				$calendarsContent .= $this->showMonth($show_month,$show_year,$datesarray);

				// Set values of first and last month timestamp for listview
				if ($i==0) $this->starttime = $this->getStartTimestamp($show_month,$show_year);
				if ($i==($this->cals-1)) $this->endtime =  $this->getEndTimestamp($show_month,$show_year);

				$i++;
			}

			// insert clearer at the end of each row
			$calendarsContent .= $this->cObj->getSubpart($this->templateCode,'###CALENDARS_CLEARER###');

		}

		// navigation arrow "next"
		$next_arrow = $this->getNavArrow('next', $cur_month, $cur_year);

		// show "create event" link?
		if ($this->conf['enableFrontendEditing'] || $this->ffdata['enableFrontendEditing']) {
			unset($linkconf);
			$linkconf['parameter'] = $GLOBALS['TSFE']->id;
			$linkconf['additionalParams'] = '&tx_keyac_pi1[action]=create';
			$linkconf['useCacheHash'] = false;
			$createEventLink = $this->cObj->typoLink($this->pi_getLL('create_event_link'),$linkconf);

			// create event icon
			$imageConf['file'] = t3lib_extMgm::siteRelPath($this->extKey).'res/images/add.png';
			$imageConf['altText'] = $this->pi_getLL('create_event');
			$createEventIcon = $this->cObj->IMAGE($imageConf);

			$createEventLinkContent = $this->cObj->getSubpart($this->templateCode,'###SUB_CREATE_LINK###');
			$createEventLinkContent = $this->cObj->substituteMarker($createEventLinkContent,'###CREATE_EVENT_ICON###',$createEventIcon);
			$createEventLinkContent = $this->cObj->substituteMarker($createEventLinkContent,'###CREATE_EVENT_LINK###',$createEventLink);
		}
		else $createEventLinkContent = '';

		// show link "hide calendar" if set in FF or TS
		if ($this->ffdata['showHideCalendarLink'] || $this->conf['showHideCalendar']) $hideCalendar = $this->getHideCalendarLink();

		// show legend if set in FF or TS
		if ($this->ffdata['showLegend'] || $this->conf['showLegend']) $legend = $this->legend();

		// list events if set in FF or TS
		if ($this->ffdata['showList'] || $this->conf['showList']) $listView = $this->listView();

		$markerArray = array(
			'navigation' => $monthsNav,
			'prev_arrow' => $prev_arrow,
			'calendars' => $calendarsContent,
			'next_arrow' => $next_arrow,
			'hide_calendar' => $hideCalendar,
			'legend' => $legend,
			'listview' => $listView,
		);
		
		// Hook for additional markers
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_keyac']['additionalMainTemplateMarkers'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_keyac']['additionalMainTemplateMarkers'] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$_procObj->additionalMainTemplateMarkers(&$markerArray,$this);
			}
		}
		
		$content = $this->cObj->getSubpart($this->templateCode,'###MAIN_TEMPLATE###');
		$content = $this->cObj->substituteMarkerArray($content,$markerArray,$wrap='###|###',$uppercase=1);
	
		// set checkbox 
		$showpassedChecked = $this->sessionData['showpassed'] == 'on' ? 'checked="checked" ' : '';
		$content = $this->cObj->substituteMarker($content,'###SHOWPASSED_CHECKED###', $showpassedChecked );
		$content = $this->cObj->substituteMarker($content,'###LABEL_SHOW_PASSED###', $this->pi_getLL('label_show_passed_events'));
		
		// overwrite subparts if not activated
		if (!$this->ffdata['showHideCalendarLink'] && !$this->conf['showHideCalendar']) $content = $this->cObj->substituteSubpart ($content, '###SUB_HIDE_CALENDAR###', '');
		if (!$this->ffdata['showLegend'] && !$this->conf['showLegend']) $content = $this->cObj->substituteSubpart ($content, '###SUB_LEGEND###', '');
		if (!$this->ffdata['showList'] && !$this->conf['showList']) $content = $this->cObj->substituteSubpart ($content, '###SUB_LISTVIEW###', '');

		// fill "create event link"
		$content = $this->cObj->substituteSubpart ($content, '###SUB_CREATE_LINK###', $createEventLinkContent);

		return $content;
	}


	/*
	* Generate linked nav arrows for switching to prev/next month
	*/
	function getNavArrow($mode, $cur_month, $cur_year) {

		// calculate target month
		$target_month = $mode == 'prev' ? $cur_month -1 : $cur_month + 1;
		$target_year = $cur_year;
		if ($target_month > 12) {
			$target_month -= 12;
			$target_year +=1;
		}
		else if ($target_month < 1) {
			$target_month += 12;
			$target_year -= 1;
		}

		// Prev Image
		$imageConf = $this->conf['calendar.']['prevIcon.'];
		$prevIcon=$this->cObj->IMAGE($imageConf);
		// Next Image
		$imageConf = $this->conf['calendar.']['nextIcon.'];
		$nextIcon=$this->cObj->IMAGE($imageConf);

		// generate link
		$image = $mode == 'prev' ? $prevIcon : $nextIcon;
		$overrulePIVars=array('month' => $target_month, 'year' => $target_year);
		$link = $this->pi_linkTP_keepPIVars($image, $overrulePIVars,$cache=1,0);
		return $link;
	}


	/*
	* Generate the link for switching the calendar view on / off
	*/
	function getHideCalendarLink() {
		if ($this->piVars['showCal']==1) {
			$overrulePIvars = array('showCal' => '0');
			$text = $this->pi_getLL('hideCalendar');
		}
		else {
			$overrulePIvars = array('showCal' => '1');
			$text = $this->pi_getLL('showCalendar');
		}
		// generate link
		$link = $this->pi_linkTP_keepPIvars($text,$overrulePIvars,$cache=1,$clearAnyway=0);

		$content = $this->cObj->getSubpart($this->templateCode,'###HIDE_CALENDAR_TEMPLATE###');
		$content = $this->cObj->substituteMarker($content,'###LINK###',$link);
		return $content;
	}


	/*
	* Generate Months Navigation Bar
	*/
	function getMonthsNavigation($num, $cur_month, $cur_year) {
		if ($num % 2 != 0) $num += 1;
		$center = ceil($num / 2);
		#$cals = $this->ffdata['rows'] * $this->ffdata['columns'];

		$pre_month = $cur_month - $center;
		$pre_year = $cur_year;
		if ($pre_month < 1) {
			$pre_month +=12;
			$pre_year -= 1;
		}

		$post_month = $cur_month + $this->cals;
		$post_year = $cur_year;
		if ($post_month > 12) {
			$post_month -=12;
			$post_year += 1;
		}

		// pre nav
		for ($pre_content='', $i=0; $i<$center; $pre_month++, $i++) {
			if ($pre_month > 12) {
				$pre_month -= 12;
				$pre_year +=1;
			}
			$timestamp = mktime(0,0,0,$pre_month,1,$pre_year);
			$text = utf8_decode(strftime('%B %y',$timestamp));

			$var_year = date('Y',$timestamp);
			$var_month = date('m',$timestamp);
			// print text
			$overrulePIVars=array('month' => $var_month, 'year' => $var_year);
			$link = $this->pi_linkTP_keepPIVars($text,$overrulePIVars,$cache=1);
			$temp_content = $this->cObj->getSubpart($this->templateCode,'###NAVIGATION_PRE_SINGLE###');
			$temp_content = $this->cObj->substituteMarker($temp_content,'###LINK###',$link);
			$pre_content .= $temp_content;
		}

		// post nav
		for ($post_content='', $i=0; $i<$center; $post_month++, $i++) {
			if ($post_month > 12) {
				$post_month -= 12;
				$post_year +=1;
			}
			$timestamp = mktime(0,0,0,$post_month,1,$post_year);
			$text = utf8_decode(strftime('%B %y',$timestamp));

			$var_year = date('Y',$timestamp);
			$var_month = date('m',$timestamp);
			// print text
			$overrulePIVars=array('month' => $var_month, 'year' => $var_year);
			$link = $this->pi_linkTP_keepPIVars($text,$overrulePIVars,$cache=1);
			$temp_content = $this->cObj->getSubpart($this->templateCode,'###NAVIGATION_POST_SINGLE###');
			$temp_content = $this->cObj->substituteMarker($temp_content,'###LINK###',$link);
			$post_content .= $temp_content;
		}

		$markerArray = array(
			'pre' => $pre_content,
			'post' => $post_content,
		);
		$content = $this->cObj->getSubpart($this->templateCode,'###NAVIGATION_TEMPLATE###');
		$content = $this->cObj->substituteMarkerArray($content,$markerArray,$wrap='###|###',$uppercase=1);
		return $content;

	}



	/*
	* get data for specified month from db and return as array
	*/
	function getDBData($month,$year) {

		$lcObj=t3lib_div::makeInstance('tslib_cObj');

		// get timestamp for first day of month, 0:00:00
		$timestamp_start = $this->getStartTimestamp($month,$year);
		// get timestamp for last day of month, 23:59:59
		$timestamp_end = $this->getEndTimestamp($month,$year);
		// get number of days in this month
		$days_month = date("t",$timestamp_start);

		// generate array for results
		$datesarray = array();

		// db query
		$fields = '*, tx_keyac_dates.uid as dateuid';
		$table = 'tx_keyac_dates';
		$enableFields1 = $lcObj->enableFields('tx_keyac_dates',$show_hidden=0);
		$where = ' ( ( startdat >= '.$timestamp_start.' AND startdat <= '.$timestamp_end.' )';
		$where.= ' OR (enddat >= '.$timestamp_start.' AND enddat <= '.$timestamp_end.' )';
		$where.= ' OR ( startdat <= '.$timestamp_start.' AND enddat >= '.$timestamp_end.' ) )';
		$where.=$enableFields1.$enableFields2;
		$where.=' and tx_keyac_dates.pid in ('.$this->pids.') ';

		// extend where clause if only one given category is shown
		if ($this->ffdata['singleCat']) {
			$catEntries = $this->getCategoryEntriesList($this->ffdata['singleCat']);
			if (!empty($catEntries)) $where .= ' AND tx_keyac_dates.uid in ('.$catEntries.') ';
			else $where .= ' AND 1=2 ';
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
		// walk through results
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			// get category data from db
			$fields = '*';
 			$table = 'tx_keyac_cat, tx_keyac_dates_cat_mm';
 			$where = 'uid_local="'.$row['dateuid'].'" ';
 			$where .= 'AND uid_foreign=tx_keyac_cat.uid ';
 			$where .= $this->cObj->enableFields('tx_keyac_cat');
 			$catRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
 			$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($catRes);
 			$catRow=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($catRes);

			// set default category if activated in conf and no category set
			if ( ($this->conf['showEventsWithoutCat'] || $this->ffdata['showEventsWithoutCat'])  && !is_array($catRow)) {
				$catRow['uid'] = 'def';
			}

			// get day from timestamp
			if ($row['startdat']) {
				$starttag = date('j',$row['startdat']);
				$startmonat = date('n',$row['startdat']);
				$startyear = date('Y', $row['startdat']);
			}
			if ($row['enddat']) {
				$endtag = date('j',$row['enddat']);
				$endmonat = date('n',$row['enddat']);
				$endyear = date('Y', $row['enddat']);
			}

			// no enddate
			if (!$row['enddat']) {
				if ($datesarray[$starttag]!='')
					$datesarray[$starttag] = "999s0";
				else $datesarray[$starttag] = $catRow['uid']."s0";
			}
			// event with end date
			else {

				// start and end in different years
				if ($startyear != $endyear) {

					// if startmonth
					if ($month == $startmonat && $year==$startyear) {
						for ($i=$starttag;$i<=$days_month;$i++) {

							// if there is already another event this day -> set type to "999"
							// if "s" is already set -> maintain
							if ($datesarray[$i]!='' && strpos($datesarray[$i],"s"))
								$datesarray[$i]="999s2";
							else if ($datesarray[$i]!='') $datesarray[$i]=999;
							// if there is no other event this day -> set type to category
							else $datesarray[$i]=$catRow['uid'];
							// mark start day with "s"
							if ($i==$starttag && !strpos($datesarray[$i],"s"))
								$datesarray[$i].="s2";
						}
					}
					//if endmonth
					else if ($month == $endmonat && $year == $endyear) {
						for ($i=$endtag;$i>0;$i--) {
							// if there is already another event this day -> set type to "999"
							if ($datesarray[$i]!='') $datesarray[$i]=999;
							else $datesarray[$i]=$catRow['uid'];
						}
					}
					// if month between startmonth and endmonth
					else {
						for ($i=1; $i<=$days_month; $i++) {
							if ($datesarray[$i]!='') $datesarray[$i]=999;
							else $datesarray[$i]=$catRow['uid'];
						}
					}

				}

				// start and end in same year
				else {
					// start and end in same month
					if ($startmonat == $endmonat) {
						// one-day
						if ($starttag == $endtag) {
							// if there is already another event this day -> set type to "999"
							if ($datesarray[$starttag]!='')
								$datesarray[$starttag]="999s1";
							// if there is no other event this day -> set type to category
							else $datesarray[$starttag]=$catRow['uid']."s1";
						}
						// several days
						else {
							for ($i=$starttag;$i<=$endtag;$i++) {

								// if there is already another event this day -> set type to "999"
								// if "s" is already set -> maintain
								if ($datesarray[$i]!='' && strpos($datesarray[$i],"s"))
									$datesarray[$i]="999s2";
								else if ($datesarray[$i]!='') $datesarray[$i]=999;
								// if there is no other event this day -> set type to category
								else $datesarray[$i]=$catRow['uid'];
								// mark start day with "s"
								if ($i==$starttag && !strpos($datesarray[$i],"s"))
									$datesarray[$i].="s2";
							}
						}
					}
					// start and end date not in same month
					else {
						// start of event in current month
						if ($month == $startmonat) {
							for ($i=$starttag;$i<=$days_month;$i++) {
								// if there is already another event this day -> set type to "999"
								if ($datesarray[$i]!="" && strpos($datesarray[$i],"s"))
									$datesarray[$i]="999s3";
								else if ($datesarray[$i]!='')
									$datesarray[$i]=999;
								else $datesarray[$i]=$catRow['uid'];
								// mark start day with "s"
								if ($i==$starttag && !strpos($datesarray[$i],"s"))
									$datesarray[$i].="s3";
							}
						}
						// end of date in current month
						if ($month == $endmonat) {
							for ($i=$endtag;$i>0;$i--) {
								// if there is already another event this day -> set type to "999"
								if ($datesarray[$i]!='') $datesarray[$i]=999;
								else $datesarray[$i]=$catRow['uid'];
							}
						}

						// duration of event over 1 month
						// all days with event
						if ($month > $startmonat && $month < $endmonat) {
							for ($i=1; $i<=$days_month; $i++) {
								if ($datesarray[$i]!='') $datesarray[$i]=999;
								else $datesarray[$i]=$catRow['uid'];
							}
						}
					}
				}
			}
		}
		// return results array
		return $datesarray;
	} // end func getDBData





	/*
	 * function getCategoryEntries
	 * @param $categoryUid int Uid of cat to show
	 */

	function getCategoryEntriesList($categoryUid) {

		$fields = '*';
		$table = 'tx_keyac_dates_cat_mm';
		$where = 'uid_foreign="'.intval($categoryUid).'" ';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
		$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$whereIn .= $row['uid_local'].',';
		}
		$whereIn = t3lib_div::rm_endcomma($whereIn);

		return $whereIn;

	}



	/**************************************************************************
	* function for viewing a small calendar
	* month and year needed
	* array with events (see function getDBData)
	************************************************************************* */
	function showMonth($month,$year,$dates) {

		// get day of week for first day in month
		$erster_timestamp = mktime(0,0,0,$month,1,$year);
		$erster_wochentag = strftime('%w',$erster_timestamp);

		// if first day of month is sunday set var to 7
		if($erster_wochentag==0) $erster_wochentag=7;

		// current date in this calendar?
		$heute = time();
		$heute_monat = intval(date('m',$heute));
		$heute_jahr = intval(date('Y',$heute));
		// yes
		if ($heute_monat == intval($month) && $heute_jahr == intval($year))
			$heute_tag = intval(date('d',$heute));
		// no
		else $heute_tag = 0;

		// full name of this month
		$akt_monat = strftime('%B %Y',$erster_timestamp);


		// days in this month
		$days = date("t",$erster_timestamp);

		// get number of rows
		$rows = ceil(($days+($erster_wochentag-1))/7);

		// show number of week in calendar?
		if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showWeekNumberRow', 'CALENDAR') || $this->conf['showWeekNumberRow'])
			$cols = 8;
		else $cols=7;

		// start output of table
		$content.='<table class="calendar">
						<tr><th colspan="'.$cols.'">'.$akt_monat.'</th></tr>';

		// if "show calendar" is set
		if ($this->piVars['showCal']==1) {

			// show day of week in calendar?
			if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showDaysRow', 'CALENDAR') || $this->conf['showDaysRow']) {
				$tsmon = mktime(0,0,0,1,1,2007);
				$content.='<tr>';
				// additional col if week number is shown
				if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showWeekNumberRow', 'CALENDAR') || $this->conf['showWeekNumberRow']) {
					$content.='<td class="day">&nbsp;</td>';
				}
				for ($i=1;$i<=7;$i++) {
					$content.='<td class="day">'.substr(strftime('%a',$tsmon),0,1).'</td>';
					$tsmon+=60*60*24;
				}
				$content.='</tr>';
			} // end if

			$content.='	<tr>';

			// run through loop
			for ($i=1,$day=1;$i<=$rows*7;$i++) {

				// start of week and showWeekNumber? -> show additional cell withNumber
				if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'showWeekNumberRow', 'CALENDAR') || $this->conf['showWeekNumberRow']) {
					// first week -> first cell ($i) could be empty, so take first day of month
					if ($i==1) {
						$ts = mktime(0,0,0,$month,1,$year);
						$content.='<td class="week">'.strftime('%W',$ts).'</td>';
					}
					// other weeks -> take $day
					else if (($i-1)%7==0) {
						$ts = mktime(0,0,0,$month,$day,$year);
						$content.='<td class="week">'.strftime('%W',$ts).'</td>';
					}
				}

				// if $i less than first day of month or $i greater than last day of month -> show empty cell
				if ($i<$erster_wochentag || $day > $days) {
					$content.='<td class="normal">&nbsp;</td>';
				}
				// print event in cell
				else if ($day <= $days) {
					$stellen = 2;

					// formats for different types
					// current date and event(s) at this day
					if ($day==$heute_tag && $dates[$day]) {
						$class = 'todaycat'.$dates[$day];
						if (strpos($class,"s"))
							$class = substr($class,0,-2);
					}
					// if current date and no events
					else if ($day==$heute_tag)
						$class = 'today';
					// if not current day and date exists
					else if ($dates[$day]) {
						$class='cat'.$dates[$day];
						if (strpos($class,"s"))
							$class = substr($class,0,-2);
					}
					// if not current date and no event
					else $class='normal';

					// if event on this day and start date -> link to entry in listView
					if ($dates[$day] && strpos($dates[$day],"s")) {
						$day_format = sprintf("%02d",$day);
						$month_format = sprintf("%02d",$month);
						$anchorlink = $day_format.'.'.$month_format.'.'.$year;
						// $content.='<td class="'.$class.'"><a href="#'.$anchorlink.'">'.sprintf("%0{$stellen}d",$day).'</a></td>';
						$onmouseover = 'document.getElementById(\''.$anchorlink.'\').style.display=\'inline\'';
						$onmouseout = 'document.getElementById(\''.$anchorlink.'\').style.display=\'none\'';

						// if set, render direct link to single view
						if ($this->ffdata['linkToSingleView'] || $this->conf['linkToSingleView'] ) {
							$timestamp_start = mktime(0,0,0,$month,$day,$year);
							$timestamp_end = mktime(23,59,59,$month,$day,$year);

							$fields = '*';
							$table = 'tx_keyac_dates';
							$where = ' ( ( startdat >= '.$timestamp_start.' AND startdat <= '.$timestamp_end.' )';
							$where.= ' OR (enddat >= '.$timestamp_start.' AND enddat <= '.$timestamp_end.' )';
							$where.= ' OR ( startdat <= '.$timestamp_start.' AND enddat >= '.$timestamp_end.' ) )';
							$where.=$this->cObj->enableFields('tx_keyac_dates');
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='startdat',$limit='1');
							$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
							$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

							// generate Link to single view
							#$overrulePIvars = array('showUid' => $row['uid']);
							#$daylink = $this->pi_linkTP_keepPIvars_url($overrulePIvars,$cache=1,$clearAnyway=0);
							#debug($this->singleviewPid,'svp');
							unset($linkconf);
							$linkconf['parameter'] = $this->singleviewPid;
							$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.intval($row['uid']);
							$linkconf['additionalParams'] .= '&tx_keyac_pi1[showCal]='.intval($this->piVars['showCal']);
							// set backlink pid if singleview pid is different from current pid
							if ($this->singleviewPid != $GLOBALS['TSFE']->page['uid']) $linkconf['additionalParams'] .= '&tx_keyac_pi1[backPid]='.$GLOBALS['TSFE']->page['uid'];
							$linkconf['useCacheHash'] = true;
							$daylink = $this->cObj->typoLink_URL($linkconf);
							#debug($daylink,'daylink');
						}
						// otherwise, link to entry in listview
						else {
							unset($linkconf);
							$linkconf['parameter'] = $GLOBALS['TSFE']->id;
							if (isset($this->piVars['month']) && $this->piVars['year']) {
								$linkconf['additionalParams'] = '&'.$this->prefixId.'[month]='.intval($this->piVars['month']);
								$linkconf['additionalParams'] .= '&'.$this->prefixId.'[year]='.intval($this->piVars['year']);
							}
							$daylink = $this->cObj->typoLink_URL($linkconf).'#'.$anchorlink;
						}


						// set event handler for tooltips without mootools?
						if ($this->conf['showTooltipsWithoutMootools']) {
							$mouseover = 'showEl(\''.$anchorlink.'_layer\');';
							$mouseout = 'hideEl(\''.$anchorlink.'_layer\');';
						}
						// show tooltips by title tag
						else if($this->conf['showTooltipsAsTitleTag']) {
							$mouseover = '';
							$mouseout = '';
							$titleTag = trim($this->listView($day_format, $month_format, $year, true));
							$titleTag = strip_tags($titleTag);
							$titleTag = str_replace("\t", '',$titleTag);
							$titleTag = str_replace("\n", '',$titleTag);
							$titleTag = str_replace("\r", '',$titleTag);
							$titleTag = str_replace(chr(10), '',$titleTag);
							$titleTag = str_replace(chr(13), '',$titleTag);
							$titleTag = 'title="'.$titleTag.'"';
							#$daylink = urlencode($daylink);
						}
						else {
							$mouseover = '';
							$mouseout = '';
						}
						// generate table cell content for current day
						$content.='
							<td class="'.$class.'" id="'.$anchorlink.'_cell">
								<div id="'.$anchorlink.'_link"><a href="'.$daylink.'" onmouseover="'.$mouseover.'" '.$titleTag.' onmouseout="'.$mouseout.'">'.sprintf("%0{$stellen}d",$day).'</a></div>
								<div id="'.$anchorlink.'_layer" class="yac-tooltip" style="" name="'.$anchorlink.'_layer">'.$this->listView($day_format, $month_format, $year,true).'</div>
							</td>';


						// generate tooltips if set in FF or TS
						if ($this->ffdata['showTooltips'] || $this->conf['showTooltips']) {
							$content .= '
								<script type="text/javascript">
									<!--

										var fadeout = 0;

										document.getElementById(\''.$anchorlink.'_link\').addEvent(\'mouseenter\', function(e) {
											e.stop();
											list = $$(\'td div.yac-tooltip\');
											if (fadeout > 0) window.clearTimeout(fadeout);
											for (var i = 0, j = list.length; i < j; i++){
												element = list[i];
												element.fade(0);
												element.setStyle(\'display\', \'none\');
											}
											document.getElementById(\''.$anchorlink.'_layer\').setStyle(\'z-index\', \'99999\');
											document.getElementById(\''.$anchorlink.'_layer\').setStyle(\'display\', \'inline\');
											document.getElementById(\''.$anchorlink.'_layer\').fade(1);
										});

										document.getElementById(\''.$anchorlink.'_cell\').addEvent(\'mouseleave\', function(e) {
											e.stop();
											document.getElementById(\''.$anchorlink.'_layer\').setStyle(\'z-index\', \'1\');
											fadeout = window.setTimeout("document.getElementById(\''.$anchorlink.'_layer\').fade(0)", '.$this->tooltipDuration.');
										});

									-->
								</script>';
						}

					}
					else
					$content.='<td class="'.$class.'">'.sprintf("%0{$stellen}d",$day).'</td>';

					$day++;
				} // end else if
				// end row and begin new one if not last row
				if ($i%7==0 && $i!=$rows*7) $content.='</tr><tr>';
				// end last row
				else if ($i%7==0) $content.='</tr>';
			} // end for...
		}
		$content.='</table>';

		return $content;
	} // end func showMonth


	/**
	 * list view of events for the months shown in calendar
	 */
	function listView($day=0,$month=0,$year=0,$tooltip=false) {
		
		$lcObj=t3lib_div::makeInstance('tslib_cObj');

		// generate "more" icon
		$imageConf = $this->conf['listview.']['moreIcon.'];
		$moreIcon=$this->cObj->IMAGE($imageConf);

		// list events
		$table = 'tx_keyac_dates';
		$fields = '*, tx_keyac_dates.title as datetitle, tx_keyac_dates.uid as dateuid';
		$enableFields = $lcObj->enableFields('tx_keyac_dates',$show_hidden=0);

		// get dates for single day if specified
		if ($day && $month && $year) {
			$start_ts = mktime(0, 0, 0, $month, $day, $year);
			$end_ts = mktime(23, 59, 59, $month, $day, $year) ;
			$entryLimit = '';
		}
		// Show only X most actual entries in listview
		else if ($this->conf['limitListView']) {
			$entryLimit = $this->conf['limitListView'];
			$start_ts = strtotime('today');
			$end_ts = $this->endtime;
		}
		// checkbox for viewing passed events not set
		else if ($this->sessionData['showpassed'] != 'on') {
			$start_ts = strtotime('today');
			$end_ts = $this->endtime;
			$entryLimit = '';
			$addWhere .= ' AND (startdat >= '.strtotime('today').' OR enddat >= '.strtotime('today').') ';
		}
		// show complete period
		else {
			$start_ts = $this->starttime;
			$end_ts = $this->endtime;
			$entryLimit = '';
		}

		$where = ' ( ( startdat >= '.$start_ts.' AND startdat <= '.$end_ts.' )';
		$where.= ' OR (enddat >= '.$start_ts.' AND enddat <= '.$end_ts.' )';
		$where.= ' OR ( startdat <= '.$start_ts.' AND enddat >= '.$end_ts.' ) ';
		$where .= ' ) ';
		if ($addWhere) $where .= $addWhere;
		$where.=$enableFields;
		$where.=' and tx_keyac_dates.pid in ('.$this->pids.') ';

		// extend where clause if only one given category is shown
		if ($this->ffdata['singleCat']) {
			$catEntries = $this->getCategoryEntriesList($this->ffdata['singleCat']);
			if (!empty($catEntries)) $where .= ' AND tx_keyac_dates.uid in ('.$catEntries.') ';
			else $where .= ' AND 1=2 ';

		}
		
		#if ($tooltip==false) debug($GLOBALS['TYPO3_DB']->SELECTquery($fields,$table,$where,$groupBy='dateuid',$orderBy='startdat,enddat',$entryLimit));
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='dateuid',$orderBy='startdat,enddat',$entryLimit);
		$content = '';
		$resultsNum = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

		// no results found
		if (!$resultsNum) return $this->pi_getLL('noResults');

		$i=1;
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			// get category data from db
			$fields = '*';
 			$table = 'tx_keyac_cat, tx_keyac_dates_cat_mm';
 			$where = 'uid_local="'.$row['dateuid'].'" ';
 			$where .= 'AND uid_foreign=tx_keyac_cat.uid ';
 			$where .= $this->cObj->enableFields('tx_keyac_cat');
 			$catRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
 			$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($catRes);
 			$catRow=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($catRes);

			// show Record if category is set or displaying of records without category is activated
			if (is_array($catRow) ||  ($this->conf['showEventsWithoutCat'] || $this->ffdata['showEventsWithoutCat'])  ) {

				$start = strftime('%d.%m.%Y',$row['startdat']);
				$ende = strftime('%d.%m.%Y',$row['enddat']);

				$startday = strftime('%d', $row['startdat']);
				$startmonth = strftime('%m', $row['startdat']);
				$startyear = strftime('%Y', $row['startdat']);
				$endday = strftime('%d', $row['enddat']);
				$endmonth = strftime('%m', $row['enddat']);
				$endyear = strftime('%Y', $row['enddat']);

				// no end date set
				if (!$row['enddat']) $ende_uhrzeit =0;

				// date and time
				strftime('%H:%M',$row['startdat']) != '00:00' ? $beginn = strftime($this->formatStringWithTime,$row['startdat']) : $beginn = strftime($this->formatStringWithoutTime,$row['startdat']);
				// begin date
				$beginn_datum = strftime($this->formatStringWithoutTime,$row['startdat']);
				// begin time
				$beginn_uhrzeit = strftime($this->formatTime,$row['startdat']);
				// end date
				$ende_datum = strftime($this->formatStringWithoutTime,$row['enddat']);
				// end time
				$ende_uhrzeit = strftime($this->formatTime,$row['enddat']);
				$dat = array(
					'start datum' => $beginn_datum,
					'ende datum' => $ende_datum,
					'start zeit' => $beginn_uhrzeit,
					'ende zeit' => $ende_uhrzeit
				);

				// no start date set
				if (!$row['startdat'])
					$datstring = $this->pi_getLL('nodate');
				// don't show time - just date if set in record or ts option is set
				else if ($row['showtime'] == 0 || $this->conf['listview.']['dontShowTime']) {
					// begin and end at one day
					if ($ende_datum==$beginn_datum || !$row['enddat']) $datstring = $beginn_datum;
					// begin and end at different days
					else $datstring = $beginn_datum.' '.$this->pi_getLL('until').' '.$ende_datum;
				}
				// show time
				else {
					// no end date
					if (!$row['enddat']) $datstring = $beginn_datum.', '.$beginn_uhrzeit;
					// begin and end on the same day
					else if ($ende_datum==$beginn_datum ) $datstring = $beginn_datum.' '.$beginn_uhrzeit.' '.$this->pi_getLL('until').' '.$ende_uhrzeit;
					// begin and end at different days
					else $datstring = $beginn_datum .', '.$beginn_uhrzeit.' '.$this->pi_getLL('until').' '.$ende_datum.', '.$ende_uhrzeit;
				}

				// generate link
				#$overrulePIvars = array('showUid' => $row['dateuid']);
				#$linkStart = $this->pi_linkTP_keepPIvars_url ($overrulePIvars, $cache=1, $clearAnyway=0);
				unset($linkconf);
				$linkconf['parameter'] = $this->singleviewPid;
				$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.intval($row['dateuid']);
				$linkconf['additionalParams'] .= '&tx_keyac_pi1[showCal]='.intval($this->piVars['showCal']);
				// set backlink pid if singleview pid is different from current pid
				if ($this->singleviewPid != $GLOBALS['TSFE']->page['uid']) $linkconf['additionalParams'] .= '&tx_keyac_pi1[backPid]='.$GLOBALS['TSFE']->page['uid'];
				$linkconf['useCacheHash'] = true;
				$linkStart = $this->cObj->typoLink_URL($linkconf);

				// generate anchor tag
				if ($day!=0 && $month!=0 && $year!=0) $anchor = '';
				else $anchor = '<a name="'.$start.'" >&nbsp;</a>';

				// generate category icon - use default if no cat set
				$catIconConf = $this->conf['categoryIcon.'][$catRow['uid'].'.'];
				if (empty($catIconConf)) $catIconConf = $this->conf['categoryIcon.']['def.'];
				$catIcon = $this->cObj->IMAGE($catIconConf);

				// generate thumbnail
				$images = t3lib_div::trimExplode(',',$row['images'], 1);
				unset($thumbnail);
				if (sizeof($images)) {
					$thumbConf = $this->conf['listview.']['thumbnail.'];
					$thumbConf['file'] = 'uploads/tx_keyac/'.$images[0];
					$thumbnail = $this->cObj->IMAGE($thumbConf);
				}
				// show default image if activated
				else if ($this->conf['showDefaultImageInListview'] || $this->ffdata['showDefaultImageInListview']) {
					$thumbConf = $this->conf['listview.']['thumbnail.'];
					$thumbConf['file'] = $this->conf['listviewDefaultImg.']['file'];
					$thumbnail = $this->cObj->IMAGE($thumbConf);
				}

				// attendees
				$attendeesArray = $this->getAttendees($row['uid']);
				#debug($attendeesArray);

				if (sizeof($attendeesArray)) {
					$i = 1;
					$attendeesString = '';
					foreach ($attendeesArray as $key=>$data) {
						$attendeesString .= $this->getUserNameFromUserId($data['uid']);
						if ($i < count($attendeesArray)) $attendeesString .= ', ';
						$i++;
					}
				}
				else $attendeesString = '';

				$markerArray = array(
					'title' => $row['datetitle'],
					'date' => $datstring,
					'anchor' => $anchor,
					'caticon' => $catIcon,
					'more_icon' => $moreIcon,
					'more_text' => $this->pi_getLL('more'),
					'link_start' => '<a href="'.$linkStart.'">',
					'link_end' => '</a>',
					'thumbnail' => $thumbnail,
					'teasertext' => $this->pi_RTEcssText($row['teaser']),
					'location' => $row['location'],
					'css_row' => $i%2 == 0 ? 'even' : 'odd',
					'label_attendees' => $this->pi_getLL('attendees'),
					'attendees' => $attendeesString,
				);

				// private event and user is not the owner: show as private
				if ($row['private'] && $GLOBALS['TSFE']->fe_user->user['uid'] != $row['owner']) {
				    $ownerData = $this->getUserRecord($row['owner']);
					$markerArray['title'] = $this->pi_getLL('private_event');
					/*
						. ' ('
						. $this->getUserNameFromUserId($ownerData['uid'])
						. ')';
					*/
					$markerArray['link_start'] = '';
					$markerArray['link_end'] = '';
					$markerArray['teasertext'] = '';
				}

				// special css for first row
				if ($i==0) $markerArray['css_row'] .= ' first';

				// Hook for additional markers (if not deactivated in typoscript)
				if (!$this->conf['deactivateAdditionalListviewMarkersHook']) {
				    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_keyac']['additionalListviewMarkers'])) {
					    foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_keyac']['additionalListviewMarkers'] as $_classRef) {
						    $_procObj = & t3lib_div::getUserObj($_classRef);
						    $_procObj->additionalListviewMarkers($markerArray,$this);
					    }
				    }
				}

				// use listview or special tooltip subpart for rendering?
				$subpart = $tooltip ? '###TOOLTIP_ROW###' : '###LISTVIEW_SINGLE###';
				$temp_content = $this->cObj->getSubpart($this->templateCode,$subpart);
				$temp_content = $this->cObj->substituteMarkerArray($temp_content,$markerArray,$wrap='###|###',$uppercase=1);
				$content .= $temp_content;
			}

			$i++;
		}
		return $content;
	}


	/**
 	* Gets the username from a user uid.
 	* Takes into account the database fields first_name, last_name, name and username.
 	*
 	* @param   integer $userId
 	* @global  string
 	* @static
 	* @author  Christian Buelter <buelter@kennziffer.com>
 	* @since   Wed Apr 07 2010 11:00:48 GMT+0200
 	*/
	function getUserNameFromUserId ($userId) {
		$userName = '';
		$data = $this->fe_getRecord('*', 'fe_users', 'uid=' . $userId);
		if ($data) {
			if ($data['last_name']) {
				if ($data['last_name']) {
					$userName = $data['first_name'] . ' ';
				}
				$userName .= $data['last_name'];
			} else if ($data['name']) {
				$userName = $data['name'];
			} else {
				$userName = $data['username'];
			}
		}
		return $userName;
	}
	
	function getUserNameFromUserRecord ($userRow) {
		$userName = '';
		$data = $userRow;
		if ($data) {
			if ($data['last_name']) {
				if ($data['last_name']) {
					$userName = $data['first_name'] . ' ';
				}
				$userName .= $data['last_name'];
			} else if ($data['name']) {
				$userName = $data['name'];
			} else {
				$userName = $data['username'];
			}
		}
		return $userName;
	}
	

	/**
	 * fe_getRecord
	 *
	 * Returns one record from a certain table.
	 *
	 * @param string $fields
	 * @param string $from_table
	 * @param string $where_clause
	 * @param string $groupBy
	 * @param string $orderBy
	 * @param string $limit
	 * @access public
	 * @return array / false
	 */
	function fe_getRecord($fields, $from_table, $where_clause, $groupBy='',$orderBy='',$limit='1') {
		$lcObj=t3lib_div::makeInstance('tslib_cObj');
		$where_clause .= $lcObj->enableFields($from_table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$from_table,$where_clause,$groupBy,$orderBy,$limit);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		}
		return is_array($row) ? $row : false;
	}


	/**
	 * single view of event
	 */
	function singleView($id) {
		$lcObj=t3lib_div::makeInstance('tslib_cObj');

		// get event data from db
		$table = 'tx_keyac_dates';
		$fields = '*, tx_keyac_dates.uid as dateuid, tx_keyac_dates.title as datetitle';
		$enableFields = $lcObj->enableFields('tx_keyac_dates',$show_hidden=0);
		$where=' tx_keyac_dates.uid="'.$id.' " ';
		$where.=$enableFields;

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
		$numberOfResults = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			// check authorization
			// private: show only if user is owner
			if ($row['private'] && $GLOBALS['TSFE']->fe_user->user['uid'] != $row['owner']  ) {
			    $content = $this->cObj->getSubpart($this->templateCode,'###GENERAL_MESSAGE###');
			    $content = $this->cObj->substituteMarker($content,'###CSSCLASS###','message-fail');
			    $content = $this->cObj->substituteMarker($content,'###TEXT###',$this->pi_getLL('no_authorization'));
			    $content = $this->cObj->substituteMarker($content,'###BACKLINK_ICON###', $this->backlinkIcon);
			    $content = $this->cObj->substituteMarker($content,'###BACKLINK###',$this->getListviewLink($this->pi_getLL('back')));
			    return $content;
			}

			// set pagetitle to event title
			if ($this->conf['setPagetitle']) {
				$GLOBALS['TSFE']->page['title'] = $row['datetitle'];
				$GLOBALS['TSFE']->indexedDocTitle = $row['datetitle'];
			}

			// get category data from db
			$fields = '*';
 			$table = 'tx_keyac_cat, tx_keyac_dates_cat_mm';
 			$where = 'uid_local="'.$row['dateuid'].'" ';
 			$where .= 'AND uid_foreign=tx_keyac_cat.uid ';
 			$where .= $this->cObj->enableFields('tx_keyac_cat');
 			$catRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
			while ($catRow=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($catRes)) {
			    $categoriesText .= $catRow['title'].', ';
			}
			$categoriesText = trim($categoriesText);
			$categoriesText = t3lib_div::rm_endcomma($categoriesText);


			// no end date set
			if (!$row['enddat']) $ende_uhrzeit =0;

			// date and time
			strftime('%H:%M',$row['startdat']) != '00:00' ? $beginn = strftime($this->formatStringWithTime,$row['startdat']) : $beginn = strftime($this->formatStringWithoutTime,$row['startdat']);
			// begin date
			$beginn_datum = strftime($this->formatStringWithoutTime,$row['startdat']);
			// begin time
			strftime('%H:%M',$row['startdat']) != '00:00' ? $beginn_uhrzeit = strftime($this->formatTime,$row['startdat']) : $beginn_uhrzeit = "";
			strftime('%H:%M',$row['enddat']) != '00:00' ? $ende = strftime($this->formatStringWithTime,$row['enddat']) : $ende = strftime($this->formatStringWithoutTime,$row['enddat']);
			// end date
			$ende_datum = strftime($this->formatStringWithoutTime,$row['enddat']);
			// end time
			strftime('%H:%M',$row['enddat']) != '00:00' ? $ende_uhrzeit = strftime($this->formatStringWithoutTime,$row['enddat']) : $ende_uhrzeit = "";
			strftime('%H:%M',$row['enddat']) != '00:00' ? $ende_uhrzeit = strftime($this->formatTime,$row['enddat']) : $ende_uhrzeit = "";
			$dat = array('start datum' => $beginn_datum,
				'ende datum' => $ende_datum,
				'start zeit' => $beginn_uhrzeit,
				'ende zeit' => $ende_uhrzeit
			);

			// no start date set
			if (!$row['startdat'])	$datstring = $this->pi_getLL('nodate');
			// don't show time - just date
			else if ($row['showtime'] == 0) {
				// begin and end at one day
				if ($ende_datum==$beginn_datum || !$row['enddat'])  	$datstring = $beginn_datum;
				// begin and end at different days
				else $datstring = $beginn_datum.' '.$this->pi_getLL('until').' '.$ende_datum;
			}
			// show time
			else {
				// no end date
				if (!$row['enddat'])	$datstring = $beginn_datum.', '.$beginn_uhrzeit.' Uhr<br />';
				// begin and end on the same day
				else if ($ende_datum==$beginn_datum ) $datstring = $beginn_datum.'<br />'.$beginn_uhrzeit.' '.$this->pi_getLL('until').' '.$ende_uhrzeit.'<br />';
				// begin and end at different days
				else $datstring = $beginn_datum .', '.$beginn_uhrzeit.'<br /> '.$this->pi_getLL('until').' '.$ende_datum.', '.$ende_uhrzeit;
			}

			// generate infolink
			if ($row['infolink']) {
				$infolinkText = $row['infolink_text'] ? $row['infolink_text'] : $row['infolink'];
				$infolink = $this->pi_linkToPage($infolinkText,$row['infolink'],$target='_blank',$urlParameters=array());
				#unset($linkconf);
				#$linkconf['parameter'] = $row['infolink'];
				#$infolink =$this->cObj->typoLink($infolinkText,$linkconf);
			}

			// generate backlink
			if ($this->piVars['backPid']) $backlink = $this->pi_linkToPage($this->pi_getLL('back'),intval($this->piVars['backPid']),$target='',$urlParameters=array());
			else $backlink = $this->getListviewLink($this->pi_getLL('back'));

			// generate attendance info / link
			if ($this->feuserIsAttendent($GLOBALS['TSFE']->fe_user->user['uid'],$id)) {
				$attendanceStatus = $this->pi_getLL('user_is_attendee');
				unset($linkconf);
				$linkconf['parameter'] = $GLOBALS['TSFE']->id;
	 			$linkconf['additionalParams'] = '&'.$this->prefixId.'[showUid]='.intval($this->piVars['showUid']);
	 			$linkconf['additionalParams'] .= '&'.$this->prefixId.'[action]=delattendance';
	 			$linkconf['useCacheHash'] = true;
	 			$attendanceAction = $this->cObj->typoLink($this->pi_getLL('delete_attendance'),$linkconf);
			} else {
				// user is no attendant
				$attendanceStatus = $this->pi_getLL('user_is_no_attendee');
				unset($linkconf);
				$linkconf['parameter'] = $GLOBALS['TSFE']->id;
	 			$linkconf['additionalParams'] = '&'.$this->prefixId.'[showUid]='.intval($this->piVars['showUid']);
	 			$linkconf['additionalParams'] .= '&'.$this->prefixId.'[action]=attend';
	 			$linkconf['useCacheHash'] = true;
	 			$attendanceAction = $this->cObj->typoLink($this->pi_getLL('attend'),$linkconf);
			}

			// owner data
			$ownerData = $this->getUserRecord($row['owner']);

			// link to singleview
			unset($linkconf);
			$linkconf['parameter'] = $GLOBALS['TSFE']->id;
			$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.intval($this->piVars['showUid']);
			$linkconf['additionalParams'] .= '&tx_keyac_pi1[showCal]='.intval($this->piVars['showCal']);
			$linkconf['additionalParams'] .= '&tx_keyac_pi1[backPid]='.intval($this->piVars['backPid']);
			$linkconf['useCacheHash'] = true;
			$singleview_url =$this->cObj->typoLink_URL($linkconf);

			// fill markers
			$this->markerArray = array(
				'title' => $row['datetitle'],
				'category' => $categoriesText,
				'label_event' => $this->pi_getLL('event'),
				'datestring' => $datstring,
				'label_location' => $this->pi_getLL('location'),
				'location' => $row['location'],
				'label_teasertext' => $this->pi_getLL('teaser'),
				'teasertext' => $this->pi_RTEcssText($row['teaser']),
				'label_description' => $this->pi_getLL('description'),
				'description' => $this->pi_RTEcssText($row['bodytext']),
				'label_infolink' => $this->pi_getLL('infolink'),
				'infolink' => $infolink,
				'infolink_icon' => $this->cObj->IMAGE($this->conf['singleview.']['infolinkIcon.']),
				'label_images' => $this->pi_getLL('images'),
				'images' => $this->renderFEField('images',$row['images']),
				'label_attachments' => $this->pi_getLL('attachments'),
				'attachments' => $this->renderFEField('attachments',$row['attachments']),
				'label_attendees' => $this->pi_getLL('attendees'),
				'attendees' => $this->renderFEField('attendees',$row['dateuid']),
				'backlink' => $backlink,
				'backlink_icon' => $this->cObj->IMAGE($this->conf['singleview.']['backlinkIcon.']),
				'label_attendance' => $this->pi_getLL('attendance'),
				'attendance_status' => $attendanceStatus,
				'attendance_action' => $attendanceAction,
				'label_owner' => $this->pi_getLL('owner'),
				'owner_first_name' => '',
				'owner_last_name' => $this->getUserNameFromUserId($row['owner']),
				'owner_username' => $ownerData['username'],
				'owner_email' => $ownerData['email'],
				'creation_date' => strftime('%d.%m.%Y', $row['crdate']),
				'edit_date' => strftime('%d.%m.%Y', $row['tstamp']),
				'address' => $row['address'],
				'zip' => $row['zip'],
				'city' => $row['city'],
				'singleview_url' => $singleview_url,
				'to_form' => $this->pi_getLL('to_form','zur Anmeldung'),
				'label_creation_date' => $this->pi_getLL('label_creation_date'),
				'label_edit_date' => $this->pi_getLL('label_edit_date'),
			);

			// show map?
			if ($row['location'] && $row['address'] && $row['zip'] && $row['city']) {
				// include api file
				if (!class_exists('GoogleMapAPI')) require_once(dirname(__FILE__). '/../res/GoogleMapAPI.class.php');
				
				// render map
				$this->markerArray['map'] = $this->renderGoogleMap(
					$this->getFieldContent('gmaps_address',$row),
					$this->getFieldContent('gmaps_company',$row),
					1,
					$this->getFieldContent('gmaps_htmladdress',$row),
					$row['googlemap_zoom']
				);
				// get js content for map
				$this->markerArray['mapJS'] = $this->gmapsJSContent;
				$this->markerArray['label_map'] = $this->pi_getLL('map');
				// add onload and onunload functions to body tag
				$GLOBALS['TSFE']->pSetup['bodyTagAdd'] = " onload=\"onLoad1();\" onunload=\"GUnload();\"";
			}

			// Hook for additional markers
			if (!$this->conf['deactivateAdditionalSingleviewMarkersHook']) {
			    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_keyac']['additionalSingleviewMarkers'])) {
				    foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_keyac']['additionalSingleviewMarkers'] as $_classRef) {
					    $_procObj = & t3lib_div::getUserObj($_classRef);
					    $_procObj->additionalSingleviewMarkers($this);
				    }
			    }
			}

			// fill marker
			$content = $this->cObj->getSubpart($this->templateCode,'###SINGLEVIEW_TEMPLATE###');
			$content = $this->cObj->substituteMarkerArray($content,$this->markerArray,$wrap='###|###',$uppercase=1);

			// overwrite subparts if no content available
			if (empty($row['teaser'])) $content = $this->cObj->substituteSubpart($content, '###SUB_TEASERTEXT###', '');
			if (empty($row['bodytext'])) $content = $this->cObj->substituteSubpart($content, '###SUB_DESCRIPTION###', '');
			if (empty($row['infolink'])) $content = $this->cObj->substituteSubpart($content, '###SUB_INFOLINK###', '');
			if (empty($row['images']) && !$this->conf['showDefaultImageInSingleview'] && !$this->ffdata['showDefaultImageInSingleview']) $content = $this->cObj->substituteSubpart($content, '###SUB_IMAGES###', '');
			if (empty($row['attachments'])) $content = $this->cObj->substituteSubpart($content, '###SUB_ATTACHMENTS###', '');
			if (!$row['location'] || !$row['address'] || !$row['zip'] || !$row['city']) $content = $this->cObj->substituteSubpart($content, '###SUB_MAP###', '');
			if (!$row['address'] || !$row['zip'] || !$row['city'])  $content = $this->cObj->substituteSubpart($content, '###SUB_ADDRESS###', '');
			if (!$GLOBALS['TSFE']->loginUser) $content = $this->cObj->substituteSubpart($content, '###SUB_ATTENDANCE###', '');
			// special: show link to form only if form is activated in record
			if (!$row['tx_keseminars_show_form']) $content = $this->cObj->substituteSubpart($content, '###SUB_LINK_TO_FORM###', '');


			// generate footer menu
			$footerMenuContent = $this->generateFooterMenu();
			$content = $this->cObj->substituteSubpart ($content, '###SUB_FOOTER_MENU###', $footerMenuContent, $recursive=1);

			// event has attendees?
			#debug($this->getNumberOfAttendees($id),'Teilnehmer');
			if (!$this->getNumberOfAttendees($id)) {
				$content = $this->cObj->substituteSubpart ($content, '###SUB_ATTENDEES###', '');
			}
			
			// UNIVERSAL KEWORKS BROWSER
			// AK 13.04.2010
			if (t3lib_extMgm::isLoaded('ke_ukb')) {
				require_once(t3lib_extMgm::extPath('ke_ukb').'class.ke_ukb.php');
				$ukb = t3lib_div::makeInstance('ke_ukb');
				$content = $this->cObj->substituteMarker($content,'###LABEL_UKB###', $this->pi_getLL('label_ukb'));
				$content = $this->cObj->substituteMarker($content,'###UKB_CONTENT###', $ukb->renderContent('tx_keyac_dates', intval($this->piVars['showUid'])));
				$content = $this->cObj->substituteMarker($content,'###UKB_FORM###', $ukb->renderForm());
			}
			else $content = $this->cObj->substituteSubpart ($content, '###SUB_UNIVERSAL_KEWORKS_BROWSER###', '');
			
			
		}

		// Event not found
		if (!$numberOfResults) {
		    $content = $this->pi_getLL('event_not_found');
		    $content = $this->cObj->getSubpart($this->templateCode,'###GENERAL_MESSAGE###');
		    $content = $this->cObj->substituteMarker($content,'###CSSCLASS###','message-fail');
		    $content = $this->cObj->substituteMarker($content,'###TEXT###',$this->pi_getLL('event_not_found'));
		    $content = $this->cObj->substituteMarker($content,'###BACKLINK_ICON###', $this->backlinkIcon);
		    $content = $this->cObj->substituteMarker($content,'###BACKLINK###',$this->getListviewLink($this->pi_getLL('back')));
		}

		return $content;
	}


	/**
	* myEventsView
	*
 	* render view "my events"
 	* return string	html content
 	*/
 	function myEventsView() {

		$content = $this->cObj->getSubpart($this->templateCode,'###MYEVENTS###');

		// print message if no user is logged in
		if (!$GLOBALS['TSFE']->loginUser) {
			$content = $this->cObj->substituteSubpart ($content, '###MYEVENTS_ROW###', $this->pi_getLL('no_login'));
			return $content;
		}

		// singleview pid
		$singleViewPid = $this->ffdata['myEventsSinglePid'] ? $this->ffdata['myEventsSinglePid'] : $this->conf['myEvents.']['singleViewPid'];

		// icon
		$myEventsIcon = $this->cObj->IMAGE($this->conf['myEvents.']['icon.']);

		// today
		$today = strtotime('today');

		$fields = '*';
 		$table = 'tx_keyac_dates, tx_keyac_dates_attendees_mm';
 		$where = 'tx_keyac_dates.uid=uid_local ';
		$where .= ' AND enddat >= '.$today.' ';
 		$where .= 'AND uid_foreign="'.intval($GLOBALS['TSFE']->fe_user->user['uid']).'" ';
 		$where .= $this->cObj->enableFields('tx_keyac_dates');
 		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='startdat asc',$limit='');
 		$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
 		$rowsContent = '';
		$i=1;
		while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			// get formatstring for strftime from ts
			$startNoTime = strftime($this->conf['myevents.']['strftimeFormatStringWithoutTime'], $row['startdat']);
			$endNoTime = strftime($this->conf['myevents.']['strftimeFormatStringWithoutTime'], $row['enddat']);
			$startWithTime = strftime($this->conf['myevents.']['strftimeFormatStringWithTime'], $row['startdat']);
			$endWithTime = strftime($this->conf['myevents.']['strftimeFormatStringWithTime'], $row['enddat']);
			$endTimeOnly = strftime($this->conf['myevents.']['strftimeFormatTime'], $row['enddat']);
			$startday = strftime('%d.%m.%Y',$row['startdat']);
			$endday = strftime('%d.%m.%Y',$row['enddat']);
			

			// do not print time in teaser
			if (!$row['showtime'] || $this->conf['teaser.']['dontShowTime'] ) {
				if ($startNoTime == $endNoTime) $timestr = $startNoTime;
				else $timestr = $startNoTime.' - '.$endNoTime;
			}
			// print time information
			else {
				if ($startWithTime == $endWithTime) $timestr = $startWithTime;
				else if ($startday == $endday) $timestr = $startWithTime.' - '.$endTimeOnly;
				else $timestr = $startWithTime.' - '.$endWithTime;
			}
			
			// generate single view url
			$linkconf['parameter'] = $singleViewPid;
 			$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.$row['uid'];
 			$linkconf['additionalParams'] .= '&tx_keyac_pi1[backPid]='.$GLOBALS['TSFE']->id;
 			$linkconf['useCacheHash'] = true;
 			$singleViewURL = $this->cObj->typoLink_URL($linkconf);

			$tempContent = $this->cObj->getSubpart($this->templateCode,'###MYEVENTS_ROW###');
			$tempMarker = array(
				'icon' => $myEventsIcon,
				'link_start' => '<a href="'.$singleViewURL.'">',
				'link_end' => '</a>',
				'title' => $row['title'],
				'teasertext' => $this->pi_RTEcssText($row['teaser']),
				'date' => $timestr,
				'css_class' => $i%2 ? 'odd' : 'even',
			);
			$tempContent = $this->cObj->substituteMarkerArray($tempContent,$tempMarker,$wrap='###|###',$uppercase=1);

			$rowsContent .= $tempContent;
			$i++;
 		}

		if ($anz) $content = $this->cObj->substituteSubpart ($content, '###MYEVENTS_ROW###', $rowsContent, $recursive=1);
		else $content = $this->cObj->substituteSubpart ($content, '###MYEVENTS_ROW###', $this->pi_getLL('noResults'), $recursive=1);

		t3lib_div::debug($this->pi_getLL('myevents_header'),1);
		$content = $this->cObj->substituteMarker($content,'###HEADER###',$this->pi_getLL('myevents_header'));


		return $content;
 	}





	/**
 	* Description:
 	* Author: Andreas Kiefer (kiefer@kennziffer.com)
 	*
 	*/
 	function renderGoogleMap($address,$company,$i,$htmladdress,$zoom) {
		
		//Create dynamic DIV to show GoogleMaps-element in
		$gMaps = new GoogleMapAPI('keyac_map_'.$i);

		//Set API-Key(s)
		$gMaps->setAPIKey($this->conf['gmaps.']['apiKey']);

		// zoomLevel
		$gmapsZoom = $zoom>0 ? $zoom : $this->conf['gmaps.']['defaultZoom'];

		//GoogleMaps-Settings
		$gMaps->setWidth($this->conf['gmaps.']['width']);
		$gMaps->setHeight($this->conf['gmaps.']['height']);
		$gMaps->setZoomLevel($gmapsZoom);
		$gMaps->addMarkerByAddress($address,$company,nl2br($htmladdress),$company);
		$gMaps->setInfoWindowTrigger('mouseover');
		if ($this->conf['gmaps.']['disableMapControls']) $gMaps->disableMapControls();
		if ($this->conf['gmaps.']['enableTypeControls']) $gMaps->enableTypeControls();

		//Create cacheable, dynamical js-File
		$md5= md5($address.$i);
		$filename="typo3temp/gmap_{$md5}.js";
		$fh=fopen($filename,'w');
		fputs($fh,preg_replace('/<\/?script[^>]*>/i','',$gMaps->getMapJS($i)));
		fclose($fh);

		//Include requires JS and GoogleMap-element
		$sidebar_dummy='<div id="sidebar_keyac_map_'.$i.'" style="display:none"></div>';
		$content= $sidebar_dummy.$gMaps->getMap();
		$this->gmapsJSContent .= "\n\n".$gMaps->getHeaderJS()."\n<script src='{$filename}' type='text/javascript' ></script>";
		$GLOBALS["TSFE"]->additionalHeaderData[$this->prefixId] .= '
			<script type="text/javascript" >
				function keyac_popit_'.$i.'() {
					if(isArray(marker_html_'.$i.'[0])) { markers[0].openInfoWindowTabsHtml(marker_html_'.$i.'[0]); }
				else { markers[0].openInfoWindowHtml(marker_html_'.$i.'[0]); }
				}
			</script>';

		return $content;
 	}


	/**
 	* Description:
 	* Author: Andreas Kiefer (kiefer@kennziffer.com)
 	*
 	*/
 	function renderFEField($fieldname, $data) {

		switch ($fieldname) {

			// IMAGES
			case 'images':
				// explode images string
				$images = t3lib_div::trimExplode (',', $data, $onlyNonEmptyValues=1);
				// run through the array and render image as set in ts
				if (sizeof($images)) {
					foreach ($images as $img) {
						$imgConf = $this->conf['singleviewImg.'];
						$imgConf['file'] = 'uploads/tx_keyac/'.$img;
						$imgContent = $this->cObj->getSubpart($this->templateCode,'###IMAGE_ROW###');
						$imgContent = $this->cObj->substituteMarker($imgContent,'###IMAGE###',$this->cObj->IMAGE($imgConf));
						$fieldContent .= $imgContent;
					}
				}
				else {
					// show default image
					if ($this->conf['showDefaultImageInSingleview'] || $this->ffdata['showDefaultImageInSingleview']) {
						$imgConf = $this->conf['singleviewDefaultImg.'];
						$imgContent = $this->cObj->getSubpart($this->templateCode,'###IMAGE_ROW###');
						$imgContent = $this->cObj->substituteMarker($imgContent,'###IMAGE###',$this->cObj->IMAGE($imgConf));
						$fieldContent .= $imgContent;
					}
				}
				break;

			// ATTACHMENTS
			case 'attachments':
				// explode attachments string
				$attachments = explode(',', $data);
				// run through the array and render links to files
				foreach ($attachments as $att) {
					unset($linkconf);
					// generate link
					$linkconf['parameter'] = 'uploads/tx_keyac/'.$att;
					$linkconf['target'] = '_blank';

					// generate attachment icon
					$filetype = strtolower(substr(strrchr($att, '.'), 1));
					if (!empty($this->conf['attachmentIcon.'][$filetype.'.'])) $imageConf = $this->conf['attachmentIcon.'][$filetype.'.'];
					else $imageConf = $this->conf['attachmentIcon.']['default.'];
					$attIcon = $this->cObj->IMAGE($imageConf);

					$attContent = $this->cObj->getSubpart($this->templateCode,'###ATTACHMENT_ROW###');
					$attContent = $this->cObj->substituteMarker($attContent,'###ATTACHMENT_LINK###',$this->cObj->typoLink($att,$linkconf));
					$attContent = $this->cObj->substituteMarker($attContent,'###ATTACHMENT_ICON###',$attIcon);
					$fieldContent .= $attContent;
				}
				break;

			// OWNER
			case 'owner':
				$fields = '*';
 				$table = 'fe_users';
 				$where = 'uid="'.intval($data).'" ';
 				$where .= $this->cObj->enableFields($table);
 				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='1');
 				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$fieldContent .= $this->cObj->getSubpart($this->templateCode,'###OWNER_ROW###');

					// generate www link
					unset($linkconf);
					$linkconf['parameter'] = $row['www'].' _blank';
					$wwwLink = $this->cObj->typoLink($row['www'],$linkconf);

					// generate email link
					unset($linkconf);
					$linkconf['parameter'] = $row['email'];
					$emailLink = $this->cObj->typoLink($row['email'],$linkconf);

					$markerArray = array (
						'name' => $this->getUserNameFromUserId($row['uid']),
						'email' => $emailLink,
						'company' => $row['company'],
						'www'  => $wwwLink,
					);
					$fieldContent = $this->cObj->substituteMarkerArray($fieldContent,$markerArray,$wrap='###|###',$uppercase=1);
 				}

				break;

			// ATTENDEES
			case 'attendees':

				unset($imageConf);
				$imageConf['file'] = t3lib_extMgm::siteRelPath($this->extKey).'res/images/attendee.gif';
				$icon=$this->cObj->IMAGE($imageConf);

				$fields = '*';
 				$table = 'fe_users, tx_keyac_dates_attendees_mm';
 				$where = 'uid_local = "'.intval($data).'" ';
 				$where .= 'AND uid_foreign=fe_users.uid';
 				$where .= $this->cObj->enableFields('fe_users');
 				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='last_name',$limit='');
 				$i=1;
				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$fieldContent .= $this->cObj->getSubpart($this->templateCode,'###ATTENDEE_ROW###');

					// generate www link
					unset($linkconf);
					$linkconf['parameter'] = $row['www'].' _blank';
					$wwwLink = $this->cObj->typoLink($row['www'],$linkconf);

					// generate email link
					unset($linkconf);
					$linkconf['parameter'] = $row['email'];
					$emailLink = $this->cObj->typoLink($row['email'],$linkconf);

					$markerArray = array (
						'name' => $this->getUserNameFromUserId($row['uid']),
						'email' => $emailLink,
						'company' => $row['company'],
						'www'  => $wwwLink,
						'css_row' => $i%2 == 0 ? 'attendees-firstrow' : 'attendees-secondrow',
						'attendee_icon' => $icon,
						'first_name' => '',
						'last_name' => $this->getUserNameFromUserId($row['uid']),
					);

					$fieldContent = $this->cObj->substituteMarkerArray($fieldContent,$markerArray,$wrap='###|###',$uppercase=1);
					$i++;
 				}
				break;
		}

		return $fieldContent;
 	}



	/*
	* returns timestamp for start of month
	*/
	function getStartTimestamp($month,$year) {
		return mktime(0,0,0,$month,1,$year);
	}

	/*
	* returns timestamp for end of month
	*/
	function getEndTimestamp($month,$year) {
		// get number of days in this month
		$days_month = date("t",$this->getStartTimestamp($month,$year));
		return mktime(23,59,59,$month,$days_month,$year);
	}


	/*
	* returns legend for calendar view
	*/
	function legend() {
		$fields = '*';
		$table = 'tx_keyac_cat';
		$enableFields = $this->cObj->enableFields($table,$show_hidden=0);
		$where = '1=1 '.$enableFields;
		$where.=' and tx_keyac_cat.pid in ('.$this->pids.') ';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
		//$lv=0;
		$cat_entries = '';
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			// generate category icon
			$catIcon=$this->cObj->IMAGE($this->conf['categoryIcon.'][$row['uid'].'.']);

			$markerArray = array(
				'caticon' => $catIcon,
				'cattext' => $row['title'],
			);

			$listitem_temp = $this->cObj->getSubpart($this->templateCode,'###LEGEND_SINGLE###');
			$listitem_temp = $this->cObj->substituteMarkerArray($listitem_temp,$markerArray,$wrap='###|###',$uppercase=1);
			$cat_entries .= $listitem_temp;
			
		}

		$content = $this->cObj->getSubpart($this->templateCode,'###LEGEND_TEMPLATE###');
		$content = $this->cObj->substituteMarker($content,'###CAT_ENTRIES###', $cat_entries);

		return $content;
	}


	/*
	* show teaser for latest events
	*/
	function teaserView() {
		$lcObj=t3lib_div::makeInstance('tslib_cObj');
		
		// get pid of singleView and no. of entries in teaser; either from flexforms or from ts
		$singlePid = $this->ffdata['singlePid'] ? $this->ffdata['singlePid'] : $this->conf['singlePid'];
		$limit = $this->ffdata['teaserLimit'] ? $this->ffdata['teaserLimit'] : $this->conf['teaserLimit'];
		$teaserlength = $this->ffdata['teaserLength'] ? $this->ffdata['teaserLength'] : $this->conf['teaserLength'];

		// get data from db
		$table = 'tx_keyac_dates';
		$enableFields = $lcObj->enableFields($table,$show_hidden=0);
		$jetzt = time();
		$where = "startdat > ".$jetzt." ".$enableFields;
		$where.=' and tx_keyac_dates.pid in ('.$this->pids.') ';

		// extend where clause if only one given category is shown
		if ($this->ffdata['singleCat']) {
			$catEntries = $this->getCategoryEntriesList($this->ffdata['singleCat']);
			if (!empty($catEntries)) $where .= ' AND tx_keyac_dates.uid in ('.$catEntries.') ';
			else $where .= ' AND 1=2 ';
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table,$where,$groupBy='',$orderBy='startdat asc',$limit);

		$entries = '';

		// if no result -> print message
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))  {
			$entries = $this->cObj->getSubpart($this->templateCode,'###TEASER_NOENTRIES###');
			$entries = $this->cObj->substituteMarker($content,'###NOENTRIES###',$this->pi_getLL('noItemsInCategory'));
		}
		// if result -> print teaser box with data
		else {
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				
				// get formatstring for strftime from ts
				$startNoTime = strftime($this->conf['teaser.']['strftimeFormatStringWithoutTime'], $row['startdat']);
				$endNoTime = strftime($this->conf['teaser.']['strftimeFormatStringWithoutTime'], $row['enddat']);
				$startWithTime = strftime($this->conf['teaser.']['strftimeFormatStringWithTime'], $row['startdat']);
				$endWithTime = strftime($this->conf['teaser.']['strftimeFormatStringWithTime'], $row['enddat']);
				$endTimeOnly = strftime($this->conf['teaser.']['strftimeFormatTime'], $row['enddat']);
				$startday = strftime('%d.%m.%Y',$row['startdat']);
				$endday = strftime('%d.%m.%Y',$row['enddat']);
				
				// shorten title?
				$title = strlen($row['title'])>$this->conf['teaserLength'] ? substr($row['title'],0,$this->conf['teaserLength']).'...' : $row['title'];
				
				// build attendees string
				$attendees = $this->getAttendees($row['uid']);
				$attendeesString = '';
				if (sizeof($attendees)) {
					foreach ($attendees as $key => $data) {
						$attendeesString .= $this->getUserNameFromUserRecord($data).', ';
					}
					$attendeesString = trim($attendeesString);
					$attendeesString = t3lib_div::rm_endcomma($attendeesString);
				}
				else $attendeesString = '';


				// do not print time in teaser
				if (!$row['showtime'] || $this->conf['teaser.']['dontShowTime'] ) {
					if ($startNoTime == $endNoTime) $timestr = $startNoTime;
					else $timestr = $startNoTime.' - '.$endNoTime;
				}
				// print time information
				else {
					if ($startWithTime == $endWithTime) $timestr = $startWithTime;
					else if ($startday == $endday) $timestr = $startWithTime.' - '.$endTimeOnly;
					else $timestr = $startWithTime.' - '.$endWithTime;
				}
				
				// user is not allowed to see private event
				// do not link event
				if ($row['private'] && $GLOBALS['TSFE']->fe_user->user['uid'] != $row['owner']) {
					$linktitle = $this->pi_getLL('private_event');
				}
				else {
					// user is allowed to see private event
					// link event
					$linkconf['parameter'] = $singlePid;
					$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.$row['uid'];
					$linkconf['additionalParams'] .= '&tx_keyac_pi1[backPid]='.$GLOBALS['TSFE']->id;
					$linkconf['useCacheHash'] = true;
					$linktitle = $this->cObj->typoLink($title,$linkconf);
				}
				
				$temp_marker = array(
					'location' => $row['location'],
					'datetime' => $timestr,
					'title' => $linktitle,
					'teasertext' => strip_tags($row['teaser']),
					'city' => $row['city'],
					'attendants' => $attendeesString,
				);
				$temp_content = $this->cObj->getSubpart($this->templateCode,'###TEASER_SINGLE###');
				$temp_content = $this->cObj->substituteMarkerArray($temp_content,$temp_marker,$wrap='###|###',$uppercase=1);
				$entries .= $temp_content;

			}
		}

		$markerArray = array(
			'title' => $this->pi_getLL('teaserheader'),
			'teaser_entries' => $entries,
		);
		$content = $this->cObj->getSubpart($this->templateCode,'###TEASER_TEMPLATE###');
		$content = $this->cObj->substituteMarkerArray($content,$markerArray,$wrap='###|###',$uppercase=1);



		return $content;
	}


	/**
 	* Description:
 	* Author: Andreas Kiefer (kiefer@kennziffer.com)
 	*
 	*/
 	function getFieldContent($fieldname, $data) {

		switch ($fieldname) {

			case 'gmaps_address':
				$address = $data['address'].' ';
				$address .= $data['zip'].' ';
				$address .= $data['city'];
				return $address;
				break;

			case 'gmaps_company':
				$company = $data['location'];
				break;

			case 'gmaps_htmladdress':
				$htmlAddress = '<b>'.$data['location'].'</b><br />';
				$htmlAddress .= $data['address'].'<br />';
				$htmlAddress .= $dat['zip'].' '.$data['city'].'<br />';
				return $htmlAddress;
				break;

		}

		return $content;
 	}


	/**
 	* Description:
 	* Author: Andreas Kiefer (kiefer@kennziffer.com)
 	*
 	*/
 	function feuserIsAttendent($feUserUid, $eventUid) {
		$fields = '*';
 		$table = 'tx_keyac_dates, tx_keyac_dates_attendees_mm';
 		$where = 'tx_keyac_dates.uid=tx_keyac_dates_attendees_mm.uid_local';
		$where .= ' AND tx_keyac_dates_attendees_mm.uid_local ="'.$eventUid.'" ';
		$where .= ' AND tx_keyac_dates_attendees_mm.uid_foreign="'.$feUserUid.'" ';
 		$where .= $this->cObj->enableFields('tx_keyac_dates');
 		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
 		$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
 		if ($anz) return true;
		else return false;
 	}


	/**
 	* Description:
 	* Author: Andreas Kiefer (kiefer@kennziffer.com)
 	*
 	*/
 	function clearPageCache($pid) {
		$TCE = t3lib_div::makeInstance('t3lib_TCEmain');
		$TCE->admin = 1;
		$TCE->clear_cacheCmd('pages');
		$TCE->clear_cacheCmd($pid);
 	}


	/*
	 * function getNumberOfAttendees
	 * @param $arg
	 */
	function getNumberOfAttendees($eventUid) {
		$fields = '*';
 		$table = 'fe_users, tx_keyac_dates_attendees_mm';
 		$where = 'tx_keyac_dates_attendees_mm.uid_local="'.$eventUid.'" ';
		$where .= ' AND tx_keyac_dates_attendees_mm.uid_local ="'.$eventUid.'" ';
		$where .= ' AND tx_keyac_dates_attendees_mm.uid_foreign=fe_users.uid';
 		$where .= $this->cObj->enableFields('fe_users');
 		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
 		return $GLOBALS['TYPO3_DB']->sql_num_rows($res);
	}


	/*
	 * function generateFooterMenu
	 */
	function generateFooterMenu() {

		// user already attends: delete attendance
		if ($this->feuserIsAttendent($GLOBALS['TSFE']->fe_user->user['uid'],intval($this->piVars['showUid']))) {
			// delete attendance
			$attend = $this->cObj->getSubpart($this->templateCode,'###SUB_ATTEND###');
			unset($linkconf);
			$linkconf['parameter'] = $GLOBALS['TSFE']->id;
			$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.intval($this->piVars['showUid']);
			$linkconf['additionalParams'] .= '&tx_keyac_pi1[action]=delattendance';
			$linkconf['useCacheHash'] = false;
			$attend = $this->cObj->substituteMarker($attend,'###LINK###',$this->cObj->typoLink($this->pi_getLL('label_button_delete_attendance','delete attendance'),$linkconf));
		}
		// user is no attendant yet
		else {
			// attend
			$attend = $this->cObj->getSubpart($this->templateCode,'###SUB_ATTEND###');
			unset($linkconf);
			$linkconf['parameter'] = $GLOBALS['TSFE']->id;
			$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.intval($this->piVars['showUid']);
			$linkconf['additionalParams'] .= '&tx_keyac_pi1[action]=attend';
			$linkconf['useCacheHash'] = false;
			$attend = $this->cObj->substituteMarker($attend,'###LINK###',$this->cObj->typoLink($this->pi_getLL('label_button_attend','attend'),$linkconf));
		}

		// invite
		$invite = $this->cObj->getSubpart($this->templateCode,'###SUB_INVITE###');
		unset($linkconf);
		$linkconf['parameter'] = $GLOBALS['TSFE']->id;
		$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.intval($this->piVars['showUid']);
		$linkconf['additionalParams'] .= '&tx_keyac_pi1[action]=invite';
		$linkconf['useCacheHash'] = false;
		$invite = $this->cObj->substituteMarker($invite,'###LINK###',$this->cObj->typoLink($this->pi_getLL('label_button_invite','invite'),$linkconf));

		// edit
		$edit = $this->cObj->getSubpart($this->templateCode,'###SUB_EDIT###');
		unset($linkconf);
		$linkconf['parameter'] = $GLOBALS['TSFE']->id;
		$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.intval($this->piVars['showUid']);
		$linkconf['additionalParams'] .= '&tx_keyac_pi1[action]=edit';
		$linkconf['useCacheHash'] = false;
		$edit = $this->cObj->substituteMarker($edit,'###LINK###',$this->cObj->typoLink($this->pi_getLL('label_button_edit','edit'),$linkconf));

		// move
		$move = $this->cObj->getSubpart($this->templateCode,'###SUB_MOVE###');
		unset($linkconf);
		$linkconf['parameter'] = $GLOBALS['TSFE']->id;
		$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.intval($this->piVars['showUid']);
		$linkconf['additionalParams'] .= '&tx_keyac_pi1[action]=move';
		$linkconf['useCacheHash'] = false;
		$move = $this->cObj->substituteMarker($move,'###LINK###',$this->cObj->typoLink($this->pi_getLL('label_button_move','move'),$linkconf));

		// delete
		$delete = $this->cObj->getSubpart($this->templateCode,'###SUB_DELETE###');
		unset($linkconf);
		$linkconf['parameter'] = $GLOBALS['TSFE']->id;
		$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.intval($this->piVars['showUid']);
		$linkconf['additionalParams'] .= '&tx_keyac_pi1[action]=delete';
		$linkconf['useCacheHash'] = false;
		$delete = $this->cObj->substituteMarker($delete,'###LINK###',$this->cObj->typoLink($this->pi_getLL('label_button_delete','delete'),$linkconf));

		// generate complete content
		$content = $this->cObj->getSubpart($this->templateCode,'###SUB_FOOTER_MENU###');
		$content = $this->cObj->substituteSubpart ($content, '###SUB_ATTEND###', $attend, $recursive=1);
		$content = $this->cObj->substituteSubpart ($content, '###SUB_INVITE###', $invite, $recursive=1);
		$content = $this->cObj->substituteSubpart ($content, '###SUB_EDIT###', $edit, $recursive=1);
		$content = $this->cObj->substituteSubpart ($content, '###SUB_MOVE###', $move, $recursive=1);
		$content = $this->cObj->substituteSubpart ($content, '###SUB_DELETE###', $delete, $recursive=1);
		return $content;
	}


	/*
	 * function showInviteForm
	 */

	function showInviteForm($include=false) {

		// check if usergroup configuration is set
		if (empty($this->ffdata['userGroups'])) return 'No usergroups are selected in plugin configuration';

		$fields = '*';
		$table = 'fe_users';
		$where = 'email<>"" AND ';
		// generate where clauses for usergroups
		$i=0;
		// generate invitation rows
		$groups = explode(',',$this->ffdata['userGroups']);
		foreach ($groups as $group) {
		    if ($i>0) $where .= ' OR ';
		    $where .= $GLOBALS['TYPO3_DB']->listQuery('usergroup', $group , 'fe_users');
		    $i++;
		}
		$where .= $this->cObj->enableFields($table);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='last_name',$limit='');
		$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		$rowsContent = '';
		while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			// only show users that arent attendents yet and do not show the current user
			if (!$this->feuserIsAttendent(intval($row['uid']), intval($this->piVars['showUid'])) && intval($row['uid']) != $GLOBALS['TSFE']->fe_user->user['uid']) {
				$name = $this->getUserNameFromUserId($row['uid']);

				$checkbox = '<input type="checkbox" name="'.$this->prefixId.'[user]['.$row['uid'].']" ';
				if ($this->piVars['user'][$row['uid']] == 'on') $checkbox .= ' checked="checked" ';
				$checkbox .= '>';

				$tempContent = $this->cObj->getSubpart($this->templateCode,'###INVITATION_ROW###');
				$tempMarkerArray = array(
					'checkbox' => '<input type="checkbox" name="'.$this->prefixId.'[user]['.$row['uid'].']" >',
					'checkbox' => $checkbox,
					'name' => $name,
				);
				$tempContent = $this->cObj->substituteMarkerArray($tempContent,$tempMarkerArray,$wrap='###|###',$uppercase=1);
				$rowsContent .= $tempContent;
			}
		}

		// generate backlink
		unset($linkconf);
		$linkconf['parameter'] = $GLOBALS['TSFE']->id;
		$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.intval($this->piVars['showUid']);
		$backlink = $this->cObj->typoLink_URL($linkconf);

		// generate form action
		unset($linkconf);
		$linkconf['parameter'] = $GLOBALS['TSFE']->id;
		$linkconf['additionalParams'] = '&tx_keyac_pi1[action]=invite';
		$linkconf['useCacheHash'] = false;
		$action =$this->cObj->typoLink_URL($linkconf);

		$hiddenFields = '<input type="hidden" name="tx_keyac_pi1[showUid]" value="'.intval($this->piVars['showUid']).'" >';

		// generate content
		$subpart = $include ? '###SUB_INVITATION_INCLUDE###' : '###SUB_INVITATION###';
		$content = $this->cObj->getSubpart($this->templateCode,$subpart);
		$markerArray = array(
			'text' => $this->pi_getLL('text_invitation'),
			'submit' => '<input name="'.$this->prefixId.'[submitinvite]" type="submit" value="'.$this->pi_getLL('label_button_submit_invitation').'">',
			'back' => '<input type="button"value="'.$this->pi_getLL('back').'"  onclick="window.location.href=\''.$backlink.'\'" >',
			'action' => $action,
			'hiddenfields' => $hiddenFields,
			'invitation_text_label' => $this->pi_getLL('invitation_text_label'),
			'invitation_text_input' => '<textarea name="tx_keyac_pi1[invitation_text]">'.t3lib_div::removeXSS($this->piVars['invitation_text']).'</textarea>',
			'input_invitation_mode' => $this->renderFormField('invitation_mode'),

		);
		$content = $this->cObj->substituteMarkerArray($content,$markerArray,$wrap='###|###',$uppercase=1);
		$content = $this->cObj->substituteSubpart ($content, '###INVITATION_ROW###', $rowsContent, $recursive=1);

		return $content;

	}

	/*
	 * function processInviteData
	 */

	function processInviteData() {
		
		$eventRow = $this->getEventRecord(intval($this->piVars['showUid']));

		// run through all checked users
		if (count($this->piVars['user'])) {

			foreach ($this->piVars['user'] as $uid => $value) {
				$userRow = $this->getUserRecord($uid);

				// generate salutation by gender
				switch ($userRow['gender']) {
					case 'm':	$salutationText = 'salutation_male'; break;
					case 'f':	$salutationText = 'salutation_female'; break;
					default:	$salutationText = 'salutation_general'; break;
				}

				// generate invitation mail content
				$mailTemplate = $this->piVars['invitation_mode'] != 'set' ? '###INVITATION_MAIL###' : '###INVITATION_SET_MAIL###';
				$mailContent = $this->cObj->getSubpart($this->templateCode, $mailTemplate);
				$markerArray = array(
					'salutation' => $this->pi_getLL($salutationText),
					'first_name' => '',
					'last_name' => $this->getUserNameFromUserId($userRow['uid']),
					'event_link' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').$this->getSingleviewLink(intval($this->piVars['showUid'])),
					'mail_footer' => $this->cObj->getSubpart($this->templateCode,'###GENERAL_MAIL_FOOTER###'),
					'event_title' => $eventRow['title'],
					'label_title' => $this->pi_getLL('label_title'),
					'label_startdat' => $this->pi_getLL('label_startdat'),
					'event_startdat' => strftime('%d.%m.%Y %H:%M',$eventRow['startdat']),
					'label_enddat' => $this->pi_getLL('label_enddat'),
					'event_enddat' => strftime('%d.%m.%Y %H:%M',$eventRow['enddat']),
					'label_location' => $this->pi_getLL('label_location'),
					'event_location' => $eventRow['location'],
					'label_address' => $this->pi_getLL('label_address'),
					'event_address' => $eventRow['address'],
					'label_zip_city' => $this->pi_getLL('label_zip_city'),
					'event_zip' => $eventRow['zip'],
					'event_city' => $eventRow['city'],
					'label_teaser' => $this->pi_getLL('label_teaser'),
					'event_teaser' => $eventRow['teaser'],
					// TODO: Use different markers or fill in the correct first_name
					// and last_name values hier. It's just a workaround to use only
					// the last_name.
					'user_first_name' => '',
					'user_last_name' => $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']),
					'you_have_been_invited' => sprintf($this->pi_getLL('you_have_been_invited'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $eventRow['title']),
					'invitation_text_headline' => $this->pi_getLL('invitation_text_headline'),
					'use_following_link' => $this->pi_getLL('use_following_link'),
				);
				
				$mailContent = $this->cObj->substituteMarkerArray($mailContent,$markerArray,$wrap='###|###',$uppercase=1);
				
				// show invitation text?
				$invText  = trim(t3lib_div::removeXSS($this->piVars['invitation_text']));
				if (!empty($invText)) {
					$mailContent = $this->cObj->substituteMarker($mailContent,'###INVITATION_TEXT###',nl2br($this->piVars['invitation_text']));
				} else {
					$mailContent = $this->cObj->substituteSubpart ($mailContent, '###SUB_INVITATION_TEXT###', '');
				}

				// generate subject
				$subjectTextIndex = $this->piVars['invitation_mode'] != 'set' ? 'invitation_subject' : 'invitation_subject_set';
				$subject = sprintf($this->pi_getLL($subjectTextIndex), $eventRow['title']);

				// generate mail
				$this->sendNotificationMail($userRow['email'], $subject, $mailContent, $userRow['email'], $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']));
			}

			$content = $this->cObj->getSubpart($this->templateCode,'###GENERAL_MESSAGE###');
			$messageText = $this->piVars['invitation_mode'] != 'set' ? $this->pi_getLL('invite_success') : $this->pi_getLL('invite_success_set');
			$content = $this->cObj->substituteMarker($content,'###TEXT###', $messageText);
			
			$content = $this->cObj->substituteMarker($content,'###BACKLINK###', $this->getSingleviewLink(intval($this->piVars['showUid']),$this->pi_getLL('back')));
			$content = $this->cObj->substituteMarker($content,'###BACKLINK_ICON###', $this->backlinkIcon);
			$content = $this->cObj->substituteMarker($content,'###CSSCLASS###','message-success');

		} else {
			// no invitations
			$content = $this->cObj->getSubpart($this->templateCode,'###GENERAL_MESSAGE###');
			$content = $this->cObj->substituteMarker($content,'###TEXT###', $this->pi_getLL('invite_no_selection'));
			$content = $this->cObj->substituteMarker($content,'###BACKLINK###', $this->getSingleviewLink(intval($this->piVars['showUid']),$this->pi_getLL('back')));
			$content = $this->cObj->substituteMarker($content,'###BACKLINK_ICON###', $this->backlinkIcon);
			$content = $this->cObj->substituteMarker($content,'###CSSCLASS###','message-fail');
		}

		return $content;
	}

	/*
	* Benachrichtigung senden
	*/
	function sendNotificationMail($recipient, $subject, $content, $from_email="", $from_name="") {

		// Only ASCII is allowed in the header
		$subject = html_entity_decode(t3lib_div::deHSCentities($subject), ENT_QUOTES, $GLOBALS['TSFE']->renderCharset);
		$subject = t3lib_div::encodeHeader($subject, 'base64', $GLOBALS['TSFE']->renderCharset);

		$Typo3_htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
		$Typo3_htmlmail->start();

		$html_message = $content;
		// create the plain message body
		$plaintext_message = html_entity_decode(strip_tags($html_message), ENT_QUOTES, $GLOBALS['TSFE']->renderCharset);

		$Typo3_htmlmail->subject = $subject;
		$Typo3_htmlmail->from_email = $from_email ? $from_email : $this->conf['email_from'];
		$Typo3_htmlmail->from_name = $from_name ? $from_name : $this->conf['email_from_name'];
		$Typo3_htmlmail->replyto_email = $from_email ? $from_email : $this->conf['email_from'];
		$Typo3_htmlmail->replyto_name = $from_name ? $from_name : $this->conf['email_from_name'];
		$Typo3_htmlmail->organisation = '';

		// Fetches the content of the page
		$Typo3_htmlmail->theParts['html']['content'] = $html_message;
		$Typo3_htmlmail->theParts['html']['path'] = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/';

		$Typo3_htmlmail->extractMediaLinks();
		$Typo3_htmlmail->extractHyperLinks();
		$Typo3_htmlmail->fetchHTMLMedia();
		$Typo3_htmlmail->substMediaNamesInHTML(0);	// 0 = relative
		$Typo3_htmlmail->substHREFsInHTML();
		$Typo3_htmlmail->setHTML($Typo3_htmlmail->encodeMsg($Typo3_htmlmail->theParts['html']['content']));
		$Typo3_htmlmail->addPlain($plaintext_message);
		$Typo3_htmlmail->setHeaders();
		$Typo3_htmlmail->setContent();
		$Typo3_htmlmail->setRecipient($recipient);
		$Typo3_htmlmail->sendTheMail();
	}



	/*
	 * function showDeleteForm
	 */
	function showDeleteForm() {
		$content = $this->cObj->getSubpart($this->templateCode,'###DELETE_FORM###');

		// form action
		unset($linkconf);
		$linkconf['parameter'] = $GLOBALS['TSFE']->id;
		$linkconf['additionalParams'] = '&tx_keyac_pi1[action]=delete';
		$linkconf['additionalParams'] .= '&tx_keyac_pi1[showUid]='.intval($this->piVars['showUid']);
		$linkconf['useCacheHash'] = false;
		$actionUrl = $this->cObj->typoLink_URL($linkconf);

		$markerArray = array(
			'action' => $actionUrl,
			'delete_question' => sprintf($this->pi_getLL('delete_question'),$this->getEventRecord(intval($this->piVars['showUid'],'title'))),
			'yes' => $this->pi_getLL('yes'),
			'no' => $this->pi_getLL('no'),
			'reason_cancellation' => $this->pi_getLL('reason_cancellation'),
		);

		$content = $this->cObj->substituteMarkerArray($content,$markerArray,$wrap='###|###',$uppercase=1);
		return $content;
	}

	/**
	* Description
	*
	* @param	type		desc
	* @return	The content that is displayed on the website
	*/
	function processDelete() {
		// get event data
		$eventRecord= $this->getEventRecord(intval($this->piVars['showUid']));
		// get current users' data
		$userRecord = $this->getUserRecord($GLOBALS['TSFE']->fe_user->user['uid']);

		$table = 'tx_keyac_dates';
		$where = 'uid="'.intval($this->piVars['showUid']).'" ';
		$fields_values['deleted'] = 1;
		$fields_values['tstamp'] = time();

		if ($GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,$where,$fields_values,$no_quote_fields=FALSE)) {
		    // generate content
		    $content = $this->cObj->getSubpart($this->templateCode,'###GENERAL_MESSAGE###');
		    $content = $this->cObj->substituteMarker($content,'###TEXT###',sprintf($this->pi_getLL('delete_success'),$eventRecord['title']));
		    $content = $this->cObj->substituteMarker($content,'###BACKLINK###',$this->getListviewLink($this->pi_getLL('back')));
		    $content = $this->cObj->substituteMarker($content,'###BACKLINK_ICON###', $this->backlinkIcon);
		    $content = $this->cObj->substituteMarker($content,'###CSSCLASS###','message-success');

		    // send notifications
		    $attendees = $this->getAttendees(intval($this->piVars['showUid']));

		    // generate reason content
		    $reasonContent = $this->cObj->getSubpart($this->templateCode,'###SUB_DELETE_REASON###');
		    $reasonContent = $this->cObj->substituteMarker($reasonContent,'###TEXT###',nl2br(t3lib_div::removeXSS($this->piVars['delete_reason'])));

		    // run through attendees
		    if (count($attendees)) {
				foreach ($attendees as $key => $attendee) {
					if ($attendee['uid'] != $GLOBALS['TSFE']->fe_user->user['uid'] && $attendee['uid'] != $eventRecord['owner'] ) {

					// generate salutation by gender
					switch ($attendee['gender']) {
						case 'm':	$salutationText = 'salutation_male'; break;
						case 'f':	$salutationText = 'salutation_female'; break;
						default:	$salutationText = 'salutation_general'; break;
					}

					// generate mail content
					$mailContent = $this->cObj->getSubpart($this->templateCode,'###DELETE_MAIL###');
					$markerArray = array(
						'salutation' => $this->pi_getLL($salutationText),
						// TODO: fill marker first_name and last_name
						// correctly, if first_name and last_name
						// are not set
						'first_name' => '',
						'last_name' => $this->getUserNameFromUserId($attendee['uid']),
						'user_name' => $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']),
						'event_title' => $eventRecord['title'],
						'event_link' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').$this->getSingleviewLink(intval($this->piVars['showUid'])),
						'mail_footer' => $this->cObj->getSubpart($this->templateCode,'###GENERAL_MAIL_FOOTER###'),
						'label_title' => $this->pi_getLL('label_title'),
						'label_startdat' => $this->pi_getLL('label_startdat'),
						'event_startdat' => strftime('%d.%m.%Y %H:%M',$eventRecord['startdat']),
						'label_enddat' => $this->pi_getLL('label_enddat'),
						'event_enddat' => strftime('%d.%m.%Y %H:%M',$eventRecord['enddat']),
						'label_location' => $this->pi_getLL('label_location'),
						'event_location' => $eventRecord['location'],
						'label_address' => $this->pi_getLL('label_address'),
						'event_address' => $eventRecord['address'],
						'label_zip_city' => $this->pi_getLL('label_zip_city'),
						'event_zip' => $eventRecord['zip'],
						'event_city' => $eventRecrod['city'],
						'label_teaser' => $this->pi_getLL('label_teaser'),
						'event_teaser' => $eventRecord['teaser'],
						'delete_notification_subject' => sprintf($this->pi_getLL('delete_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']),$eventRecord['title'] ),
						'cancellation_text_headline' => $this->pi_getLL('cancellation_text_headline'),
					);

					$mailContent = $this->cObj->substituteMarkerArray($mailContent,$markerArray,$wrap='###|###',$uppercase=1);

					// fill reason subpart
					$reason = t3lib_div::removeXSS($this->piVars['delete_reason']);
					if (!empty($reason)) {
						$mailContent = $this->cObj->substituteSubpart ($mailContent, '###SUB_DELETE_REASON###', $reasonContent, $recursive=1);
						$mailContent = $this->cObj->substituteMarker($mailContent,'###CANCELLATION_TEXT_HEADLINE###', $this->pi_getLL('cancellation_text_headline'));
					}
					else $mailContent = $this->cObj->substituteSubpart ($mailContent, '###SUB_DELETE_REASON###', '', $recursive=1);

					// set subject
					$subject = sprintf($this->pi_getLL('delete_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $eventRecord['title']);

					// send notification
					$this->sendNotificationMail($attendee['email'], $subject, $mailContent, $GLOBALS['TSFE']->fe_user->user['email'], $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']));
					}
				}
		    }

		    // send notification to owner if he isn't the current user
		    if ($eventRecord['owner'] != $GLOBALS['TSFE']->fe_user->user['uid'] && $eventRecord['owner'] != "") {

			$ownerRecord = $this->getUserRecord($eventRecord['owner']);

			// generate salutation by gender
			switch ($ownerRecord['gender']) {
				case 'm':	$salutationText = 'salutation_male'; break;
				case 'f':	$salutationText = 'salutation_female'; break;
				default:	$salutationText = 'salutation_general'; break;
			}

			// generate mail content
			$mailContent = $this->cObj->getSubpart($this->templateCode,'###DELETE_MAIL###');
			$markerArray = array(
			    'salutation' => $this->pi_getLL($salutationText),
			    'first_name' => '',
			    'last_name' => $this->getUserNameFromUserId($eventRecord['owner']),
			    'user_name' => $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']),
			    'event_title' => $eventRecord['title'],
			    'event_link' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').$this->getSingleviewLink(intval($this->piVars['showUid'])),
			    'mail_footer' => $this->cObj->getSubpart($this->templateCode,'###GENERAL_MAIL_FOOTER###'),
				'label_title' => $this->pi_getLL('label_title'),
				'label_startdat' => $this->pi_getLL('label_startdat'),
				'event_startdat' => strftime('%d.%m.%Y %H:%M',$eventRecord['startdat']),
				'label_enddat' => $this->pi_getLL('label_enddat'),
				'event_enddat' => strftime('%d.%m.%Y %H:%M',$eventRecord['enddat']),
				'label_location' => $this->pi_getLL('label_location'),
				'event_location' => $eventRecord['location'],
				'label_address' => $this->pi_getLL('label_address'),
				'event_address' => $eventRecord['address'],
				'label_zip_city' => $this->pi_getLL('label_zip_city'),
				'event_zip' => $eventRecord['zip'],
				'event_city' => $eventRecrod['city'],
				'label_teaser' => $this->pi_getLL('label_teaser'),
				'event_teaser' => $eventRecord['teaser'],
				'delete_notification_subject' => sprintf($this->pi_getLL('delete_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']),$eventRecord['title'] ),
			);

			$mailContent = $this->cObj->substituteMarkerArray($mailContent,$markerArray,$wrap='###|###',$uppercase=1);

			// fill reason subpart
			$reason = t3lib_div::removeXSS($this->piVars['delete_reason']);
			if (!empty($reason)) {
				$mailContent = $this->cObj->substituteSubpart ($mailContent, '###SUB_DELETE_REASON###', $reasonContent, $recursive=1);
				$mailContent = $this->cObj->substituteMarker($mailContent,'###CANCELLATION_TEXT_HEADLINE###', $this->pi_getLL('cancellation_text_headline'));
			}
			else $mailContent = $this->cObj->substituteSubpart ($mailContent, '###SUB_DELETE_REASON###', '', $recursive=1);

			// set subject
			$subject = sprintf($this->pi_getLL('delete_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $eventRecord['title']);

			// send notification
			$this->sendNotificationMail($ownerRecord['email'], $subject, $mailContent, $userRecord['email'], $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']));
		    }
		}
		else {
			$content = $this->cObj->getSubpart($this->templateCode,'###GENERAL_MESSAGE###');
			$content = $this->cObj->substituteMarker($content,'###TEXT###',sprintf($this->pi_getLL('delete_error'),$eventTitle));
			$content = $this->cObj->substituteMarker($content,'###BACKLINK###',$this->getListviewLink($this->pi_getLL('back')));
			$content = $this->cObj->substituteMarker($content,'###BACKLINK_ICON###', $this->backlinkIcon);
			$content = $this->cObj->substituteMarker($content,'###CSSCLASS###','message-fail');
		}
		return $content;

	}


	/*
	 * function setUserAsAttendant
	 * @param $eventUid
	 * @param $userUid
	 */
	function setUserAsAttendant($eventUid, $userUid, $notification=true) {
		
		if (!$this->feuserIsAttendent($userUid, $eventUid)) {
			
			$userRecord = $this->getUserRecord($userUid);

			$table = 'tx_keyac_dates_attendees_mm';
			$fields_values = array(
				'uid_local' => $eventUid,
				'uid_foreign' => $userUid,
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($table,$fields_values,$no_quote_fields=FALSE);

			// set notification to other attendees
			if ($notification) {
			    $recordData = $this->getEventRecord($eventUid);

			    // send notification to other attendees (but not to current user and not to owner)
			    $attendees = $this->getAttendees($eventUid);
			    foreach ($attendees as $key => $attendee) {
					if ($attendee['uid'] != $GLOBALS['TSFE']->fe_user->user['uid'] && $attendee['uid'] != $recordData['owner'] ) {

						// generate salutation by gender
						switch ($attendee['gender']) {
							case 'm':	$salutationText = 'salutation_male'; break;
							case 'f':	$salutationText = 'salutation_female'; break;
							default:	$salutationText = 'salutation_general'; break;
						}

						// generate mail content
						$mailContent = $this->cObj->getSubpart($this->templateCode,'###ATTEND_MAIL###');
						$markerArray = array(
							'salutation' => $this->pi_getLL($salutationText),
							// TODO: fill marker first_name and last_name
							// correctly, if first_name and last_name
							// are not set
							'first_name' => '',
							'last_name' => $this->getUserNameFromUserId($attendee['uid']),
							'attendee_name' => $this->getUserNameFromUserId($userRecord['uid']),
							'event_title' => $recordData['title'],
							'event_link' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').$this->getSingleviewLink(intval($this->piVars['showUid'])),
							'mail_footer' => $this->cObj->getSubpart($this->templateCode,'###GENERAL_MAIL_FOOTER###'),
							'label_title' => $this->pi_getLL('label_title'),
							'label_startdat' => $this->pi_getLL('label_startdat'),
							'event_startdat' => strftime('%d.%m.%Y %H:%M',$recordData['startdat']),
							'label_enddat' => $this->pi_getLL('label_enddat'),
							'event_enddat' => strftime('%d.%m.%Y %H:%M',$recordData['enddat']),
							'label_location' => $this->pi_getLL('label_location'),
							'event_location' => $recordData['location'],
							'label_address' => $this->pi_getLL('label_address'),
							'event_address' => $recordData['address'],
							'label_zip_city' => $this->pi_getLL('label_zip_city'),
							'event_zip' => $recordData['zip'],
							'event_city' => $recordData['city'],
							'label_teaser' => $this->pi_getLL('label_teaser'),
							'event_teaser' => $recordData['teaser'],
							'use_following_link' => $this->pi_getLL('use_following_link'),
							'attendance_notification_text' => sprintf($this->pi_getLL('attendance_notification_subject'),$this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']) ,$recordData['title']),
						);
						
						$mailContent = $this->cObj->substituteMarkerArray($mailContent,$markerArray,$wrap='###|###',$uppercase=1);
						
						#debug($mailContent, 'mailContentAttendant' );
						$subject = sprintf($this->pi_getLL('attendance_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $recordData['title']);
						$this->sendNotificationMail($attendee['email'], $subject, $mailContent, $userRecord['email'], $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']));
					}
			    }

			    // send notification to owner (if owner is set and is not the current user)
			    if ($recordData['owner'] != $GLOBALS['TSFE']->fe_user->user['uid'] && $recordData['owner'] != "") {
					$ownerRecord = $this->getUserRecord($recordData['owner']);

					// generate salutation by gender
					switch ($ownerRecord['gender']) {
						case 'm':	$salutationText = 'salutation_male'; break;
						case 'f':	$salutationText = 'salutation_female'; break;
						default:	$salutationText = 'salutation_general'; break;
					}

					// generate mail content
					$mailContent = $this->cObj->getSubpart($this->templateCode,'###ATTEND_MAIL###');
					$markerArray = array(
						'salutation' => $this->pi_getLL($salutationText),
						// TODO: fill marker first_name and last_name
						// correctly, if first_name and last_name
						// are not set
						'first_name' => '',
						'last_name' => $this->getUserNameFromUserId($ownerRecord['uid']),
						'attendee_name' => $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']),
						'event_title' => $recordData['title'],
						'event_link' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').$this->getSingleviewLink(intval($this->piVars['showUid'])),
						'mail_footer' => $this->cObj->getSubpart($this->templateCode,'###GENERAL_MAIL_FOOTER###'),
						'label_title' => $this->pi_getLL('label_title'),
						'label_startdat' => $this->pi_getLL('label_startdat'),
						'event_startdat' => strftime('%d.%m.%Y %H:%M',$recordData['startdat']),
						'label_enddat' => $this->pi_getLL('label_enddat'),
						'event_enddat' => strftime('%d.%m.%Y %H:%M',$recordData['enddat']),
						'label_location' => $this->pi_getLL('label_location'),
						'event_location' => $recordData['location'],
						'label_address' => $this->pi_getLL('label_address'),
						'event_address' => $recordData['address'],
						'label_zip_city' => $this->pi_getLL('label_zip_city'),
						'event_zip' => $recordData['zip'],
						'event_city' => $recordData['city'],
						'label_teaser' => $this->pi_getLL('label_teaser'),
						'event_teaser' => $recordData['teaser'],
						'use_following_link' => $this->pi_getLL('use_following_link'),
						'attendance_notification_text' => sprintf($this->pi_getLL('attendance_notification_subject'),$this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']) ,$recordData['title']),
					);
					$mailContent = $this->cObj->substituteMarkerArray($mailContent,$markerArray,$wrap='###|###',$uppercase=1);
					#debug($mailContent, 'mailContentOwner' );
					$subject = sprintf($this->pi_getLL('attendance_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $recordData['title']);
					$this->sendNotificationMail($ownerRecord['email'], $subject, $mailContent, $userRecord['email'], $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']));
			    }
			}
		}
	}

	/*
	 * function deleteUserAsAttendant
	 * @param $eventUid
	 * @param $userUid
	 */

	function deleteUserAsAttendant($eventUid, $userUid) {

		$table = 'tx_keyac_dates_attendees_mm';
		$where = ' uid_local="'.intval($this->piVars['showUid']).'" AND uid_foreign="'.$GLOBALS['TSFE']->fe_user->user['uid'].'"  ';
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($table,$where);

		$userRecord = $this->getUserRecord($userUid);

		// set notification to other attendees
		$recordData = $this->getEventRecord($eventUid);

		// send notification to other attendees (but not to current user and not to owner)
		$attendees = $this->getAttendees($eventUid);
		if (sizeof($attendees)) {
		    foreach ($attendees as $key => $attendee) {
			if ($attendee['uid'] != $GLOBALS['TSFE']->fe_user->user['uid'] && $attendee['uid'] != $recordData['owner'] ) {

			    // generate salutation by gender
			    switch ($attendee['gender']) {
				    case 'm':	$salutationText = 'salutation_male'; break;
				    case 'f':	$salutationText = 'salutation_female'; break;
				    default:	$salutationText = 'salutation_general'; break;
			    }

			    // generate mail content
			    $mailContent = $this->cObj->getSubpart($this->templateCode,'###ATTEND_DELETE_MAIL###');
			    $markerArray = array(
					'salutation' => $this->pi_getLL($salutationText),
					'first_name' => '',
					'last_name' => $this->getUserNameFromUserId($attendee['uid']),
					'attendee_name' => $this->getUserNameFromUserId($userRecord['uid']),
					'event_title' => $recordData['title'],
					'event_link' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').$this->getSingleviewLink(intval($this->piVars['showUid'])),
					'mail_footer' => $this->cObj->getSubpart($this->templateCode,'###GENERAL_MAIL_FOOTER###'),
					'label_title' => $this->pi_getLL('label_title'),
					'label_startdat' => $this->pi_getLL('label_startdat'),
					'event_startdat' => strftime('%d.%m.%Y %H:%M',$recordData['startdat']),
					'label_enddat' => $this->pi_getLL('label_enddat'),
					'event_enddat' => strftime('%d.%m.%Y %H:%M',$recordData['enddat']),
					'label_location' => $this->pi_getLL('label_location'),
					'event_location' => $recordData['location'],
					'label_address' => $this->pi_getLL('label_address'),
					'event_address' => $recordData['address'],
					'label_zip_city' => $this->pi_getLL('label_zip_city'),
					'event_zip' => $recordData['zip'],
					'event_city' => $recordData['city'],
					'label_teaser' => $this->pi_getLL('label_teaser'),
					'event_teaser' => $recordData['teaser'],
					'attendance_delete_notification_text' => sprintf($this->pi_getLL('attendance_delete_notification_subject'), $this->getUserNameFromUserId($userRecord['uid']), $recordData['title']),
					'use_following_link' => $this->pi_getLL('use_following_link'),
			    );
			    $mailContent = $this->cObj->substituteMarkerArray($mailContent,$markerArray,$wrap='###|###',$uppercase=1);
			    #debug($mailContent, 'mailContentAttendant' );
			    $subject = sprintf($this->pi_getLL('attendance_delete_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $recordData['title']);
			    $this->sendNotificationMail($attendee['email'], $subject, $mailContent, $userRecord['email'], $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']));
			}
		    }
		}


		// send notification to owner (if owner is set and is not the current user)
		if ($recordData['owner'] != $GLOBALS['TSFE']->fe_user->user['uid'] && $recordData['owner'] != "") {

		    $ownerRecord = $this->getUserRecord($recordData['owner']);

		    // generate salutation by gender
		    switch ($ownerRecord['gender']) {
			    case 'm':	$salutationText = 'salutation_male'; break;
			    case 'f':	$salutationText = 'salutation_female'; break;
			    default:	$salutationText = 'salutation_general'; break;
		    }

		    // generate mail content
		    $mailContent = $this->cObj->getSubpart($this->templateCode,'###ATTEND_DELETE_MAIL###');
		    $markerArray = array(
				'salutation' => $this->pi_getLL($salutationText),
				'first_name' => '',
				'last_name' => $this->getUserNameFromUserId($ownerRecord['uid']),
				'attendee_name' => $this->getUserNameFromUserId($userRecord['uid']),
				'event_title' => $recordData['title'],
				'event_link' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').$this->getSingleviewLink(intval($this->piVars['showUid'])),
				'mail_footer' => $this->cObj->getSubpart($this->templateCode,'###GENERAL_MAIL_FOOTER###'),
				'label_title' => $this->pi_getLL('label_title'),
				'label_startdat' => $this->pi_getLL('label_startdat'),
				'event_startdat' => strftime('%d.%m.%Y %H:%M',$recordData['startdat']),
				'label_enddat' => $this->pi_getLL('label_enddat'),
				'event_enddat' => strftime('%d.%m.%Y %H:%M',$recordData['enddat']),
				'label_location' => $this->pi_getLL('label_location'),
				'event_location' => $recordData['location'],
				'label_address' => $this->pi_getLL('label_address'),
				'event_address' => $recordData['address'],
				'label_zip_city' => $this->pi_getLL('label_zip_city'),
				'event_zip' => $recordData['zip'],
				'event_city' => $recordData['city'],
				'label_teaser' => $this->pi_getLL('label_teaser'),
				'event_teaser' => $recordData['teaser'],
				'attendance_delete_notification_text' => sprintf($this->pi_getLL('attendance_delete_notification_subject'), $this->getUserNameFromUserId($userRecord['uid']), $recordData['title']),
				'use_following_link' => $this->pi_getLL('use_following_link'),
		    );
		    $mailContent = $this->cObj->substituteMarkerArray($mailContent,$markerArray,$wrap='###|###',$uppercase=1);
		    #debug($mailContent, 'mailContentOwner' );
		    $subject = sprintf($this->pi_getLL('attendance_delete_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $recordData['title']);
		    $this->sendNotificationMail($ownerRecord['email'], $subject, $mailContent, $userRecord['email'], $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']));
		}
	}



	/*
	 * function showForm
	 */
	function showForm($editUid=0,$errors=array(), $conflicts=array()) {

		// edit form?
		if ($editUid) $data = $this->getRecordData($editUid);

		// generate form action url
		unset($linkconf);
		$linkconf['parameter'] = $GLOBALS['TSFE']->id;
		$linkconf['additionalParams'] = 'tx_keyac_pi1[action]=create';
		$linkconf['useCacheHash'] = false;
		$link =$this->cObj->typoLink($text,$linkconf);

		// form fields
		$fields = array(
		    'startdat',
		    'enddat',
		    'showtime',
		    'private',
		    'title',
		    'location',
		    'teaser',
		    'bodytext',
		    'cat',
		    'address',
		    'zip',
		    'city',
		    'attachments',
		);

		// fields that must be filled
		$mustFields = array(
		    'title',
		    'startdat',
		    'enddat',
		);

		// get subpart content and fill markers
		$content = $editUid ? $this->cObj->getSubpart($this->templateCode,'###EDIT_FORM###') : $this->cObj->getSubpart($this->templateCode,'###CREATE_FORM###');
		#$content = $this->cObj->getSubpart($this->templateCode,'###CREATE_FORM###');
		$backUrl = $editUid ? $this->getSingleviewLink(intval($this->piVars['showUid'])) : $this->getListviewLink();
		$markerArray = array(
			'action' => $formAction,
			'form_title' => $this->pi_getLL('form_title_create'),
			'submit' => '<input type="submit" name="tx_keyac_pi1[submitcreate]">',
			'cancel' => '<input type="button" onclick="window.location.href=\''.$backUrl.'\'" value="'.$this->pi_getLL('back').'">'
		);
		if ($editUid) {
			$markerArray['submit'] = '<input type="submit" name="tx_keyac_pi1[submitedit]">';
			$markerArray['form_title'] = $this->pi_getLL('form_title_edit');
		}

		// render the form fields
		foreach ($fields as $key => $fN) {
			$markerArray['field_'.$fN] = $this->renderFormField($fN, $data);
			$markerArray['label_'.$fN] = $this->pi_getLL('label_'.$fN);
			$markerArray['error_'.$fN] = $errors[$fN];

			if (in_array($fN, $mustFields))  {
				$markerArray['label_'.$fN] .= ' '.$this->pi_getLL('required_mark');
			}
		}
		$content = $this->cObj->substituteMarkerArray($content,$markerArray,$wrap='###|###',$uppercase=1);

		// INVITATONS
		if (!$editUid) {
			$content = $this->cObj->substituteMarker($content,'###INVITATIONS_TEXT###','Einladen:');
			$content = $this->cObj->substituteMarker($content,'###INVITATIONS###', $this->showInviteForm(true));
		}
		
		// OWNER - ATTEND?
		if (!$editUid) {
			$content = $this->cObj->substituteMarker($content,'###LABEL_OWNER_ATTEND###',$this->pi_getLL('label_owner_attend'));
			$content = $this->cObj->substituteMarker($content,'###FIELD_OWNER_ATTEND###', $this->renderFormField('owner_attend', $this->piVars['owner']));
		}
		

		// CONFLICTS
		$conflictsSubpart = $editUid ? '###SUB_CONFLICTS_EDIT###' : '###SUB_CONFLICTS_CREATE###';
		$conflictsRowSubpart = $editUid ? '###SUB_CONFLICT_ROW_EDIT###' : '###SUB_CONFLICT_ROW_CREATE###';
		if (sizeof($conflicts)) {
		    $conflictsRowsContent = '';
		    foreach ($conflicts as $key => $conflictData) {
				$tempRowContent = $this->cObj->getSubpart($this->templateCode,$conflictsRowSubpart);
				$tempMarkerArray = array(
					'user_first_name' => $conflictData['user_first_name'],
					'user_last_name' => $conflictData['user_last_name'],
					'event_title' => !$conflictData['private'] ? $conflictData['title'] : $this->pi_getLL('private_event'),
					'event_startdat' => strftime('%d.%m.%Y %H:%M Uhr',$conflictData['startdat']),
					'event_enddat' => strftime('%d.%m.%Y %H:%M Uhr',$conflictData['enddat']),
					'label_user' => $this->pi_getLL('label_user'),
					'label_event' => $this->pi_getLL('event'),
					'label_from' => $this->pi_getLL('label_from'),
					'label_until' => $this->pi_getLL('label_until'),
					'conflicts_found' => $this->pi_getLL('conflicts_found'),
					'ignore_conflicts' => $this->pi_getLL('ignore_conflicts'),
					'search_date' => $this->pi_getLL('search_date'),
					
				);
				$tempRowContent = $this->cObj->substituteMarkerArray($tempRowContent, $tempMarkerArray, $wrap='###|###',$uppercase=1);
				$conflictsRows .= $tempRowContent;
		    }
		    $conflictsContent = $this->cObj->getSubpart($this->templateCode,$conflictsSubpart);
		    $conflictsContent = $this->cObj->substituteSubpart ($conflictsContent, $conflictsRowSubpart, $conflictsRows, $recursive=1);
		    $content = $this->cObj->substituteSubpart ($content, $conflictsSubpart, $conflictsContent, $recursive=1);
		}
		else $content = $this->cObj->substituteSubpart ($content, $conflictsSubpart, '', $recursive=1);
		return $content;
	}


	/*
	 * function getRecordData
	 * @param $arg
	 */
	function getRecordData($uid) {

		$fields = '*';
		$table = 'tx_keyac_dates';
		$where = 'uid="'.intval($uid).'" ';
		$where .= $this->cObj->enableFields($table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
		$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$result['event'] = $row;

		$fields = '*';
		$table = 'tx_keyac_cat, tx_keyac_dates_cat_mm';
		$where = 'tx_keyac_dates_cat_mm.uid_local="'.intval($uid).'" ';
		$where .= ' AND tx_keyac_dates_cat_mm.uid_foreign=tx_keyac_cat.uid ';
		$where .= $this->cObj->enableFields('tx_keyac_cat');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
		$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$result['cat'][ ] = $row;
		}
		return $result;

	}


	/*
	 * function renderFormField
	 * @param $arg
	 */
	function renderFormField($fieldName,$data=array()) {
		// set value
		if ($this->piVars['submitedit'] ||$this->piVars['submitcreate'] || $this->piVars['submitsuggestion']) $value = $this->piVars[$fieldName];
		else $value = $data['event'][$fieldName];

		switch ($fieldName) {
			// TEXT FIELD
			default:
				$fieldContent = '<input class="text" type="text" name="tx_keyac_pi1['.$fieldName.']" value="'.$value.'">';
				break;
			
			// Date2Cal Field
			case 'enddat':
			case 'startdat':
				
				$format = '%H:%M %d.%m.%Y';
				if ($this->piVars['submitedit'] ||$this->piVars['submitcreate'] || $this->piVars['submitsuggestion']) $value = $this->piVars[$fieldName];
				else if ($data['event'][$fieldName]) $value = strftime($format,$data['event'][$fieldName]);
				else $value = '';
				
				$prefill = $value;

				// overwrite field value with suggestion data if existent
				if ($this->piVars['suggestion']) {
				    $suggestionData = explode('|',$this->piVars['suggestion']);
				    if ($fieldName == 'startdat') $prefill = strftime($format, $suggestionData[0]);
				    if ($fieldName == 'enddat') $prefill = strftime($format, $suggestionData[1]);
				}
				
				// render calendar stuff
				$this->JSCalendar->setInputField('tx_keyac_pi1['.$fieldName.']');
				$fieldContent = $this->JSCalendar->render($prefill);
				break;
			
			
			
			// CHECKBOX
			case 'showtime':
			case 'private':
				$fieldContent = '<input type="checkbox" name="tx_keyac_pi1['.$fieldName.']" value="1" ';
				if ($value) $fieldContent .= ' checked="checked" ';
				$fieldContent .= '>';
				break;
			
			case 'owner_attend':
				$fieldContent = '<input type="checkbox" name="tx_keyac_pi1['.$fieldName.']" value="1" ';
				
				// submitted and value == 1
				if (($this->piVars['submitcreate'] || $this->piVars['submitsuggestion']) && $this->piVars[$fieldName] == 1) $fieldContent .= ' checked="checked" ';
				// not submitted: activate checkbox by default
				else $fieldContent .= ' checked="checked" ';
				
				
				if ($value) $fieldContent .= ' checked="checked" ';
				$fieldContent .= '>';
				break;
			
			
			// TEXTAREA
			case 'teaser':
			case 'bodytext':
			case 'reason':
				$fieldContent = '<textarea name="tx_keyac_pi1['.$fieldName.']">'.$value.'</textarea>';
				break;

			// CAT
			case 'cat':
			    if(is_array($data[$fieldName])) {
					$selectedValues = array();
					foreach ($data[$fieldName] as $key => $values) {
						$selectedValues[] = $values['uid_foreign'];
					}
			    }
			    if (is_array($value)) {
				    $selectedValues = array();
				    foreach ($value as $key => $catUid) {
						$selectedValues[] = $catUid;
				    }
			    }

			    $fields = '*';
			    $table = 'tx_keyac_cat';
			    $where = 'pid in ('.$this->pids.') ';
			    $where .= $this->cObj->enableFields($table);
			    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
			    while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$options .= '<option value="'.$row['uid'].'" ';
				if (is_array($selectedValues)) {
				    if (in_array($row['uid'], $selectedValues)) $options .= ' selected="selected" ';
				}
				$options .= '>'.$row['title'].'</option>';
			    }
			    // Multiple
			    // $fieldContent = '<select name="tx_keyac_pi1[cat][]" multiple="multiple" size="3">'.$options.'</select>';
			    // Single
			    $fieldContent = '<select name="tx_keyac_pi1[cat][]" size="1">'.$options.'</select>';
			    break;



			case 'attachments':
			    // show the files, which already have been uploaded
			    // including a delete link
			    if (strlen($value)) {
				foreach (explode(',', $value) as $filename) {
				    if (file_exists($this->uploadFolder . $filename)) {
					$content .= '<div class="filename">';
					// get the delete image configuration from typoscript
					$imageConf = $this->conf['icons.']['deleteFile.'];

					// generate the delete link
					$additionalParams = '&' . $this->prefixId . '[showUid]=' . intval($this->piVars['showUid']);
					$additionalParams .= '&' . $this->prefixId . '[deleteFile]=' . $filename;
					#$additionalParams .= $this->getAdditionalParamsFromKeepPiVars();
					$deleteLinkConf = array(
					    'parameter' => $GLOBALS['TSFE']->id,
					    'additionalParams' => $additionalParams
					    );
					$deleteLink_URL = $this->cObj->typoLink_URL( $deleteLinkConf );

					//$imageConf['wrap'] = '<a href="javascript:areYouSure(\' ' . $deleteLink_URL . '\')">|</a>';
					$imageConf['wrap'] = '<a href="javascript:areYouSure(\' ' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $deleteLink_URL . '\')">|</a>';

					// generate the alt text
					$imageConf['altText'] = $this->pi_getLL('altText_deletefile', 'Delete file.');

					// finally generate the delete icon
					$content .= $this->cObj->IMAGE($imageConf);

					// generate the link to the file
					$content .= ' ' . $this->cObj->typoLink(
					    $filename,
					    array(
						    'parameter' => $this->fileUploadDir . $filename,
						    'target' => '_blank'
						    )
					    );
					// render the file size
					$content .= ' (' . $this->filesize_format(filesize($this->uploadFolder. $filename)) . ')';
					$content .= '</div>';
				    }
				}
			    }
			    // show the form elements for the new files
			    $content .= '<table border="0" cellpadding="0" cellspacing="0">';
			    #debug($this->maxFiles,'maxFiles');
			    for ($i = 1; $i<=$this->maxFiles; $i++) {
				    $content .= '<tr id="' . $fieldName . '_' . $i . '_row' . '"';
				    if ($i>1) {
					    $content .= ' style="display:none;"';
				    }
				    $content .= '><td>';
				    $content .= '<input type="file" id="' . $fieldName . '_' . $i .'" name="' . $this->prefixId . '_' . $fieldName . '_' . $i . '" value="" size="' . $fieldConf['size'] . '" maxlength="' . $fieldConf['maxlength'] . '">';
				    $j = $i + 1;
				    if ($i < $this->maxFiles) {
					    #$content .= ' <a href="#zum_upload" onClick="document.getElementById(\'' . $fieldName . '_' . $j . '_row' . '\').style.display = \'inline\'; this.style.visibility = \'hidden\';">' . $this->pi_getLL('more_files','more') . '</a>';
					    $content .= ' <a href="javascript:document.getElementById(\'' . $fieldName . '_' . $j . '_row' . '\').style.display = \'block\'; this.style.visibility = \'hidden\';">' . $this->pi_getLL('more_files') . '</a>';
				    }
				    $content .= '</td></tr>';
				    $content .= '<input type="hidden" name="tx_keyac_pi1[attachments]" value="'.$this->piVars['attachments'].'" >';
			    }
			    $content .= '</table>';
			    $fieldContent = $content;
				break;
			
			case 'invitation_mode':
				$modes = array('invite', 'set');
				if (empty($this->piVars['invitation_mode'])) $this->piVars['invitation_mode'] = 'invite';
				foreach ($modes as $mode) {
					$fieldContent .= '<input type="radio" name="'.$this->prefixId.'['.$fieldName.']" value="'.$mode.'" ';
					if ($this->piVars['invitation_mode'] == $mode) $fieldContent .= ' checked="checked" ';
					$fieldContent .= '> '.$this->pi_getLL('label_invitation_mode_'.$mode).'<br />';
				}
				
				break;
			

		}

		return $fieldContent;

	}


	/*
	 * function evaluateFormData
	 */
	function evaluateCreateData() {

		$errors = array();

		// no startdate set
		if (empty($this->piVars['startdat'])) $errors['startdat'] = $this->pi_getLL('error_startdat_not_set');
		// no enddate
		if (empty($this->piVars['enddat'])) $errors['enddat'] = $this->pi_getLL('error_enddat_not_set');

		// startdat and enddat is set
		if (!empty($this->piVars['startdat']) && !empty($this->piVars['enddat'])) {

		    // invalid dates
		    $startdatTimestamp = strtotime($this->piVars['startdat']);
		    $enddatTimestamp = strtotime($this->piVars['enddat']);

		    if ($startdatTimestamp  == '') $errors['startdat'] = $this->pi_getLL('error_invalid_date');
		    if ($enddatTimestamp == '') $errors['enddat'] = $this->pi_getLL('error_invalid_date');

		    // valid dates -> further checks
		    if (!$errors['enddat'] && !$errors['startdat']) {
			// enddat before startdat
			if ($startdatTimestamp > $enddatTimestamp) $errors['enddat'] = $this->pi_getLL('error_enddat_before_startdat');
		    }
		}

		// no title set
		if (empty($this->piVars['title'])) $errors['title'] = $this->pi_getLL('error_title_not_set');

		// check for uploaded files
		for ($i = 1; $i<=$this->maxFiles; $i++) {
			$attachmentName = $this->prefixId . '_attachments_' . $i;
			if (strlen($_FILES[$attachmentName]['name'])) {
				$uploadedFile = $this->handleUpload($attachmentName);
				if (strlen($uploadedFile)) {
					if (strlen($this->piVars['attachments'])) $this->piVars['attachments'] .= ',';
					$this->piVars['attachments'].= $uploadedFile;
				}
			}
		}

		// check conflicts for invited persons
		if (!isset($this->piVars['submitcreateignore'])) {
			$attendees = $this->piVars['user'];
			// add current user only if he wants to 
			if ($this->piVars['owner_attend']) $attendees[$GLOBALS['TSFE']->fe_user->user['uid']] = 'on' ;
			$conflicts = $this->checkConflictsCreate($attendees);
		}

		// no errors
		if (!sizeof($errors) && !sizeof($conflicts)) {
		    // process data
		    $content = $this->processFormData();
		}
		// errors
		else $content = $this->showForm(0,$errors, $conflicts);

		return $content;
	}


	/*
	 * function evaluateEditData
	 */

	function evaluateEditData($editUid) {

	    $errors = array();

	    // no startdate set
	    if (empty($this->piVars['startdat'])) $errors['startdat'] = $this->pi_getLL('error_startdat_not_set');
	    // no enddate
		if (empty($this->piVars['enddat'])) $errors['enddat'] = $this->pi_getLL('error_enddat_not_set');

	    // startdat and enddat is set
	    if (!empty($this->piVars['startdat']) && !empty($this->piVars['enddat'])) {
			
			// invalid dates
			$startdatTimestamp = strtotime($this->piVars['startdat']);
			$enddatTimestamp = strtotime($this->piVars['enddat']);
			
			if ($startdatTimestamp  == '') $errors['startdat'] = $this->pi_getLL('error_invalid_date');
			if ($enddatTimestamp == '') $errors['enddat'] = $this->pi_getLL('error_invalid_date');
			
			// valid dates -> further checks
			if (!$errors['enddat'] && !$errors['startdat']) {
				// enddat before startdat
				if ($startdatTimestamp > $enddatTimestamp) $errors['enddat'] = $this->pi_getLL('error_enddat_before_startdat');
			}
	    }

	    // no title set
	    if (empty($this->piVars['title'])) $errors['title'] = $this->pi_getLL('error_title_not_set');


	    // check if any attendee has a conflicting event
	    $oldData = $this->getEventRecord($editUid);

	    // TODO: cat, private,
	    $checkFields = array('startdat', 'enddat', 'showtime', 'title', 'teaser',  'location', 'address', 'city', 'zip', 'bodytext');
	    $changedFields = array();

	    // check if fields have changed
	    foreach ($checkFields as $fieldName) {
			switch ($fieldName) {
				default:
				if ($oldData[$fieldName] != $this->piVars[$fieldName]) $changedFields[] = $fieldName;
				break;
	
				case 'startdat':
				case 'enddat':
				if ($oldData[$fieldName] != strtotime($this->piVars[$fieldName])) $changedFields[] = $fieldName;
				break;
	
				case 'showtime':
				// checkbox has been clicked
				if (isset($this->piVars[$fieldName]) && $oldData[$fieldName] == 0) $changedFields[] = $fieldName;
				else if (!isset($this->piVars[$fieldName]) && $oldData[$fieldName] == 1 ) $changedFields[] = $fieldName;
				break;
			}
	    }

	    // check for uploaded files
	    $tempValue = $oldData['attachments'];
	    #debug($tempValue,'pre');
	    for ($i = 1; $i<=$this->maxFiles; $i++) {
		    $attachmentName = $this->prefixId . '_attachments_' . $i;
		    if (strlen($_FILES[$attachmentName]['name'])) {
		        $uploadedFile = $this->handleUpload($attachmentName);
		        #if (strlen($uploadedFile)) {
		        if (strlen($tempValue)) $tempValue .= ',';
			$tempValue .= $uploadedFile;
		    }
	    }
	    $this->piVars['attachments'] = $tempValue;


	    // if startdat, enddat or showtime has changed -> changed for conflicts for new date
	    if (!$this->piVars['submiteditignore'] && (in_array('startdat',$changedFields) || in_array('enddat', $changedFields) || in_array('showtime', $changedFields))) {
		$conflicts = $this->checkConflictsEdit($oldData,$changedFields);
	    }

	    // no errors
	    if (!sizeof($errors) && !sizeof($conflicts)) $content = $this->processFormData($editUid,$changedFields);
	    // errors
	    else $content = $this->showForm($editUid,$errors,$conflicts);

	    return $content;


	}

	/*
	 * function checkConflicts
	 * @param $arg
	 */
	function checkConflictsEdit($oldData,$changedFields) {

	    // comparison timestamps
	    $compStart = in_array('startdat', $changedFields)  ? strtotime($this->piVars['startdat']) : $oldData['startdat'];
	    $compEnd = in_array('enddat', $changedFields)  ? strtotime($this->piVars['enddat']) : $oldData['enddat'];

	    // get attendees
	    $attendees = $this->getAttendees($oldData['uid']);
		if (count($attendees)) {
			foreach($attendees as $key => $attendee) {
				$fields = '*, tx_keyac_dates.uid as eventuid, fe_users.uid as useruid, tx_keyac_dates.title as eventtitle';
				$table = 'tx_keyac_dates, tx_keyac_dates_attendees_mm, fe_users';
				$where = 'eventuid <> "'.$oldData['uid'].'" ';
				$where .= ' AND  tx_keyac_dates_attendees_mm.uid_foreign="'.$attendee['uid'].'" ';
				$where .= ' AND tx_keyac_dates.uid=tx_keyac_dates_attendees_mm.uid_local';
				$where .= ' AND fe_users.uid=tx_keyac_dates_attendees_mm.uid_foreign ';
				$where .= ' AND (';
				$where .= 'startdat between '.$compStart.' AND '.$compEnd;
				$where .= ' OR enddat between '.$compStart.' AND '.$compEnd;
				$where .= ' OR (startdat < '.$compStart.' AND enddat > '.$compEnd.')';
				$where .= ' OR (startdat = '.$compStart.' AND enddat='.$compEnd.')';
				$where .= ' OR (startdat > '.$compStart.' AND enddat < '.$compEnd.')';
				$where .= ' OR (startdat = '.$compStart.' AND enddat < '.$compEnd.')';
				$where .= ' OR (startdat > '.$compStart.' AND enddat = '.$compEnd.')';
				$where .= ')';
				$where .= $this->cObj->enableFields('tx_keyac_dates');
				$where .= $this->cObj->enableFields('fe_users');
				#debug($GLOBALS['TYPO3_DB']->SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit=''),'SELECT');
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
				$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$conflicts[] = array(
						'title' => $row['eventtitle'],
						'startdat' => $row['startdat'],
						'enddat' => $row['enddat'],
						'showtime' => $row['showtime'],
						'private' => $row['private'],
						'user_first_name' => '',
						'user_last_name' => $this->getUserNameFromUserId($row['useruid']),
					);
				}
			}
		}
	    return $conflicts;
	}



	/*
	 * function checkConflicts
	 * @param $arg
	 */
	function checkConflictsCreate($attendees) {

		// comparison timestamps
	    $compStart = strtotime($this->piVars['startdat']);
	    $compEnd = strtotime($this->piVars['enddat']);
		
	    // get attendees
		if (count($attendees)) {
			foreach($attendees as $attendee => $val) {
				$fields = '*, tx_keyac_dates.uid as eventuid, fe_users.uid as useruid, tx_keyac_dates.title as eventtitle';
				$table = 'tx_keyac_dates, tx_keyac_dates_attendees_mm, fe_users';
				$where = 'tx_keyac_dates_attendees_mm.uid_foreign="'.$attendee.'" ';
				$where .= ' AND tx_keyac_dates.uid=tx_keyac_dates_attendees_mm.uid_local';
				$where .= ' AND fe_users.uid=tx_keyac_dates_attendees_mm.uid_foreign ';
				$where .= ' AND (';
				$where .= 'startdat between '.$compStart.' AND '.$compEnd;
				$where .= ' OR enddat between '.$compStart.' AND '.$compEnd;
				$where .= ' OR (startdat < '.$compStart.' AND enddat > '.$compEnd.')';
				$where .= ' OR (startdat = '.$compStart.' AND enddat='.$compEnd.')';
				$where .= ' OR (startdat > '.$compStart.' AND enddat < '.$compEnd.')';
				$where .= ' OR (startdat = '.$compStart.' AND enddat < '.$compEnd.')';
				$where .= ' OR (startdat > '.$compStart.' AND enddat = '.$compEnd.')';
				$where .= ')';
				$where .= $this->cObj->enableFields('tx_keyac_dates');
				$where .= $this->cObj->enableFields('fe_users');
				#debug($GLOBALS['TYPO3_DB']->SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit=''),'SELECT');
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
				$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$conflicts[] = array(
						'title' => $row['eventtitle'],
						'startdat' => $row['startdat'],
						// 'startdat' => strftime('%d.%m.%Y %H:%M', $row['startdat']),
						'enddat' => $row['enddat'],
						// 'enddat' => strftime('%d.%m.%Y %H:%M', $row['enddat']),
						'showtime' => $row['showtime'],
						'private' => $row['private'],
						'user_first_name' => '',
						'user_last_name' => $this->getUserNameFromUserId($row['useruid']),
					);
				}
			}
		}
	    return $conflicts;
	}



	/*
	 * function processFormData
	 */
	function processFormData($editUid=0,$changedFields=array()) {

		$table = 'tx_keyac_dates';

		$fields_values = array(
		    'pid' => $this->pids,
		    'startdat' => strtotime($this->piVars['startdat']),
		    'enddat' => strtotime($this->piVars['enddat']),
		    'title' => t3lib_div::removeXSS($this->piVars['title']),
		    'bodytext' => $this->piVars['bodytext'],
		    'teaser' => $this->piVars['teaser'],
		    'showtime' => $this->piVars['showtime'],
		    'private' => $this->piVars['private'],
		    'location' => t3lib_div::removeXSS($this->piVars['location']),
		    'cat' => sizeof($this->piVars['cat']),
		    'address' => t3lib_div::removeXSS($this->piVars['address']),
		    'zip' => t3lib_div::removeXSS($this->piVars['zip']),
		    'city' => t3lib_div::removeXSS($this->piVars['city']),
		    'tstamp' => time(),
		    'crdate' => time(),
		    'cruser_id' => intval($GLOBALS['TSFE']->fe_user->user['uid']),
		    'owner' => intval($GLOBALS['TSFE']->fe_user->user['uid']),
		    'attachments' => $this->piVars['attachments'],
		);

		// Termin ganztägig?
		if (!$this->piVars['showtime']) {
		    $newStartdat = strtotime($this->piVars['startdat']);
		    $newStartdat = strftime('%d.%m.%Y', $newStartdat).' 00:00';
		    $newStartdat = strtotime($newStartdat);
		    $fields_values['startdat'] = $newStartdat;

		    $newEnddat = strtotime($this->piVars['enddat']);
		    $newEnddat= strftime('%d.%m.%Y', $newEnddat).' 23:59';
		    $newEnddat= strtotime($newEnddat);
		    $fields_values['enddat'] = $newEnddat;
		}

		// new entry
		if (!$editUid) {
			// write to db
			if ($GLOBALS['TYPO3_DB']->exec_INSERTquery($table,$fields_values,$no_quote_fields=FALSE)) {
				$insertedUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
				$this->piVars['showUid'] = $insertedUid;

				// store category mm table entries
				if (sizeof($this->piVars['cat'])) {
					$table = 'tx_keyac_dates_cat_mm';
					foreach ($this->piVars['cat'] as $key => $catUid) {
						$fields_values = array(
							'uid_local' => $insertedUid,
							'uid_foreign' => $catUid,
							'tablenames' => '',
							'sorting' => 1,
						);
						$GLOBALS['TYPO3_DB']->exec_INSERTquery($table,$fields_values,$no_quote_fields=FALSE);
					}
				}
				
				// send invitations
				if (count($this->piVars['user'])) {
					// automatically set invited persons as attendee 
					if ($this->piVars['invitation_mode'] == 'set') {
						foreach ($this->piVars['user'] as $invUser => $value) {
							$this->setUserAsAttendant($insertedUid, $invUser, false);
						}
					}
					$this->processInviteData();
				}
				
				// set owner as attendee ?
				if ($this->piVars['owner_attend']) $this->setUserAsAttendant($insertedUid, $GLOBALS['TSFE']->fe_user->user['uid'], false);

				// clear page cache
				$this->clearPageCache($GLOBALS['TSFE']->id);

				// print success message
				$content = $this->cObj->getSubpart($this->templateCode,'###GENERAL_MESSAGE###');
				$content = $this->cObj->substituteMarker($content,'###TEXT###',$this->pi_getLL('create_successful'));
				$content = $this->cObj->substituteMarker($content,'###BACKLINK###',$this->getListviewLink($this->pi_getLL('back')));
				$content = $this->cObj->substituteMarker($content,'###BACKLINK_ICON###',$this->cObj->IMAGE($this->conf['singleview.']['backlinkIcon.']));
				$content = $this->cObj->substituteMarker($content,'###CSSCLASS###','message-success');
			}
			// print error message
			else $content = $this->pi_getLL('creation_error');
		}
		// update existing entry
		else {

			$where = 'uid="'.intval($editUid).'" ';
			$fields_values = array(
				'title' => t3lib_div::removeXSS($this->piVars['title']),
				'startdat' => strtotime($this->piVars['startdat']),
				'enddat' => strtotime($this->piVars['enddat']),
				'bodytext' => $this->piVars['bodytext'],
				'teaser' => $this->piVars['teaser'],
				'showtime' => $this->piVars['showtime'],
				'private' => $this->piVars['private'],
				'location' => t3lib_div::removeXSS($this->piVars['location']),
				'cat' => sizeof($this->piVars['cat']),
				'address' => t3lib_div::removeXSS($this->piVars['address']),
				'zip' => t3lib_div::removeXSS($this->piVars['zip']),
				'city' => t3lib_div::removeXSS($this->piVars['city']),
				'tstamp' => time(),
				'attachments' => $this->piVars['attachments'],
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,$where,$fields_values,$no_quote_fields=FALSE);

			// delete existing category mm entries
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_keyac_dates_cat_mm','uid_local="'.$editUid.'" ');
			// store category mm table entries
			if (sizeof($this->piVars['cat'])) {
				$table = 'tx_keyac_dates_cat_mm';
				foreach ($this->piVars['cat'] as $key => $catUid) {
					$fields_values = array(
						'uid_local' => intval($editUid),
						'uid_foreign' => intval($catUid),
						'tablenames' => '',
						'sorting' => 1,
					);
					$GLOBALS['TYPO3_DB']->exec_INSERTquery($table,$fields_values,$no_quote_fields=FALSE);
				}
			}

			// clear page cache
			$this->clearPageCache($GLOBALS['TSFE']->id);

			// print success message
			$content = $this->cObj->getSubpart($this->templateCode,'###GENERAL_MESSAGE###');
			$content = $this->cObj->substituteMarker($content,'###TEXT###',$this->pi_getLL('edit_successful'));
			$content = $this->cObj->substituteMarker($content,'###BACKLINK###',$this->getListviewLink($this->pi_getLL('back')));
			$content = $this->cObj->substituteMarker($content,'###BACKLINK_ICON###', $this->backlinkIcon);
			$content = $this->cObj->substituteMarker($content,'###CSSCLASS###','message-success');

			// send notification
			$recordData = $this->getEventRecord($editUid);
			$userRecord = $this->getUserRecord($GLOBALS['TSFE']->fe_user->user['uid']);

			// send notification to other attendees (but not to current user and not to owner)
			$attendees = $this->getAttendees($editUid);
			if (count($attendees)) {

			    // generate "changed fields" content
			    $changedFieldsContent = '';
			    if (count($changedFields)) {
					$rows = '';
					foreach ($changedFields as $field) {
						$rowContent = $this->cObj->getSubpart($this->templateCode,'###CHANGED_FIELD_ROW###');
						$rowContent = $this->cObj->substituteMarker($rowContent,'###FIELD###',$this->pi_getLL('label_'.$field));
						switch ($field) {
							case 'startdat':
							case 'enddat':
								$value = strftime('%d.%m.%Y %H:%M' , $recordData[$field]);
								break;
							case 'showtime':
								$value = $recordData[$field] == 1 ? $this->pi_getLL('yes') : $this->pi_getLL('no');
								break;
							default:
								$value = $recordData[$field];
								break;
						}
						$rowContent = $this->cObj->substituteMarker($rowContent,'###VALUE###',$value);
						$rows .= $rowContent;
					}
					$changedFieldsContent = $this->cObj->getSubpart($this->templateCode,'###CHANGED_FIELDS###');
					$changedFieldsContent = $this->cObj->substituteSubpart ($changedFieldsContent, '###CHANGED_FIELD_ROW###', $rows, $recursive=1);
			    }

			    foreach ($attendees as $key => $attendee) {
					if ($attendee['uid'] != $GLOBALS['TSFE']->fe_user->user['uid'] && $attendee['uid'] != $recordData['owner'] ) {

						// generate salutation by gender
						switch ($attendee['gender']) {
							case 'm':	$salutationText = 'salutation_male'; break;
							case 'f':	$salutationText = 'salutation_female'; break;
							default:	$salutationText = 'salutation_general'; break;
						}

						// generate mail content
						$mailContent = $this->cObj->getSubpart($this->templateCode,'###EDIT_NOTIFICATION_MAIL###');
						$markerArray = array(
							'salutation' => $this->pi_getLL($salutationText),
							// TODO: fill marker first_name and last_name
							// correctly, if first_name and last_name
							// are not set
							'first_name' => '',
							'last_name' => $this->getUserNameFromUserId($attendee['uid']),
							'editor_name' => $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']),
							'event_title' => $recordData['title'],
							'event_link' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').$this->getSingleviewLink($editUid),
							'mail_footer' => $this->cObj->getSubpart($this->templateCode,'###GENERAL_MAIL_FOOTER###'),
							'edit_notification_subject' => sprintf($this->pi_getLL('edit_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $recordData['title']),
							'use_following_link' => $this->pi_getLL('use_following_link'),
						);
						$mailContent = $this->cObj->substituteMarkerArray($mailContent,$markerArray,$wrap='###|###',$uppercase=1);
						$mailContent = $this->cObj->substituteSubpart ($mailContent, '###CHANGED_FIELDS###', $changedFieldsContent, $recursive=1);
						$subject = sprintf($this->pi_getLL('edit_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $recordData['title']);
						$this->sendNotificationMail($attendee['email'], $subject, $mailContent, $userRecord['email'], $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']));
					}
			    }
			}

		    // send notification to owner
		    if ($recordData['owner'] != $GLOBALS['TSFE']->fe_user->user['uid']) {
			$ownerData = $this->getUserRecord($recordData['owner']);
			// generate salutation by gender
			switch ($ownerData['gender']) {
				case 'm':	$salutationText = 'salutation_male'; break;
				case 'f':	$salutationText = 'salutation_female'; break;
				default:	$salutationText = 'salutation_general'; break;
			}

			// generate mail content
			$mailContent = $this->cObj->getSubpart($this->templateCode,'###EDIT_NOTIFICATION_MAIL###');
			$markerArray = array(
			    'salutation' => $this->pi_getLL($salutationText),
			    'first_name' => '',
			    'last_name' => $this->getUserNameFromUserId($ownerData['uid']),
			    'editor_name' => $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']),
			    'event_title' => $recordData['title'],
			    'event_link' => t3lib_div::getIndpEnv('TYPO3_SITE_URL').$this->getSingleviewLink($editUid),
			    'mail_footer' => $this->cObj->getSubpart($this->templateCode,'###GENERAL_MAIL_FOOTER###'),
				'edit_notification_subject' => sprintf($this->pi_getLL('edit_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $recordData['title']),
				'use_following_link' => $this->pi_getLL('use_following_link'),
			);
			$mailContent = $this->cObj->substituteMarkerArray($mailContent,$markerArray,$wrap='###|###',$uppercase=1);
			$mailContent = $this->cObj->substituteSubpart ($mailContent, '###CHANGED_FIELDS###', $changedFieldsContent, $recursive=1);
			$subject = sprintf($this->pi_getLL('edit_notification_subject'), $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']), $recordData['title']);
			$this->sendNotificationMail($ownerData['email'], $subject, $mailContent, $userRecord['email'], $this->getUserNameFromUserId($GLOBALS['TSFE']->fe_user->user['uid']));
		    }
		}
		return $content;
	}


	/*
	 * function getListviewLinkUrl
	 * @param $arg
	 */
	function getListviewLink($text='') {
		// generate url to listview for "back" button
		#$linkconf['parameter'] = $GLOBALS['TSFE']->id;
		$linkconf['parameter'] = $this->listviewPid;
		$linkconf['additionalParams'] = '';
		$linkconf['useCacheHash'] = false;

		if ($text) return $this->cObj->typoLink($text, $linkconf);
		else return $this->cObj->typoLink_URL($linkconf);
	}

	/*
	 * function getListviewLinkUrl
	 * @param $arg
	 */
	function getSingleviewLink($uid, $text='') {
		// generate url to listview for "back" button
		#$linkconf['parameter'] = $GLOBALS['TSFE']->id;
		$linkconf['parameter'] = $this->singleviewPid;
		$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.$uid;
		$linkconf['useCacheHash'] = false;
		if ($text) return $this->cObj->typoLink($text, $linkconf);
		else return $this->cObj->typoLink_URL($linkconf);
	}



	/*
	 * function getUserRecord
	 * @param $arg
	 */
	function getUserRecord($uid, $field='') {
		$fields = '*';
		$table = 'fe_users';
		$where = 'uid="'.intval($uid).'" ';
		$where .= $this->cObj->enableFields($table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='1');
		$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return !empty($field) ? $row[$field] : $row;
	}

	/*
	 * function getEventRecord
	 * @param $arg
	 */
	function getEventRecord($uid, $field='') {
		$fields = '*';
		$table = 'tx_keyac_dates';
		$where = 'uid="'.intval($uid).'" ';
		$where .= $this->cObj->enableFields($table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='1');
		$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return !empty($field) ? $row[$field] : $row;
	}


	/*
	 * function getAttendees
	 */

	function getAttendees($eventUid) {
	    $fields = '*';
	    $table = 'tx_keyac_dates_attendees_mm, fe_users';
	    $where = 'tx_keyac_dates_attendees_mm.uid_local = "'.$eventUid.'" ';
	    $where .= 'AND tx_keyac_dates_attendees_mm.uid_foreign = fe_users.uid';
	    $where .= $this->cObj->enableFields('fe_users');
	    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='uid_foreign',$orderBy='',$limit='');
	    $anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
	    while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
		    $attendees[] = $row;
	    }
	    return $attendees;
	}



	/*
	 * function findEventTime()
	 * @param $arg
	 */
	function findEventTime($editUid=0) {

	    $initialStartdat = strtotime($this->piVars['startdat']);
	    $initialEnddat = strtotime($this->piVars['enddat']);

	    // get Attendees for this event
	    if ($editUid) {
			$attendees = $this->getAttendees($editUid);
		} else {
			if (count($this->piVars['user'])) {
				foreach ($this->piVars['user'] as $userId => $value) {
					$attendees[] = array('uid' => $userId);
				}
			}
	    }
		
	    // run through days and check for conflicts
	    for($i=1; $i <= 10; $i++) {
		$startdat = $initialStartdat + ($i * 3600 * 24);
		$enddat =  $initialEnddat + ($i * 3600 * 24);
		
			if (strftime('%u',$startdat) < 6 ) {
				$conflicts = $this->checkConflicts($startdat, $enddat, $attendees);
				if (!is_array($conflicts)) {
					// add as suggestion if no conflict found
					$suggestions[] = array(
						'startdat' => $startdat,
						'enddat' => $enddat,
					);
				}
			}
	    }

	    // if suggestions found
	    if (count($suggestions)) {
			// build suggestion rows
			foreach($suggestions as $suggestion) {
				$suggestionRowTemp = $this->cObj->getSubpart($this->templateCode,'###SUGGESTION_ROW###');
				$suggestionRowTemp = $this->cObj->substituteMarker($suggestionRowTemp,'###VALUE###',$suggestion['startdat'].'|'.$suggestion['enddat']);
				$suggestionRowTemp = $this->cObj->substituteMarker($suggestionRowTemp,'###STARTDAT###', strftime('%a, %d.%m.%Y %H:%M',$suggestion['startdat']));
				$suggestionRowTemp = $this->cObj->substituteMarker($suggestionRowTemp,'###ENDDAT###', strftime('%a, %d.%m.%Y %H:%M',$suggestion['enddat']));
				$suggestionRows .=  $suggestionRowTemp;
			}
			// build suggestion form
			$content = $this->cObj->getSubpart($this->templateCode,'###SUB_SUGGESTIONS###');
			$content = $this->cObj->substituteSubpart ($content, '###SUGGESTION_ROW###', $suggestionRows, $recursive=1);
			$content = $this->cObj->substituteMarker($content,'###DATE_SUGGESTIONS###', $this->pi_getLL('date_suggestions'));
			
			// build hidden fields content
			foreach ($this->piVars as $field => $value) {
				if ($field != "submiteditfind" && $field != "submitcreatefind") {
					if (is_array($value)) {
						if ($field == 'user') {
							foreach ($value as $userId => $val) {
								$hiddenfields .= '<input type="hidden" name="tx_keyac_pi1['.$field.']['.$userId.']" value="'.$val.'">';
							}
						}
					} else {
						$hiddenfields .= '<input type="hidden" name="tx_keyac_pi1['.$field.']" value="'.$value.'">';
					}
				}
			}
			$content = $this->cObj->substituteMarker($content,'###HIDDENFIELDS###',$hiddenfields);
	    }
	    // no suggestions found
	    else {
		$content = $this->cObj->getSubpart($this->templateCode,'###SUB_SUGGESTIONS###');
		$content = $this->cObj->substituteSubpart ($content, '###SUGGESTION_ROW###', $this->pi_getLL('no_suggestions_found'), $recursive=1);
	    }

	    return $content;

	}


	/*
	 * function checkConflicts
	 * @param $arg
	 */
	function checkConflicts($startdat, $enddat, $attendees) {

	    // comparison timestamps
	    $compStart = $startdat;
	    $compEnd = $enddat;

	    // get attendees
	    if (count($attendees)) {
			foreach($attendees as $key => $attendee) {
				$fields = '*, tx_keyac_dates.uid as eventuid, fe_users.uid as useruid, tx_keyac_dates.title as eventtitle';
				$table = 'tx_keyac_dates, tx_keyac_dates_attendees_mm, fe_users';
				$where = 'tx_keyac_dates_attendees_mm.uid_foreign="'.$attendee['uid'].'" ';
				$where .= ' AND tx_keyac_dates.uid=tx_keyac_dates_attendees_mm.uid_local';
				$where .= ' AND fe_users.uid=tx_keyac_dates_attendees_mm.uid_foreign ';
				$where .= ' AND (';
				$where .= 'startdat between '.$compStart.' AND '.$compEnd;
				$where .= ' OR enddat between '.$compStart.' AND '.$compEnd;
				$where .= ' OR (startdat < '.$compStart.' AND enddat > '.$compEnd.')';
				$where .= ' OR (startdat = '.$compStart.' AND enddat='.$compEnd.')';
				$where .= ' OR (startdat > '.$compStart.' AND enddat < '.$compEnd.')';
				$where .= ' OR (startdat = '.$compStart.' AND enddat < '.$compEnd.')';
				$where .= ' OR (startdat > '.$compStart.' AND enddat = '.$compEnd.')';
				$where .= ')';
				$where .= $this->cObj->enableFields('tx_keyac_dates');
				$where .= $this->cObj->enableFields('fe_users');
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
				$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$conflicts[] = array(
						'title' => $row['eventtitle'],
						'startdat' => $row['startdat'],
						'enddat' => $row['enddat'],
						'showtime' => $row['showtime'],
						'user_first_name' => '',
						'user_last_name' => $this->getUserNameFromUserId($row['uid']),
					);
				}
			}
	    }
	    return $conflicts;
	}


	/**
	 * Uploads the file given in the form-field $attachmentName to the server
	 *
	 * success: returns the new filename
	 * no success: returns false
	 *
	 * @param string $attachmentName
	 * @return array
	 */
	public function handleUpload($attachmentName='attachment') {
		$success = true;

		// does the directory exist?
		if (!is_dir($this->uploadFolder)) $this->formErrors[] = $this->pi_getLL('error_no_upload_directory','Upload directory does not exist.');
		// set deault values
		$this->conf['maxFileSize'] = $this->conf['maxFileSize'] ? $this->conf['maxFileSize'] : 20000000;
		// get the destination filename
		$filefuncs = new t3lib_basicFilefunctions();
		$uploadfile = $filefuncs->getUniqueName($filefuncs->cleanFileName($_FILES[$attachmentName]['name']), $this->uploadFolder);
		// Filesize OK?
		if($_FILES[$attachmentName]['size'] > $this->conf['maxFileSize']) {
			$this->formErrors[] = $this->pi_getLL('error_file_too_big','Error: File is too big.');
			$success=false;
		}
		// File extension allowed?
		if(!$this->extAllowed($_FILES[$attachmentName]['name'])) {
			$this->formErrors[] = $this->pi_getLL('error_filetype_not_allowed','Error: This Filetype is not allowed.');
			$success=false;
		}

		if($success && move_uploaded_file($_FILES[$attachmentName]['tmp_name'], $uploadfile)) {
			chmod($uploadfile,octdec('0744'));
 		} else {
			$this->formErrors[] = $this->pi_getLL('error_file_upload_not_successful','Error: File upload was not successfull.');
			$success=false;
		}
		
		if ($success) {
			return basename($uploadfile);
		} else {
			return false;
		}
	}


	/**
	 * Helper public function for handleUpload
	 * Is the file extension allowed?
	 *
	 * @return boolean
	 */
	public function extAllowed($filename) {
		// set default values
		$this->conf['checkFileExt'] = $this->conf['checkFileExt'] ? $this->conf['checkFileExt'] : 1;
		$this->conf['extInclude'] = $this->conf['extInclude'] ? $this->conf['extInclude'] : 'pdf,doc,rtf,txt,odt,sxw,jpg,jpeg,gif,png,bmp';

		//all extensions allowed?
		if (!($this->conf['checkExt'])) return TRUE;

		$includelist = explode(",",$this->conf['extInclude']);

		//overrides includelist
		$excludelist = explode(",",$this->conf['extExclude']);

		$extension='';
		if($extension=strstr($filename,'.')) {
			$extension=strtolower(substr($extension, 1));
			return ((in_array($extension,$includelist) || in_array('*',$includelist)) && (!in_array($extension,$excludelist)));
		} else {
			return false;
		}
	}


     /**
     * Format a number of bytes into a human readable format.
     * Optionally choose the output format and/or force a particular unit
     *
     * @param   int     $bytes      The number of bytes to format. Must be positive
     * @param   string  $format     Optional. The output format for the string
     * @param   string  $force      Optional. Force a certain unit. B|KB|MB|GB|TB
     * @return  string              The formatted file size
     */
    function filesize_format($bytes, $format = '', $force = '') {
        $force = strtoupper($force);
        $defaultFormat = '%01d %s';
        if (strlen($format) == 0) $format = $defaultFormat;
        $bytes = max(0, (int) $bytes);
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $power = array_search($force, $units);
        if ($power === false) $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return sprintf($format, $bytes / pow(1024, $power), $units[$power]);
    }


} // end class
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ke_yac/pi1/class.tx_keyac_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ke_yac/pi1/class.tx_keyac_pi1.php']);
}

?>
