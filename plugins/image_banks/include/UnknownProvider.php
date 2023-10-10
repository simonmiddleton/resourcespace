<?php

declare(strict_types=1);

namespace ImageBanks;

class NoProvider extends Provider
    {
    function __construct(array $lang, string $temp_dir_path)
            {
            $this->id = 999;
            $this->name = $lang['unknown'];
            $this->configs = [];
            $this->warning = '';
            $this->lang = $lang;
            $this->temp_dir_path = $temp_dir_path;
            }
    public function checkDependencies(): array
        {
        return [];
        }

    public function buildConfigPageDefinition(array $page_def): array
        {
        return $page_def;
        }

    public function runSearch($keywords, $per_page = 24, $page = 1): ProviderSearchResults
        {
        return new ProviderSearchResults();
        }
    }
