<?php
/**
 * Wordpress Form Builder Utility Class
 * 
 * A group of classes designed to make it easier and quicker to create forms 
 * within wordpress plugins for the admin section. Using this class should hopefully 
 * reduce development and debugging time.
 * 
 * This code is very much in alpha phase, and should not be distributed with plugins 
 * other than by Dan Harrison.
 * 
 * @author Dan Harrison (http://www.danharrison.co.uk)
 *
 * Version History
 * 
 * V0.01 - Initial version released.
 *
 */


/**
 * Class that represents a HTML form for the Wordpress admin area.
 */
if (!class_exists('FormBuilder')) { class FormBuilder {

	/**
	 * A list of the elements to go in the HTML form. 
	 * @var Array
	 */
	private $elementList;
	
	/**
	 * The form name, used for the name attribute of the form.
	 * @var String The name of the form.
	 */
	private $formName;
	
	/**
	 * A list of the buttons to go in the HTML form. 
	 * @var Array
	 */
	private $buttonList;	
		
	/**
	 * The text used on the submit button.
	 * @var String The text used on the submit button.
	 */
	private $submitlabel;
	
	/**
	 * Constructor
	 */
	function FormBuilder($name = false) {
		$this->elementList = array();
		$this->buttonList = array();
		$this->setSubmitLabel(false);
		$this->formName = $name;
	}
		
	/**
	 * Set the label for the submit button to the specified text. If the specified label is blank, 
	 * then "Update Settings" is used as a default.
	 * 
	 * @param $label The text to use for the submit button.
	 */
	function setSubmitLabel($label)
	{
		// Only update if $label is a valid string, otherwise set default.
		if ($label)
			$this->submitlabel = $label;
		else 
			$this->submitlabel = "Update Settings";
	}
	
	/**
	 * Add the specified form element to the internal list of elements to put on the form.
	 * @param $formElement A <code>FormElement</code> object to add to the form.
	 */
	function addFormElement($formElement) {
		array_push($this->elementList, $formElement);
	}
	
	/**
	 * Add a button to be added to the end of the form.
	 * @param $buttonName The name of the button.
	 * @param $buttonText The text to be used for the button itself.
	 */
	function addButton($buttonName, $buttonText) {
		$this->buttonList[$buttonName] = $buttonText;
	}
	
	/**
	 * Generates the HTML for the form object.
	 * @return String The HTML for this form object.
	 */
	function toString() {
		
		$namestring = "";
		if ($this->formName) {
			$namestring = " name=\"$this->formName\"";	
		}
		
		// Start form
		$resultString = "\n".'<form '.$namestring.' method="POST" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">'."\n";
		$resultString .= '<table class="form-table">'."\n";
		
		// Now add all form elements
		foreach ($this->elementList as $element)
		{
			if ($element->type == 'hidden') {
				continue;
			}

			// Render form element				
			$resultString .= $element->toString();			
		}
		
		$resultString .= "</table>\n";
		
		// Button area
		$resultString .= '<p class="submit">'."\n";
		
		// Add submit button
		$resultString .= "\t".'<input class="button-primary" type="submit" name="Submit" value="'.$this->submitlabel.'" />'."\n";
		
		// Add remaining buttons
		foreach ($this->buttonList as $buttonName => $buttonLabel) {
			$resultString .= "\t<input type=\"submit\" name=\"$buttonName\" value=\"$buttonLabel\" />\n";		
		}
				
		// Hidden field to indicate update is happening
		$resultString .= "\t".'<input type="hidden" name="update" value="update" />'."\n";
				
		// Add any extra hidden elements
		foreach ($this->elementList as $element)
		{
			// Leave all hidden elements until the end.
			if ($element->type == 'hidden') {	
				$resultString .= "\t".'<input type="hidden" name="'.$element->name.'" value="'.$element->value.'" />'."\n";
			}
		}		
		
		$resultString .= '</p>'."\n";
							
		// End form
		$resultString .= "\n</form>\n";
		
		return $resultString;
	}
}


/**
 * Class that represents a HTML form element for the Wordpress admin area.
 */
class FormElement {
	
	/**
	 * The different types of form element, including <code>select</code>, <code>text</code>, 
	 * <code>checkbox</code>, <code>hidden</code> and <code>textarea</code>.  
	 *    
	 * @var String The type of the form element.
	 */
	public $type;	
	
	/**
	 * The current value of this form element.
	 * @var String The current value of this form element.
	 */
	public $value;
	
	/**
	 * The label for this form element.
	 * @var String The descriptive label for this form element.
	 */
	public $label;
	
	/**
	 * The <code>name</code> of the form element, as in the HTML attribute name.
	 * @var String The HTML attribute name of this element.
	 */
	public $name;
	
	/**
	 * The description of this form element, that typically goes after the element.
	 * @var String The description of this form element.
	 */
	public $description;
	
	/**
	 * Boolean flag to determine if the field is a form field (which if true, automatically adjusts the entry field to fit the screen size)
	 * @var Boolean True if this is a form field, false otherwise.
	 */
	public $isformfield;
	
	/**
	 * The number of rows to use in a text area.
	 * @var Integer the number of rows to use in a text area.
	 */
	public $textarea_rows;

	/**
	 * The number of columns to use in a text area.
	 * @var Integer the number of columns to use in a text area.
	 */	
	public $textarea_cols;
	
	/**
	 * The list of items used in an HTML select box.
	 * @var Array
	 */
	public $select_itemlist;
	
	/**
	 * The label for a checkbox.
	 * @var String The text that goes next to a checkbox.
	 */
	public $checkbox_label;
	
	/**
	 * The CSS class to set the HTML form element to.
	 * @var String The CSS class to set teh HTML form element to.
	 */
	public $cssclass;
	
	/**
	 * HTML rendered after the form element, but before the description.
	 * @var String The HTML used to go after the form element. 
	 */
	public $afterFormElementHTML;		
	
	/**
	 * Constructor
	 */
	function FormElement($name, $label) {
		$this->name  = $name;
		$this->label = $label;
		$this->type = "text";
		
		// Set defaults for text area
		$this->textarea_rows = 4;
		$this->textarea_cols = 70;		
		
		// A formfield by default
		$this->isformfield = true;
	}	
	
	/**
	 * Sets this element to be a checkbox.
	 */
	function setTypeAsCheckbox($labeltext = false) {
		$this->type = "checkbox";
		$this->checkbox_label = $labeltext;
		
		// Formfield doesn't work if a checkbox
		$this->isformfield = false;
	}
	
	
	/**
	 * Set the type of this element to be a text area with the specified number of rows and columns. 
	 * @param $rows The number of rows for this text area, the default is 4.
	 * @param $cols The number of columns for this text area, the default is 70.
	 */
	function setTypeAsTextArea($rows = 4, $cols = 70) {
		$this->type = "textarea";
		$this->textarea_cols = $cols;
		$this->textarea_rows = $rows;
	}
	
	/**
	 * Sets this element to be a hidden element.
	 */
	function setTypeAsHidden() {
		$this->type = "hidden";
	}
	
	/**
	 * Sets the type to be static, where the value is used rather than a normal form field.
	 */
	function setTypeAsStatic() {
		$this->type = "static";
	}
		
	/**
	 * Sets the element type to be a combo box (A SELECT element in HTML). The specified list of 
	 * items can be a simple list (e.g. x, y, z), or a list of values mapping to a description 
	 * (e.g. a => 1, b => 2, c => 3). However, in the case of a simple list, the values will be 
	 * interpreted as their actual index e.g. (0 => x, 1 => y, 2 => z). If the value of this element
	 * matches one of the options in the list, then that option will be selected when the HTML is rendered.
	 * 
	 * @param $itemList The list of items to set in the combo box.
	 */
	function setTypeAsComboBox($itemList) {
		$this->type = "select";
		$this->select_itemlist = $itemList;
	}

	
	/**
	 * Render the current form element as an HTML string.
	 * @return String This form element as an HTML string.
	 */
	function toString() {
		
		// Formfield class, on by default
		$trclass = ' class="form-field"';
		if (!$this->isformfield) {
			$trclass = "";
		}
		
		$elementString = "<tr valign=\"top\"$trclass>\n";

		// The label
		$elementString .= "\t".'<th scope="row"><label for="'.$this->name.'">'.$this->label.'</label></th>'."\n";		
		
		// Start the table data for the form element and description 
		$elementString .= "\t<td>\n\t\t";

		if ($this->cssclass) {
			$elementclass = "class=\"$this->cssclass\"";
		}
		
		// The actual form element
		switch ($this->type)
		{
			case 'select':
				$elementString .= "<select name=\"$this->name\" $elementclass>";
				foreach ($this->select_itemlist AS $value => $label)
				{
					$htmlselected = "";
					if ($value == $this->value) {
						$htmlselected = ' selected="selected"';
					}
					
					$elementString .= "\n\t\t\t";
					$elementString .= '<option value="'.$value.'"'.$htmlselected.'>'.$label.'&nbsp;&nbsp;</option>';
				}
				$elementString .= "\n</select>";
				break; 
			
			case 'textarea':
				$elementString .= "<textarea name=\"$this->name\" rows=\"$this->textarea_rows\" cols=\"$this->textarea_cols\" $elementclass>$this->value</textarea>";  
				break; 
				
			case 'checkbox':
				$checked = "";
				if ($this->value == 1 || $this->value == "on")
					$checked = ' checked=checked';
				
				$elementString .= "<input type=\"checkbox\" name=\"$this->name\" $checked $elementclass/> $this->checkbox_label";  
				break;
							
			/* A static type is just the value field. */
			case 'static':
				$elementString .= $this->value;
				break;
				
			/* The default is just a normal text box. */
			default:
				// Add a default style
				if (!$this->cssclass) {
					$elementclass = 'class="regular-text"';
				}
					
				$elementString .= "<input type=\"text\" name=\"$this->name\" value=\"$this->value\" $elementclass/>";
				break; 
		}
		
		$elementString .= "\n";
				
		// Add extra HTML after form element if specified
		if ($this->afterFormElementHTML) {
			$elementString .= $this->afterFormElementHTML . "\n";
		}
		
		// Only add description if one exists.
		if ($this->description) {
			$elementString .= "\t\t".'<span class="setting-description"><br>'.$this->description.'</span>'."\n";
		}
		
		$elementString .= "\t</td>\n";
		
		// All done
		$elementString .= '</tr>'."\n";
		return $elementString;
	}
}}

?>