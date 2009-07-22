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
		
		// starting point
		$pages = $this->cObj->data['pages'] ? $this->cObj->data['pages'] : ( $this->conf['dataPids'] ? $this->conf['dataPids'] : $GLOBALS['TSFE']->id);
		$this->pids = $this->pi_getPidList($pages,$this->cObj->data['recursive']);
		
		// Include HTML Template 
		$this->templateFile = $this->ffdata['templateFile'] ? $this->uploadFolder.$this->ffdata['templateFile'] : $this->conf['templateFile'];
		$this->templateCode = $this->cObj->fileResource($this->templateFile);		
		
		// get Format Strings from FF or TS
		$this->formatStringWithTime = $this->ffdata['strftimeFormatStringWithTime'] ? $this->ffdata['strftimeFormatStringWithTime'] : $this->conf['strftimeFormatStringWithTime'];
		$this->formatStringWithoutTime = $this->ffdata['strftimeFormatStringWithoutTime'] ? $this->ffdata['strftimeFormatStringWithoutTime'] : $this->conf['strftimeFormatStringWithoutTime'];
		$this->formatTime = $this->ffdata['strftimeFormatTime'] ? $this->ffdata['strftimeFormatTime'] : $this->conf['strftimeFormatTime'];
		
		// Duration until fadeout for tooltips
		$this->tooltipDuration = isset($this->ffdata['tooltipDuration']) ? $this->ffdata['tooltipDuration'] : $this->conf['tooltipDuration'];
		
		// get the plugin-mode from flexforms
		$mode_selector = $this->ffdata['mode_selector'];
		// Overwrite Mode if teaser is set in TS
		if ($this->conf['mode'] == 'TEASER') $mode_selector = 1;
		
		// get Content corresponding to mode
		switch($mode_selector) {
			
			// TEASER-VIEW
			case "1": 
				$content.=$this->teaserView();
				break;
				
			// CALENDAR VIEW
			case "0": 
			default: 
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
		
		} 
		return $this->pi_wrapInBaseClass($content);
	}
	
	
	
	function loadJS() {
		
		// Load Javascript Library (Mootools)
		// only if listview is shown
		if ($this->conf['useJS']) {
			$slideJS = t3lib_extMgm::siteRelPath($this->extKey).'pi1/js/slide.js';
			$mootoolsJS = t3lib_extMgm::siteRelPath($this->extKey).'pi1/js/mootools-1.2.js';
			#$mootoolsJS = t3lib_extMgm::siteRelPath($this->extKey).'pi1/js/mootoolsv1.11.js';
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= '<script src="'.$mootoolsJS.'" type="text/javascript"></script>';
			$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] .= '<script src="'.$slideJS.'" type="text/javascript"></script>';
		}
		
	}
	
	
	/* 
	* show calendars and other elements of this view
	*/
	function getCalendarView($month=0,$year=0) {
		
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
		if ($this->ffdata['showMonthsNavigation'] > 0) $monthsNav = $this->getMonthsNavigation($this->ffdata['showMonthsNavigation'], $cur_month, $cur_year);
		else if ($this->conf['showMonthsNavigation'] > 0) $monthsNav = $this->getMonthsNavigation($this->conf['showMonthsNavigation'], $cur_month, $cur_year);
		else $monthsNav = '';
			
		// navigation arrow "back"
		$prev_arrow = $this->getNavArrow('prev', $cur_month, $cur_year);
		
		// Generate calendars starting from given month 
		$calendarsContent = '';
		$i=0;
		// run through number of rows
		for ($row=0; $row < $this->ffdata['rows']; $row++) {
			
			// run through number of cols for every row
			for ($col=0; $col < $this->ffdata['columns']; $col++) {
				
				// which month has to be shown?
				$show_month = ($row * $this->ffdata['columns']) + $col + $cur_month;
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
				if ($i==$this->cals) $this->endtime =  $this->getEndTimestamp($show_month,$show_year);
				
				$i++;
			}
			
			// insert clearer at the end of each row
			$calendarsContent .= $this->cObj->getSubpart($this->templateCode,'###CALENDARS_CLEARER###');
			
		}
		
		// navigation arrow "next"
		$next_arrow = $this->getNavArrow('next', $cur_month, $cur_year);
		
		// show link "hide calendar" if set in FF or TS
		if ($this->ffdata['showHideCalendarLink'] || $this->conf['showHideCalendar']) $hideCalendar = $this->getHideCalendarLink();
		else $hideCalendar = '';
		
		// show legend if set in FF or TS
		if ($this->ffdata['showLegend'] || $this->conf['showLegend']) $legend = $this->legend();
		else $legend = '';
		
		// list events if set in FF or TS
		if ($this->ffdata['showList'] || $this->conf['showList']) $listView = $this->listView();
		else $listView = '';
		
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
		$backImageConf['file'] = $this->ffdata['backImagePath'] ? $this->uploadFolder.$this->ffdata['backImagePath'] : $this->conf['backImagePath'];
		$prevImage=$this->lcObj->IMAGE($backImageConf);
		
		// Next Image
		$nextImageConf['file'] = $this->ffdata['nextImagePath'] ? $this->uploadFolder.$this->ffdata['nextImagePath'] : $this->conf['nextImagePath'];
		$nextImage=$this->lcObj->IMAGE($nextImageConf);
		
		// generate link
		$image = $mode == 'prev' ? $prevImage : $nextImage;
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
		$cals = $this->ffdata['rows'] * $this->ffdata['columns'];
		
		$pre_month = $cur_month - $center;
		$pre_year = $cur_year;
		if ($pre_month < 1) {
			$pre_month +=12;
			$pre_year -= 1;
		}
		
		$post_month = $cur_month + $cals;
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
					$datesarray[$starttag] = "99s0";
				else $datesarray[$starttag] = $row['catuid']."s0";
			}
			// event with end date
			else {
				
				// start and end in different years
				if ($startyear != $endyear) {
								
					// if startmonth
					if ($month == $startmonat && $year==$startyear) {
						for ($i=$starttag;$i<=$days_month;$i++) {
								
							// if there is already another event this day -> set type to "99"
							// if "s" is already set -> maintain
							if ($datesarray[$i]!='' && strpos($datesarray[$i],"s")) 
								$datesarray[$i]="99s2";
							else if ($datesarray[$i]!='') $datesarray[$i]=99;
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
							// if there is already another event this day -> set type to "99"
							if ($datesarray[$i]!='') $datesarray[$i]=99;
							else $datesarray[$i]=$row['catuid'];
						}	
					}
					// if month between startmonth and endmonth
					else {
						for ($i=1; $i<=$days_month; $i++) {
							if ($datesarray[$i]!='') $datesarray[$i]=99;
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
							// if there is already another event this day -> set type to "99"
							if ($datesarray[$starttag]!='') 
								$datesarray[$starttag]="99s1";
							// if there is no other event this day -> set type to category
							else $datesarray[$starttag]=$row['catuid']."s1";
						} 
						// several days
						else {
							for ($i=$starttag;$i<=$endtag;$i++) {
								
								// if there is already another event this day -> set type to "99"
								// if "s" is already set -> maintain
								if ($datesarray[$i]!='' && strpos($datesarray[$i],"s")) 
									$datesarray[$i]="99s2";
								else if ($datesarray[$i]!='') $datesarray[$i]=99;
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
								// if there is already another event this day -> set type to "99"
								if ($datesarray[$i]!="" && strpos($datesarray[$i],"s"))
									$datesarray[$i]="99s3";
								else if ($datesarray[$i]!='') 
									$datesarray[$i]=99;
								else $datesarray[$i]=$row['catuid'];
								// mark start day with "s"
								if ($i==$starttag && !strpos($datesarray[$i],"s")) 
									$datesarray[$i].="s3";
							}	
						}
						// end of date in current month
						if ($month == $endmonat) {
							for ($i=$endtag;$i>0;$i--) {
								// if there is already another event this day -> set type to "99"
								if ($datesarray[$i]!='') $datesarray[$i]=99;
								else $datesarray[$i]=$row['catuid'];
							}	
						}
						
						// duration of event over 1 month
						// all days with event
						if ($month > $startmonat && $month < $endmonat) {
							for ($i=1; $i<=$days_month; $i++) {
								if ($datesarray[$i]!='') $datesarray[$i]=99;
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
								<div id="'.$anchorlink.'_layer" class="yac-tooltip" style="display:none;">'.$this->listView($day_format, $month_format, $year).'</div>
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
	function listView($day=0,$month=0,$year=0) {
		
		$lcObj=t3lib_div::makeInstance('tslib_cObj');
	
		//init category items
		$table = 'tx_keyac_cat';
		$where = '1=1'.$lcObj->enableFields($table,$show_hidden=0);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$table,$where,$groupBy='',$orderBy='',$limit='');
		while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->conf['catImage.']['file'] = $this->uploadFolder.$row['image'];
			$catImages[$row['uid']] = $lcObj->IMAGE($this->conf['catImage.']);
		}
		
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
			
			#$content.='	<div class="termine-item">';
			
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
				if (!$row['enddat'])	$datstring = $beginn_datum.', '.$beginn_uhrzeit;
				// begin and end on the same day
				else if ($ende_datum==$beginn_datum ) $datstring = $beginn_datum.' '.$beginn_uhrzeit.' '.$this->pi_getLL('until').' '.$ende_uhrzeit;
				// begin and end at different days
				else $datstring = $beginn_datum .', '.$beginn_uhrzeit.' '.$this->pi_getLL('until').' '.$ende_datum.', '.$ende_uhrzeit;
			}
			$overrulePIvars = array('showUid' => $row['dateuid']);
			$date = $this->pi_linkTP_keepPIvars($datstring, $overrulePIvars,$cache=1,$clearAnyway=0);
			
			if ($day!=0 && $month!=0 && $year!=0) $anchor = '';
			else $anchor = '<a name="'.$start.'"></a>';
			
			$markerArray = array(
				'title' => $row['datetitle'],
				'date' => $date,
				'anchor' => $anchor,
				'catimage' => $catImages[$row['catuid']],
			);
			$temp_content = $this->cObj->getSubpart($this->templateCode,'###LISTVIEW_SINGLE###');
			$temp_content = $this->cObj->substituteMarkerArray($temp_content,$markerArray,$wrap='###|###',$uppercase=1);
			$content .= $temp_content;
		}
		return $content;
	}

	
	
	
	/**
	 * single view of event
	 */
	function singleView($id)	{
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
						'ende zeit' => $ende_uhrzeit );
			
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
				if (!$row['enddat'])	$datstring = $beginn_datum.', '.$beginn_uhrzeit.' Uhr<br />';
				// begin and end on the same day
				else if ($ende_datum==$beginn_datum ) $datstring = $beginn_datum.'<br />'.$beginn_uhrzeit.' '.$this->pi_getLL('until').' '.$ende_uhrzeit.'<br />';
				// begin and end at different days
				else $datstring = $beginn_datum .', '.$beginn_uhrzeit.'<br /> '.$this->pi_getLL('until').' '.$ende_datum.', '.$ende_uhrzeit;
			}
			$content.='	<h1>'.$row['datetitle'].'</h1>
						<div class="category-title">'.$row['cattitle'].'</div>
						<div class="content-left"><b>'.$this->pi_getLL('event').'</b></div>
						<div class="content-right">'.$datstring.'</div><hr class="clearer">
						<div class="content-left"><b>'.$this->pi_getLL('place').'</b></div>
						<div class="content-right">'.$row['place'].'</div><hr class="clearer">
						<div class="content-left"><b>'.$this->pi_getLL('description').'</b></div>
						<div class="content-right">'.$this->pi_RTEcssText($row['bodytext']).'</div><hr class="clearer">';
			// if infolink is set
			if ($row['infolink']) {
				// if linktext is set: take it; otherwise take url as linktext
				$text = $row['infolink_text'] ? $row['infolink_text'] : $row['infolink'];
				$content.='	<div class="content-left"><b>'.$this->pi_getLL('infolink').'</b></div>
							<div class="content-right">'.$this->pi_linkToPage($text,$row['infolink'],$target='_blank',$urlParameters=array()).'</div><hr class="clearer">';
			}
		}
		
		// back link
		if ($this->piVars['backPid']) 
			$zLink = $this->pi_linkToPage($this->pi_getLL('back'),$this->piVars['backPid'],$target='',$urlParameters=array());
		else	$zLink = $this->pi_linkTP_keepPIvars($this->pi_getLL('back'),$pivars,$cache=1,$clearAnyway=0);
		$content.= '	<br />
					<div class="content-left">&nbsp;</div>
					<div class="content-right">'.$zLink.'</div>';
		return $content;
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
			
			$this->conf['catimage.']['file'] = 'uploads/tx_keyac/'.$row['image'];
			$catimage=$this->cObj->IMAGE($this->conf['catimage.']);
						
			$markerArray = array(
				'catimage' => $catimage,
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
		
		// get header text, pid of singleView and no. of entries in teaser; either from flexforms or from ts
		$teaserheader = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'teaserHeader', 'TEASER') ? $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'teaserHeader', 'TEASER') : $this->conf['teaserHeader'];
		$singlePid = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'singlePid', 'TEASER') ? $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'singlePid', 'TEASER') :$this->conf['singlePid'];
		$limit = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'teaserLimit', 'TEASER') ? $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'teaserLimit', 'TEASER') : $this->conf['teaserLimit'];
		$teaserlength = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'teaserLength', 'TEASER') ? $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'teaserLength', 'TEASER') : $this->conf['teaserLength'];
		
		// begin output pf teaser-box
		//$content.='<div class="cal-teaser">
		//			<div class="header">'.$teaserheader.'</div><ul>';
		
		
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
					'place' => $row['place'],
					'datetime' => $timestr,
					'title' => $title,
					'link' => $link,
				);
				$temp_content = $this->cObj->getSubpart($this->templateCode,'###TEASER_SINGLE###');
				$temp_content = $this->cObj->substituteMarkerArray($temp_content,$temp_marker,$wrap='###|###',$uppercase=1);
				$entries .= $temp_content;
				
			} // end while
			//$content.='</ul></div>';
		} // end else
		
		$markerArray = array(
			'title' => $teaserheader,
			'teaser_entries' => $entries,
		);
		$content = $this->cObj->getSubpart($this->templateCode,'###TEASER_TEMPLATE###');
		$content = $this->cObj->substituteMarkerArray($content,$markerArray,$wrap='###|###',$uppercase=1);
		
		
		
		return $content;
	} 
	
	
	
	
} // end class

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ke_yac/pi1/class.tx_keyac_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ke_yac/pi1/class.tx_keyac_pi1.php']);
}

?>