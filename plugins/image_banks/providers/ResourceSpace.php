<?php

declare(strict_types=1);

namespace ImageBanks;

use RuntimeException;

class ResourceSpace extends Provider implements MultipleInstanceProviderInterface
    {
    /**
     * Only valid instances
     * @var list<ResourceSpaceProviderInstance>
     */
    private array $instances = [];
    private int $selected_instance_id;

    function __construct(array $lang, string $temp_dir_path)
            {
            $this->id = 3;
            $this->name = "ResourceSpace";
            // $this->download_endpoint = "https://yourRS.tld/api";
            $this->configs = ['resourcespace_instances_cfg' => ''];
            $this->warning = "";
            $this->lang = $lang;
            $this->temp_dir_path = $temp_dir_path;
            }

    public function checkDependencies(): array
        {
        if (!function_exists('curl_version'))
            {
            return [$this->lang["image_banks_error_detail_curl"]];
            }
        return [];
        }

    public function buildConfigPageDefinition(array $page_def): array
        {
        $page_def[] = config_add_text_input(
            'resourcespace_instances_cfg',
            $this->lang['image_banks_label_resourcespace_instances_cfg'],
            false,
            800,
            true
        );
        return $page_def;
        }

    public function runSearch($keywords, $per_page = 24, $page = 1): ProviderSearchResults
        {
        if($per_page < 3)
            {
            $per_page = 3;
            }
        else if($per_page > 200)
            {
            $per_page = 200;
            }

        if($page < 1)
            {
            $page = 1;
            }

        try
            {
            /* 
            todo: 
            - need to finish the callAPI to allow requesting anything from RS
            - always search for pre,thm,col (or as needed). If an instance has renamed them, have a remap in its configuration instead
            */           
            $api_results = $this->callApi('search_get_previews');
            }
        catch (RuntimeException $r)
            {
            $search_results = new ProviderSearchResults();
            $search_results->setError($r->getMessage());
            return $search_results;
            }

        foreach($api_results as $row)
            {
            printf('<pre>%s</pre>', print_r($row, true));die('You died at line ' . __LINE__ . ' in file ' . __FILE__);
            }
        die('You died at line ' . __LINE__ . ' in file ' . __FILE__);
        return new ProviderSearchResults();
        }

    function callApi(string $function/* , array $data */)
        {
        $instance = $this->instances[$this->selected_instance_id];
        $err_msg_prefix = sprintf('%s - %s: ', $this->name, $instance->getName());
        $api = $instance->toArray();

        // Build request & send
        $query = sprintf('user=%s&function=%s&search=bike&getsizes=pre,thm,col', $api['username'], $function);
        $sign = hash('sha256', $api['key'] . $query);
        $request = file_get_contents(
            "{$api['baseURL']}/api/?$query&sign=$sign",
            false,
            stream_context_create([
                'http' => [
                    'ignore_errors' => true,
                ],
            ])
        );
        $status_code = preg_match('/\d{3}/', $http_response_header[0], $match) ? (int) $match[0] : 0;
        $results = json_decode($request, true);

        // Handle generic fails (simple string responses)
        if ($status_code !== 200 && JSON_ERROR_NONE !== json_last_error())
            {
            throw new RuntimeException($err_msg_prefix . $request);
            }
        // Handle generic (structured) errors (usually done using ajax_functions.php)
        else if ($status_code !== 200 && isset($results['error']['detail']))
            {
            throw new RuntimeException($err_msg_prefix . $results['error']['detail']);
            }
        else if ($status_code === 200 && JSON_ERROR_NONE !== json_last_error())
            {
            throw new RuntimeException("$err_msg_prefix (JSON) " . json_last_error_msg());
            }

        return $results;
        }

    public function parseInstancesConfiguration(): array
        {
        $errs = [];
        $raw_instances = array_values(array_filter(explode(PHP_EOL, trim($this->configs['resourcespace_instances_cfg']))));
        $create_instance_id_from = createProviderInstanceId($this);
        foreach ($raw_instances as $id => $data)
            {
            $parsed = ResourceSpaceProviderInstance::parseRaw($data);
            if ($parsed instanceof ResourceSpaceProviderInstance)
                {
                $this->instances[$create_instance_id_from($id)] = $parsed;
                continue;
                }
            $errs[] = $this->lang[$parsed] ?? "%PROVIDER - $parsed";
            }
        return $errs;
        }

    public function getAllInstances(): array
        {
        return $this->instances;
        }

    public function selectSystemInstance(int $id): Provider
        {
        $this->selected_instance_id = $id;
        return $this;
        }
    }
