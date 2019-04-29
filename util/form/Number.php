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
require_once 'Element.php';

class PLF_Number extends PLF_Element
{
  var $minValue;
  var $maxValue;

  public function __construct($name, $label, $minValue, $maxValue, $required) {
    PLF_Element::PLF_Element($name, $label, $required);
    $this->minValue = $minValue;
    $this->maxValue = $maxValue;
  }

  public function PLF_Number($name, $label, $minValue, $maxValue, $required) {
    self::__construct($name, $label, $minValue, $maxValue, $required);
  }

  // MyForm class will set tabindex before calling this method
  function render($tabIndex) {
    $length = strlen($this->maxValue) + 2;
    $maxLength = $length + 20;
    $toReturn =' <input type="text" tabindex="'.$tabIndex.'" name="'.$this->getName().'" id="'.$this->getName().'" size="'.$length.'" maxlength="'.$maxLength.'"';
    if (strlen($this->getValue()) > 0) {
      $toReturn .= ' value="'.htmlspecialchars($this->getValue()).'"';
    }
    $toReturn .=' '.$this->attribute.' />';
    return $toReturn;
  }


  function validate () {
  // call standard parent validation method (required field)
  // then do own validation
    $this->value = trim($this->value);
    if (parent::validate()) {
      if (strlen($this->value) > 0) {

        if (!is_numeric($this->value)) {
          $this->requiredText = 'please enter a number';
          $isValid = false;
        }
        else if ($this->value > $this->maxValue || $this->value < $this->minValue) {
          $this->requiredText = 'please enter a number between '.$this->minValue.' and '.$this->maxValue;
          $isValid = false;
        }
        else {
          // must cast to double here since that's what is used for the < > check above
          // if we don't do this, $this->value (a string) may hold a number larger
          // than the max, but still pass the greater than max check since the number
          // holds too many significant digits.
          // ex: (1000.00000000000001 > 1000) == false while
          //     (1000.0000000000001 > 1000) == true
          // and we don't want this value to end up on the database, since it's larger
          // then the max.
          $this->value = (double)$this->value;
          return true;
        }
      }
      else {
        return true;
      }
    }
    else {
      return false;
    }
  }

}
?>
