/*
* ====================================================================
*
* License:      GNU General Public License
*
* Copyright (c) 2005 Centare Group Ltd.  All rights reserved.
*
* This file is part of PHP Lite Framework
*
* PHP Lite Framework is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2.1
* of the License, or (at your option) any later version.
*
* PHP Lite Framework is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* Please refer to the file license.txt in the root directory of this
* distribution for the GNU General Public License or see
* http://www.gnu.org/licenses/lgpl.html
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
* ====================================================================
*
*/

//
// plf.js Java script include file. 
// Included in the framework automatically from internalFunctions.php file. 
//

function toggle_visibility(id) {
var e = document.getElementById(id);
if(e.style.display == 'none')
e.style.display = 'block';
else
e.style.display = 'none';
}

function toggle_visibility_sa(id) {
var e = document.getElementById(id);
if(e.style.display == 'none')
Effect.Appear(id);
else
Effect.Fade(id);
}

 /** 
  *
  * Function called from makeAjaxCheckbox() from frameworkFunctions.php to update a div tag from
  * an checkbox seletion. Needs prototype.js library included in http://script.aculo.us/
  * See: http://www.prototypejs.org/
  * 
  **/       
function ajaxCheckbox(url, div) {

  var myAjax = new Ajax.Updater(
					{success: div}, 
					url, 
					{
						method: 'post'
					}
	);
}

 /** 
  *
  * Function called from makeAjaxSelect() from frameworkFunctions.php to update a div tag from
  * an Ajax select drop down list. Needs prototype.js library included in http://script.aculo.us/
  * See: http://www.prototypejs.org/
  * 
  **/      
function ajaxSelect(url, div, controlName, paramName) {
  
  var pars; 
  if (paramName != '') {
    var selectedValue=controlName.value;
    pars = paramName+'='+selectedValue;
  }
  
  var myAjax = new Ajax.Updater(
					{success: div}, 
					url, 
					{
						method: 'post', 
						parameters: pars
					}
	);
					
}
       