<?php
namespace ImageBanks;

class Pixabay extends Provider
    {
    protected $id   = 0;
    protected $name = 'Pixabay';


    public function getId()
        {
        return $this->id;
        }

    public function getName()
        {
        return $this->name;
        }


    static function checkDependencies()
        {
        // This provider doesn't require any third party API clients
        return true;
        }

    public function buildConfigPageDefinition(array $page_def)
        {
        $page_def[] = \config_add_section_header($this->name);
        $page_def[] = \config_add_text_input('pixabay_api_key', $this->lang["image_banks_pixabay_api_key"]);

        return $page_def; 
        }
    }