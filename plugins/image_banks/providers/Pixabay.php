<?php
namespace ImageBanks;

class Pixabay extends Provider
    {
    protected $id   = 1;
    protected $name = "Pixabay";
    protected $configs = array(
        "pixabay_api_key" => ""
    );


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

    public function runSearch($keywords, $per_page = 24, $page = 1)
        {
        // TODO: do API request or retrieve from cache (24h old max) as per https://pixabay.com/api/docs/
        // TODO: handle expected errors and notify users nicely

        // check cache based on hash of keywords,per_page and page and if nothing found, searchPixabay()
        // $api_results = $this->searchPixabay($keywords, $per_page, $page);

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


    private function searchPixabay($keywords, $per_page = 24, $page = 1)
        {
        $pixabay_api_url = generateURL(
            "https://pixabay.com/api/",
            array(
                "key"      => $this->configs["pixabay_api_key"],
                "q"        => $keywords,
                "per_page" => $per_page,
                "page"     => $page,
            )
        );

        $curl_handle = curl_init();
        $curl_response_headers = array();

        curl_setopt($curl_handle, CURLOPT_URL, $pixabay_api_url);
        curl_setopt($curl_handle, CURLOPT_HEADER, 0);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $curl_handle,
            CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$curl_response_headers)
                {
                $length = strlen($header);
                $header = explode(':', $header, 2);

                // Invalid header
                if(count($header) < 2)
                    {
                    return $length;
                    }

                $name = strtolower(trim($header[0]));

                if(!array_key_exists($name, $curl_response_headers))
                    {
                    $curl_response_headers[$name] = array(trim($header[1]));
                    }
                else
                    {
                    $curl_response_headers[$name][] = trim($header[1]);
                    }

                return $length;
                }
        );

        $result = curl_exec($curl_handle);
        curl_close($curl_handle);

        // TODO: deal with the case of running out of allowed requests to the API
        // echo "<pre>";print_r($curl_response_headers["x-ratelimit-remaining"]);echo "</pre>";
        // echo "<pre>";print_r($curl_response_headers["x-ratelimit-reset"]);echo "</pre>";
        
        return json_decode($result, true);
        }
    }