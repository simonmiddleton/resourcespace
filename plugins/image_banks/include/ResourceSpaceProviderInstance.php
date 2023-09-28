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

    private function __construct(string $name, string $baseURL, string $username, string $key, array $configuration)
        {
        todo: use as input an array (keep input simple)
        $this->name = i18n_get_translated($name);
        $this->baseURL = $baseURL;
        $this->username = $username;
        $this->key = $key;
        $this->configuration = $configuration;
        }

    public static function parseRaw($instance): self|string
        {
        // $instance = '~en:Release~ro:Lansare|http://localhost|api_user|api_ky|{"title": 8}';
        todo: updte interface based on this implementation

        $instance_parts = array_filter(array_map('trim', explode('|', $instance)));
        $instance_parts_count = count($instance_parts);
        if ($instance_parts_count > 0 && $instance_parts_count < 5)
            {
            return 'image_banks_error_resourcespace_invalid_instance_cfg';
            }
        [$name, $baseURL, $username, $key, $json_cfg] = $instance_parts;

        if ('http' !== mb_strtolower(mb_substr($baseURL, 0, 4)))
            {
            return 'image_banks_error_bad_url_scheme';
            }

        $configuration = json_decode($json_cfg, true);
        if (json_last_error() !== JSON_ERROR_NONE)
            {
            return '(JSON) ' . json_last_error_msg();
            }

        return new self($name, $baseURL, $username, $key, $configuration);
        }
    }
