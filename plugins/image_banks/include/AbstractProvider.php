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

    /**
    * Search providers' database based on specified keywords
    * 
    * @abstract
    * 
    * @param  string   $keywords  Search keyword(s)
    * @param  integer  $page      Select the page number
    * @param  integer  $per_page  Number of results per page
    * 
    * @return \ImageBanks\ProviderSearchResults
    */
    abstract protected function runSearch($keywords, $page = 1, $per_page = 24);

    /**
    * Search providers' database based on specified keywords
    * 
    * @param  string   $keywords  Search keyword(s)
    * @param  integer  $page      Select the page number
    * @param  integer  $per_page  Number of results per page
    * 
    * @return \ImageBanks\ProviderSearchResults
    */
    public final function search($keywords, $page = 1, $per_page = 24)
        {
        $search_results = $this->runSearch($keywords, $page, $per_page);

        if(!($search_results instanceof \ImageBanks\ProviderSearchResults))
            {
            trigger_error("Provider '{$this->getName()}' search results must be of type ProviderSearchResults");
            }

        return $search_results;
        }
    }