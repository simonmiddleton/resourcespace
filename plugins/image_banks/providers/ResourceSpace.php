<?php

declare(strict_types=1);

namespace ImageBanks;

class ResourceSpace extends Provider implements MultipleInstanceProviderInterface
    {
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

    public function buildConfigPageDefinition(array $page_def)
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

    public function runSearch($keywords, $per_page = 24, $page = 1)
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

        trigger_error('[ImageBanks][ResourceSpace] to be implemented...');
        return [];
        }

    public function getProviderLinkedSystems(): array
        {
        // process provider config and parse it as needed to get the list out
        $raw_instances = explode(PHP_EOL, trim($this->configs['resourcespace_instances_cfg']));
        printf('<pre>%s</pre>', print_r($raw_instances, true));die('You died at line ' . __LINE__ . ' in file ' . __FILE__);
        return [];
        }
    }
