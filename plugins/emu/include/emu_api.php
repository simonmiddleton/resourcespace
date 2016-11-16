<?php
require_once dirname(__FILE__) . '/../lib/imu_api/IMu.php';
require_once IMu::$lib . '/Session.php';
require_once IMu::$lib . '/Module.php';
require_once IMu::$lib . '/Terms.php';

/**
* EMuAPI is a wrapper for IMu API
*
* EMuAPI wrapps aroung IMu APi by providing an easy way of searching
* and retrieving objects from an KE EMu database. Current functionality
* allows getting objects mainly based on IRN
*
* Example usage:
* $emu_api = new EMuAPI($emu_api_server, $emu_api_server_port, 'epublic');
* $emu_api->setColumns(array('ClaInstitution', 'ObjTitle', 'ObjectNumber', 'AffOriCountry_tab', 'OriginSummaryData_tab'));
* echo '<pre>';print_r($emu_api->getObjectByIrn(362024));echo '</pre>';
*
* @package EMuAPI
* @author  Alin Cota
* @version 1.0
* @access  public
*/
class EMuAPI
    {
    protected $session;
    protected $module;
    protected $terms;
    protected $columns = array();


    /**
    * Initialises a connection to a EMu server
    * 
    * Creates a sessions for a given server address and port and sets
    * a new Module and Terms object 
    * 
    * @param string  $server_address KE EMu server address
    * @param integer $server_port    KE EMu server port to connect to
    * @param string  $module         KE EMu table <=> Texpress database
    * 
    * @return void
    */
    public function __construct($server_address, $server_port, $module)
        {
        $this->session = new IMuSession($server_address, $server_port);
        $this->module  = new IMuModule($module, $this->session);
        $this->terms   = new IMuTerms();

        return;
        }


    /**
    * Set IRN column
    * 
    * @return void
    */
    public function setIrnColumn()
        {
        $this->columns[] = 'irn';
        $this->module->addFetchSet('object_fields', $this->columns);

        return;
        }


    /**
    * Set columns property
    * 
    * @param array $names Column name(s) to set
    * 
    * @return void
    */
    public function setColumns(array $names)
        {
        $this->columns = array_merge($this->columns, $names);

        if(!empty($this->columns))
            {
            $this->module->addFetchSet('object_fields', $this->columns);
            }

        return;
        }


    /**
    * Get all columns names or just one of them
    * 
    * @param array $name Column name to individually select/ list
    * 
    * @return void
    */
    public function getColumns($name = '')
        {
        $return = $this->columns;

        if('' !== trim($name))
            {
            $return = $this->columns[$name];
            }

        return $return;
        }


    /**
    * Get EMu object based on IRN
    * 
    * @param integer $irn Unique KE EMu object identifier
    * 
    * @return array
    */
    public function getObjectByIrn($irn)
        {
        $this->module->findKey($irn);

        return $this->getResults('object_fields');
        }


    /**
    * Get EMu objects based on an array of IRNs
    * 
    * @param array $irns Array of Unique KE EMu object identifiers
    * 
    * @return array
    */
    public function getObjectsByIrns(array $irns)
        {
        $count_irns = count($irns);

        if(0 === $count_irns)
            {
            return array();
            }

        $this->module->findKeys($irns);

        return $this->getResults('object_fields', 0, $count_irns);
        }


    /**
    * Get results based on search done before calling this function
    * 
    * @param string|array $columns The columns to retrieve for a record. You can use either
    *                              an array of columns or an alias (which is defined by calling
    *                              $this->module->addFetchSet('[alias]', array(columns assigned to alias));)
    *                              Note: using aliases is faster when requesting the same columns
    * @param integer      $offset
    * @param integer      $count
    * 
    * @return array
    */
    public function getResults($columns, $offset = 0, $count = 1)
        {
        return $this->module->fetch('start', $offset, $count, $columns)->rows;
        }


    /**
    * Test IMu API -- To be deleted once done
    */
    public function testSearch()
        {
        $this->terms->add('ClaInstitution', 'CMH');
        $this->module->findTerms($this->terms);

        $result = $this->getResults('object_fields');

        return $result;
        }
    }