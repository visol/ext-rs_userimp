<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Rainer Sudhoelter <r.sudhoelter (at) web.de>
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

/**
 * Main class for the 'rs_userimp' extension.
 * TODO: bad word blocker
 *
 * @author	Rainer Sudhoelter <r.sudhoelter (at) web.de>
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   92: class tx_rsuserimp
 *
 *              SECTION: Section 1
 *  137:     function tx_rsuserimp()
 *  158:     function init()
 *  172:     function setUserTypeDefaultData()
 *
 *              SECTION: Section 2
 *  300:     function readCSV()
 *  323:     function readSamplesFromCSV()
 *  351:     function getColumnNamesFromCSV()
 *  373:     function getColumnNamesFromDB()
 *  394:     function removeNoMapFields($dbFields)
 *  411:     function createImportForm()
 *  428:     function createMappingForm ()
 *  477:     function evaluateMappingForm ()
 *  556:     function createSelector($x)
 *  598:     function fieldSelector($n)
 *
 *              SECTION: Section 3
 *  629:     function importUsers ()
 *  864:     function generateCustomValue($main,$config,$row)
 *  911:     function checkUserDataFE (&$user,&$importUser)
 * 1026:     function checkUserDataTT (&$user,$importUser)
 * 1040:     function checkUserDataBE (&$user,$importUser)
 * 1058:     function _fputcsv($fileName, $dataArray, $delimiter, $enclosure)
 *
 * TOTAL FUNCTIONS: 19
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * This class provides CSV to TYPO3 DB user import features. It lets you map CSV to
 * corresponding user-specific DB fields and was inspired by the need to mass-import >> 1000 FE users.
 * Mapping features have successfully applied on Microsoft (c) Outlook Express WAB and Outlook address books,
 * CSV exports of Microsoft (c) Excel sheets and general text export files from 3COM (c) Palm Desktop and Lotus Notes (c).
 * The class is to be called after all necessary pre-mapping info has been provided.
 * This means we already have a CSV file in place and basic import setting configuration.
 *
 * USAGE:
 * The class is intended to be used by creating an instance of it.
 *
 * @author	Rainer Sudhoelter
 * @package rs_userimp
 */

 require_once(PATH_t3lib.'class.t3lib_div.php');

/**
 * Class tx_userimp: a generic user importer based on formated text files.
 *
 * @return	string	HTML content
 */
class tx_rsuserimp {

	var $previewNum;
	var $CSVhasTitle;
	var $fieldDelimiter;
	var $fieldEncaps;
	var $fieldmap;
	var $mandatoryFields = array();
	var $additionalMandatoryFields;
	var $enableAutoValues;
	var $enableAutoRename;
	var $enableUpdate;
	var $defaultUserData;
	var $file;
	var $CSV = array();
	var $num;
	var $importOK;
	var $importNow;
	var $map;
	var $columnNamesFromCSV;
	var $columnNamesFromDB;
	var $numMap;
	var $inData = array();
	var $noMap = array();
	var $userType;
	var $userTypeDB;
	var $userTypeDBTable;
	var $useRecycler;
	var $createDropFile;
	var $uniqueUserIdentifier;

	/*************************
	 *
	 * Section 1
	 *
	 * Explanation:
	 * Initialization of class.
	 *
	 *************************/

	/**
	 * Constructor: set default values.
	 *
	 * @return	void		initialize the class with default values
	 */
	function tx_rsuserimp() {

		$this->userType = 'FE';
		$this->uniqueUserIdentifier = 'username';
		$this->enableAutoRename = FALSE;
		$this->enableAutoValues = FALSE;
		$this->enableUpdate = FALSE;
		$this->bg1 = '#FFEFBF';
		$this->bg2 = '#FFE79F';
		$this->useRecycler = 1;	// 0 = no, 1 = if available, 2 = always
		$this->previewNum = 3;
		$this->CSVhasTitle = TRUE;
		$this->importNow = t3lib_div::_POST('importNow') ? 'TRUE' : 'FALSE';
	} //end  constructor

	/**
	 * Get basic mapping data needed for dropdown menus.
	 * Read in sample CSV data to display during mapping session.
	 *
	 * @return	void
	 */
	function init() {

		$this->setUserTypeDefaultData();
		$this->CSV = $this->readSamplesFromCSV();
		$this->columnNumCSV = count($this->CSV[0]);
		$this->columnNamesFromCSV = $this->getColumnNamesFromCSV();
		$this->columnNamesFromDB = $this->getColumnNamesFromDB();
	}

	/**
	 * Sets default data for the selected user type.
	 * Default data depend on the user type to be imported: FE and tt_address vary and must be handled accordingly here.
	 *
	 * @return	void
	 */
	function setUserTypeDefaultData() {

		global $TCA;

		$now = mktime();

		switch((string)$this->userType)	{

			case 'TT':
				/**
				* These fields are removed from the tt_address mapping process.
				*/
				$this->noMap = array(
					'pid',
					'hidden',
					'image'
					);

				$this->mandatoryFields = array(
					);

				$this->defaultUserData = array(
					'pid' => $this->defaultUserData['pid'],
					'tstamp' => $now
					);

				$this->userTypeDB = $TCA['tt_address']['feInterface']['fe_admin_fieldList'];
				$this->userTypeDBTable = 'tt_address';

				$this->uniqueUserIdentifier = 'email';
			break;

			case 'FE':
				/**
				 * These fields are removed from the FE mapping process since they are
				 * either computed or are set once the user logs in for the first time ...
				 */
				$this->noMap = array(
						'crdate',
						'cruser_id',
						'deleted',
						'disable',
						'endtime',
						'fe_cruser_id',
						'image',
						'is_online',
						'lastlogin',
						'lockToDomain',
						'pid',
						'starttime',
						'TSconfig',
						'tstamp',
						'uc',
						'uid',
						'usergroup'
						);

				$this->mandatoryFields = array(
					'username',
					'password'
					);

				$this->defaultUserData = array(
					'usergroup' => $this->defaultUserData['usergroup'],
					'pid' 		=> $this->defaultUserData['pid'],
					'tstamp' 	=> $now,
					'crdate' 	=> $now
					);

				$this->userTypeDB = $TCA['fe_users']['feInterface']['fe_admin_fieldList'];
				$this->userTypeDBTable = 'fe_users';
				
				$this->uniqueUserIdentifier = 'username';				
			break;

			case 'BE':
				/**
				* In preparation for BEuserImp ...
				* These fields are removed from the mapping process since they are either computed
				* or are set once the user logs in for the first time ...
				*/
				$this->noMap = array(
					'admin',
					'allowed_languages',
					'createdByAction',
					'db_mountpoints',
					'disable',
					'disableIPlock',
					'endtime',
					'file_mountpoints',
					'fileoper_perms',
					'lang',
					'lockToDomain',
					'options',
					'starttime',
					'TSconfig',
					'userMods'
					);

				$this->mandatoryFields = array(
					'username',
					'password',
					'usergroup');

				$this->defaultUserData = array(
					'usergroup' => '',
					'pid' => '',
					'tstamp' => $now,
					'crdate' => $now
					);

				$this->userTypeDB = $TCA['be_users'];
				$this->userTypeDBTable = 'be_users';
			break;
		}
		return;
	}

	/*************************
	 *
	 * Section 2
	 *
	 * Explanation:
	 * File functions: reading CSV files (partly and as a whole)
	 *
	 *************************/

	/**
	 * Read in the given CSV file. The function is used during the final file import.
	 * Removes first the first data row if the CSV has fieldnames.
	 *
	 * @return	array		file content in array
	 */
	function readCSV() {

		$file = $this->file;
		$mydata = array();
		$handle = fopen($file, "r");
		$i=0;
		$delimiter = ($this->fieldDelimiter === 'TAB') ? chr(9) : $this->fieldDelimiter;
		while (($data = fgetcsv($handle, 10000, $delimiter, $this->fieldEncaps)) !== FALSE) {
			$mydata[] = $data;
		}
		fclose ($handle);
		reset ($mydata);
		if ($this->CSVhasTitle) {
			$mydata = array_slice($mydata,1); //delete first row
		}
		return $mydata;
	}

	/**
	 * Read in sample data from CSV file. Needed for the mapping session to display some samples.
	 * This function could actually be merged with function readCSV =:o)
	 *
	 * @return	array		sample file content in array	...
	 */
	function readSamplesFromCSV() {

		$file = $this->file;
		$mydata = array();
		$handle = fopen($file, "r");
		$i=0;
		$delimiter = ($this->fieldDelimiter == 'TAB') ? chr(9): $this->fieldDelimiter ;
		$fieldEncaps = empty($this->fieldEncaps) ? '\'' : $this->fieldEncaps;
		while (($data = fgetcsv($handle, 10000, $delimiter, $fieldEncaps)) !== FALSE) {
			$mydata[] = $data;
			if ($i == $this->previewNum) {
				break;
			} else {
				$i++;
			}
		}
		fclose ($handle);
		reset ($mydata);
		return $mydata;
	}

	/**
	 * Reads the fieldnames from the CSV file. If there are no fieldnames in the first row, 
	 * create fieldnames of the form Field_[x].
	 * The fieldnames are needed during the mapping session to support the user some in his mapping task.
	 * Also cuts off the first array item if is has field titles.
	 *
	 * @return	array	$myheader: array with fieldnames
	 */
	function getColumnNamesFromCSV() {

		global $LANG;
		$num = count($this->CSV[0]);

		if ($this->CSVhasTitle) {
			$myheader = $this->CSV[0];
			$this->CSV = array_slice($this->CSV, 1); //delete first row
		} else {
			for ($n = 0; $n < $this->columnNumCSV; $n++) {
				$myheader[$this->CSV[0][$n]] = $LANG->getLL('f1.tab3.mapper.fieldset2.field').sprintf("[%02s]",$n);
			}
		}
		return $myheader;
	}

	/**
	 * Reads DB columns (fieldnames) from the DB using $TCA.
	 * Also, disallowed mapping fields are removed by calling removeNoMapFields(&$fields).
	 * The fieldnames are needed during the mapping session to support the user some in his mapping task.
	 *
	 * @return	array	$dbfields: array with DB fieldnames
	 */
	function getColumnNamesFromDB() {

		global $TCA;

		/**
		 * This was the initial way of getting available DB table fields for table fe_users:
		 * $dbFields = array_keys($TCA['fe_users']['columns']);
		 * Didn't work on 3.8.0rc1 systems, so it had to be changed to the following
		 */
		$dbFields = explode(',',$this->userTypeDB);
		$this->removeNoMapFields($dbFields); //passed by REFERENCE

		return $dbFields;
	}

	/**
	 * Delete/unset disallowed mapping fields from passed fieldnames.
	 * Not all available DB fields should be allowed for mapping. Some values are automatically computed, 
	 * others are set during first login and others are IMHO simply too dangerous to be set during an user import.
	 *
	 * @param	array		$dbFields passed by REFERENCE
	 * @return	void
	 */
	function removeNoMapFields(&$dbFields) {

		$i = 0;
		foreach ($dbFields as $key => $value) {
			if (in_array($value, $this->noMap)) {
				unset ($dbFields[$i]);
			}
			$i++;
		}
		$dbFields = array_values($dbFields);
	}

	/*************************
	 *
	 * Section 3
	 *
	 * Explanation:
	 * HTML helper functions: displays mapping forms and elements
	 *
	 *************************/


	/**
	 * Displays the import button for the import form.
	 *
	 * @return	string		HTML content
	 */
	function createImportForm() {

		global $LANG;

		$content = $LANG->getLL('f1.tab4.section.import.label').'
				<div align="right">
					<input type="submit" name="importNow" value="'.$LANG->getLL('f1.tab4.section.import.import',1).'" '.(t3lib_div::_POST('importNow') ? 'disabled' : '').' onclick="return confirm(\''.$LANG->getLL('f1.tab4.section.import.sure',1).'\');">
				</div>';
		return $content;
	}


	/**
	 * Display the mapping form.
	 *
	 * @return	string		HTML content
	 */
	function createMappingForm () {

		global $LANG;

		$content = '';
		$content .= '<fieldset>';
		$content .= '<legend align=left><b>'.$LANG->getLL('f1.tab3.mapper.fieldset1').'</b></legend>';

		if ($this->inData['fieldname']) {
			$map = array();
			$content .= $this->evaluateMappingForm();
		}

		if (!$this->inData['import']) {
			$content .= '<div align="right"><input type="submit" name="map" value="'.$LANG->getLL('f1.tab3.mapper.import').'" '.(t3lib_div::_POST('importNow') ? 'disabled' : '').'></div>';
			$content .= '<table style="font-size : 8pt;" width="100%" border=0 callpading=0 cellspacing=0>';
			$i = 0;

			$content .= '<tr style="background-color:#FFE79F;">
								<td><b>#</b></td>
								<td><b>'.$LANG->getLL('f1.tab3.mapper.description').'</b></td>'.
								(($this->enableAutoValues) ? '<td><b>'.$LANG->getLL('f1.tab3.mapper.auto').'</b></td>' : '').
								'<td><b>'.$LANG->getLL('f1.tab3.mapper.mapping').'</b></td>
								<td><b>'.$LANG->getLL('f1.tab3.mapper.values').'</b></td>
							</tr>';

			foreach ($this->columnNamesFromCSV as $key) {
				$colorcount = ($colorcount == 1) ? 0: 1;
				$color = ($colorcount == 1) ? $bg = $this->bg1 : $bg = $this->bg2;
				$content .= '<tr style="background-color:'.$bg.'; border-top : 1px dotted #a3a3a3;">'.
								'<td>'.$i.'</td>'.
								'<td>'.$key.'</td>';
				$content .= (($this->enableAutoValues) ? '<td><input '.(t3lib_div::_POST('importNow') ? 'disabled' : '').' onclick="toggleOptions('.$i.')" type="checkbox" name="tx_rsuserimp[autoval]['.$i.']" '.( ($this->inData[autoval][$i] == 'on') ? ' checked ':'').'/></td>' : '');
				$content .= $this->createSelector ($i);
				$content .= '</tr>';
				$i++;
			}

			$content .= '</table><div align="right"><input type="submit" name="map" value="'.$LANG->getLL('f1.tab3.mapper.import').'" '.(t3lib_div::_POST('importNow') ? 'disabled' : '').'></div>';
			$content .= '</fieldset>';
		}
		return $content;
	}

	/**
	 * Check if all necessary mapping information is provided and if we may proceed with 
	 * the import workflow.
	 *
	 * @return	string		HTML content
	 */
	function evaluateMappingForm () {

		global $LANG;

		// merge mandatory and userdefined mandatory mapping fields
		if (!empty($this->additionalMandatoryFields)) {
			$this->mandatoryFields = array_unique(array_merge($this->mandatoryFields,$this->additionalMandatoryFields));
		}
		$this->importOK = FALSE;
		$mandatoryFieldError = array();

		$m = count($this->inData['fieldmap']);
		//delete empty values
		$n = array_diff($this->inData['fieldmap'], array(''));
		$org = $n;
		$x = count($n);
		// delete duplicate values
		$n = array_unique($n);

		$duplicateMapping = array_diff_assoc($org,$n);

		if (!empty($duplicateMapping)) {
			$msg = ' <b>'.implode(', ',$duplicateMapping).'</b>';
		}

		$y = count($n);
		$content = '';

		// check if we have already a mapping
		if ($y != 0) {
			// do we have mapping entries ???
			if (!$this->inData['import']) {
				$content .= sprintf($LANG->getLL('f1.tab3.mapper.message.info'),$x,$m,$y);
			}

			// do we have multiple mappings for a distinct field?
			if ($x != $y) {
				$content .= ' - '.$LANG->getLL('f1.tab3.mapper.message.error').$msg.'<br>';
			} else {
				// mapping seems to be OK, continue with import button or map additional values
				if (!$this->inData['import']) {
					foreach ($this->mandatoryFields as $mandatoryField) {
						if (!in_array($mandatoryField, $this->inData['fieldmap'])) {
							$mandatoryFieldError[] = $mandatoryField;
						}
					}
					if (!empty($mandatoryFieldError)) {
						$this->importOK = FALSE;
						$content .= $LANG->getLL('f1.tab3.mapper.message.provideMandatory').' <b>'.implode(', ',$mandatoryFieldError).'</b>';
					} else {
						$this->importOK = TRUE;
						$content .=	$LANG->getLL('f1.tab3.mapper.message.allMandatoryYes');
					}
				}

				//prepare POST data for next pageclick
				$i = 0;
				foreach ($this->CSV as $row) {
					$m = 0;
					foreach ($this->inData['fieldmap'] as $val) {
						if ($val != '') {
							$this->map[$i][$val] = $row[$m];
							$this->numMap[$val] = $m;
						}
						$m++;
					}
					$i++;
				}
			}
		}
		return $content;
	}

	/**
	 * Creates the HTML mapping form which we need to map CSV fields to DB fields. 
	 * Provides data examples read from CSV to support the mapping process.
	 *
	 * @param	integer		number of column for which to create a fieldmap
	 * @return	string		returns a HTML TD element
	 */
	function createSelector($x) {

		global $LANG;

		$content = '';

		if (empty($this->columnNamesFromCSV[$x])) {
 			$header[$x] = $LANG->getLL('f1.tab3.mapper.field') . $x;
		} else {
			$header[$x] = $this->columnNamesFromCSV[$x];
		}

		$content .= '<td>';
		$content .= '<input type="hidden" name="tx_rsuserimp[fieldname]['.$x.']" value="'.$header[$x].'" title="'.$header[$x].'">';
		$content .= $this->fieldSelector($x);
		$content .= '</td>';
		// print CSV values in a row
		$content .= '<td>';
		if (isset($this->enableAutoValues)) {
			$content .= '<div id="rsdivon_'.$x.'" style="display: block">';
		}
		for ($n=0; $n <= ($this->previewNum-1); $n++) {
			$row = $this->CSV[$n];
			$colorcount = ($colorcount == 1) ? 0: 1;
			$color = ($colorcount == 1) ? $bg=$this->bg1 : $bg=$this->bg2;
			$content .= '<div style="background-color:'.$bg.'">'.($this->CSV[$n][$x] ? $this->CSV[$n][$x].'</div>' : '&nbsp;</div>');
		}
		if (isset($this->enableAutoValues)) {
			$content .= '</div>';
			$content .= '<div style="display: none" id="rsdivoff_'.$x.'">';
			$content .= '<input name="tx_rsuserimp[customValue]['.$x.']" type="text"'.(t3lib_div::_POST('importNow') ? 'disabled' : '').' value="'.$this->inData[customValue][$x].'" /></div>';
		}
		$content .= '</td>';
		return $content;
	}

	/**
	 * Creates a HTML select box for the mapping process.
	 *
	 * @param	integer		number of column for which to create a HTML selct box element ...
	 * @return	string		HTML SELECT element
	 */
	function fieldSelector($n) {

		global $LANG;

		$box = '<select style="display: block" name="tx_rsuserimp[fieldmap]['.$n.']" '.' size="1" '.(t3lib_div::_POST('importNow') ? 'disabled' : '').'>'."\n";
		$box .= '<option value="">'.$LANG->getLL('f1.tab3.mapper.mapsTo').'</option>'."\n";
		foreach ($this->columnNamesFromDB as $key => $value) {
			$box.='<option value="' . $value. '"';
			if ($this->inData['fieldmap'][$n] == $value) {
				$box .= ' SELECTED ';
			}
			$box .= '>' . $value. '</option>'."\n";
		}
		$box.='</select>'."\n";
		return $box;
	}

	/*************************
	 *
	 * Section 4
	 *
	 * Explanation:
	 * The final DB import process.
	 *
	 *************************/

	/**
	 * Import users from CSV after all necessary mapping info has been provided.
	 *
	 * @return	string		formated HTML to be printed
	 */
	function importUsers () {

		global $BE_USER, $LANG, $FILEMOUNTS;

		// we need the UIDs of existing users later if we want to update users
		// unfortunately, sql_insert_id() doesn't work for SQL UPDATE statements 
		// even though the MySQL reference handbook tells you so...
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($this->uniqueUserIdentifier.',uid',$this->userTypeDBTable,'pid='.$this->defaultUserData['pid']);

		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
			$users[] = $row[0];
			$userIDS[] = $row[1];
		}

		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		// read the import file
		$CSV = $this->readCSV();

		/**
		 * Hook added on request of Franz Ripfel: 
		 * hook for pre-processing of read-in data. At this time of the import session, we have read in 
		 * the CSV as an array and pass it and the object itself to the external function as a REFERENCE.
		 * Your external function may manipulate the array as it likes (fx. delete, alter datasets).
		 * See function readCSV() for import details.
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rs_userimp']['beforeImportHook'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rs_userimp']['beforeImportHook'] as $_classRef) {
				$_procObj = & t3lib_div::getUserObj($_classRef);
				$CSV = $_procObj->manipulateData($CSV, $this);
			}
		}


		// line counter
		$n=0;
		// $main[] holds all read CSV values
		$main = array();
		foreach ($CSV as $row) {
			// we are not working with numerical indexes,
			// so we need the keys for CSV values
			foreach ($this->numMap as $key => $value) {
				$sub[$key] = $row[$value];
			}
			// merge in defaultUserData[]
			$main[$n] = array_merge($sub, $this->defaultUserData);
			// substitute customValues
			if($this->enableAutoValues && is_array($this->inData['autoval'])) {
				// to save time, only defined values get computed
				foreach ($this->inData['autoval'] as $key2=>$val2) {
					// we need a second array for to store generated custom values
					// because it might happen that a user maps a field to a new value
					// and later wants to map the original field again (would have been overwritten by then).
					// the original value is preserved that way and may
					// be used throughout the whole mapping process.
					$sub2[$n][$this->inData['fieldmap'][$key2]] = $this->generateCustomValue($main[$n][$this->inData['fieldmap'][$key2]],$this->inData['customValue'][$key2],$row);
				}
				// merge the 2 arrays, $sub2 takes precedence
				$main[$n] = array_merge($main[$n], $sub2[$n]);
			}
			unset($sub);
			$n++;
		}
		// some counters
		$i = 0; // total users imported
		$j = 0; // total users skipped
		$m = 0; // total users read from CSV
		$u = 0; // total users updated


		// process users one by one
		foreach ($main as $v1) {
			$msg = array();
			$importUser = TRUE;
			$updateUser = FALSE;

			// check the imported user data - values are manipulated
			// and are passed by REFERENCE
			switch((string)$this->userType) {
				case 'FE':
					$msg[] = $this->checkUserDataFE($v1,$importUser);
				break;

				case 'TT':
					$msg[] = $this->checkUserDataTT($v1,$importUser);
				break;

				case 'BE':
					$msg[] = $this->checkUserDataBE($v1,$importUser);
				break;

				default:
					die($LANG->getLL('f1.tab5.error.unknownFatalError'));
			}

			// the user array has now only valid values
			// handle the user array: check if we have users, rename them if necessary,
			// skip import if necessary.
			// if we have already users in the DB...
			if (!empty($users) && $importUser) {
				// the username already exists
				while (in_array($v1[$this->uniqueUserIdentifier], $users)) {
					if (!$this->enableAutoRename && !$this->enableUpdate) {
						$msg = array();
						$msg[] = $LANG->getLL('f1.tab5.error.duplicateUserName');
						$importUser = FALSE;
						// jump out of while
						break 1;
					} elseif ($this->enableUpdate) {
						//$msg = array();
						//$msg[] = ;//$LANG->getLL('f1.tab5.error.duplicateUserName');
						$updateUser = TRUE;
						break 1;
					} elseif ($this->enableAutoRename) {
						// rename current import users
						$newName = $v1[$this->uniqueUserIdentifier].'0';
//						$msg[] = $LANG->getLL('f1.tab5.error.renamed');
						// check for max value
						if ( strlen($newName) > 39 ) {
							$msg = array();
							$msg[] = $LANG->getLL('f1.tab5.error.userNameTooLong');
							$importUser = FALSE;
							// jump out of while
							break 1;
						} else {
							$v1[$this->uniqueUserIdentifier] = $newName;
							$main[$i][$this->uniqueUserIdentifier] = $v1[$this->uniqueUserIdentifier];
						}
					}
				}
			}

			$uid = $userIDS[$m];

			if ($importUser) {
				if ($updateUser) {
					//updated existing user
					// SQL injection prevention
					foreach ($v1 as $key=>$val) {
						$v1[$key] = $GLOBALS['TYPO3_DB']->quoteStr($val,$this->userTypeDBTable);
					}

					$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->userTypeDBTable,($this->uniqueUserIdentifier."='".$main[$m][$this->uniqueUserIdentifier]."'"),$v1);
					// set uid
					$BE_USER->writelog(1,1,0,'','User %s [UID %s] updated by CSV import action',array($v1['username'],$uid,));
					$content .= '<b><font color="#76CF67">'.sprintf($LANG->getLL('f1.tab5.userUpdated'),$v1[$this->uniqueUserIdentifier],$userIDS[$m]).'</font></b> '.(!empty($msg) ? implode(',',$msg) : '').'<br />';
					$u++;
					// BEWARE: updated users are  added to the rollback dataset!!!
					$rollbackDataTemp[] = $uid;
				} else {
					// insert new user
					// SQL injection prevention
					foreach ($v1 as $key=>$val) {
						$v1[$key] = $GLOBALS['TYPO3_DB']->quoteStr($val,$this->userTypeDBTable);
					}
					$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->userTypeDBTable,$v1);
					// get uid
					$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();
					$BE_USER->writelog(1,1,0,'','User %s [UID %s] created by CSV import action',array($v1[$this->uniqueUserIdentifier],$uid,));
					$users[] = $v1[$this->uniqueUserIdentifier];
					$i++;
					$content .= '<b><font color="#76CF67">'.sprintf($LANG->getLL('f1.tab5.userInserted'),$v1[$this->uniqueUserIdentifier],$v1['name'],$uid).'</font></b> '.(!empty($msg) ? implode(',',$msg) : '').'<br />';
					$rollbackDataTemp[] = $uid;
				}
			} else {
				$export[] = $CSV[$m];
				$content .= '<b><font color="red">'.sprintf($LANG->getLL('f1.tab5.userSkipped'),$v1[$this->uniqueUserIdentifier],$uid).'</font></b>'.(!empty($msg) ? implode(',',$msg) : '').'<br />';
				$j++;
			}
		$m++;
		} //end foreach

		$content = '<b>'.sprintf($LANG->getLL('f1.tab5.usersImported'),$m,$i,$u,$j).'</b><br>'.$content;

		/**
		 *	If createDropFile is set, create a drop file which holds all skipped users.
		 * The CSV format is determined by the import settings, so this file may be edited and re-imported
		 * by simply using the previous preset (mapping).
		 */
		if ($this->createDropFile && is_array($export)) {
			$fileInfo = t3lib_basicFileFunctions::getTotalFileInfo($this->file);

			$newFileName = 'DROPPED_'.$fileInfo['file'];

			$fileContent = array();
			$fileContent[0][] = $this->columnNamesFromCSV;
			$fileContent = array_merge($fileContent[0],$export);

			$newAbsFile = t3lib_div::getFileAbsFileName('uploads/tx_rsuserimp/' . 'DROPPED_'.$fileInfo['file']); //PATH_site.'typo3temp/'.$newFileName;
			$newRelFile = '/uploads/tx_rsuserimp/'.$newFileName;

			_fputcsv($newAbsFile, $fileContent, $this->fieldDelimiter, $this->fieldEncaps);

			$content .= '<div align="center"><a href="'.$newRelFile.'">'.$LANG->getLL('f1.tab5.downloadFile').'</a>';
		}

		if (!empty($rollbackDataTemp)) {
			$fileInfo = t3lib_basicFileFunctions::getTotalFileInfo($this->file);
			$file = $fileInfo['file'];

			$rollbackDataSets = implode(",", $rollbackDataTemp);
			$rollbackData = array (
				'crdate' => time(),
				'target_pid' => $this->defaultUserData['pid'],
				'user_uid' => $BE_USER->user['uid'],
				'title' => 'Import session of user '.$BE_USER->user['username'].' [UID '.$BE_USER->user['uid'].']: '.$i.' users imported to PID '.$this->defaultUserData['pid'],
				'usertype' => $this->userType,
				'db_table' => $this->userTypeDBTable,
				'unique_identifier' => $this->uniqueUserIdentifier,
				'num_imp' => $i,
				'num_drop' => $j,
				'num_upd' => $u,
				'file' => $file,
				'dropfile' => $newAbsFile,
				'active' => '1',
				'deleted' => '0',
				'session_data' => $rollbackDataSets
			);
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_rsuserimp_sessions',$rollbackData); //insert
		}

		/**
		 * After parsing the CSV file, the file gets deleted by either moving it to the recycler (if
		 * present) or by deleting it.
		 */
		$FILE = array();

		$FILE['delete'][] = array('data'=>$this->file);

		$fileProcessor = t3lib_div::makeInstance('t3lib_extFileFunctions');
		$fileProcessor->init($FILEMOUNTS, $TYPO3_CONF_VARS['BE']['fileExtensions']);
		$fileProcessor->init_actionPerms($BE_USER->user['fileoper_perms']);
		// use recycler: 0 = no, 1 = if available, 2 = always
		$fileProcessor->useRecycler = $this->useRecycler;

		$fileProcessor->start($FILE);
		$fileProcessor->processData();

		return $content;
	}

	/**
	 * This function parses the the custom mapping field for allowed susbtitution patterns.
	 * The regex used here finds patterns of the form {f(p)} where "f" is a substitution function
	 * and "p" is the parameter passed to "f". Supports fixed strings (s), CSV fields (f), bitmasks (b), 
	 * lowercased strings (l) and md5 values of CSV fields (m). 
	 * Apart from option b(), multiple substitutions within a single value are allowed.
	 *
	 * @param	string		$main: $main[$n][$this->inData['fieldmap'][$key]]: reference to current CSV values
	 * @param	string		$config: $this->inData['customValue'][$key]: option/config string
	 * @param	array			$row: $main[$n] the whole dataset n
	 * @return	string		the manipulated/substituted value
	 */
	function generateCustomValue($main,$config,$row) {

		$copy = $row;
		$hit = '';

		// {<= this curly brace is needed by my editor to find corresponding braces
		// the regex expression below makes this necessary.
		// please ignore this comment =:o)
		// but since you are already here: hi, hope you like what you've found!
		preg_match_all("/\{([bflms])\((\d{1,}|[^()]*)\)[^}]*\}/", $config, $hit, PREG_SET_ORDER);
		$val = '';
		foreach ($hit as $value) {
			switch ($value[1]) {
				case "f":
					$temp = array_keys($row);
					$f = intval($value[2]);
					$val .= $row[$temp[$f]];
				break;

				case "l":
					$temp = array_keys($row);
					$l = intval($value[2]);
					$val .= strtolower($row[$temp[$l]]);
				break;

				case "m":
					$temp = array_keys($row);
					$m = intval($value[2]);
					$val .= md5($row[$temp[$m]]);
				break;

				case "s":
					$val .= $value[2];
				break;

				case "b":
					$temp = array_keys($row);
					$mask = 0;
					$keys = explode(',',$value[2]);

					foreach ($keys as $k=>$v) {
						$data[] = $row[$temp[$v]];
					}
					while(list($k,$b)=each($data))	{
						if ($b)	{$mask |= pow (2,$k);}
					}
					$val = intval($mask);
				break;
			}
		}
		$main = !empty($val) ? $val : '';
		return $main;
	} // end function

	/**
	 * Checks FE user data for TYPO3 compliancy. Some FE data have limitations which we check in this function.
	 * Some values can be fixed (fx. upper/lowercase conditions), others can not be fixed (fx. empty username/password).
	 * Each value passed is manipulated by REFERENCE.
	 * $user is the data array for the current user.
	 * $importUser is the (boolean) import flag whether to import or not import the user.
	 * The value returned is the HTML content to be displayed.
	 *
	 * @param	array			$user: current user to be checked
	 * @param	boolean		$importUser: flag as to import or skip current user
	 * @return	string		HTML content to be displayed
	 */
	function checkUserDataFE (&$user,&$importUser) {

		global $LANG;

		/* knock out conditions - default mandatory fields */

		$importUser = TRUE;

		if ( empty($user[$this->uniqueUserIdentifier]) ) { // check for empty username value
			$error[] = $LANG->getLL('f1.tab5.error.emptyUserName');//'empty username';
			$importUser = FALSE;
			$fatalError = TRUE;
		}

		if ( strlen($user['username']) > 50 ) { // check for max username value
			$error[] = $LANG->getLL('f1.tab5.error.userNameTooLong');//'username too long';
			$importUser = FALSE;
			$fatalError = TRUE;
		}

		if ( empty($user['password']) ) { // check for empty password value
			$error[] = $LANG->getLL('f1.tab5.error.emptyPassword');//'empty password';
			$importUser = FALSE;
			$fatalError = TRUE;
		}

		if ( strlen($user['password']) > 39 ) { // check for max password value
			$error[] = $LANG->getLL('f1.tab5.error.passwordTooLong');//'password too long';
			$importUser = FALSE;
			$fatalError = TRUE;
		}

		/* knock out conditions - user-defined mandatory fields */
		if (!empty($this->additionalMandatoryFields)) {
			foreach ($this->additionalMandatoryFields as $value) {
				if ( empty($user[$value]) ) {
					$error[] = sprintf($LANG->getLL('f1.tab5.error.emptyMandatory'),$value); // empty user-defined mandatory field
					$importUser = FALSE;
					$fatalError = TRUE;
				}
			}
		}

		/* These are recoverable conditions */
		if ( !$fatalError && $this->enableAutoRename ) {
			if ( strlen($user['password']) != strlen(ereg_replace(' ','',$user['password'])) ) { // check for space in password
				$msg[] = $LANG->getLL('f1.tab5.corrected.WSP'); //'corrected whitespace in password';
				$user['password'] = ereg_replace(' ', '', $user['password']); // replace spaces
			}
			if ( strlen($user['username']) != strlen(ereg_replace(' ','',$user['username'])) ) { // check for space in password
				$msg[] = $LANG->getLL('f1.tab5.corrected.WSU'); //'corrected whitespace in username';
				$user['username'] = ereg_replace(' ', '', $user['username']); // replace spaces
			}
		}

		/* knock out conditions if $this->enableAutoRename is NOT set*/
		if ( !$fatalError && !$this->enableAutoRename && strlen($user['username']) != strlen(ereg_replace(' ','',$user['username'])) ) { // check for space in username
			$msg[] = $LANG->getLL('f1.tab5.error.WSU'); //'whitespace in username';
			$importUser = FALSE;
		}

		if ( !$fatalError && !$this->enableAutoRename && strtolower($user['username']) != $user['username'] ) { // check for uppercase username values
			$msg[] = $LANG->getLL('f1.tab5.error.UCU'); //'uppercase in username';
			$importUser = FALSE;
		}

		if ( !$fatalError && !$this->enableAutoRename && strlen($user['password']) != strlen(ereg_replace(' ','',$user['password'])) ) { // check for space in password
			$msg[] = $LANG->getLL('f1.tab5.error.WSP'); //'whitespace in password';
			$importUser = FALSE;
		}

		if ( !$fatalError && !$this->enableAutoRename && strtolower($user['password']) != $user['password'] ) { // check for uppercase password values
			$msg[] = $LANG->getLL('f1.tab5.error.UCP'); //'uppercase in password';
			$importUser = FALSE;
		}

		/* These conditions may be recovered */

		if ( !$fatalError && $this->enableAutoRename ) {
			if ( strtolower($user['username']) != $user['username'] ) { // check for uppercase username values
				$msg[] = $LANG->getLL('f1.tab5.corrected.UCU'); //'corrected uppercase in username';
				$user['username'] = strtolower($user['username']);
				$importUser = TRUE;
			}

			if ( strtolower($user['password']) != $user['password'] ) { // check for uppercase password values
				$msg[] = $LANG->getLL('f1.tab5.corrected.UCP'); //'corrected uppercase in password';
				$user['password'] = strtolower($user['password']);
				$importUser = TRUE;
			}
		}

		/* Generate the content string */

		if ($fatalError) {
			$content = !empty($error) ? $LANG->getLL('f1.tab5.error').implode(', ',$error) : '';
		}

		if (!empty($msg)) {
			$content = $LANG->getLL('f1.tab5.warning').implode(', ',$msg);
		}

		return $content;
	}

	/**
	 * Each value passed is manipulated by REFERENCE.
	 * tt_address is less restrictive in what data is allowed or not =:o)
	 * @param	array			$user: current user to be checked
	 * @param	boolean		$importUser: flag as to import or skip current user
	 * @return	string		HTML content to be displayed
	 */
	function checkUserDataTT (&$user,$importUser) {
		$importUser = TRUE;
		$content = '';
		return $content;
	}

	/**
	 * This function does nothing. It will be used in a later version
	 * where BE user import will be enabled.
	 *
	 * @param	array			$user: current user to be checked
	 * @param	boolean		$importUser: flag as to import or skip current user
	 * @return	string		HTML content to be displayed
	 */
	function checkUserDataBE (&$user,$importUser) {
		return;
	}

} //end class

/* General helper functions */

/**
 * In contrast to fgetcsv(), fputcsv() is not yet in the PHP 4 core (but in PHP5 CSV).
 * So we fake the function here. Should be interchangeable once official PHP5 is out.
 *
 * @param	string		$fileName: the file to write to
 * @param	array		$dataArray: the array to write
 * @param	string		$delimiter: the field delimiter
 * @param	string		$enclosure: the enclosure character
 * @return	void		...
 */
function _fputcsv($fileName, $dataArray, $delimiter, $enclosure) {

	// Build the string
	$line = "";
	$writeDelimiter = FALSE;
	foreach($dataArray as $dataRow){
		foreach ($dataRow as $dataElement) {
			if($writeDelimiter) $line .= $delimiter;
			$line .= $enclosure . $dataElement . $enclosure;
			$writeDelimiter = TRUE;
		} // end foreach($dataArray as $dataElement)
	// Append new line
	$line .= "\n";
	$writeDelimiter = FALSE;
	}
	if (t3lib_div::writeFile($fileName,$line)) {
		//	print_r('success');
	} else {
		//	print_r('error');
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/rs_userimp/mod1/class.tx_rsuserimp.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/rs_userimp/mod1/class.tx_rsuserimp.php"]);
}
?>