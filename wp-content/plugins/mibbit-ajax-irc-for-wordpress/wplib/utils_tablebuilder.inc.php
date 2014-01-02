<?php
/**
 * Wordpress Table Builder Utility Class
 * 
 * A group of classes designed to make it easier and quicker to create tables 
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
 * Class that represents a HTML table for the Wordpress admin area.
 */
if (!class_exists('TableBuilder')) { class TableBuilder {
	
	/**
	 * An array of HTML attributes to apply to the table.
	 * @var Array A list of (attribute name => attribute value) pairs.
	 */
	public $attributes;
	
	/**
	 * The internal list of columns.
	 * @var Array
	 */
	private $columnList;
	
	/**
	 * The internal list of rows.
	 * @var Array
	 */
	private $rowList;
	
	/**
	 * Constructor
	 */
	function TableBuilder() {
		$this->columnList = array();
		$this->rowList = array();
	}
	
	/**
	 * Add the specified column to the table builder.
	 * @param $column The column to add to the table.
	 */
	function addColumn($column) {
		array_push($this->columnList, $column);
	}
	
	/**
	 * Add the specified row to the table builder.
	 * @param $column The row to add to the table.
	 */
	function addRow($row) {
		array_push($this->rowList, $row);
	}	
	
	/**
	 * Generates the HTML for the table object.
	 * @return String The HTML for this table object.
	 */
	function toString() {	
		
		// Determine attributes and add them to the end of the table
		$attributeString = "";
		if (count($this->attributes) > 0) {
			foreach ($this->attributes as $aname => $avalue) {
				$attributeString .= "$aname=$avalue ";
			}
		}
		
		$resultString = "\n<table class=\"widefat\" $attributeString>";
		
		// Print the column header and footer
		$columnHeader = "\t<tr>";
		foreach ($this->columnList as $columnObj) {
			$columnHeader .= "\n\t\t".$columnObj->toHeaderString()."";
		}
		$columnHeader .= "\n\t</tr>";
		
		// Use same header for both header and footer.
		$resultString .= "\n<thead>\n$columnHeader\n</thead>";
		$resultString .= "\n<tfoot>\n$columnHeader\n</tfoot>";
		
		// Do the table body
		$resultString .= "\n<tbody>";
		
		// Handle columns for each row
		foreach ($this->rowList as $rowdata)
		{
			$resultString .= "<tr>";
			
			// Use columns to determine order of data in table
			foreach ($this->columnList as $columnObj)
			{
				$celldata = "";
				
				// If there's matching data for this column, add it to cell, 
				// otherwise leave the cell empty.
				if (isset($rowdata[$columnObj->columnKey])) {  
					$celldata = $rowdata[$columnObj->columnKey];
				}
				
				// Add HTML				
				$resultString .= $columnObj->toCellDataString($celldata);
			}
			
			$resultString .= "</tr>";
		}
		
		// Close remaining tags
		$resultString .= "\n</tbody>";
		$resultString .= "\n</table><br/>";
		return $resultString;
	}
		
}

/**
 * Class that represents a table column for use with <code>TableBuilder</code>.
 */
class TableColumn {
	
	/**
	 * The table title for this column. 
	 * @var String
	 */
	public $columnTitle;
	
	/**
	 * The string that represents this column.
	 * @var String
	 */
	public $columnKey;
	
	/**
	 * The HTML class associated with a table header cell. 
	 * @var String
	 */
	public $headerClass;
	
	/**
	 * The HTML class associated with an individual table data cell.
	 * @var String
	 */
	public $cellClass;
		
	/**
	 * Constructor creates a column in the table using the specified column title.
	 * @param $columnTitle The title of the column as used in the HTML.
	 * @param $columnKey A key used to refer to the column.
	 */
	function TableColumn($columnTitle, $columnKey) {
		$this->columnTitle = $columnTitle;
		$this->columnKey = $columnKey;
	}
	
	/**
	 * Generates the HTML for the header of this <code>TableColumn</code> object.
	 * @return String The HTML for the header of this <code>TableColumn</code> object.
	 */
	function toHeaderString()
	{		
		$classdata = "";
		if ($this->headerClass) {
			$classdata = 'class="'.$this->headerClass.'"';
		}
		
		$returnString = '<th id="'.$this->columnKey.'" scope="col"'.$classdata.'>'.$this->columnTitle.'</th>';		
		return $returnString;
	}
	
	/**
	 * Converts the specified data into a correctly formatted cell data, 
	 * using the style associated with this column.
	 * @param $data The data string to insert between &lt;td&gt;&lt;/td&gt; tags.
	 * @return String The HTML for the correctly formatted cell data.
	 */
	function toCellDataString($data)
	{
		$classdata = "";
		if ($this->cellClass) {
			$classdata = 'class="'.$this->cellClass.'"';
		}
		
		$returnString = '<td '.$classdata.'>'.$data.'</td>';		
		return $returnString;		
	}
}}

?>