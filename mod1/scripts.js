<!--
	/***************************************************************
	 * Copyright notice
	 * 
	 * (c) 2004 macmade.net
	 * All rights reserved
	 * 
	 * This program is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License as
	 * published by the Free Software Foundation; either version 2
	 * of the License, or (at your option) any later version.
	 * 
	 * This script is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 * 
	 * The GNU General Public License can be found at
	 * http://www.gnu.org/copyleft/gpl.html
	 * 
	 * This copyright notice MUST APPEAR in all copies of the script!
	 **************************************************************/
	
	/**
	 * JavaScript functions.
	 * 
	 * @author		Jean-David Gadina / macmade.net (info@macmade.net)
	 * @version		2.0
	 */
	/**
	 * toggle session items
	 *
	 * @param id 		the id of the session item to hide or show
	 * @param single	true to show only one item at a time, false the open as many as you want
	 */

	function toggleSession(id) {	
		if(document.getElementById('rs_userimp_be_rollbacksession_'+id).style.display == 'none') {
			toggleAll(id, false);	
			showHideSession(id, true);
		}
		else {
			showHideSession(id, false);
		}			
	}

	/**
	 * shows or hides a session item at a time depending on the given status
	 *
	 * @param id 		the id of the session item to hide or show
	 * @param status	true to show the item, false to hide it
	 */

	function showHideSession(id, status) {
		var session_id= 'rs_userimp_be_rollbacksession_'+id; //answer
		if(status) {
			if(navigator.appName == "Microsoft Internet Explorer") {
				document.getElementById(session_id).style.display = 'inline';
			} 
			if(navigator.appName == "Netscape") {
				document.getElementById(session_id).style.display = 'table-row';
			}		
		} else {
			document.getElementById(session_id).style.display = 'none';	
		}
	}

	/**
	 * shows or hides all session items with one click
	 *
	 * @param mode	true to show the items, false to hide them
	 */

	function toggleAll(id, mode) {
		var num=document.getElementsByName("sessionrow").length;
		for(i = 0; i < num; i++) {
			showHideSession(i, mode);
		}				
	}	

	/**
	 * Change row bgColor.
	 * 
	 * This function change the background color for the specified row.
	 * The color will depend of the given action (rollover or click).
	 * 
	 * @param		row					The row to process
	 * @param		id					The row id
	 * @param		action				The action to process
	 * @param		color1				The new color
	 * @param		color2				The old color
	 * @return		Nothing
	 */
	var rows = new Array();

	function setRowColor(row,i,action,color1,color2) {
		var ttt = 'sessionrow_'+i;
		var oldcol = document.getElementById(ttt).bgColor.toUpperCase();
		if (action == "click") {
			row.bgColor = color1;	
			toggleSession(i);
		} 
		if (action == "out") {
			if (row.bgColor == oldcol) {
				row.bgColor = color1;
			} else {
				row.bgColor = color1;
			}
		} 
		if (action == "over") {
			row.bgColor = color1;
		}
	}

	/**
	 * Select / Unselect all checkboxes.
	 * 
	 * This function select or unselect all checkboxes in the
	 * specified group.
	 * 
	 * @param		field				The field group to process
	 * @return		Nothing
	 */
	var check = 0;

	function checkBoxList(field) {
		check = (check == 0) ? 1 : 0;
		for (i = 0; i < field.length; i++) {
			field[i].checked = check;
		}
	}
	
//-->
