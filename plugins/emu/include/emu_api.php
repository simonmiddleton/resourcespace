<?php
require_once '../lib/imu_api/IMu.php';
require_once IMu::$lib . '/Session.php';
require_once IMu::$lib . '/Module.php';
require_once IMu::$lib . '/Terms.php';

class EMuAPI
    {
    protected $session;
    protected $module;
    protected $terms;
    protected $columns = array();


    /**
    * 
    */
    public function __construct($emu_server_ip, $emu_server_port, $emu_module)
        {
        $this->session     = new IMuSession($emu_server_ip, $emu_server_port);
        $this->module      = new IMuModule($emu_module, $this->session);
        $this->terms       = new IMuTerms();
        }

    public function setIrnColumn()
        {
        $this->columns[] = 'irn';

        return;
        }

    public function setColumns(array $names)
        {
        $this->columns = array_merge($this->columns, $names);

        return;
        }

    public function getColumns($name = '')
        {
        $return = $this->columns;

        if('' !== trim($name))
            {
            $return = $this->columns[$name];
            }

        return $return;
        }

    public function resetColumns($name = '')
        {
        if('' !== trim($name))
            {
            unset($this->columns[$name]);

            return;
            }

        $this->columns = array();

        return;
        }

    public function testSearch()
        {
        }

    }