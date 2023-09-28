<?php

declare(strict_types=1);

namespace ImageBanks;

interface MultipleInstanceProviderInterface
    {
    /**
     * @return list<ProviderInstanceInterface>
     */
    public function getProviderLinkedSystems(): array;
    // public function selectSystemInstance();
    }
