<?php
namespace ImageBanks;

abstract class Provider
    {
    protected $lang;
    protected $temp_dir_path;

    public final function __construct(array $lang, $temp_dir_path)
        {
        if(!isset($this->id))
            {
            throw new \LogicException(get_class($this) . ' must have a $id property');
            }

        if(!isset($this->name))
            {
            throw new \LogicException(get_class($this) . ' must have a $name property');
            }

        if(!isset($this->configs))
            {
            throw new \LogicException(get_class($this) . ' must have a $configs property');
            }

        $this->lang = $lang;
        $this->temp_dir_path = $temp_dir_path;
        }

    abstract public function getId();
    abstract public function getName();

    abstract static function checkDependencies();
    abstract public function buildConfigPageDefinition(array $page_def);


    /**
    * Register configuration options required by the Provider in the GLOBAL scope
    * 
    * @param  array $globals The globals variable - $GLOBALS
    * 
    * @return array Returns the $GLOBALS array back with any config vars required by a provider
    */
    public final function registerConfigurationNeeds(array $globals)
        {
        foreach($this->configs as $config => $value)
            {
            if(array_key_exists($config, $globals))
                {
                // GLOBALS have been set from the plugin configuration, no reason to set to the default value now
                $this->configs[$config] = $globals[$config];

                continue;
                }

            $globals[$config] = $value;
            }

        return $globals;
        }

    /**
    * Search providers' database based on specified keywords
    * 
    * @abstract
    * 
    * @param  string   $keywords  Search keyword(s)
    * @param  integer  $per_page  Number of results per page
    * @param  integer  $page      Select the page number
    * 
    * @return \ImageBanks\ProviderSearchResults
    */
    abstract protected function runSearch($keywords, $per_page = 24, $page = 1);

    /**
    * Search providers' database based on specified keywords
    * 
    * @param  string   $keywords  Search keyword(s)
    * @param  integer  $per_page  Number of results per page
    * @param  integer  $page      Select the page number
    * 
    * @return \ImageBanks\ProviderSearchResults
    */
    public final function search($keywords, $per_page = 24, $page = 1)
        {
        $search_results = $this->runSearch($keywords, $per_page, $page);

        if(!($search_results instanceof \ImageBanks\ProviderSearchResults))
            {
            trigger_error("Provider '{$this->getName()}' search results must be of type ProviderSearchResults");
            }

        return $search_results;
        }

    /**
    * Get Cache from the providers' temporary directory
    * 
    * @return boolean|string  Returns FALSE if no cache found or the content of the file
    */
    protected final function getCache($id)
        {
        $files = new \DirectoryIterator($this->temp_dir_path);
        foreach($files as $file)
            {
            if($file->isDot())
                {
                continue;
                }

            $filename = $file->getFilename();

            if($filename != $id)
                {
                continue;
                }
            }

        return false;
        }
    }