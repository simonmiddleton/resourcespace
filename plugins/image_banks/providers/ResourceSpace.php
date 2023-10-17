<?php

declare(strict_types=1);

namespace ImageBanks;

use RuntimeException;
use SplFileInfo;

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
            $this->configs = ['resourcespace_instances_cfg' => ''];
            $this->warning = "";
            $this->lang = $lang;
            $this->temp_dir_path = $temp_dir_path;
            }

    /** @inheritdoc */
    public function checkDependencies(): array
        {
        if (!function_exists('curl_version'))
            {
            return [$this->lang["image_banks_error_detail_curl"]];
            }
        return [];
        }

    /** @inheritdoc */
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

    /** @inheritdoc */
    public function runSearch(string $keywords, int $per_page = 24, int $page = 1): ProviderSearchResults
        {
        $per_page = $per_page > 0 ? $per_page : $GLOBALS['default_perpage'];
        $page = $page > 0 ? $page : 1;
        $offset = ($page - 1) * $per_page;

        try
            {
            $instance = $this->getSelectedSystemInstance()->toArray();
            $instance_cfg = $instance['configuration'];
            $api_results = $this->callApi(
                'do_search',
                [
                    'search' => $keywords,
                    'fetchrows' => "{$offset},{$per_page}",
                ]
            );
            }
        catch (RuntimeException $r)
            {
            $search_results = new ProviderSearchResults();
            $search_results->setError($r->getMessage());
            return $search_results;
            }

        $view_title_field = $instance_cfg['view_title_field'] ?? $GLOBALS['view_title_field'];
        $results = new ProviderSearchResults();

        foreach($api_results['data'] as $row)
            {
            $item = (new ProviderResult($row['ref'], $this))
                ->setTitle($row["field{$view_title_field}"])
                ->setProviderUrl(generateURL($instance['baseURL'], ['r' => $row['ref']]));

            try
                {
                $resource_sizes = $this->callApi('get_resource_all_image_sizes', ['resource' => $row['ref']]);
                }
            catch (RuntimeException $r)
                {
                $resource_sizes = [];
                $item = $item
                    ->setPreviewUrl(sprintf(
                        '%s/gfx/%s',
                        $GLOBALS['baseurl'],
                        get_nopreview_icon($row['resource_type'], $row['file_extension'], false)
                    ))
                    ->setPreviewWidth(128)
                    ->setPreviewHeight(128);
                }

            foreach ($resource_sizes as $rsize)
                {
                // Select the original file (if allowed), otherwise go for the next available high resolution version
                if (in_array($rsize['size_code'], ['original', 'hpr', 'lpr']) && $item->getOriginalFileUrl() === null)
                    {
                    $item = $item->setOriginalFileUrl($rsize['url']);
                    continue;
                    }
                else if ($rsize['size_code'] === 'thm')
                    {
                    $item = $item
                        ->setPreviewUrl($rsize['url'])
                        ->setPreviewWidth($row['thumb_width'])
                        ->setPreviewHeight($row['thumb_height']);
                    }
                }

            $results[] = $item;
            }
        $results->total = $api_results['total'];
        return $results;
        }

    /** @inheritdoc */
    public function getDownloadFileInfo(string $file): SplFileInfo
        {
        // todo: also cater for case when the URL points to the filestore directly (ie no hide_real_filepath)
        parse_str(parse_url($file, PHP_URL_QUERY), $qs_params);
        $ref = $qs_params['ref'] ?? '';
        $ext = $qs_params['ext'] ?? '';
        return new SplFileInfo("$ref.$ext");
        }

    /**
     * Helper method to use ResourceSpace API
     *
     * @param string $function API binding function name
     * @param array $data Request payload
     * @return array|int|string JSON decoded data, as received from the API binding
     */
    function callApi(string $function, array $data)
        {
        $instance = $this->getSelectedSystemInstance();
        $err_msg_prefix = sprintf('%s - %s: ', $this->name, $instance->getName());
        $api = $instance->toArray();

        // Build request & send
        $query = http_build_query(
            array_merge(
                [
                    'user' => $api['username'],
                    'function' => $function,
                ],
                $data
            )
        );
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

    /** @inheritdoc */
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

    /** @inheritdoc */
    public function getAllInstances(): array
        {
        return $this->instances;
        }

    /** @inheritdoc */
    public function selectSystemInstance(int $id): Provider
        {
        $this->selected_instance_id = $id;
        $this->download_endpoint = ($this->getSelectedSystemInstance()->toArray())['baseURL'];
        return $this;
        }

    /** @inheritdoc */
    public function getSelectedSystemInstance(): ResourceSpaceProviderInstance
        {
        return $this->instances[$this->selected_instance_id];
        }
    }
