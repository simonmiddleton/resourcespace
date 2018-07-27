<?php
namespace ImageBanks;

class ProviderResult
    {
    private $id;
    private $source;
    protected $original_file_url;
    protected $preview_url;
    protected $provider_url;

    public function __construct($id, Provider $provider)
        {
        $this->id = $id;
        $this->source = $provider->getName();
        }

    public function getId()
        {
        return $this->id;
        }

    public function getSource()
        {
        return $this->source;
        }

    public function setOriginalFileUrl($url)
        {
        $this->original_file_url = $url;

        return $this;
        }

    public function getOriginalFileUrl()
        {
        return $this->original_file_url;
        }

    public function setPreviewUrl($url)
        {
        $this->preview_url = $url;

        return $this;
        }

    public function getPreviewUrl()
        {
        return $this->preview_url;
        }

    public function setProviderUrl($url)
        {
        $this->provider_url = $url;

        return $this;
        }

    public function getProviderUrl()
        {
        return $this->provider_url;
        }
    }