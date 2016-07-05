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
    protected $columns;


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
        $this->columns = array('irn');
        }


    }