<?php
namespace ImageBanks;

// TODO: implement Iterator to allow ProviderSearchResults to be traversable (ie. foreach)
class ProviderSearchResults implements \ArrayAccess, \Countable
    {
    private $results = array();

    public function __construct() {}

    public function offsetSet($offset, $value)
        {
        if(!($value instanceof \ImageBanks\ProviderResult))
            {
            return;
            }

        if(is_null($offset))
            {
            $this->results[] = $value;

            return;
            }

        $this->results[$offset] = $value;

        return;
        }

    public function offsetGet($offset)
        {
        return isset($this->results[$offset]) ? $this->results[$offset] : null;
        }

    public function offsetExists($offset)
        {
        return isset($this->results[$offset]);
        }

    public function offsetUnset($offset)
        {
        unset($this->results[$offset]);

        return;
        }

    public function count()
        {
        return count($this->results);
        }
    }