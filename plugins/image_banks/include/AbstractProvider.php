<?php
namespace ImageBanks;

abstract class Provider
    {
    protected $lang;

    public final function __construct(array $lang)
        {
        if(!isset($this->id))
            {
            throw new \LogicException(get_class($this) . ' must have a $id property');
            }

        if(!isset($this->name))
            {
            throw new \LogicException(get_class($this) . ' must have a $name property');
            }

        $this->lang = $lang;
        }

    abstract public function getId();
    abstract public function getName();

    abstract static function checkDependencies();
    abstract public function buildConfigPageDefinition(array $page_def);
    abstract public function search($keywords);
    }