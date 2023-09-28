<?php

declare(strict_types=1);

namespace ImageBanks;

final class ResourceSpaceProviderInstance implements ProviderInstanceInterface
    {
    private string $name;
    private string $baseURL;
    private string $username;
    private string $key;
    private array $configuration = [];

    private function __construct(string $name)
        {
        $this->name = i18n_get_translated($name);
        }

    public static function parseRaw($instance): self|string
        {
        $instance_parts = explode('|', $instance);
        if (count($instance_parts) < 6)
            {
            return 'image_banks_error_resourcespace_invalid_instance_cfg';
            }

        printf('<pre>%s</pre>', print_r($instance_parts, true));die('You died at line ' . __LINE__ . ' in file ' . __FILE__);
        // return new self(...$instance_parts);



        return 'image_banks_error_generic_parse';
        }
    }
