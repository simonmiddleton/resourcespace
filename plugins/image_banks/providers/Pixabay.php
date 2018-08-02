<?php
namespace ImageBanks;

class Pixabay extends Provider
    {
    protected $id   = 1;
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

    public function runSearch($keywords, $page = 1, $per_page = 24)
        {
        // TODO: build API request
        // TODO: do API request or retrieve from cache (24h old max) as per https://pixabay.com/api/docs/
        // TODO: handle expected errors and notify users nicely
        // TODO: based on the result set, return back a list of ProviderResult objects

        // get test API response from file which is not in CVS for obvious reasons
        $pixabay_api_response_file = fopen(dirname(__DIR__) . '/pixabay_api_response.json', 'rb');
        $api_results = fread($pixabay_api_response_file, filesize(dirname(__DIR__) . '/pixabay_api_response.json'));
        $api_results = json_decode($api_results, true);

        $provider_results = new ProviderSearchResults();

        foreach($api_results["hits"] as $result)
            {
            // As per https://pixabay.com/api/docs/ , imageURL key/value pair is only available if the account has been 
            // approved for full API access
            $original_file_url = $result['largeImageURL'];
            if(isset($result['imageURL']) && $result['imageURL'] !== '')
                {
                $original_file_url = $result['imageURL'];
                }

            $provider_result = new \ImageBanks\ProviderResult($result["id"], $this);
            $provider_result
                ->setOriginalFileUrl($original_file_url)
                ->setProviderUrl($result['pageURL'])
                ->setPreviewUrl($result['previewURL'])
                ->setPreviewWidth($result['previewWidth'])
                ->setPreviewHeight($result['previewHeight']);

            $provider_results[] = $provider_result;
            }

        $provider_results->total = count($provider_results);
        if(isset($api_results["total"]))
            {
            $provider_results->total = $api_results["total"];
            }

        return $provider_results;
        }
    }