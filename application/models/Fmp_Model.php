<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	This is a new base model class for use with the Fmp library.  
 *
 *	Subclasses should define a layout and a field map (see examples).  Once that's done, you can
 *	use the varios model methods to do handy and useful things.
 *
 *	(Exaplain the field map, especially portals and the _r convention.)
 */
class Fmp_Model {
	protected $layout = '';
	protected $layout_data = false;
	protected $fieldMap = array();
	protected $ci = null;
	protected $fmp = null;
	
	function __construct() {
		$this->ci = get_instance();
		$this->fmp = $this->ci->fmp;		
	}
	
	/**
	 * __get
	 *
	 * Allows models to access CI's loaded classes using the same
	 * syntax as controllers.
	 *
	 * @access private
	 */
	function __get($key) {
		return $this->ci->$key;
	}

	/**
	 *	Takes a FileMaker_Record object, and turns it into a stdClass with attributes mapped from
	 *	the model's fieldMap.
	 *
	 *	@param FileMaker_Record $rec The FileMaker record to convert
	 *	@param array $fieldMap If null, use the model's field map.
	 */
	function recordToObject(FileMaker_Record $rec, $fieldMap=null) {
		$retval = new stdClass;
		$rowLayout = null;
		
		if (empty($fieldMap))
			$fieldMap = $this->fieldMap;
			
		foreach ($fieldMap as $fmFieldName=>$objFieldName) {
			if (empty($objFieldName)) {
				continue;
			} elseif (is_scalar($objFieldName)) {
				if (substr($objFieldName,-2) == '_r') {
					// Handle multiple repititions for fields ending in _r
					if (!$rowLayout)
						$rowLayout = $rec->getLayout();
					$fieldInfo = $rowLayout->getField($fmFieldName);
					if ($fieldInfo instanceof FileMaker_Field)
						$reps = $fieldInfo->getRepetitionCount();
					else
						$reps = 0;
					$tmpArray = array();
					for ($index=0; $index<$reps; $index++)
						$tmpArray[] = $rec->getField($fmFieldName, $index);
					$retval->{$objFieldName} = $tmpArray;
				} else
					$retval->{$objFieldName} = $rec->getField($fmFieldName);
			} else {
				$portal = array();
				$portalName = $objFieldName['name'];
				$fieldList = $objFieldName['fields'];
				
				$relatedSet = $rec->getRelatedSet($fmFieldName);
				if (is_array($relatedSet)) {
					foreach($relatedSet as $oneRelatedRec)
						$portal[] = $this->recordToObject($oneRelatedRec, $fieldList);
				}
				$retval->{$portalName} = $portal;
			}
		}
		
		$retval->_recid = $rec->getRecordId();
		// We tend to run out of memory when we do this
		// $retval->_fmrec = $rec;
		// $retval->_fieldmap = $fieldMap;
		
		$retval = $this->postRecordDecode($retval);
		
		return $retval;
	}

	/**
	 *	Takes an associative array of fields (from a form, for example) and translates all of
	 *	the keys into their FileMaker version, using the current field map.
	 *
	 *	@deprecated
	 *	@param array $fields An associative array of fields
	 *	@return array An associative array of FileMaker fieldname=>value.
	 */
	function arrayToRecord($fields) {
		$record = array();
		
		foreach ($fields as $fieldName=>$fieldValue) {
			$fmName = array_search($fieldName, $this->fieldMap);
			if ($fmName !== false) {
				$record[$fmName] = $fieldValue;
			}
		}
		
		return $record;
	}
	
	/**
	 *	Adds criteria to a FileMaker find command, according to the current layout.
	 *
	 *	@param FileMaker_Command $cmd The find command you wish to refine.
	 *	@param array $criteria The niceName=>value criteria to add to the search.
	 *	@param array $fieldMap The field map to use (optional).  Assumes $this->layout.
	 *	@return FileMaker_Command $cmd
	 */
	public function addCriteria($cmd, $criteria, $fieldMap=null) {
		if (empty($criteria))
			$criteria = array();
		if (empty($fieldMap))
			$fieldMap = $this->fieldMap;

		foreach ($criteria as $fieldName=>$fieldValue) {
			$fmName = array_search($fieldName, $fieldMap);
			if ($fmName !== false)
				$cmd->addFindCriterion($fmName, $fieldValue);
		}
		
		return $cmd;
	}

	/**
	 *	Adds a sort order to the find results.  The order is defined as an associative array 
	 *	where <code>fieldName=>"+"/"-" . precedence</code>.  "+" indicates ascending, and "-"
	 *	indicates descending.  The precedence indicates the order in which the sorting is applied.
	 *	For example, the following would sort your return results by date descending, followed by
	 *	quantity ascending:
	 *
	 *	<pre>array( 'date'=>-1, 'qty'=>+2 )</pre>
	 *
	 *	@param FileMaker_Command_Find $cmd The FileMaker command to change
	 *	@param array $order The order specification
	 *	@return FileMaker_Command_Find The $cmd
	 */
	public function addOrder($cmd, $order) {
		if (is_array($order)) {
			foreach($order as $field=>$spec) {
				$fmName = array_search($field, $this->fieldMap);
				$spec = (int)$spec;
				$direction = ((int)$spec) >= 0 ? FILEMAKER_SORT_ASCEND : FILEMAKER_SORT_DESCEND ;
				$prec = abs($spec);
				if ($fmName !== false)
					$cmd->addSortRule($fmName, $prec, $direction);
			}
		}
		
		return $cmd;
	}
	
	/**
	 *	Retrieves a single record by its FileMaker ID value.
	 *	
	 *	@param string $site_id The ID of the record you want to know about.
	 */
	function getById($id) {
		$retval = false;

		$retval = $this->findOne(array('id'=>$id));
		
		return $retval;
	}
		
	/**
	 *	Find multiple records matching some conditions.  Return the result as an array of stdClass
	 *	objects.  If there are no matches, an empty array will be returned.
	 *
	 *	@param array $criteria An array of niceNames=>matchingValue
	 *	@param array $order Ordering values, as niceName=>"+/-"precedence
	 *	@return array An array of matching records
	 */
	public function find($criteria=null, $order=null) {
		$retval = array();
		
		$cmd = $this->fmp->newFindCommand($this->layout);
		$this->addCriteria($cmd, $criteria);
		$this->addOrder($cmd, $order);
		$result = $cmd->execute();
		
		if ($result instanceof FileMaker_Result) {
			$this->layout_data = $result->getLayout();
			foreach($result->getRecords() as $oneRecord)
				$retval[] = $this->recordToObject($oneRecord, $this->fieldMap);
		} elseif ($result instanceof FileMaker_Error) {
			$code = $result->getCode();
			if ($code != 401)
				show_error($result->getMessage() . " (ID=$id) (code=$code)");
		}
				
		return $retval;
	}
	
	/**
	 *	Find records whose $fieldName matches one of the values in $valueArray.
	 *	
	 *	
	 */
	public function find_in($fieldName, $valueArray, $order=null) {
		$retval = array();
		
		$compoundFind = $this->fmp->newCompoundFindCommand($this->layout);
		foreach($valueArray as $index=>$oneValue) {
			$find = $this->fmp->newFindRequest($this->layout);
			$this->addCriteria($find, array($fieldName=>$oneValue));
			$compoundFind->add(intval($index), $find);
		}
		$this->addOrder($compoundFind, $order);
		$result = @$compoundFind->execute();
		
		if ($result instanceof FileMaker_Result) {
			$this->layout_data = $result->getLayout();
			foreach($result->getRecords() as $oneRecord)
				$retval[] = $this->recordToObject($oneRecord, $this->fieldMap);
		} elseif ($result instanceof FileMaker_Error) {
			$code = $result->getCode();
			if ($code != 401)
				show_error($result->getMessage() . " (ID=$id) (code=$code)");
		}
				
		return $retval;
	}
	
	/**
	 *	As find(), but only returns a single object, or false if there is no match.
	 *
	 *	@param array $criteria An array of niceNames=>matchingValue
	 *	@return stdClass A matching record, or false on failure.
	 */
	public function findOne($criteria=null, $order=null) {
		$retval = false;
		
		$cmd = $this->fmp->newFindCommand($this->layout);
		$this->addCriteria($cmd, $criteria);
		$this->addOrder($cmd, $order);
		$cmd->setRange(0,1);
		$result = $cmd->execute();
		
		if ($result instanceof FileMaker_Result) {
			$this->layout_data = $result->getLayout();
			$retval = $this->recordToObject($result->getFirstRecord(), $this->fieldMap);
		} elseif ($result instanceof FileMaker_Error) {
			$code = $result->getCode();
			if ($code != 401)
				show_error($result->getMessage() . " (ID=$id) (code=$code)");
		}
				
		return $retval;
	}
	
	/**
	 *	Find the first matching record and return the container data from the relevant field.
	 *
	 *	@param array $criteria As for find()
	 *	@param string $container The name of the field which is the container whose data we want.
	 *	@return string A binary string containing image data, or false on failure.
	 */
	public function findContainerData($criteria, $container) {
		$retval = false;
		$error = false;
		
		if(array_search($container, $this->fieldMap) === false)
			show_error("Invalid container name $container");
		
		$record = $this->findOne($criteria);
		if ($record && $record->{$container}) {
			$image = $this->fmp->getContainerData($record->{$container});
			if ($image instanceof FileMaker_Error)
				$error = $image;
			else
				$retval = $image;
		}

		if ($error)
			show_error("{$error->getMessage()} ({$error->getCode()})");
		
		return $retval;
	}
	
	/**
	 *	This just calls insertArray.  It is deprecated: you should use insertArray() directly, or
	 *	better yet use the insertRecord() method.
	 *
	 *	@param $record An associative array
	 *	@return A stdClass object representing the newly created record
	 *	@deprecated
	 */
	function insert($record) { return $this->insertArray($record); }
	
	function insertArray($record) {
		$retval = false;
		
		if (is_object($record))
			$record = (array) $record;
		$record = $this->arrayToRecord($record);
			
		if (!empty($record)) {
			$cmd =& $this->fmp->newAddCommand($this->layout, $record);
			$result = $cmd->execute();
			if ($result instanceof FileMaker_Result) {
				if ($result->getFetchCount()>0)
					$retval = $this->recordToObject($result->getFirstRecord(), $this->fieldMap);
			} else
				show_error($result->getMessage() . "(code={$result->getCode()})");
		}
		return $retval;
	}
	
	/**
	 *	If the fieldMap has a sub-array that specifies a portal, return FileMaker portal name.
	 *
	 *	On failure, returns false.
	 *
	 *	@param string $name The friendly name of the portal.
	 *	@return string The FileMaker name of the portal
	 */
	public function findRelatedFieldName($name) {
		$retval = false;
		
		foreach($this->fieldMap as $fmKey=>$spec) {
			if (isset($spec['name'], $spec['fields']) && $spec['name']==$name) {
				$retval = $fmKey;
				break;
			}
		}
		
		return $retval;
	}
	
	/**
	 *	Fills a FileMaker command or record object with the fields, using the given field map.  
	 *	Repetion fields are supported, but portal records are not.  Your input fields can be any
	 *	iterative object.
	 *
	 *	@param mixed $rec Any FileMaker object that supports the setField() command.
	 *	@param array|object $fields The data to use in the new record
	 *	@param array $fieldMap The array that maps FileMaker names to nice names
	 *	@return mixed Returns $rec
	 */
	public function fillRecord($rec, $fields, $fieldMap) {
		if (is_array($fields))
			$fields = (object) $fields;
		
		$fields = $this->preRecordEncode($fields);
		
		foreach ($fields as $fieldName=>$fieldValue) {
			$fmName = array_search($fieldName, $fieldMap);
			if ($fmName === false) {
				; // do nothing
			} else if (substr($fieldName,-2)=='_r' && !is_scalar($fieldValue)) {
				$idx=0;
				foreach($fieldValue as $oneValue) {
					$rec->setField($fmName, $oneValue, $idx);
					$idx++;
				}
			} else if (is_scalar($fieldValue) && is_scalar($fieldMap[$fmName])) {
				$rec->setField($fmName, $fieldValue);
			}
		}
		return $rec;
	}
	
	/**
	 *	Create a new record using the fields provided.  Portal records will not be created.
	 *
	 *	@param array|object $fields The data for the new record
	 *	@param boolean $throw Whether to throw an exception.  If not, show an error instead.
	 *	@return object The newly-created record
	 */
	public function insertRecord($fields, $throw=false) {
		$retval = false;
		
		$cmd = $this->fmp->newAddCommand($this->layout);
		$this->fillRecord($cmd, $fields, $this->fieldMap);
		$result = $cmd->execute();
		if ($result instanceof FileMaker_Result)
			$retval = $this->recordToObject($result->getFirstRecord(), $this->fieldMap);
		else if ($result instanceof FileMaker_Error) {
			if ($throw)
				throw new Exception($result->getMessage(), $result->getCode());
			else
				show_error("{$result->getMessage()} ({$result->getCode()})");
		}
		
		return $retval;
	}
	
	/**
	 *	Find the record of a given ID, and update it with information from an array or object.
	 *
	 *	@param string $id The ID of the record you want to change.
	 *	@param array|object $record the fields you want to change.
	 */
	function updateById($id, $record) {
		$retval = false;
		$error = null;
		
		$rec = $this->getById($id);
		if (!empty($record) && isset($rec->_recid)) {
			$cmd = $this->fmp->newEditCommand($this->layout, $rec->_recid);
			$this->fillRecord($cmd, $record, $this->fieldMap);
			$result = $cmd->execute();
			if ($result instanceof FileMaker_Result)
				$retval = $this->recordToObject($result->getFirstRecord(), $this->fieldMap);
			else if ($result instanceof FileMaker_Error)
				show_error("{$result->getMessage()} ({$result->getCode()})");
			else
				$retval = $result;
		} else
			show_error("Invalid input data");

		return $retval;
	}
	
	function deleteRecord($record, $throw=false) {
		$retval = false;
		
		if (isset($record->id, $record->_recid)) {
			$retval = $this->deleteByRecid($record->_recid);
		} else {
			if ($throw)
				throw new Exception("The record is insufficiently complete to delete.");
			else
				show_error("The record is insufficiently complete to delete");
		}
		
		return $retval;
	}
	
	function deleteByRecid($recid) {
		$retval = false;
		
		$cmd = $this->fmp->newDeleteCommand($this->layout, $recid); 
		$result = $cmd->execute();
		if ($result instanceof FileMaker_Result)
			$retval = true;
			
		return $retval;
	}
	
	function getLayout() {
		if (empty($this->layout_data)) {
			$data = $this->fmp->getLayout($this->layout);
			if ($data instanceof FileMaker_Layout)
				$this->layout_data = $data;
		}
		
		return $this->layout_data;
	}
	
	function getValueList($name) {
		$retval = false;
		
		$layout = $this->getLayout();
		if ($layout) {
			$list = $layout->getValueList($name);
			if (!empty($list))
				$retval = $list;
		}
		
		return $retval;
	}

	/**
	 *	This is a method that gets called whenever an object/array is about to be encoded into
	 *	a FileMaker_Record object.  It is your last-ditch chance to do any fancy fiddling with
	 *	data formats.  You shouldn't need this very often: only in very special cases.
	 *
	 *	If you choose to implement this, then you have to be sure to return an object.
	 *
	 *	@param object $data
	 *	@return object
	 */
	protected function preRecordEncode($data) {
		return $data;
	}
	
	/**
	 *	This is a method that gets called right after a FileMaker_Record object is decoded into
	 *	an object.
	 *
	 *	If you choose to implement this, then you have to be sure to return an object.
	 *
	 *	@param object $data
	 *	@return object
	 */
	protected function postRecordDecode($data) {
		return $data;
	}
}
