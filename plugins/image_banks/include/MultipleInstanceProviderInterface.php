<?php

declare(strict_types=1);

namespace ImageBanks;

interface MultipleInstanceProviderInterface
    {
    public function supportMultipleInstances(): bool;
    // public function getProviderLinkedSystems(): array;#: [<ProviderInstanceInterface>]
    // public function selectSystemInstance();
    }
