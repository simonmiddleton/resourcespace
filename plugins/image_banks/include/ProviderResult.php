<?php
namespace ImageBanks;

class ProviderResult
    {
    private $id;
    private $provider;
    private $title = "";

    protected $original_file_url;
    protected $provider_url;

    protected $preview_url;
    protected $preview_width;
    protected $preview_height;

    public function __construct($id, Provider $provider)
        {
        $this->id = $id;
        $this->provider = $provider;

        return $this;
        }

    public function getId()
        {
        return $this->id;
        }

    public function getSource()
        {
        return $this->provider->getName();
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

    public function setPreviewWidth($width)
        {
        if(!is_int($width))
            {
            trigger_error("setPreviewWidth function only accepts integers. Argument supplied was: '{$width}'");
            }

        $this->preview_width = $width;

        return $this;
        }
    public function getPreviewWidth()
        {
        return $this->preview_width;
        }

    public function setPreviewHeight($height)
        {
        if(!is_int($height))
            {
            trigger_error("setPreviewHeight function only accepts integers. Argument supplied was: '{$height}'");
            }

        $this->preview_height = $height;

        return $this;
        }
    public function getPreviewHeight()
        {
        return $this->preview_height;
        }

    public function setTitle($title)
        {
        $this->title = trim($title);

        return $this;
        }
    public function getTitle()
        {
        return $this->title;
        }
    }