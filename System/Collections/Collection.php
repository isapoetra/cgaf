<?php
namespace System\Collections;
use \Convert, \System\Exceptions\SystemException;

class Collection extends \Object implements \IteratorAggregate, \ArrayAccess, \Countable {
	/**
	 * internal data storage
	 * @var array
	 */
	protected $_d = array();
	/**
	 * number of items
	 * @var integer
	 */
	protected $_c = 0;
	/**
	 * @var boolean whether this list is read-only
	 */
	private $_r = false;
	private $_iClass = null;
	private $_allowDuplicate = false;
	/**
	 * Constructor.
	 * Initializes the list with an array or an iterable object.
	 * @param array|Iterator the initial data. Default is null, meaning no initialization.
	 * @param boolean whether the list is read-only
	 * @throws TInvalidDataTypeException If data is not null and neither an array nor an iterator.
	 */
	public function __construct($data = null, $readOnly = false, $itemClass = null) {
		if ($data !== null)
			$this->assign($data);
		$this->setReadOnly($readOnly);
		$this->setItemClass($itemClass);
	}

	public function setItemClass($value) {
		if (!$value) {
			$this->_iClass = null;
			return;
		}
		if (!class_exists($value))
			throw new SystemException("Class $value not Found");
		$this->_iClass = $value;
	}

	public function getItemClass() {
		return $this->_iClass;
	}

	/**
	 * @return boolean whether this list is read-only or not. Defaults to false.
	 */
	public function getReadOnly() {
		return $this->_r;
	}

	/**
	 * @param boolean whether this list is read-only or not
	 */
	protected function setReadOnly($value) {
		$this->_r = Convert::toBoolean($value);
	}

	/**
	 * Returns an iterator for traversing the items in the list.
	 * This method is required by the interface IteratorAggregate.
	 * @return Iterator an iterator for traversing the items in the list.
	 */
	public function getIterator() {
		return new ListIterator($this->_d);
	}

	function getItems() {
		return new ListIterator($this->_d);
	}

	/**
	 * Returns the number of items in the list.
	 * This method is required by Countable interface.
	 * @return integer number of items in the list.
	 */
	public function count() {
		return $this->getCount();
	}

	/**
	 * @return integer the number of items in the list
	 */
	public function getCount() {
		return $this->_c;
	}

	/**
	 * Returns the item at the specified offset.
	 * This method is exactly the same as {@link offsetGet}.
	 * @param integer the index of the item
	 * @return mixed the item at the index
	 * @throws TInvalidDataValueException if the index is out of the range
	 */
	public function itemAt($index) {
		if ($index >= 0 && $index < $this->_c)
			return isset($this->_d[$index]) ? $this->_d[$index] : null;
		else
			throw new InvalidOperationException('list_index_invalid', $index);
	}
	private function isDuplicate($item) {
		if ($this->_allowDuplicate) {
			return false;
		}
		foreach ($this->_d as $k => $v) {
			if (is_object($v)) {
				if ($v->equals($item)) {
					return true;
				}
			}
		}
		return false;
	}
	/**
	 * Appends an item at the end of the list.
	 * @param mixed new item
	 * @return integer the zero-based index at which the item is added
	 */
	public function add($item, $multi = false) {
		if (is_array($item) && $multi) {
			foreach ($item as $itm) {
				$this->add($itm, false);
			}
			return $this->_c - 1;
		}
		if ($this->_iClass) {
			if ((get_class($item) !== $this->_iClass) && (!is_subclass_of($item, $this->_iClass)))
				throw new SystemException("Item Not Valid Required class $this->_iClass given class " . get_class($item));
		}
		if ($this->isDuplicate($item)) {
			return $this->_c;
		}
		$this->insertAt($this->_c, $item);
		return $this->_c - 1;
	}

	/**
	 * Inserts an item at the specified position.
	 * Original item at the position and the next items
	 * will be moved one step towards the end.
	 * @param integer the specified position.
	 * @param mixed new item
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws InvalidOperationException if the list is read-only
	 */
	public function insertAt($index, $item) {
		if (!$this->_r) {
			if ($index === $this->_c)
				$this->_d[$this->_c++] = $item;
			else if ($index >= 0 && $index < $this->_c) {
				array_splice($this->_d, $index, 0, array($item));
				$this->_c++;
			} else
				throw new InvalidOperationException('list_index_invalid', $index);
		} else
			throw new InvalidOperationException('list_readonly', get_class($this));
	}

	/**
	 * Removes an item from the list.
	 * The list will first search for the item.
	 * The first item found will be removed from the list.
	 * @param mixed the item to be removed.
	 * @return integer the index at which the item is being removed
	 * @throws TInvalidDataValueException If the item does not exist
	 */
	public function remove($item) {
		if (($index = $this->indexOf($item)) >= 0) {
			$this->removeAt($index);
			return $index;
		} else
			throw new InvalidOperationException('list_item_inexistent');
	}

	/**
	 * Removes an item at the specified position.
	 * @param integer the index of the item to be removed.
	 * @return mixed the removed item.
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws InvalidOperationException if the list is read-only
	 */
	public function removeAt($index) {
		if (!$this->_r) {
			if ($index >= 0 && $index < $this->_c) {
				$this->_c--;
				if ($index === $this->_c)
					return array_pop($this->_d);
				else {
					$item = $this->_d[$index];
					array_splice($this->_d, $index, 1);
					return $item;
				}
			} else
				throw new InvalidOperationException('list_index_invalid', $index);
		} else
			throw new InvalidOperationException('list_readonly', get_class($this));
	}

	/**
	 * Removes all items in the list.
	 */
	public function clear() {
		for ($i = $this->_c - 1; $i >= 0; --$i)
			$this->removeAt($i);
	}

	/**
	 * @param mixed the item
	 * @return boolean whether the list contains the item
	 */
	public function contains($item) {
		return $this->indexOf($item) >= 0;
	}

	/**
	 * @param mixed the item
	 * @return integer the index of the item in the list (0 based), -1 if not found.
	 */
	public function indexOf($item) {
		if (($index = array_search($item, $this->_d, true)) === false)
			return -1;
		else
			return $index;
	}

	/**
	 * @return array the list of items in array
	 */
	public function toArray() {
		return $this->_d;
	}

	/**
	 * Copies iterable data into the list.
	 * Note, existing data in the list will be cleared first.
	 * @param mixed the data to be copied from, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor a Traversable.
	 */
	function assign($var, $val = null) {
		if (is_array($var) || ($var instanceof Traversable)) {
			if ($this->_c > 0)
				$this->clear();
			foreach ($var as $item) {
				$this->add($item);
			}
		} else if ($var !== null)
			throw new SystemException('list_data_not_iterable');
	}

	/**
	 * Merges iterable data into the map.
	 * New data will be appended to the end of the existing data.
	 * @param mixed the data to be merged with, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function mergeWith($data) {
		if (is_array($data) || ($data instanceof Traversable)) {
			foreach ($data as $item)
				$this->add($item);
		} else if ($data !== null)
			throw new InvalidOperationException('list_data_not_iterable');
	}

	/**
	 * Returns whether there is an item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer the offset to check on
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return ($offset >= 0 && $offset < $this->_c);
	}

	/**
	 * Returns the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer the offset to retrieve item.
	 * @return mixed the item at the offset
	 * @throws TInvalidDataValueException if the offset is invalid
	 */
	public function offsetGet($offset) {
		return $this->itemAt($offset);
	}

	/**
	 * Sets the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer the offset to set item
	 * @param mixed the item value
	 */
	public function offsetSet($offset, $item) {
		if ($offset === null || $offset === $this->_c)
			$this->insertAt($this->_c, $item);
		else {
			$this->removeAt($offset);
			$this->insertAt($offset, $item);
		}
	}

	/**
	 * Unsets the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer the offset to unset item
	 */
	public function offsetUnset($offset) {
		$this->removeAt($offset);
	}
}

?>