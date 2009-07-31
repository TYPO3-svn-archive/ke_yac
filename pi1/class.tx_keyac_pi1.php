<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2008 Andreas Kiefer <kiefer@kennziffer.com>
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
	var $pi_checkCHash = TRUE;
	var $uploadFolder = "uploads/tx_keyac/";
	
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
		
		// DB DEBUG
 		$GLOBALS['TYPO3_DB']->debugOutput = true;
		
		// starting point
		$pages = $this->cObj->data['pages'] ? $this->cObj->data['pages'] : ( $this->conf['dataPids'] ? $this->conf['dataPids'] : $GLOBALS['TSFE']->id);
		$this->pids = $this->pi_getPidList($pages,$this->cObj->data['recursive']);
		
		// Include HTML Template 
		$this->templateFile = $this->ffdata['templateFile'] ? $this->uploadFolder.$this->ffdata['templateFile'] : $this->conf['templateFile'];
		$this->templateCode = $this->cObj->fileResource($this->templateFile);
		
		// Include CSS File
		$cssfile = $this->conf['cssfile'] ? $this->conf['cssfile'] : t3lib_extMgm::siteRelPath($this->extKey).'res/css/yac.css';
		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= '<link rel="stylesheet" type="text/css" href="'.$cssfile.'" />';
		
		// get Format Strings from FF or TS
		$this->formatStringWithTime = $this->ffdata['strftimeFormatStringWithTime'] ? $this->ffdata['strftimeFormatStringWithTime'] : $this->conf['strftimeFormatStringWithTime'];
		$this->formatStringWithoutTime = $this->ffdata['strftimeFormatStringWithoutTime'] ? $this->ffdata['strftimeFormatStringWithoutTime'] : $this->conf['strftimeFormatStringWithoutTime'];
		$this->formatTime = $this->ffdata['strftimeFormatTime'] ? $this->ffdata['strftimeFormatTime'] : $this->conf['strftimeFormatTime'];
		
		// Duration until fadeout for tooltips
		$this->tooltipDuration = $this->ffdata['tooltipDuration'] ? $this->ffdata['tooltipDuration'] : $this->conf['tooltipDuration'];
		
		// get the plugin-mode from flexforms
		$mode_selector = $this->ffdata['mode_selector'];
		// Overwrite Mode if teaser is set in TS
		if ($this->conf['mode'] == 'TEASER') $mode_selector = 1;
		
		// get Content corresponding to mode
		switch($mode_selector) {
			
			// TEASER-VIEW
			case "1": 
				$content = $this->teaserView();
				break;
			
			case "2":
				$content = $this->myEventsView();
				break;
			
			// CALENDAR VIEW
			case "0": 
			default: 
				
				// action handling
				// user wants to attend
				if ($this->piVars['action'] == 'attend' && $GLOBALS['TSFE']->loginUser) {
					$table = 'tx_keyac_dates_attendees_mm';
					$fields_values = array(
						'uid_local' => $this->piVars['showUid'],
						'uid_foreign' => $GLOBALS['TSFE']->fe_user->user['uid'],
					);
					$GLOBALS['TYPO3_DB']->exec_INSERTquery($table,$fields_values,$no_quote_fields=FALSE);
					#debug('angemeldet');
					
					// clear page cache
					$this->clearPageCache($GLOBALS['TSFE']->id);
				}
				// user wants to delete his attendance
				if ($this->piVars['action'] == 'delattendance' && $GLOBALS['TSFE']->loginUser) {
					$table = 'tx_keyac_dates_attendees_mm';
					$where = ' uid_local="'.$this->piVars['showUid'].'" AND uid_foreign="'.$GLOBALS['TSFE']->fe_user->user['uid'].'"  ';
					$GLOBALS['TYPO3_DB']->exec_DELETEquery($table,$where);
					#debug('abgemeldet');
					
					// clear page cache
					$this->clearPageCache($GLOBALS['TSFE']->id);
				}
				
				if ($this->piVars['showCal']=="") $this->piVars['showCal']=1;
				// single view if event is chosen
				if ($this->piVars['showUid']) {
					$content.=$this->singleView($this->piVars['showUid']);
				} 
				// if month and year for viewing the cal are chosen
				else if ($this->piVars['month'] && $this->piVars['year']) {
					$this->loadJS();
					$content.=$this->getCalendarView($this->piVars['month'],$this->piVars['year']);
				} 
				// show current month if nothing is set
				else {
					$this->loadJS();
					$content.=$this->getCalendarView();
				} 
				break;
			
			
			
		} 
		return $this->pi_wrapInBaseClass($content);
	}
	
	
	
	function loadJS() {
		
		// Load Javascript Library (Mootools)
		// only if listview is shown
		if ($this->conf['useJS']) {
			$slideJS = t3lib_extMgm::siteRelPath($this->extKey).'res/js/slide.js';
			$mootoolsJS = t3lib_extMgm::siteRelPath($this->extKey).'res/js/mootools-1.2.js';
			#$mootoolsJS = t3lib_extMgm::siteRelPath($this->extKey).'pi1/js/mootoolsv1.11.js';
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= '<script src="'.$mootoolsJS.'" type="text/javascript"></script>';
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= '<script src="'.$slideJS.'" type="text/javascript"></script>';
		}
		
	}
	
	
	/* 
	* show calendars and other elements of this view
	*/
	function getCalendarView($month=0,$year=0) {
		
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
		
		$content = $this->cObj->getSubpart($this->templateCode,'###MAIN_TEMPLATE###');
		$content = $this->cObj->substituteMarkerArray($content,$markerArray,$wrap='###|###',$uppercase=1);
		
		// overwrite subparts if not activated
		if (!$this->ffdata['showHideCalendarLink'] && !$this->conf['showHideCalendar']) $content = $this->cObj->substituteSubpart ($content, '###SUB_HIDE_CALENDAR###', '');
		if (!$this->ffdata['showLegend'] && !$this->conf['showLegend']) $content = $this->cObj->substituteSubpart ($content, '###SUB_LEGEND###', '');
		if (!$this->ffdata['showList'] && !$this->conf['showList']) $content = $this->cObj->substituteSubpart ($content, '###SUB_LISTVIEW###', '');
		
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
			$text = strftime('%B %y',$timestamp);
						
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
			$text = strftime('%B %y',$timestamp);
			
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
		$fields = '*, tx_keyac_cat.uid as catuid, tx_keyac_dates.uid as dateuid';
		$table = 'tx_keyac_dates, tx_keyac_cat, tx_keyac_dates_cat_mm';
		$enableFields1 = $lcObj->enableFields('tx_keyac_dates',$show_hidden=0);
		$enableFields2 = $lcObj->enableFields('tx_keyac_cat',$show_hidden=0);
		$where = ' ( ( startdat >= '.$timestamp_start.' AND startdat <= '.$timestamp_end.' )';
		$where.= ' OR (enddat >= '.$timestamp_start.' AND enddat <= '.$timestamp_end.' )';
		$where.= ' OR ( startdat <= '.$timestamp_start.' AND enddat >= '.$timestamp_end.' ) )';
		$where.= ' AND tx_keyac_dates_cat_mm.uid_local = tx_keyac_dates.uid';
		$where.= ' AND tx_keyac_dates_cat_mm.uid_foreign = tx_keyac_cat.uid';
		$where.=$enableFields1.$enableFields2;
		$where.=' and tx_keyac_dates.pid in ('.$this->pids.') and tx_keyac_cat.pid in ('.$this->pids.') ';
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
		// walk through results
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
		
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
				else $datesarray[$starttag] = $row['catuid']."s0";
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
							else $datesarray[$i]=$row['catuid'];
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
							else $datesarray[$i]=$row['catuid'];
						}	
					}
					// if month between startmonth and endmonth
					else {
						for ($i=1; $i<=$days_month; $i++) {
							if ($datesarray[$i]!='') $datesarray[$i]=999;
							else $datesarray[$i]=$row['catuid'];
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
							else $datesarray[$starttag]=$row['catuid']."s1";
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
								else $datesarray[$i]=$row['catuid'];
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
								else $datesarray[$i]=$row['catuid'];
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
								else $datesarray[$i]=$row['catuid'];
							}	
						}
						
						// duration of event over 1 month
						// all days with event
						if ($month > $startmonat && $month < $endmonat) {
							for ($i=1; $i<=$days_month; $i++) {
								if ($datesarray[$i]!='') $datesarray[$i]=999;
								else $datesarray[$i]=$row['catuid'];
							}
						}
					}
				}
			}
		}
		// return results array
		return $datesarray;
	} // end func getDBData
	
	
	
	/**************************************************************************
	* function for viewing a small calendar
	* month and year needed
	* array with events (see function getDBData)
	************************************************************************* */
	function showMonth($month,$year,$dates) {
		#debug(strftime('%B'),1);
		
		
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
							$overrulePIvars = array('showUid' => $row['uid']);
							$daylink = $this->pi_linkTP_keepPIvars_url($overrulePIvars,$cache=1,$clearAnyway=0);
						}
						// otherwise, link to entry in listview
						else {
							$linkconf['parameter'] = $GLOBALS['TSFE']->id;
							$daylink = $this->cObj->typoLink_URL($linkconf).'#'.$anchorlink;
						}
						
						
						// generate table cell content for current day
						$content.='
							<td class="'.$class.'" id="'.$anchorlink.'_cell">
								<a href="'.$daylink.'" id="'.$anchorlink.'_link">'.sprintf("%0{$stellen}d",$day).'</a>
								<div id="'.$anchorlink.'_layer" class="yac-tooltip" style="display:none;">'.$this->listView($day_format, $month_format, $year,true).'</div>
							</td>';
						
						
						// generate tooltips if set in FF or TS
						if ($this->ffdata['showTooltips'] || $this->conf['showTooltips']) {
							$content .= '
								<script type="text/javascript">
									<!--
									
										var fadeout = 0;
										
										$(\''.$anchorlink.'_link\').addEvent(\'mouseenter\', function(e) {
											e.stop();
											list = $$(\'td div.yac-tooltip\');
											if (fadeout > 0) window.clearTimeout(fadeout);
											for (var i = 0, j = list.length; i < j; i++){
												element = list[i];
												element.fade(0);
												element.setStyle(\'display\', \'none\');
											}
											$(\''.$anchorlink.'_layer\').setStyle(\'z-index\', \'99999\');
											$(\''.$anchorlink.'_layer\').setStyle(\'display\', \'inline\');
											$(\''.$anchorlink.'_layer\').fade(1);
										});
										
										$(\''.$anchorlink.'_cell\').addEvent(\'mouseleave\', function(e) {
											e.stop();
											$(\''.$anchorlink.'_layer\').setStyle(\'z-index\', \'1\');
											fadeout = window.setTimeout("$(\''.$anchorlink.'_layer\').fade(0)", '.$this->tooltipDuration.');
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
	 * list view of events for the 3 months shown in calendar
	 */
	function listView($day=0,$month=0,$year=0,$tooltip=false) {
		
		$lcObj=t3lib_div::makeInstance('tslib_cObj');
		
		// generate "more" icon
		$imageConf = $this->conf['listview.']['moreIcon.'];
		$moreIcon=$this->cObj->IMAGE($imageConf);
		
		// list events 
		$table = 'tx_keyac_dates, tx_keyac_cat, tx_keyac_dates_cat_mm';
		$fields = '*, tx_keyac_cat.uid as catuid, tx_keyac_dates.title as datetitle, tx_keyac_dates.uid as dateuid';
		$enableFields1 = $lcObj->enableFields('tx_keyac_dates',$show_hidden=0);
		$enableFields2 = $lcObj->enableFields('tx_keyac_cat',$show_hidden=0);
		
		// wenn modus gesetzt: Daten nur für einzelnen Tag aus DB holen
		if ($day && $month && $year) {
			$start_ts = mktime(0, 0, 0, $month, $day, $year);
			$end_ts = mktime(23, 59, 59, $month, $day, $year) ;
		}
		else {
			$start_ts = $this->starttime;
			$end_ts = $this->endtime;
		}
		
		$where = ' ( ( startdat >= '.$start_ts.' AND startdat <= '.$end_ts.' )';
		$where.= ' OR (enddat >= '.$start_ts.' AND enddat <= '.$end_ts.' )';
		$where.= ' OR ( startdat <= '.$start_ts.' AND enddat >= '.$end_ts.' ) )';
		$where.= ' AND tx_keyac_dates_cat_mm.uid_local = tx_keyac_dates.uid';
		$where.= ' AND tx_keyac_dates_cat_mm.uid_foreign = tx_keyac_cat.uid';
		$where.=$enableFields1.$enableFields2;
		$where.=' and tx_keyac_dates.pid in ('.$this->pids.') and tx_keyac_cat.pid in ('.$this->pids.') ';
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='dateuid',$orderBy='startdat',$limit='');
		$content = '';
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			
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
				if (!$row['enddat']) $datstring = $beginn_datum.', '.$beginn_uhrzeit;
				// begin and end on the same day
				else if ($ende_datum==$beginn_datum ) $datstring = $beginn_datum.' '.$beginn_uhrzeit.' '.$this->pi_getLL('until').' '.$ende_uhrzeit;
				// begin and end at different days
				else $datstring = $beginn_datum .', '.$beginn_uhrzeit.' '.$this->pi_getLL('until').' '.$ende_datum.', '.$ende_uhrzeit;
			}
			// generate link 
			$overrulePIvars = array('showUid' => $row['dateuid']);
			$linkStart = $this->pi_linkTP_keepPIvars_url ($overrulePIvars, $cache=1, $clearAnyway=0);
			
			// generate anchor tag
			if ($day!=0 && $month!=0 && $year!=0) $anchor = '';
			else $anchor = '<a name="'.$start.'" />';
			
			// generate category icon
			$catIconConf = $this->conf['categoryIcon.'][$row['catuid'].'.'];
			if (empty($catIconConf)) $catIconConf = $this->conf['categoryIcon.']['default.'];
			$catIcon = $this->cObj->IMAGE($catIconConf);
			
			// generate thumbnail
			$images = explode(',',$row['images']);
			$thumbConf = $this->conf['listview.']['thumbnail.'];
			$thumbConf['file'] = 'uploads/tx_keyac/'.$images[0];
			$thumbnail = $this->cObj->IMAGE($thumbConf);
			
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
			);
			
			
			// use listview or special tooltip subpart for rendering?
			$subpart = $tooltip ? '###TOOLTIP_ROW###' : '###LISTVIEW_SINGLE###';
			$temp_content = $this->cObj->getSubpart($this->templateCode,$subpart);
			$temp_content = $this->cObj->substituteMarkerArray($temp_content,$markerArray,$wrap='###|###',$uppercase=1);
			$content .= $temp_content;
		}
		return $content;
	}

	
	
	
	/**
	 * single view of event
	 */
	function singleView($id) {
		$lcObj=t3lib_div::makeInstance('tslib_cObj');
		
		// get event data from db
		$table = 'tx_keyac_dates, tx_keyac_cat, tx_keyac_dates_cat_mm';
		$fields = '*, tx_keyac_dates.uid as dateuid, tx_keyac_dates.title as datetitle, tx_keyac_cat.title as cattitle  ';
		$enableFields = $lcObj->enableFields('tx_keyac_dates',$show_hidden=0);
		$where=' tx_keyac_dates.uid="'.$id.' " and tx_keyac_dates.uid=tx_keyac_dates_cat_mm.uid_local and tx_keyac_cat.uid=tx_keyac_dates_cat_mm.uid_foreign ';
		$where.=$enableFields;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			
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
			}
			
			// generate backlink
			if ($this->piVars['backPid']) $backlink = $this->pi_linkToPage($this->pi_getLL('back'),$this->piVars['backPid'],$target='',$urlParameters=array());
			else $backlink = $this->pi_linkTP_keepPIvars($this->pi_getLL('back'),$pivars,$cache=1,$clearAnyway=0);
			
			
			// generate attendance info / link
			if ($this->feuserIsAttendent($GLOBALS['TSFE']->fe_user->user['uid'],$id)) {
				$attendanceStatus = $this->pi_getLL('user_is_attendee');
				unset($linkconf);
				$linkconf['parameter'] = $GLOBALS['TSFE']->id;
	 			$linkconf['additionalParams'] = '&'.$this->prefixId.'[showUid]='.$this->piVars['showUid'];
	 			$linkconf['additionalParams'] .= '&'.$this->prefixId.'[action]=delattendance';
	 			$linkconf['useCacheHash'] = true;
	 			$attendanceAction = $this->cObj->typoLink($this->pi_getLL('delete_attendance'),$linkconf);
			}
			else {
				$attendanceStatus = $this->pi_getLL('user_is_no_attendee');
				unset($linkconf);
				$linkconf['parameter'] = $GLOBALS['TSFE']->id;
	 			$linkconf['additionalParams'] = '&'.$this->prefixId.'[showUid]='.$this->piVars['showUid'];
	 			$linkconf['additionalParams'] .= '&'.$this->prefixId.'[action]=attend';
	 			$linkconf['useCacheHash'] = true;
	 			$attendanceAction = $this->cObj->typoLink($this->pi_getLL('attend'),$linkconf);
			}
			
			
			// fill markers
			$this->markerArray = array(
				'title' => $row['datetitle'],
				'category' => $row['cattitle'],
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
				'label_owner' => $this->pi_getLL('owner'),
				'owner' => $this->renderFEField('owner',$row['owner']),
				'label_attendees' => $this->pi_getLL('attendees'),
				'attendees' => $this->renderFEField('attendees',$row['dateuid']),
				'backlink' => $backlink,
				'backlink_icon' => $this->cObj->IMAGE($this->conf['singleview.']['backlinkIcon.']),
				'label_attendance' => $this->pi_getLL('attendance'),
				'attendance_status' => $attendanceStatus,
				'attendance_action' => $attendanceAction,
			);
			
			// show map?
			#if ($this->conf['showMap']) {
			if ($row['location'] && $row['address'] && $row['zip'] && $row['city']) {
				// include api file
				require_once(dirname(__FILE__). '/../res/GoogleMapAPI.class.php');
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
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_keyac']['additionalSingleviewMarkers'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tx_keyac']['additionalSingleviewMarkers'] as $_classRef) {
					$_procObj = & t3lib_div::getUserObj($_classRef);
					$_procObj->additionalSingleviewMarkers($this);
				}
			}
			
			// fill marker
			$content = $this->cObj->getSubpart($this->templateCode,'###SINGLEVIEW_TEMPLATE###');
			$content = $this->cObj->substituteMarkerArray($content,$this->markerArray,$wrap='###|###',$uppercase=1);
			
			// overwrite subparts if no content available
			if (empty($row['teaser'])) $content = $this->cObj->substituteSubpart($content, '###SUB_TEASERTEXT###', '');
			if (empty($row['bodytext'])) $content = $this->cObj->substituteSubpart($content, '###SUB_DESCRIPTION###', '');
			if (empty($row['infolink'])) $content = $this->cObj->substituteSubpart($content, '###SUB_INFOLINK###', '');
			if (empty($row['images'])) $content = $this->cObj->substituteSubpart($content, '###SUB_IMAGES###', '');
			if (empty($row['attachments'])) $content = $this->cObj->substituteSubpart($content, '###SUB_ATTACHMENTS###', '');
			if (!$row['location'] && !$row['address'] && !$row['zip'] && !$row['city']) $content = $this->cObj->substituteSubpart($content, '###SUB_MAP###', '');
			if (!$GLOBALS['TSFE']->loginUser) $content = $this->cObj->substituteSubpart($content, '###SUB_ATTENDANCE###', '');
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
		
		$fields = '*';
 		$table = 'tx_keyac_dates, tx_keyac_dates_attendees_mm';
 		$where = 'tx_keyac_dates.uid=uid_local ';
 		$where .= 'AND uid_foreign="'.intval($GLOBALS['TSFE']->fe_user->user['uid']).'" ';
 		$where .= $this->cObj->enableFields('tx_keyac_dates');
 		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='startdat desc',$limit='');
 		$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
 		$rowsContent = '';
		$i=1;
		while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			
			// get formatstring for strftime from ts
			$startNoTime = strftime($this->formatStringWithoutTime, $row['startdat']);
			$endNoTime = strftime($this->formatStringWithoutTime, $row['enddat']);
			$startWithTime = strftime($this->formatStringWithTime, $row['startdat']);
			$endWithTime = strftime($this->formatStringWithTime, $row['enddat']);
			
			// startdate and enddate with time
			if ($row['startdat'] && $row['enddat'] && $row['showtime'])
				$datestring = $startWithTime.' - '.$endWithTime;
			// startdate and enddate without time
			else if ($row['startdat'] && $row['enddat']) 			
				$datestring = $startNoTime.' - '.$endNoTime;
			// startdate with time
			else if ($row['startdat'] && $row['showtime'])	
				$datestring = $startWithTime;
			// startdate without time
			else $datestring = $startNoTime;
			
			
			// generate single view url
			$linkconf['parameter'] = $singleViewPid;
 			$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.$row['uid'];
 			$linkconf['useCacheHash'] = true;
 			$singleViewURL = $this->cObj->typoLink_URL($linkconf);
			
			$tempContent = $this->cObj->getSubpart($this->templateCode,'###MYEVENTS_ROW###');
			$tempMarker = array(
				'icon' => $myEventsIcon,
				'link_start' => '<a href="'.$singleViewURL.'">',
				'link_end' => '</a>',
				'title' => $row['title'],
				'date' => $datestring,
				'css_class' => $i%2 ? 'odd' : 'even',
			);
			$tempContent = $this->cObj->substituteMarkerArray($tempContent,$tempMarker,$wrap='###|###',$uppercase=1);
			
			$rowsContent .= $tempContent;
			$i++;
 		}
		$content = $this->cObj->substituteSubpart ($content, '###MYEVENTS_ROW###', $rowsContent, $recursive=1);
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
		$gMaps->addMarkerByAddress($address,$company,$htmladdress,$company);
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
				$images = explode(',', $data);
				// run through the array and render image as set in ts
				foreach ($images as $img) {
					$imgConf = $this->conf['singleviewImg.'];
					$imgConf['file'] = 'uploads/tx_keyac/'.$img;
					$imgContent = $this->cObj->getSubpart($this->templateCode,'###IMAGE_ROW###');
					$imgContent = $this->cObj->substituteMarker($imgContent,'###IMAGE###',$this->cObj->IMAGE($imgConf));
					$fieldContent .= $imgContent;
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
						'name' => $row['name'],
						'email' => $emailLink,
						'company' => $row['company'],
						'www'  => $wwwLink,
					);
					$fieldContent = $this->cObj->substituteMarkerArray($fieldContent,$markerArray,$wrap='###|###',$uppercase=1);
 				}
				
				break;
			
			// ATTENDEES
			case 'attendees':
				$fields = '*';
 				$table = 'fe_users, tx_keyac_dates_attendees_mm';
 				$where = 'uid_local = "'.intval($data).'" ';
 				$where .= 'AND uid_foreign=fe_users.uid';
 				$where .= $this->cObj->enableFields('fe_users');
 				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
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
						'name' => $row['name'],
						'email' => $emailLink,
						'company' => $row['company'],
						'www'  => $wwwLink,
					);
					$fieldContent = $this->cObj->substituteMarkerArray($fieldContent,$markerArray,$wrap='###|###',$uppercase=1);					
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
			
			// generate listitem
			/*
			$listitem = $row['image'] ? '<img src="uploads/tx_keyac/'.$row['image'].'" valign="top">' : '';
			$legend.=$lv > 0 ? "&nbsp;&nbsp;&nbsp;".$listitem.$row['title'] : $listitem.$row['title'];
			$lv++;
			*/
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
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table,$where,$groupBy='',$orderBy='startdat asc',$limit);
		
		$entries = '';
		
		// if no result -> print message
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))  {
			$entries = $this->cObj->getSubpart($this->templateCode,'###TEASER_NOENTRIES###');
			$entries = $this->cObj->substituteMarker($content,'###NOENTRIES###',$this->pi_getLL('noItemsInCategory'));
			//$content.='<li>'.$this->pi_getLL('noItemsInCategory').'</li></ul></div>';
		}
		// if result -> print teaser box with data
		else {
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				
				// get formatstring for strftime from ts
				$startNoTime = strftime($this->formatStringWithoutTime, $row['startdat']);
				$endNoTime = strftime($this->formatStringWithoutTime, $row['enddat']);
				$startWithTime = strftime($this->formatStringWithTime, $row['startdat']);
				$endWithTime = strftime($this->formatStringWithTime, $row['enddat']);
				
				// shorten title?
				$title = strlen($row['titel'])>$this->conf['teaserlaenge'] ? substr($row['title'],0,$this->conf['teaserlaenge']).'...' : $row['title'];
				
				// startdate and enddate with time
				if ($row['startdat'] && $row['enddat'] && $row['showtime'])
					$timestr = $startWithTime.' - '.$endWithTime;
				// startdate and enddate without time
				else if ($row['startdat'] && $row['enddat']) 			
					$timestr = $startNoTime.' - '.$endNoTime;
				// startdate with time
				else if ($row['startdat'] && $row['showtime'])	
					$timestr = $startWithTime;
				// startdate without time
				else $timestr = $startNoTime;
				
				$linkconf['parameter'] = $singlePid;
				$linkconf['additionalParams'] = '&tx_keyac_pi1[showUid]='.$row['uid'];
				$linkconf['additionalParams'] .= '&tx_keyac_pi1[backPid]='.$GLOBALS['TSFE']->id;
				$linkconf['useCacheHash'] = false;
				$link = $this->cObj->typoLink_URL($linkconf);
				
				$temp_marker = array(
					'location' => $row['location'],
					'datetime' => $timestr,
					'title' => $title,
					'teasertext' => $this->pi_RTEcssText($row['teaser']),
					'link' => $link,
				);
				$temp_content = $this->cObj->getSubpart($this->templateCode,'###TEASER_SINGLE###');
				$temp_content = $this->cObj->substituteMarkerArray($temp_content,$temp_marker,$wrap='###|###',$uppercase=1);
				$entries .= $temp_content;
				
			} // end while
			//$content.='</ul></div>';
		} // end else
		
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
	
	
	
} // end class

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ke_yac/pi1/class.tx_keyac_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ke_yac/pi1/class.tx_keyac_pi1.php']);
}

?>