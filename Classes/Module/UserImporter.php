<?php
namespace Visol\RsUserimp\Module;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Module 'import' for the 'rs_userimp' extension.
 * @author    Rainer Sudhoelter <r.sudhoelter (at) web.de>
 * @comment Parts of this class (presets handling) are derived from SYSEXT:impexp written by Kasper Skarhoe
 */

/**
 * Extends the "ScriptClasses" for backend modules to hold this module
 *
 */
class UserImporter extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	var $pageinfo;
	var $inData = array();
	var $presetContent;
	/*****************************
	 *
	 * Main functions
	 *
	 *****************************/

	/**
	 * Initializes the module
	 *
	 * @return    void        standard initialization of BE modul
	 */
	function init() {

		parent::init();
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return    void
	 */
	function menuConfig() {

		$this->MOD_MENU = Array(
			"function" => Array(
				"1" => $GLOBALS['LANG']->getLL("function1"),
				"2" => $GLOBALS['LANG']->getLL("function2"),
				"3" => $GLOBALS['LANG']->getLL("function3"),
//					"4" => "Export",
			)
		);
		parent::menuConfig();
	}

	// If you chose "web" as main module, you will need to consider the $this->id
	// parameter which will contain the uid-number of the page clicked in the page tree
	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return    void
	 */
	function main() {

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($GLOBALS['BE_USER']->user['admin'] && !$this->id)) {

			// Draw the header.
			$this->doc = GeneralUtility::makeInstance("TYPO3\\CMS\\Backend\\Template\\MediumDocumentTemplate");
			$this->doc->backPath = $BACK_PATH;

			// grab input data
			$this->inData = GeneralUtility::_GP('tx_rsuserimp');

			// load/save/update preset
			$this->presetContent = $this->processPresets($this->inData);

			// check if the the auto feature is enabled and ...
			if ($this->inData['settings']['enableAutoValues'] == '1') {
				// ... throw away empty custom values
				if (isset($this->inData['customValue'])) {
					$myval = '';
					foreach ($this->inData['customValue'] as $key => $val) {
						$myval .= !empty($val) ? $key . ',' : '';
						if (empty($val)) {
							unset($this->inData['autoval'][$key]);
						}
					}
					$swapSelectorString = $myval;
					$this->doc->bodyTagAdditions = 'onload="toggle(); toggleSelector(' . "'" . $swapSelectorString . "'" . ');"';
				}
			} else {
				$this->doc->bodyTagAdditions = 'onload="toggle();"';
			}

			$this->doc->form = '<form name="rs_userimp" action="index.php" method="post" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '">
											<input type="hidden" name="id" value="' . $this->id . '" />';


			// Include some JavaScript
			$this->doc->JScode = '
					<script language="javascript" type="text/javascript">
						script_ended = 0;
						/*****************************
						 *
						 * General JavaScript functions
						 *
						 *****************************/
						/**
						 * Javascript function:
						 * redirect to given URL
						 *
						 * @param	string		$URL: target URL to switch to
						 * @return	void
						 */
						function jumpToUrl(URL)	{
							document.location = URL;
						}

						/**
						 * Javascript function:
						 * clears text field if select box is not empty.
						 *
						 * @return	void
						 */
						function swapPresetSelectFields() {
							var one = document.rs_userimp.swapfield.value;
							if(one != "") {
								document.rs_userimp.swapfield.value = 0;
							}
						}

						/**
						 * Javascript function:
						 * checks for empty mandatory settings, throws alert if needed.
						 *
						 * @return	void
						 */
						function checkForm () {' .
				'if (document.rs_userimp.importStorageFolder.value == "") {
							 alert("' . $GLOBALS['LANG']->getLL('f1.tab2.section.storageFolder.error') . '");
							 document.rs_userimp.importStorageFolder.style.backgroundColor = "#FFDFDF";
							 document.rs_userimp.importStorageFolder.focus();
							 return false;
						  }' .
				(($this->inData['settings']['importUserType'] == 'FE' || !($this->inData['settings']['importUserType'])) ?
					'if (document.rs_userimp.importUserGroup.value == "") {
							 alert("' . $GLOBALS['LANG']->getLL('f1.tab2.section.defaultGroup.emptyGroup.error') . '");
							 document.rs_userimp.importUserGroup.style.backgroundColor = "#FFDFDF";
							 document.rs_userimp.importUserGroup.focus();
							 return false;
						  }' :
					'') .
				'if (document.rs_userimp.importStorageFolder.value == "") {
							 alert("' . $GLOBALS['LANG']->getLL('f1.tab2.section.storageFolder.error') . '");
							 document.rs_userimp.importStorageFolder.style.backgroundColor = "#FFDFDF";
							 document.rs_userimp.importStorageFolder.focus();
							 return false;
						  }
						  if (document.rs_userimp.enableUpdate.checked == true && document.rs_userimp.uniqueIdentifier.value == "") {
							 alert("' . $GLOBALS['LANG']->getLL('f1.tab2.section.uniqueIdentifier.error') . '");
							 document.rs_userimp.uniqueIdentifier.style.backgroundColor = "#FFDFDF";
							 document.rs_userimp.uniqueIdentifier.focus();
							 return false;
						   }
						}

						/**
						 * Javascript function:
						 * unchecks enableAutovalue checkbox if enableUpdate is checked.
						 *
						 * @return	void
						 */
						function toggle() {

							if (document.rs_userimp.enableUpdate.checked == true) {
								document.rs_userimp.enableAutoRename.checked = false;
								document.rs_userimp.enableAutoRename.disabled = true;
							}
							if (document.rs_userimp.enableUpdate.checked == false) {
								document.rs_userimp.enableAutoRename.disabled = false;
							}' .
				($_POST['importNow'] ? '
							document.rs_userimp.enableAutoRename.disabled = true;'
					: '') . '
						}

						/**
						 * JavaScript function:
						 * toggles given form fields
						 *
						 * @param	string		$data: coma separated list of fields which to toggle
						 * @return	void
						 */
						function toggleSelector (data) {
							var fields = data.split(",");
							var num = fields.length;
							for(i = 0; i < num; i++) {
								toggleOptions(fields[i]);
							}
						}

						/**
						 * JavaScript function:
						 * toggle input options
						 *
						 * @param	id		the id of the session item to hide or show
						 * @param	single		true to show only one item at a time, false the open as many as you want
						 * @return	void
						 */
						function toggleOptions(id) {
							if(document.getElementById("rsdivon_"+id).style.display == "none") {
								showHideFields(id, true);
							}
							else {
								showHideFields(id, false);
							}
						}

						/**
						 * JavaScript function:
						 * shows/hides selected form element
						 *
						 * @param	integer		$id: JavaScript id of the form element
						 * @param	boolean		$status: status/visibility of form element
						 * @return	void		...
						 */
						function showHideFields(id, status) {
							if(status) {
								document.getElementById("rsdivon_"+id).style.display = "block";
								document.getElementById("rsdivoff_"+id).style.display = "none";
							} else {
								document.getElementById("rsdivon_"+id).style.display = "none";
								document.getElementById("rsdivoff_"+id).style.display = "block";
							}
						}
					</script>
					<script src="scripts.js" type="text/javascript" language="Javascript" charset="iso-8859-1"></script>';

			$this->doc->postCode = '
					<script language="javascript" type="text/javascript">
						script_ended = 1;
						if (top.fsMod) top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
					</script>';

			$this->doc->JScode .= $this->doc->getDynTabMenu();

			$headerSection = $this->doc->getHeader("pages", $this->pageinfo, $this->pageinfo['_thePath']) . "<br>" . $GLOBALS['LANG']->sL("LLL:EXT:lang/locallang_core.php:labels.path") . ": " . GeneralUtility::fixed_lgd_cs($this->pageinfo['_thePath'], 50);

			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL("title"));
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL("title"));
			$this->content .= $this->doc->section('', $this->doc->funcMenu('', \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, "SET[function]", $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'])));
			$this->content .= $this->doc->spacer(5);

			if (is_array($this->presetContent)) {
				$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('f1.tab2.section.presets'), $this->presetContent[0], 0, 1, $this->presetContent[1]);
			}

			// Render content:
			$this->moduleContent();

			// ShortCut
			if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
				$this->content .= $this->doc->spacer(5) . $this->doc->section("", $this->doc->makeShortcutIcon("id", implode(",", array_keys($this->MOD_MENU)), $this->MCONF['name']));
			}
			$this->content .= $this->doc->spacer(5);
		} else {
			// If no access or if ID == zero
			$this->doc = GeneralUtility::makeInstance("TYPO3\\CMS\\Backend\\Template\\MediumDocumentTemplate");
			$this->doc->backPath = $BACK_PATH;

			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL("title"));
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL("title"));
			$this->content .= $this->doc->spacer(5);
		}
	}

	/*****************************
	 *
	 * Output functions
	 *
	 *****************************/

	/**
	 * Prints out the module HTML
	 *
	 * @return    void        prints HTML content
	 */
	function printContent() {

		$this->content .= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return    void
	 */
	function moduleContent() {

		// get configuration values from ext_conf_template.txt
		$userimpConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rs_userimp']);
		$useRecycler = $userimpConf['useRecycler'];
		$createDropFile = $userimpConf['createDropFile'];
		$garbageCollectionTriggerTimer = $userimpConf['garbageCollectionTriggerTimer'];
		$rollbackSafetyTimespan = $userimpConf['rollbackSafetyTimespan'];
		$rollbackPreviewRows = $userimpConf['rollbackPreviewRows'];
		$rollbackDeleteFromDB = $userimpConf['rollbackDeleteFromDB'];

		//garbage collection
		$this->gc($garbageCollectionTriggerTimer, $rollbackSafetyTimespan);

		// check if we have a file upload or have an already existing file
		$newFile = $this->checkUpload();


		// check if we already have an uploaded file
		if ($this->inData['settings']['uploadfile'] && file_exists($this->inData['settings']['uploadfile'])) {
			$absFile = $this->inData['settings']['uploadfile'];
		}

		// there seems to be a new upload file
		if (!empty($newFile)) {
			// set current file to new value
			$absFile = $this->inData['settings']['uploadfile'] = $newFile;
			// and whipe out old module data to start new session
			$this->inData = '';
		}

		// always check if the file is present
		if (!file_exists($absFile)) $this->inData = '';

		if ($newFile && !file_exists($newFile)) {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('f1.tab1.section.error'), $GLOBALS['LANG']->getLL('f1.tab1.section.error.description'), 0, 1, 3);
		}

		switch ((string)$this->MOD_SETTINGS['function']) {

			// print security message
			case 3:
				$content .= $this->doc->section($GLOBALS['LANG']->getLL('f3.securityNote'), '', 0, 1, 1);
				$content .= '<table border="0" width="100%" align="center" bgcolor="' . $this->doc->bgColor4 . '">';
				$content .= '<tr>
									<td>' . $GLOBALS['LANG']->getLL('f3.securityNote.message') . '</td>
								 </tr>';
				$content .= '</table>';
				$this->content .= $content;
				break;

			// rollback function
			case 2:
// temporary hack
				$this->inData = GeneralUtility::_GP('tx_rsuserimp');
				// if the rollback button was pressed...
				if (is_array($this->inData['rollback'])) {
					// get the ID of the rollback session ...
					$rollbackSession = array_keys($this->inData['rollback']);

					if ($rollbackSession[0] != '') {
						// ... and get the rollback session details
						$data = $this->getRollbackDataSet($rollbackSession[0]);
						$candidates = explode(',', $data['session_data']);
					}
					foreach ($candidates as $user) {
// how shall we handle updated users: are they to be deleted during rollback since
// they have been there before they were updatet so they are not our "own" users...
// currently, they get deleted but i'm not sure what to do here...
						if ($rollbackDeleteFromDB) {
							$GLOBALS['TYPO3_DB']->exec_DELETEquery($data['db_table'], 'uid=' . $user . ' AND pid=' . $data['target_pid']);
							$GLOBALS['BE_USER']->writelog(1, 3, 0, '', 'UID %s deleted by CSV rollback action', Array($user));
						} else {
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery($data['db_table'], 'uid=' . $user, array('deleted' => '1'));
						}
					}
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_rsuserimp_sessions', 'uid=' . $rollbackSession[0], array('active' => '0'));
				}

				// ... else display the form
				$rollbackData = $this->getRollbackDataSets();

				if (!empty($rollbackData)) {
					$content .= '<table name="sessionlist" id="sessionlist" border="0" width="100%" cellspacing="1" cellpadding="2" align="center" bgcolor="' . $this->doc->bgColor2 . '">';
					$content .= '<tr>
										<td><strong>' . $GLOBALS['LANG']->getLL('f2.session') . '</strong></td>
										<td><strong>' . $GLOBALS['LANG']->getLL('f2.date') . '</strong></td>
										<td><strong>' . $GLOBALS['LANG']->getLL('f2.title') . '</strong></td>
										<td><strong>' . $GLOBALS['LANG']->getLL('f2.status') . '</strong></td>
									</tr>';

					$rowcount = 0;
					$colorcount = 0;

					foreach ($rollbackData as $session) {
						$info = explode(',', $session['session_data']);
						$rollbacktime = $session['crdate'] + 60 * $rollbackSafetyTimespan;
						$createtime = getdate($session['crdate'][0]);

						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username', 'be_users', 'uid=' . $session['user_uid']);
						$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

						if ($GLOBALS['TYPO3_DB']->sql_affected_rows() == 1) {
							$username = $row[0];
						} elseif ($GLOBALS['TYPO3_DB']->sql_affected_rows() == 0) {
							$username = $GLOBALS['LANG']->getLL('f2.importedBy.deleted', 1);
						} else {
							$username = $GLOBALS['LANG']->getLL('f2.importedBy.unknown', 1);
						}

						$infoBlock = '<strong>' . $GLOBALS['LANG']->getLL('f2.importSummary', 1) . '</strong>:<br>
							<div align="left">
								<table cellpadding="0px" cellspacing="0px" border="0">
									<tr>
										<td>' . $GLOBALS['LANG']->getLL('f2.importFile', 1) . '</td>
										<td width="10pt"></td>
										<td>' . $session['file'] . '</td>
									</tr>
									<tr>
										<td>' . $GLOBALS['LANG']->getLL('f2.importedBy', 1) . '</td>
										<td width="10pt"></td>
										<td>' . $username . ' [UID ' . $session['user_uid'] . ']</td>
									</tr>
									<tr>
										<td>' . $GLOBALS['LANG']->getLL('f2.userType', 1) . '</td>
										<td width="10pt"></td>
										<td>' . $session['db_table'] . '</td>
									</tr>
									<tr>
										<td>' . $GLOBALS['LANG']->getLL('f2.usersImported', 1) . '</td>
										<td width="10pt"></td>
										<td>' . $session['num_imp'] . '</td>
									</tr>
									<tr>
										<td>' . $GLOBALS['LANG']->getLL('f2.usersUpdated', 1) . '</td>
										<td width="10pt"></td>
										<td>' . $session['num_upd'] . '</td>
									</tr>
									<tr>
										<td>' . $GLOBALS['LANG']->getLL('f2.usersDropped', 1) . '</td>
										<td width="10pt"></td>
										<td>' . $session['num_drop'] . (!empty($session['dropfile']) && is_file($session['dropfile']) ? ' [<a href="/uploads/tx_rsuserimp/' . basename($session['dropfile']) . '">' . $GLOBALS['LANG']->getLL('f1.tab5.downloadFile') . '</a>]' : '') . '</td>
									</tr>
								</table>
							</div>';

						$previewNum = ($rollbackPreviewRows < count($info)) ? $rollbackPreviewRows : count($info);

						if ($previewNum > 0) {
							$infoBlock .= '<br><strong>' . $GLOBALS['LANG']->getLL('f2.sampleData', 1) . '</strong><br><br>';

							for ($i = 0; $i < $previewNum; $i++) {
								$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($session['unique_identifier'], $session['db_table'], 'uid=' . $info[$i] . ' AND deleted=0');
								$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

								if ($GLOBALS['TYPO3_DB']->sql_affected_rows() == 1) {
									$infoBlock .= $row[0] . ' [UID ' . $info[$i] . ']<br>';
								} elseif ($GLOBALS['TYPO3_DB']->sql_affected_rows() == 0) {
									$infoBlock .= sprintf($GLOBALS['LANG']->getLL('f2.userAlreadyDeleted'), $info[$i]) . '<br>';
								} else {
									$infoBlock .= $GLOBALS['LANG']->getLL('f2.unknownError') . '<br>';
								}
							}
							$infoBlock .= '[...]<br>';
						}

						$infoBlock .= '<div align="right">
													<input type="submit" onclick="return confirm(\'' . $GLOBALS['LANG']->getLL('f2.rollback.sure', 1) . '\');" name="tx_rsuserimp[rollback][' . $session['uid'] . ']" value="Roll back" ' . ((((mktime() < $rollbacktime) || $rollbackSafetyTimespan == 0) && $session['active'] == 1) ? '' : 'disabled') . '/>
												</div>';

						$colorcount = ($colorcount == 1) ? 0 : 1;
						$color = ($colorcount == 1) ? $this->doc->bgColor5 : $this->doc->bgColor4;
						$tr_params = ' onmouseover="setRowColor(this,\'' . $rowcount . '\',\'over\',\'' . $this->doc->bgColor6 . '\');"
												onclick="setRowColor(this,\'' . $rowcount . '\',\'click\',\'' . $this->doc->bgColor6 . '\',\'' . $color . '\');" bgcolor="' . $color . '"
												onmouseout="setRowColor(this,\'' . $rowcount . '\',\'out\',\'' . $color . '\',\'' . $this->doc->bgColor6 . '\');"
												';
						$content .= '<tr' . $tr_params . ' id="sessionrow_' . $rowcount . '" name="sessionrow" style="cursor: pointer;">
											<td valign="top"><strong>' . $session['uid'] . '</td>
											<td valign="top">' . \TYPO3\CMS\Backend\Utility\BackendUtility::dateTimeAge($session['crdate'], 1) . '</td>
											<td valign="top">' . $session['title'] . '</td>';

						$now = mktime();

						if ($now < $rollbacktime || $rollbackSafetyTimespan == 0) {
							if ($session['active'] == 1) {
								$content .= '<td valign="top">' . sprintf($GLOBALS['LANG']->getLL('f2.sessionRollback'), \TYPO3\CMS\Backend\Utility\BackendUtility::dateTimeAge($rollbacktime, 1)) . '</td>';
							}

							if ($session['active'] == 0) {
								$content .= '<td valign="top">' . $GLOBALS['LANG']->getLL('f2.sessionRolledBack') . '</td>';
							}
						} elseif ($now >= $rollbacktime) {
							$content .= '<td valign="top">' . sprintf($GLOBALS['LANG']->getLL('f2.sessionExpired'), \TYPO3\CMS\Backend\Utility\BackendUtility::dateTimeAge($rollbacktime, 1)) . '</td>';
						}

						$content .= '</tr>';
						$content .= '<tr style="display: none;" id="rs_userimp_be_rollbacksession_' . $rowcount . '" bgcolor="' . $this->doc->bgColor6 . '">
												<td colspan="5">' . $infoBlock . '</td>
											</tr>';
						$rowcount++;
					} //end foreach
					$content .= '</table>';
				} else { //if not empty rollbackData
					$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('f2.noSessionFound'), '', 0, 1, 1);
				}

				$this->content .= $content;
				break;

			// import function

			case 1:
				/***** start ********/
				/***** file uplod ********/
				/**** TAB 1 data *****/

				$additionalMandatoryFields = isset($this->inData['settings']['extraFields']) ? $this->inData['settings']['extraFields'] : ''; //array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$userimpConf['additionalMandatoryFields'],1));

				// create and initialize instance of our import object
				/** @var \Visol\RsUserimp\Service\UserImporterService $mapper */
				$mapper = GeneralUtility::makeInstance('Visol\\RsUserimp\\Service\\UserImporterService');

				$mapper->userType = isset($this->inData['settings']['importUserType']) ? $this->inData['settings']['importUserType'] : $mapper->userType;
				$mapper->setUserTypeDefaultData();

				$dbFieldsDefault = explode(',', $mapper->userTypeDB);

				$mapper->removeNoMapFields($dbFieldsDefault);

				$dbFieldsDefault = array_unique(array_diff($dbFieldsDefault, $mapper->mandatoryFields));

				$row[] = '
						<input type="hidden" name="tx_rsuserimp[settings][uploadfile]" value="' . $absFile . '" />';

				$row[] = '
						<tr class="tableheader bgColor5">
							<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab1.section.importFile') . '</td>
						</tr>';

				$tempDir = $this->userTempFolder();
				$row[] = '
						<tr class="bgColor4">
							<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab1.section.importFile.importFile') . '</strong></td>
							<td>' .
					$GLOBALS['LANG']->getLL('importFile') . '<br/>
								<input type="file" name="upload_1" size="30" ' . ($_POST['importNow'] ? 'disabled' : '') . '/><br/>
								<input type="hidden" name="file[upload][1][target]" value="' . htmlspecialchars($tempDir) . '" ' . ($_POST['importNow'] ? 'disabled' : '') . '/>
								<input type="hidden" name="file[upload][1][data]" value="1" />
								' . $GLOBALS['LANG']->getLL('f1.tab1.section.importFile.overwriteFile') . '
								<input type="checkbox" name="tx_rsuserimp[preset][overwriteExistingFiles]" value="1"' . (isset($this->inData['preset']['overwriteExistingFiles']) ? ' checked="checked"' : '') . ' ' . ($_POST['importNow'] ? 'disabled' : '') . '/><br/>
								<div align="right"><input type="submit" value="Upload" ' . ($_POST['importNow'] ? 'disabled' : '') . '/></div>
							</td>
						</tr>';

				if (!empty($absFile) && file_exists($absFile)) {
					$currentFileInfo = \TYPO3\CMS\Core\Utility\File\BasicFileUtility::getTotalFileInfo($absFile);
					$currentFile = $currentFileInfo['file'];
					$curentFileSize = \TYPO3\CMS\Core\Utility\File\BasicFileUtility::formatSize($currentFileInfo['size']);
					$currentFileMessage = $currentFile . ' (' . $curentFileSize . ')';
				} else {
					$currentFileMessage = $GLOBALS['LANG']->getLL('f1.tab1.section.importFile.emptyImportFile');
				}

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab1.section.importFile.currentImportFile') . '</strong></td>
								<td><div align="left"><strong>' . $currentFileMessage . '</strong></div></td>
							</tr>';

				//now compose TAB menu array for TAB1
				$menuItems[] = array(
					'label' => $GLOBALS['LANG']->getLL('f1.tab1'),
					'content' => '
							<table border="0" cellpadding="1" cellspacing="1" width="100%">
								' . implode('
								', $row) . '
							</table>
						',
					'description' => $GLOBALS['LANG']->getLL('f1.tab1.description'),
					'linkTitle' => '',
					'stateIcon' => file_exists($absFile) ? -1 : 2
				);

				/**** TAB 1 data *****/


				/***** start ********/
				/***** general import ********/
				/**** TAB 2 data *****/

				$row = array();

				$row[] = '
						<tr class="tableheader bgColor5">
							<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab2.section.userType') . '</td>
						</tr>';

				// define options for usertype dropdown menu
				$opt = array(
					'FE' => $GLOBALS['LANG']->getLL('f1.tab2.constants.FE'),
					'TT' => $GLOBALS['LANG']->getLL('f1.tab2.constants.TT')
				);

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.userType.label') . '</strong></td>
								<td>' . $this->renderSelectBox('tx_rsuserimp[settings][importUserType]', (isset($this->inData['settings']['importUserType']) ? $this->inData['settings']['importUserType'] : ''), $opt, '', "onchange='submit()'") . '</td>
							</tr>';

				$row[] = '
						<tr class="tableheader bgColor5">
							<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab2.section.storageFolder') . '</td>
						</tr>';

				// fe_users
				if ($mapper->userType == 'FE') {
					$preopt = $this->getFEuserFolder();
					$opt = array('' => '');

					foreach ($preopt as $val) {
						$opt = $opt + array($val['uid'] => $val['title'] . ' [UID ' . $val['uid'] . ']');
					}
				} else if ($mapper->userType == 'TT') {
					// tt_address
					$preopt = $this->getTTsysFolder();
					$opt = array('' => '');

					foreach ($preopt as $val) {
						$opt = $opt + array($val['uid'] => $val['title'] . ' [UID ' . $val['uid'] . ']');
					}
				} else {
					// be_users
					$preopt = $this->getTTsysFolder();
					$opt = array('' => '');

					foreach ($preopt as $val) {
						$opt = $opt + array($val['uid'] => $val['title'] . ' [UID ' . $val['uid'] . ']');
					}

					$row[] = '
							<tr class="tableheader bgColor5">
								<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab2.section.defaultGroup') . '</td>
							</tr>';
				} // end user specific settings

				if (isset($this->inData['settings']['importStorageFolder']) && !in_array($this->inData['settings']['importStorageFolder'], array_keys($opt))) {
					unset($this->inData['settings']['importStorageFolder']);
				}

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.storageFolder.label') . '</strong></td>
								<td>' . $this->renderSelectBox('tx_rsuserimp[settings][importStorageFolder]', (isset($this->inData['settings']['importStorageFolder']) ? $this->inData['settings']['importStorageFolder'] : ''), $opt, 'importStorageFolder') . '</td>
							</tr>';

				$row[] = '
						<tr class="tableheader bgColor5">
							<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab2.section.defaultGroup') . '</td>
						</tr>';

				$preopt = $this->getFEuserGroup();

				if (!empty($preopt)) {
					$opt = array();
					foreach ($preopt as $key => $val) {
						$opt = $opt + array($val['uid'] => $val['title'] . ' [UID ' . $val['uid'] . ']');
					}
				} else {
					$opt = array('' => $GLOBALS['LANG']->getLL('f1.tab2.section.defaultGroup.emptyGroup'));
				}

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.defaultGroup.label') . '</strong></td>
								<td>' . $this->renderMultipleSelector('tx_rsuserimp[settings][importUserGroup]', $opt, (isset($this->inData['settings']['importUserGroup']) ? $this->inData['settings']['importUserGroup'] : ''), 1, 'importUserGroup') . '</td>
							</tr>';

				$row[] = '
						<tr class="tableheader bgColor5">
							<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.userupdate') . '</td>
						</tr>';
				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.userupdate.description') . '</td>
								<td><input onChange="toggle();" type="checkbox" id="enableUpdate" name="tx_rsuserimp[settings][enableUpdate]" value="1"' . (isset($this->inData['settings']['enableUpdate']) ? ' checked="checked"' : '') . ' ' . ($_POST['importNow'] ? 'disabled' : '') . '/></td>
							</tr>';

				$opt = array('' => '');
				if ($mapper->userType == 'FE') {
					if (!empty($userimpConf['uniqueIdentifierListFE'])) {
						$preopt = explode(',', $userimpConf['uniqueIdentifierListFE']);
					} else {
						$preopt = isset($dbFieldsDefault) ? $dbFieldsDefault : '';
					}
				}

				if ($mapper->userType == 'TT') {
					if (!empty($userimpConf['uniqueIdentifierListTT'])) {
						$preopt = explode(',', $userimpConf['uniqueIdentifierListTT']);
					} else {
						$preopt = isset($dbFieldsDefault) ? $dbFieldsDefault : '';
					}
				}

				foreach ($preopt as $val) {
					$opt = $opt + array($val => $val);
				}

				if (isset($this->inData['settings']['uniqueIdentifier']) && !in_array($this->inData['settings']['uniqueIdentifier'], array_keys($opt))) {
					unset($this->inData['settings']['uniqueIdentifier']);
				}

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.uniqueIdentifier.label') . '</strong></td>
								<td>' . $this->renderSelectBox('tx_rsuserimp[settings][uniqueIdentifier]', (isset($this->inData['settings']['uniqueIdentifier']) ? $this->inData['settings']['uniqueIdentifier'] : ''), $opt, 'uniqueIdentifier', '') . '</td>
							</tr>';

				$row[] = '
						<tr class="tableheader bgColor5">
							<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings') . '</td>
						</tr>';

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.firstRowHasFieldnames') . '</strong></td>
								<td><input type="checkbox" name="tx_rsuserimp[settings][firstRowHasFieldnames]" value="1"' . (isset($this->inData['settings']['firstRowHasFieldnames']) ? ' checked="checked"' : '') . ' ' . ($_POST['importNow'] ? 'disabled' : '') . '/></td>
							</tr>';

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.enableAutoRename') . '</td>
								<td><input type="checkbox" id="enableAutoRename" name="tx_rsuserimp[settings][enableAutoRename]" value="1"' . (isset($this->inData['settings']['enableAutoRename']) ? ' checked="checked"' : '') . ' ' . ($_POST['importNow'] ? 'disabled' : '') . '/></td>
							</tr>';

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.enableAutoValues') . '</td>
								<td><input type="checkbox" id="enableAutoValues" name="tx_rsuserimp[settings][enableAutoValues]" value="1"' . (isset($this->inData['settings']['enableAutoValues']) ? ' checked="checked"' : '') . ' ' . ($_POST['importNow'] ? 'disabled' : '') . '/></td>
							</tr>';

				// define options for fieldDelimiter dropdown menu
				$opt = array(';' => $GLOBALS['LANG']->getLL('f1.tab2.constants.semicolon'), ',' => $GLOBALS['LANG']->getLL('f1.tab2.constants.comma'), ':' => $GLOBALS['LANG']->getLL('f1.tab2.constants.colon'), 'TAB' => $GLOBALS['LANG']->getLL('f1.tab2.constants.tab'));
				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.fieldDelimiter') . '</strong></td>
								<td>' . $this->renderSelectBox('tx_rsuserimp[settings][fieldDelimiter]', (isset($this->inData['settings']['fieldDelimiter']) ? $this->inData['settings']['fieldDelimiter'] : ''), $opt) . '</td>
							</tr>';

				// define options for fieldEncaps dropdown menu$opt = array(';',',',':');
				$opt = array('"' => '"', "'" => "'");
				//	was: $opt = array(''=>"",'"'=>'"',"'"=>"'");

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.fieldEncaps') . '</strong></td>
								<td>' . $this->renderSelectBox('tx_rsuserimp[settings][fieldEncaps]', (isset($this->inData['settings']['fieldEncaps']) ? $this->inData['settings']['fieldEncaps'] : ''), $opt) . '</td>
							</tr>';

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.previewNum') . '</strong></td>
								<td><input type="select" name="tx_rsuserimp[settings][maxPreview]" value="' . htmlspecialchars(((isset($this->inData['settings']['maxPreview']) && ($this->inData['settings']['maxPreview'] >= 0)) ? $this->inData['settings']['maxPreview'] : '3')) . '" size="2" maxlength="2" ' . ($_POST['importNow'] ? 'disabled' : '') . '></td>
							</tr>';

				$row[] = '
						<tr class="tableheader bgColor5">
							<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.additionalMandatoryFields') . '</td>
						</tr>';

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.additionalMandatoryFields.description') . '</strong></td>
								<td>' . $this->renderMultipleSelector('tx_rsuserimp[settings][extraFields]', $dbFieldsDefault, (isset($this->inData['settings']['extraFields']) ? $this->inData['settings']['extraFields'] : '')) . '</td>
							</tr>';

				$row[] = '
							<tr class="bgColor4">
								<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.update') . '</strong></td>
								<td><div align="right"><input  onclick="return checkForm()" type="submit" name="tx_rsuserimp[settings][OK]" value="' . $GLOBALS['LANG']->getLL('f1.tab2.section.generalSettings.update.update', 1) . '" ' . ($_POST['importNow'] ? 'disabled' : '') . '/></div></td>
							</tr>';

				$this->makeSaveForm($this->inData, $row); //call by reference, alters the array(row) !!!

				//now, compose TAB menu array for TAB2
				$menuItems[] = array(
					'label' => $GLOBALS['LANG']->getLL('f1.tab2'),
					'content' => file_exists($absFile) ? '
							<table border="0" cellpadding="1" cellspacing="1" width="100%">
								' . implode('
								', $row) . '
							</table>
						' : '',
					'description' => $GLOBALS['LANG']->getLL('f1.tab2.description'),
					'linkTitle' => '',
					'toggle' => 0,
					'stateIcon' => (isset($this->inData['settings']['OK']) && file_exists($absFile)) ? -1 : 0
				);
				/**** TAB 2 data *****/

				/***** start ********/
				/***** field mapping ********/
				/**** TAB 3 data *****/

				$row = array();

				if (isset($this->inData['settings']['OK'])) {
					// playing around with the import object
					$mapper->file = $absFile;
					$mapper->userType = $this->inData['settings']['importUserType'];
					$mapper->previewNum = $this->inData['settings']['maxPreview'];
					$mapper->CSVhasTitle = $this->inData['settings']['firstRowHasFieldnames'];
					$mapper->fieldDelimiter = ($this->inData['settings']['fieldDelimiter'] === 'TAB') ? chr(9) : $this->inData['settings']['fieldDelimiter'];
					$mapper->fieldEncaps = $this->inData['settings']['fieldEncaps'];
					//(isset($this->inData['settings']['enableUpdate']) && !empty($this->inData['settings']['uniqueIdentifier'])) ? $mapper->uniqueUserIdentifier = $this->inData['settings']['uniqueIdentifier'] : '';
					$mapper->enableUpdate = (isset($this->inData['settings']['enableUpdate']) && !empty($this->inData['settings']['uniqueIdentifier'])) ? TRUE : FALSE;
					$mapper->defaultUserData['pid'] = $this->inData['settings']['importStorageFolder'];
					$mapper->defaultUserData['usergroup'] = isset($this->inData['settings']['importUserGroup']) ? implode(',', $this->inData['settings']['importUserGroup']) : '';
					$mapper->enableAutoRename = $this->inData['settings']['enableAutoRename'];
					$mapper->enableAutoValues = $this->inData['settings']['enableAutoValues'];
					$mapper->createDropFile = $createDropFile;
					$mapper->additionalMandatoryFields = $additionalMandatoryFields;
					$mapper->inData = $this->inData;
					$mapper->useRecycler = $useRecycler;
					$mapper->init();
					if (isset($this->inData['settings']['enableUpdate']) && !empty($this->inData['settings']['uniqueIdentifier'])) {
						$mapper->mandatoryFields = array_merge($mapper->mandatoryFields, array($this->inData['settings']['uniqueIdentifier']));
						$mapper->uniqueUserIdentifier = $this->inData['settings']['uniqueIdentifier'];
					}

					$row[] = '
								<input type=hidden name="tx_rsuserimp[settings][OK]" value="Update">';

					$row[] = '
								<tr class="bgColor4">' . $mapper->createMappingForm() .
						'</tr>';
				}

				$menuItems[] = array(
					'label' => $GLOBALS['LANG']->getLL('f1.tab3'),
					'content' => ((isset($this->inData['settings']['OK']) || GeneralUtility::_GP('map')) && file_exists($absFile)) ? '
							<table border="0" cellpadding="1" cellspacing="1" width="100%">
								' . implode('
								', $row) . '
							</table>
						' : '',
					'description' => $GLOBALS['LANG']->getLL('f1.tab3.description'),
					'linkTitle' => '',
					'toggle' => 0,
					'stateIcon' => (!empty($mapper->importOK) || isset($this->inData['fieldmap']['OK'])) ? -1 : 0
				);
				/**** TAB 3 data *****/

				/***** start ********/
				/***** importOK ********/
				/**** TAB 4 data *****/
				$row = array();

				if (!empty($mapper->importOK)) {
					$row[] = '
						<tr class="tableheader bgColor5">
							<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab4.section.import') . '</td>
						</tr>';

					$row[] = '
							<tr class="bgColor4"><td>' . $mapper->createImportForm() .
						'</td></tr>';
				}

				if ($mapper->importNow == 'TRUE') {
					$msg = $mapper->importUsers();
				}

				$menuItems[] = array(
					'label' => $GLOBALS['LANG']->getLL('f1.tab4'),
					'content' => !empty($mapper->importOK) ? '
						<table border="0" cellpadding="1" cellspacing="1" width="100%">
							' . implode('
							', $row) . '
						</table>
					' : '',
					'description' => $GLOBALS['LANG']->getLL('f1.tab4.description'),
					'linkTitle' => '',
					'toggle' => 0,
					'stateIcon' => ($mapper->importNow == 'TRUE') ? -1 : 0
				);
				/***** TAB 4 data ********/

				/***** start ********/
				/***** import messages ********/
				/**** TAB 5 data *****/
				$row = array();

				$row[] = '
					<tr class="tableheader bgColor5">
						<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab5') . '</td>
					</tr>';

				$row[] = '
					<tr class="bgColor4">
						<td colspan=2>' . $msg . '</td>
					</tr>';

				$menuItems[] = array(
					'label' => $GLOBALS['LANG']->getLL('f1.tab5'),
					'content' => !empty($msg) ?
						'<table border="0" cellpadding="1" cellspacing="1" width="100%">
						' . implode('
						', $row) . '
					</table>'
						: '',
					'linkTitle' => '',
					'toggle' => 0,
					'stateIcon' => ($mapper->importNow == 'TRUE') ? 1 : 0
				);

				/***** TAB 5 data ********/

				// finally, print out the whole tabmenu
				//getDynTabMenu($menuItems,$identString,$toggle=0,$foldout=FALSE,$newRowCharLimit=50,$noWrap=1,$fullWidth=FALSE,$defaultTabIndex=1)
				$content = $this->doc->getDynTabMenu($menuItems, 'tx_rsuserimp_import', 0, '', 40);
				$this->content .= $content;
				break;
		} // end switch
	} // end module content

	/**
	 * Get IDs for allowed fe_users storage. These IDs are needed later on to create a dropdown selector.
	 * Allowed in this respect means that the module fe_users is installed on that page.
	 * The IDs are queried from the DB.
	 *
	 * @return    array
	 */
	function getFEuserFolder() {
		$feFolders = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,title',
			'pages',
			'module = "fe_users" AND deleted=0',
			'',
			'uid'
		);
		return $feFolders;
	}

	/**
	 * Get IDs of allowed storage folders. These IDs are needed later on to create a dropdown selector.
	 * Allowed folders means that the doktype is "sysfolder" (254) and not deleted.
	 * The IDs are queried from the DB.
	 *
	 * @return    array
	 */
	function getTTsysFolder() {
		$feFolders = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,title',
			'pages',
			'doktype = 254 AND deleted=0 AND hidden=0',
			'',
			'uid'
		);
		return $feFolders;
	}

	/**
	 * Get IDs for valid fe_groups. These IDs are needed to create  dropdown selectors.
	 * The IDs are queried from the DB.
	 *
	 * @return    array
	 */
	function getFEuserGroup() {
		$importUserGroup = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,title,description',
			'fe_groups',
			'deleted=0 AND hidden=0',
			'',
			'title'
		);
		return $importUserGroup;
	}

	/**
	 * Gets all rollback datasets from DB.
	 *
	 * @return    array        returns the session dataset as array
	 */
	function getRollbackDataSets() {

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_rsuserimp_sessions',
			'user_uid=' . $GLOBALS['BE_USER']->user['uid'] . ' AND DELETED=0',
			'uid DESC',
			''
		);
		return $result;
	}

	/**
	 * Gets a single rollback datasets from DB.
	 *
	 * @param    integer $uid : the session UID
	 * @return    string        returns the session dataset as list
	 */
	function getRollbackDataSet($uid) {

		list($result) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'target_pid,db_table,session_data',
			'tx_rsuserimp_sessions',
			'uid=' . intval($uid)
		);
		return $result;
	}

	/**
	 * Creates and returns a HTML select box prefilled with allowed DB column names.
	 * This select box is needed for the CSV to DB field mapping process. Each select box is named by the passed integer.
	 *
	 * @param    integer        name for the select box
	 * @return    string        formated HTML select box with DB column names
	 */
	function fieldSelector($n) {

		$box = '<select name="fieldmap[' . $n . ']" ' . ' size="1">' . "\n";
		$box .= '<option value="">Zuordnung...</option>' . "\n";
		foreach ($this->columnNamesFromDB as $key => $value) {
			$box .= '<option value="' . $value . '"';
			if ($this->inData['fieldmap'][$n] == $value) {
				$box .= ' SELECTED ';
			}
			$box .= '>' . $value . '</option>' . "\n";
		}
		$box .= '</select>' . "\n";
		return $box;
	}

	/**
	 * Checks if a file has been uploaded and returns the complete physical fileinfo if so.
	 *
	 * @return    string        the complete physical file name, including path info.
	 */
	function checkUpload() {

		$file = GeneralUtility::_GP('file');

		// Initializing:
		$this->fileProcessor = GeneralUtility::makeInstance('TYPO3\CMS\Core\Utility\File\ExtendedFileUtility');
		$this->fileProcessor->init($FILEMOUNTS, $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		$this->fileProcessor->init_actionPerms($GLOBALS['BE_USER']->user['fileoper_perms']);
		$this->fileProcessor->dontCheckForUnique = ($this->inData['preset']['overwriteExistingFiles']) ? 1 : 0;

		if (is_array($FILEMOUNTS) && !empty($FILEMOUNTS)) {
			// we have a filemount
			// do something here
		} else {
			// we don't have a valid file mount
			// should be fixed

			// this throws a error message because we have no rights to upload files
			// to our extension's own upload folder
			// further investigation needed
			$file['upload']['1']['target'] = GeneralUtility::getFileAbsFileName('uploads/tx_rsuserimp/');
		}

		// Checking referer / executing:
		$refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
		$httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');

		if ($httpHost != $refInfo['host'] && $this->vC != $GLOBALS['BE_USER']->veriCode() && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']) {
			$this->fileProcessor->writeLog(0, 2, 1, 'Referer host "%s" and server host "%s" did not match!', array($refInfo['host'], $httpHost));
		} else {
			$this->fileProcessor->start($file);
			$newfile = $this->fileProcessor->func_upload($file['upload']['1']);
		}
		return $newfile;
	}

	/**
	 * Select valid import and mapping presets for this user from DB.
	 *
	 * @return    array        array of preset records
	 */
	function getPresets() {

		$presets = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_rsuserimp_presets',
			'user_uid=' . intval($GLOBALS['BE_USER']->user['uid']),
			'',
			'title'
		);
		return $presets;
	}

	/**
	 * Manipulates import and mapping presets
	 *
	 * @param    array &$indata : array, passed by REFERENCE!
	 * @return    string    $content: HTML content
	 */
	function processPresets(&$inData) {

		$err = FALSE;
		$file = $inData['settings']['uploadfile'];

		// Save preset
		if (isset($inData['preset']['save'])) {
			$preset = $this->getPreset($inData['preset']['select']);
			// Update existing
			if (is_array($preset)) {
				unset($inData_temp['settings']['uploadfile']);
				unset($inData_temp['settings']['OK']);
				unset($inData['fieldmap']['OK']);

				if ($GLOBALS['BE_USER']->isAdmin() || $preset['user_uid'] === $GLOBALS['BE_USER']->user['uid']) {
					$inData_temp = $inData;
					$fields_values = array(
						'title' => $inData['preset']['savetitle'],
						'preset_data' => serialize($inData_temp)
					);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_rsuserimp_presets', 'uid=' . intval($preset['uid']), $fields_values);
					$msg = sprintf($GLOBALS['LANG']->getLL('f1.tab2.section.presets.save.created'), $preset['uid']);
				} else {
					$msg = $GLOBALS['LANG']->getLL('f1.tab2.section.presets.save.notOwner');
					$err = TRUE;
				}
			} else {
				// Insert new:
				$inData_temp = $inData;
				unset($inData_temp['settings']['uploadfile']);
				$fields_values = array(
					'user_uid' => $GLOBALS['BE_USER']->user['uid'],
					'title' => $inData['preset']['savetitle'],
					'preset_data' => serialize($inData_temp)
				);
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_rsuserimp_presets', $fields_values);
				$msg = sprintf($GLOBALS['LANG']->getLL('f1.tab2.section.presets.save.created'), $inData['preset']['savetitle']);
			}
		}

		// Delete preset:
		if (isset($inData['preset']['delete'])) {
			$preset = $this->getPreset($inData['preset']['select']);
			// Update existing
			if (is_array($preset)) {
				if ($GLOBALS['BE_USER']->isAdmin() || $preset['user_uid'] === $GLOBALS['BE_USER']->user['uid']) {
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_rsuserimp_presets', 'uid=' . intval($preset['uid']));
					$msg = sprintf($GLOBALS['LANG']->getLL('f1.tab2.section.presets.delete.deleted'), $preset['title'], $inData['preset']['select']);
					$inData['preset']['select'] = '0';
				} else {
					$msg = $GLOBALS['LANG']->getLL('f1.tab2.section.presets.delete.notOwner');
					$err = TRUE;
				}
			} else {
				$msg = $GLOBALS['LANG']->getLL('f1.tab2.section.presets.delete.noSelection');
				$err = TRUE;
			}
		}

		// Load preset
		if (isset($inData['preset']['load']) || isset($inData['preset']['merge'])) {
			$preset = $this->getPreset($inData['preset']['select']);
			// Update existing
			if (is_array($preset)) {
				$inData_temp = unserialize($preset['preset_data']);
				if (is_array($inData_temp)) {
					if (isset($inData['preset']['merge'])) {
						// Merge records in:
						if (is_array($inData_temp['record'])) {
							$inData['record'] = array_merge((array)$inData['record'], $inData_temp['record']);
						}
						// Merge lists in:
						if (is_array($inData_temp['list'])) {
							$inData['list'] = array_merge((array)$inData['list'], $inData_temp['list']);
						}
					} else {
						$msg = sprintf($GLOBALS['LANG']->getLL('f1.tab2.section.presets.load.loaded'), $preset['title'], $preset['uid']);
						$inData = array_merge($inData, $inData_temp);
						$inData['settings']['uploadfile'] = $file;
					}
				} else {
					$msg = $GLOBALS['LANG']->getLL('f1.tab2.section.presets.load.noData');
					$err = TRUE;
				}
			} else {
				$msg = $GLOBALS['LANG']->getLL('f1.tab2.section.presets.load.noSelection');
				$err = TRUE;
			}
		}

		if (strlen($msg)) {
			$content = array($msg, $err ? 3 : 1);
		}

		// clear the savetitle after processing the preset
		unset($inData['preset']['savetitle']);
		return $content;
	}

	/**
	 * Gets single import/mapping preset from DB.
	 *
	 * @param    integer        Preset record
	 * @return    array        Preset record, if any (otherwise false)
	 */
	function getPreset($uid) {
		list($preset) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_rsuserimp_presets', 'uid=' . intval($uid));
		return $preset;
	}

	/*****************************
	 *
	 * Generel Helper functions
	 *
	 *****************************/

	/**
	 * Returns first temporary folder of the user account (from $FILEMOUNTS)
	 *
	 * @return    string        Absolute path to first "_temp_" folder of the current user, otherwise blank.
	 */
	function userTempFolder() {
		//	if ($session['dropfile'] && \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr($session['dropfile']) && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($session['dropfile'],PATH_site.'uploads/tx_rsuserimp/') ) {
		foreach ($FILEMOUNTS as $filePathInfo) {
			$tempFolder = $filePathInfo['path'] . '_temp_/';
			if (@is_dir($tempFolder)) {
				return $tempFolder;
			}
		}
	}

	/**
	 * Create configuration save form
	 *
	 * @param    array        Form configuration data retrieved from GP data
	 * @param    array &$row : table row accumulation variable. This is filled with table rows.
	 * @return    void        Sets content in $this->content
	 */
	function makeSaveForm($inData, &$row) {

		// Presets:
		$row[] = '
				<tr class="tableheader bgColor5">
					<td colspan="2">' . $GLOBALS['LANG']->getLL('f1.tab2.section.presets', 1) . '</td>
				</tr>';

		$presets = $this->getPresets();

		$opt = array('');
		if (is_array($presets)) {
			foreach ($presets as $presetCfg) {
				$opt[$presetCfg['uid']] = $presetCfg['title'] . ' [' . $presetCfg['uid'] . ']';
				//	($presetCfg['public'] ? ' [Public]' : '').
				//	($presetCfg['user_uid']===$GLOBALS['BE_USER']->user['uid'] ? ' [Own]' : '');
			}
		}

		$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.presets.load', 1) . '</strong></td>
					<td>
						<class="tableheader bgColor4">' . $GLOBALS['LANG']->getLL('f1.tab2.section.presets.load.select', 1) . '</class><br/>
						' . $this->renderSelectBox('tx_rsuserimp[preset][select]', (isset($inData['preset']['select']) ?
				// JavaScript to check fields swapPresetSelectFields()
				$inData['preset']['select'] : ''), $opt, 'swapfield') . '
						<br/>
						<input type="submit" value="' . $GLOBALS['LANG']->getLL('f1.tab2.section.presets.load.load', 1) . '" name="tx_rsuserimp[preset][load]" ' . ($_POST['importNow'] ? 'disabled' : '') . '/>
						<input type="submit" value="' . $GLOBALS['LANG']->getLL('f1.tab2.section.presets.load.delete', 1) . '" name="tx_rsuserimp[preset][delete]" onclick="return confirm(\'' . $GLOBALS['LANG']->getLL('f1.tab2.section.presets.load.delete.sure', 1) . '\');" ' . ($_POST['importNow'] ? 'disabled' : '') . '/>
					</td>
				</tr>
				<tr class="bgColor4">
					<td><strong>' . $GLOBALS['LANG']->getLL('f1.tab2.section.presets.save', 1) . '</strong></td>
					<td>
						' . $GLOBALS['LANG']->getLL('f1.tab2.section.presets.save.label', 1) . '<br/>
						' . $GLOBALS['LANG']->getLL('f1.tab2.section.presets.save.name', 1) . '
						<input type="text" name="tx_rsuserimp[preset][savetitle]" value="' . htmlspecialchars(isset($inData['preset']['savetitle']) ? $inData['preset']['savetitle'] : '') . '" ' . ($_POST['importNow'] ? 'disabled' : '') . '/><br/>' .
			//	.$GLOBALS['LANG']->getLL('f1.tab2.section.presets.save.public',1).
			//	'<input type="checkbox" name="tx_rsuserimp[preset][public]" value="1"'.($inData['preset']['public'] ? ' checked="checked"' : '').' '.($_POST['importNow'] ? 'disabled' : '').'/><br/>
			'<div align="right"><input type="submit" value="' . $GLOBALS['LANG']->getLL('f1.tab2.section.presets.save.save', 1) . '" name="tx_rsuserimp[preset][save]" onclick="return swapPresetSelectFields();" ' . ($_POST['importNow'] ? 'disabled' : '') . '/></div>
					</td>
				</tr>';
	}

	/**
	 * Returns a selector-box
	 *
	 * @param    string $prefix : Form element name prefix
	 * @param    array $allValues : All possible values
	 * @param    array $postData : Current values selected
	 * @param    string $reverse : Alter behaviour
	 * @param    string $id : An identifier for the rendered selector
	 * @return    string        HTML select element
	 */
	function renderMultipleSelector($prefix, $allValues, $postData, $reverse = 0, $id = '') {

		if ($reverse) {
			$optValues = array();
			if (!empty($postData)) {
				$optValues = $postData;
			}
		} else {
			//normal behaviour
			$optValues = array();
			if (!empty($postData)) {
				while (list($k, $v) = each($postData)) {
					$optValues[$v] = $v;
				}
			}
		}

		// make box:
		$opt = array();
		reset($optValues);
		while (list($k, $v) = each($allValues)) {
			if (is_array($optValues)) {
				if ($reverse) {
					$sel = in_array($k, $optValues) ? ' selected="selected"' : '';
				} else {
					$sel = in_array($v, $optValues) ? ' selected="selected"' : '';
				}
			}
			if ($reverse) {
				$opt[] = '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
			} else {
				$opt[] = '<option value="' . htmlspecialchars($v) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
			}
		}
		return '<select id="' . $id . '" name="' . $prefix . '[]" multiple="multiple" ' . (($_POST['importNow'] || ((isset($this->inData['settings']['importUserType']) && ($this->inData['settings']['importUserType'] == 'TT')) && $id === 'importUserGroup')) ? 'disabled' : '') . ' size="' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(count($opt), 2, 6) . '">' . implode('', $opt) . '</select>';
	}

	/**
	 * Makes a selector-box from optValues
	 *
	 * $this->renderSelectBox('tx_rsuserimp[pagetree][levels]',$inData['pagetree']['levels'],$id,$JSfunction)
	 *
	 * @param    string        Form element name
	 * @param    string        Current selected value
	 * @param    array        Options to display (key/value pairs)
	 * @param    string        Optional ID value (needed for various javascript functions)
	 * @param    string        Optional JavaScript function needed for OnChange()
	 * @return    string        HTML select element
	 */
	function renderSelectBox($prefix, $value, $optValues, $id = '', $JSfunction = '') {

		$opt = array();
		$isSelFlag = 0;

		reset($optValues);
		while (list($k, $v) = each($optValues)) {
			$sel = (!strcmp($k, $value) ? ' selected="selected"' : '');
			if ($sel) $isSelFlag++;
			$opt[] = '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
		}
		if (!$isSelFlag && strcmp('', $value)) {
			$opt[] = '<option value="' . htmlspecialchars($value) . '" selected="selected">' . htmlspecialchars("['" . $value . "']") . '</option>';
		}
		return '<select ' . $JSfunction . ' id="' . $id . '" name="' . $prefix . '" ' . ($_POST['importNow'] ? 'disabled' : '') . '>' . implode('', $opt) . '</select>';
	}

	/**
	 * Garbage collection: deactivates aged out sessions and deletes no longer needed session data
	 *
	 * @param    integer $garbageCollectionTriggerTimer : ...
	 * @param    integer $rollbackSafetyTimespan : ...
	 * @return    void
	 */
	function gc($garbageCollectionTriggerTimer, $rollbackSafetyTimespan) {

		$now = mktime();

		if (TYPO3_DLOG) {
			GeneralUtility::devLog('Entering garbage collection routine at ' . strftime("%d.%m.%Y - %H:%M:%S", $now), 'rs_userimp', -1);
		}

		if ($garbageCollectionTriggerTimer == 0) {
			GeneralUtility::devLog('TriggerTimer disabled, exiting garbage collection', 'rs_userimp', 1);
			GeneralUtility::devLog('Leaving garbage collection routine at ' . strftime("%d.%m.%Y - %H:%M:%S", mktime()), 'rs_userimp', -1);
			return;
		} else {
			$garbageCollectionTriggerTimer = $garbageCollectionTriggerTimer * 24 * 60 * 60; //time in days
		}

		$rollbackData = $this->getRollbackDataSets();

		if (!empty($rollbackData)) {
			foreach ($rollbackData as $session) {
				if (TYPO3_DLOG) {
					GeneralUtility::devLog('Session ' . $session['uid'] . ' created ' . strftime("%d.%m.%Y - %H:%M:%S", $session['crdate']), 'rs_userimp', -1);
				}

				if ($rollbackSafetyTimespan == 0) {
					if (TYPO3_DLOG) {
						GeneralUtility::devLog('Rollback safety timer disabled', 'rs_userimp', -1);
					}
				} else {
					$ageOutTime = $session['crdate'] + 60 * $rollbackSafetyTimespan;
					if (TYPO3_DLOG) {
						GeneralUtility::devLog('Age out timer for session ' . $session['uid'] . ' set to ' . strftime("%d.%m.%Y - %H:%M:%S", $ageOutTime), 'rs_userimp', -1);
						GeneralUtility::devLog('Delete timer for session ' . $session['uid'] . ' set to ' . strftime("%d.%m.%Y - %H:%M:%S", $garbageCollectionTriggerTimer + $session['crdate']), 'rs_userimp', -1);
					}
				}

				if (($ageOutTime < $now) && $session['active']) {
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_rsuserimp_sessions', 'uid=' . $session['uid'], array('active' => '0'));
					GeneralUtility::devLog('Inactivated aged out session ' . $session['uid'], 'rs_userimp', 1);
				}
			}
		}
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,crdate,dropfile',
			'tx_rsuserimp_sessions',
			'user_uid=' . $GLOBALS['BE_USER']->user['uid'] . ' AND active=0',
			'uid DESC',
			''
		);

		if (!empty($result)) {
			foreach ($result as $session) {
				if (($session['crdate'] + $garbageCollectionTriggerTimer) <= $now) {
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_rsuserimp_sessions', 'uid=' . $session['uid'], array('deleted' => '1'));
					GeneralUtility::devLog('Deleted aged out session ' . $session['uid'], 'rs_userimp', 1);
					if ($session['dropfile'] && GeneralUtility::validPathStr($session['dropfile']) && GeneralUtility::isFirstPartOfStr($session['dropfile'], PATH_site . 'uploads/tx_rsuserimp/')) {
						// unlink (delete) associated drop file
						if (is_file($session['dropfile']) && @unlink($session['dropfile'])) {
							GeneralUtility::devLog('Deleted drop file ' . $session['dropfile'], 'rs_userimp', 1);
						} else {
							GeneralUtility::devLog('Unable to delete drop file ' . $session['dropfile'] . ' (file not found?).', 'rs_userimp', 3);
						}
					}
				}
			}
		}

		if (TYPO3_DLOG) {
			GeneralUtility::devLog('Leaving garbage collection routine at ' . strftime("%d.%m.%Y - %H:%M:%S", mktime()), 'rs_userimp', -1);
		}
		return;
	} // end gc
}