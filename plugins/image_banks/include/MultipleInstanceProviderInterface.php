<?php

declare(strict_types=1);

namespace ImageBanks;

interface MultipleInstanceProviderInterface
    {
    public function parseInstancesConfiguration(): array;

    /**
     * @return array<ProviderInstanceInterface>
     */
    public function getAllInstances(): array;

    // public function selectSystemInstance();
    }
