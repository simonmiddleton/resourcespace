<?php
namespace ImageBanks;

class ProviderSearchResults implements \ArrayAccess, \Iterator, \Countable
    {
    private $position = 0;
    private $results = array();
    private $error = "";
    private $warning = "";

    public $total = 0;

    /**
    * Assign a value to the specified offset
    * 
    * @param  integer                     $offset  The offset to assign the value to
    * @param  \ImageBanks\ProviderResult  $value   The value to set
    * 
    * @return  void
    */
    public function offsetSet($offset, $value)
        {
        if(!($value instanceof \ImageBanks\ProviderResult))
            {
            return;
            }

        if(is_null($offset) || !is_int($offset))
            {
            $this->results[] = $value;

            return;
            }

        $this->results[$offset] = $value;

        return;
        }

    /**
    * Offset to retrieve.
    * This method is executed when checking if offset is empty().
    * 
    * @param mixed $offset  The offset to retrieve
    * 
    * @return \ImageBanks\ProviderResult
    */
    public function offsetGet($offset)
        {
        return isset($this->results[$offset]) ? $this->results[$offset] : null;
        }

    /**
    * Whether or not an offset exists.
    * This method is executed when using isset() or empty().
    * 
    * @param  mixed  $offset  An offset to check for
    * 
    * @return boolean
    */
    public function offsetExists($offset)
        {
        return isset($this->results[$offset]);
        }

    /**
    * Unset an offset.
    * This method will not be called when type-casting to (unset).
    * 
    * @param  mixed  $offset  The offset to unset
    * 
    * @return void
    */
    public function offsetUnset($offset)
        {
        unset($this->results[$offset]);

        return;
        }

    /**
    * Return the current element
    * 
    * @return \ImageBanks\ProviderResult
    */
    public function current()
        {
        return $this->results[$this->position];
        }

    /**
    * Return the key of the current element
    * 
    * @return integer
    */
    public function key()
        {
        return $this->position;
        }

    /**
    * Move forward to next element
    * 
    * @return void
    */ 
    public function next()
        {
        ++$this->position;

        return;
        }

    /**
    * Rewind the Iterator to the first element
    * 
    * @return void
    */ 
    public function rewind()
        {
        $this->position = 0;

        return;
        }

    /**
    * Checks if current position is valid
    * 
    * @return boolean
    */ 
    public function valid()
        {
        return isset($this->results[$this->position]);
        }

    /**
    * Count elements of in ProviderSearchResults object
    * 
    * @return integer Returns the number of ProviderResult elements
    */
    public function count()
        {
        return count($this->results);
        }

    /**
    * Set error message
    * 
    * @return void
    */
    public function setError($message)
        {
        $this->error = $message;

        return;
        }

    /**
    * Get error message
    * 
    * @return string
    */
    public function getError()
        {
        return $this->error;
        }

    /**
    * Set warning message
    * 
    * @return void
    */
    public function setWarning($message)
        {
        $this->warning = $message;

        return;
        }

    /**
    * Get warning message
    * 
    * @return string
    */
    public function getWarning()
        {
        return $this->warning;
        }
    }