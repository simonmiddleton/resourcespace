<?php

declare(strict_types=1);

namespace ImageBanks;

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

        $this->callApi($this->instances[$this->selected_instance_id]);
        die('You died at line ' . __LINE__ . ' in file ' . __FILE__);
        return new ProviderSearchResults();
        }

    function callApi(ResourceSpaceProviderInstance $instance)
        {
        $api = $instance->toArray();
        $query="user={$api['username']}&function=do_search&search=bike";
        $sign = hash('sha256', $api['key'] . $query);
        $results = json_decode(file_get_contents("{$api['baseURL']}/api/?$query&sign=$sign"), true);
        printf('<pre>%s</pre>', print_r($results, true));die('You died at line ' . __LINE__ . ' in file ' . __FILE__);
        // todo: consider the output of this function. It may occasionally fail and we'll want to let user know.
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
