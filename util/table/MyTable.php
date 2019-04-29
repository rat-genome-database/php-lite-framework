<?php
/*
* ====================================================================
*
* License:      GNU General Public License
*
* Copyright (c) 2007 Centare Group Ltd.  All rights reserved.
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
class PLF_Table {

  var $tableAttributes;
  var $rows;
  var $alternating;
  var $doAlternating = false;
  
  var $headingCSSClass;
  var $evenRowCSSClass = 'tableEvenRow';
  var $oddRowCSSClass = 'tableOddRow';
  
  var $doColumnStyling = false;
  var $columnCSSClasses;
  
  var $caption;
  var $captionCSSClass;
  
  var $heading;
  
  var $numCells;
  
  var $invisibleIfEmpty;
  
  var $rowsArray = array();
  
  

  // construct a table
  // $heading can be a series of strings to be used as the heading of the table
  // or an array of strings
  public function __construct($heading = null) {
    $this->heading = $heading;
    $this->numCells = count($heading); // save off count for the spacer
    
  }

  public function PLF_Table($heading = null) {
    self::__construct($heading);
  }

  // set the attributes of the table
  // ex: setAttributes('width="100%" border="0"');
  function setAttributes($attributes) {
    $this->tableAttributes = $attributes;
  }
  
  function setInvisibleIfEmpty() {
    $this->invisibleIfEmpty = true;
  }

  /**
   * Specifies that rows in the table will be css styled
   * to achive different colors for alternating rows.
   * 
   * To specify the specific css classname to use for alternating
   * rows, use:
   * 
   * setOddRowsCSSClass()
   * and
   * setEvenRowsCSSClass()
   * 
   * If you don't call these, the css classnames will default
   * to "tableOddRow" and "tableEvenRow", respectively.
   */
  function setAlternateRowStyles() {
    $this->doAlternating = TRUE;
  }

  /**
   * Set whether to do column styling.
   * Column CSS classnames can be set using
   * setColumnCSSClasses()
   * or will default to "col1", "col2" etc.
   * 
   * Styles can then be set on columns as in the following CSS example:
   * 
   * table.tableCSSClassName td.col1 {
   *   text-align: right;
   * }
   */
  function setDoColumnStyling($bool = TRUE) {
     
     $this->doColumnStyling = $bool;
  }
    
   /**
   *  Takes an array or multiple arguments to set  
   *  an array of names used for the CSS class names
   *  applied to the <td> elements of the table by column,
   *  if not set the default is for columns to have class names
   *  "col1" , "col2" etc. 
   */
  function setColumnCSSClasses($columnClassArray){
    if (!is_array($columnClassArray)) {
      $columnClassArray = func_get_args();
    } 
    $this->columnCSSClasses = $columnClassArray;
  }
  
  /** 
   * Sets a CSSClass name for the table caption element.
   * Only really needed if you want multiple tables of the same class
   * to have different caption formats.
   * Otherwise a CSS selector like "table.tableCSSClassName caption" 
   * provides specificity.
   */ 
  function setCaptionCSSClass($captionCSSClassName) {
    $this->captionCSSClass = $captionCSSClassName;
  }
  
  /** 
   * Sets a caption for the table. 
   * As a convenience takes an optional second argument to
   * set the caption CSS class name in the one function call.
   * Otherwise the caption CSS class name can be set by a seperate call to 
   * setCaptionCSSClass($className) 
   */
  function setCaption($caption, $captionCSSClassName=null){
    if (isset($captionCSSClassName)){
      $this->setCaptionCSSClass($captionCSSClassName);
    }
    
    if (isset($this->captionCSSClass)) {
      $this->caption = '<caption class="'.$this->captionCSSClass.'">'.$caption.'</caption>';
    }
    else {
      $this->caption = '<caption>'.$caption.'</caption>';  
    }
    
  }

  /**
   * Set the desired css classname to use for the 
   * heading row of the table.  Leave blank to default
   * to "tableHeading"
   */
  function setHeadingCSSClass($className = 'tableHeading') {
    $this->headingCSSClass = $className;
  }
  
  /**
   * Set the css class name for even rows of the table
   * 
   * Must call setAlternateRowStyles() to activate
   * 
   * Defaults to tableEvenRow so if you just want to use 
   * that classname, don't bother calling this method
   */
  function setEvenRowsCSSClass($className) {
    $this->evenRowCSSClass = $className;
  }

  /**
   * Set the css class name for odd rows of the table
   * 
   * Must call setAlternateRowStyles() to activate
   * 
   * Defaults to tableOddRow so if you just want to use 
   * that classname, don't bother calling this method
   */
  function setOddRowsCSSClass($className) {
    $this->oddRowCSSClass = $className;
  }

  function addSpacer($value = '&nbsp;') {
    $this->rows .= '<tr><td colspan="'.$this->numCells.'">'.$value.'</td></tr>';
  }
  /** 
   *  Add a row to the table
   * 
   *  row can be a series of values, or a single array holding
   *  multiple values - each value will be a cell in the row
   */
  function addRow($row) {
    
    if ($this->doAlternating) {
      $this->alternating = !($this->alternating);
      if ($this->alternating) {
        $this->rows .= '<tr class="'.$this->oddRowCSSClass.'">';
      }
      else {
        $this->rows .= '<tr class="'.$this->evenRowCSSClass.'">';
      }
    }
    else {
      $this->rows .= '<tr>';
    }

    if (is_array($row)) {
      $rowArray = $row;
    }
    else {
      $rowArray = func_get_args();
    }
    
    // also save off the row array for the csv output
    // note, addSpacer does not do this, so we don't have spacers
    // in csv output.
    $this->rowsArray[] = $rowArray;
    
    $columnNumber = 0;
    foreach ($rowArray as $element) {
      $columnNumber++;
      if ($this->doColumnStyling) {
        if (isset($this->columnCSSClasses)){
          // the array will be zero indexed, but column default class names will be indexed from 1
          $tdTag = '<td class="'.$this->columnCSSClasses[$columnNumber-1].'">';
        }
        else {
          $tdTag = '<td class="col'.$columnNumber.'">';
        }
      }
      else {
        $tdTag = '<td>'; 
      }
      if (isReallySet($element)) {
        $this->rows .= $tdTag;
        $this->rows .= $element;
        $this->rows .= '</td>';
      }
      else {
        $this->rows .= '<td>&nbsp;</td>';
      }
    }
    $this->numCells = count($rowArray); // save off count for the spacer
    $this->rows .= '</tr>';
  }

  /**
   * Return a csv representation of the table. This function will set
   * the headers appropriately for a spreadsheet content type so that
   * the browser will interpret the stream correctly.
   * 
   * $withHeader indicates if the header of the table should
   * be output as the first row in the csv stream
   * $fieldSeparator the desired field separator, (defaults to comma)
   * $lineSeparator the desired line separator, (defaults to \n)
   * $filename the desired filename that will be defaulted if user chooses
   * to save the output
   */
  function toCSV($withHeader = true, $fieldSeparator = ',', $lineSeparator = "\n", $filename = 'output.csv', $quoteFields = true) {
    Header('Cache-Control:');
    Header('Pragma:');
    header("Content-type: application/vnd.ms-excel");
    header("Content-disposition: attachment; filename=$filename");    
    
    $toReturn = '';
    if ($withHeader) {
      if ($quoteFields) {
        // to work with php4,
        // can't use ($row as &$field) (this is only avail in php5)
        // so have to use $row[$key] = to change the current array values
        foreach ($this->heading as $key=>$field) {
          $this->heading[$key] = '"'.$field.'"'; 
        }
      }
      $toReturn .= implode($fieldSeparator, $this->heading);
      $toReturn .= $lineSeparator;
    }
    
    foreach ($this->rowsArray as $row) {
      if ($quoteFields) {
        // to work with php4,
        // can't use ($row as &$field) (this is only avail in php5)
        // so have to use $row[$key] = to change the current array values
        foreach ($row as $key=>$field) {
          $row[$key] = '"'.$field.'"'; 
        }
      }
      $toReturn .= implode($fieldSeparator, $row);
      $toReturn .= $lineSeparator;
    }
    return $toReturn;
  }

  /**
   * return the html representation of the table
   */
  function toHtml() {
    if ($this->invisibleIfEmpty && empty($this->rows)) {
      return;
    }
    
    $toReturn = '<table '.$this->tableAttributes.'>';
    
    if (isset($this->caption)) {
      $toReturn .= $this->caption; 
    }

    if (isset($this->heading)) {
      if (isset($this->headingCSSClass)) {
        $toReturn .= '<tr class="'.$this->headingCSSClass.'">';
      }
      else {
        $toReturn .= '<tr>';
      }
      foreach ($this->heading as $element) {
        
        $toReturn .= '<th>';
        $toReturn .= $element;
        $toReturn .= '</th>';
      }
      $toReturn .= '</tr>';
    }
    
    $toReturn .= $this->rows;
    $toReturn .= '</table>';
    
    return $toReturn;
  }
}
?>
