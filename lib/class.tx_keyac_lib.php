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

class tx_keyac_lib extends tslib_pibase {
	
	public function __construct($pObj = NULL) {
		if ($pObj !== NULL) {
			$this->pObj = $pObj;
			$this->cObj = $pObj->cObj;
		}
	}
	
	
	/*
	* get data for specified month from db and return as array
	*/
	public function getDBData($month,$year) {

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
		$where = ' ( ( startdat >= '.$timestamp_start.' AND startdat <= '.$timestamp_end.' )';
		$where.= ' OR (enddat >= '.$timestamp_start.' AND enddat <= '.$timestamp_end.' )';
		$where.= ' OR ( startdat <= '.$timestamp_start.' AND enddat >= '.$timestamp_end.' ) )';
		$where.= $this->cObj->enableFields('tx_keyac_dates',$show_hidden=0);
		$where.=' and tx_keyac_dates.pid in ('.$this->pids.') ';

		// extend where clause if only one given category is shown
		if ($this->conf['singleCat']) {
			$catEntries = $this->getCategoryEntriesList($this->conf['singleCat']);
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
			if ( ($this->conf['showEventsWithoutCat'])  && !is_array($catRow)) {
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
								if ($datesarray[$i]!="" && strpos($datesarray[$i],"s")) {
									$datesarray[$i] = "999s5";
								} else if ($datesarray[$i]!='') {
									$datesarray[$i]=999;
								} else $datesarray[$i]=$catRow['uid'];
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
	* returns timestamp for start of month
	*/
	public function getStartTimestamp($month,$year) {
		return mktime(0,0,0,$month,1,$year);
	}

	/*
	* returns timestamp for end of month
	*/
	public function getEndTimestamp($month,$year) {
		// get number of days in this month
		$days_month = date("t",$this->getStartTimestamp($month,$year));
		return mktime(23,59,59,$month,$days_month,$year);
	}
	
}

?>
