<?php
/**
 * Simple class for IIIF API
 *
 * @internal
 */
final class IIIFRequest {

    public string   $rootlevel;
    public string   $rooturl;
    public string   $rootimageurl;
    public int      $identifier_field;
    public int      $description_field;
    public int      $sequence_field;
    public int      $license_field;
    public string   $rights_statement;
    public int      $title_field;
    public int      $max_width;
    public int      $max_height;
    public bool     $custom_sizes;
    public bool     $preview_tiles;
    public int      $preview_tile_size;
    public array    $preview_tile_scale_factors;
    public int      $download_chunk_size;
    public array    $data;
    public array    $headers;
    public array    $errors;
    public int      $errorcode;
    public array    $searchresults;
    public array    $processing;

    private array   $response;
    private array   $request;
    private bool    $validrequest;
    private int     $imagewidth;
    private int     $imageheight;
    private int     $getwidth;
    private int     $getheight;
    private int     $regionx;
    private int     $regiony;
    private int     $regionw;
    private int     $regionh;

    public function __construct($iiif_options)
        {
        foreach($iiif_options as $key => $value)
            {
            if(property_exists($this, $key))
                {
                $this->$key = $value;
                }
            }
        $this->response=[];
        $this->validrequest = false;
        $this->headers = [];
        $this->errors=[];

        return $this;
        }

    /**
     * Get the IIIF response
     *
     * @return array
     *
     */
    public function getResponse(string $element = ""): array
        {
        return ($element != "" && isset($this->response[$element])) ? $this->response[$element] : $this->response;
        }

    /**
     * Return information from the request
     *
     * @param string $element
     *
     * @return mixed
     *
     */
    public function getRequest(string $element = "")
        {
        return ($element != "" && isset($this->request[$element])) ? $this->request[$element] : $this->request;
        }

    /**
     * Is the current request valid?
     *
     * @return bool
     *
     */
    public function isValidRequest()
        {
        return $this->validrequest;
        }

    /**
     * Send the IIIF information document
     *
     */
    public function infodoc()
        {
        $this->response["@context"] = "http://iiif.io/api/presentation/2/context.json";
        $this->response["id"] = $this->rooturl;
        $this->response["type"] = "sc:Manifest";
        $arr_langdefault = i18n_get_all_translations("iiif");
        foreach($arr_langdefault as $langcode=>$langdefault)
            {
            $this->response["label"][$langcode] = [$langdefault];
            }
        $this->response["width"] = 6000;
        $this->response["height"] = 4000;
        $this->response["tiles"] = array();
        $this->response["tiles"][] = array("width" => $this->preview_tile_size, "height" => $this->preview_tile_size, "scaleFactors" => $this->preview_tile_scale_factors);
        $this->response["profile"] = array("http://iiif.io/api/image/3/level0.json");
        $this->validrequest = true;
        }

    /**
     * Extract IIIF request details from the URL path
     *
     * @param string    $url    The requested URL
     *
     * @return void
     *
     */
    public function parseUrl($url): void
        {
        $this->request = [];

        $request_url=strtok($url,'?');
        $path=substr($request_url,strpos($request_url,$this->rootlevel) + strlen($this->rootlevel));
        $xpath = explode("/",$path);

        // Set API type
        if(strtolower($xpath[0]) == "image")
            {
            $this->request["api"] = "image";
            }
        elseif(count($xpath) > 1 ||  $xpath[0] != "")
            {
            $this->request["api"] = "presentation";
            }
        else
            {
            $this->request["api"]  = "root";
            return;
            }

        if($this->request["api"] == "image")
            {
            // For image need to extract: -
            // - Resource ID
            // - type (manifest)
            // - region
            // - size
            // - rotation
            // - quality
            // - format
            $this->request["id"] = trim($xpath[1] ?? '');
            $this->request["region"] = trim($xpath[2] ?? '');
            $this->request["size"] = trim($xpath[3] ?? '');
            $this->request["rotation"] = trim($xpath[4] ?? '');
            $this->request["filename"] = trim($xpath[5] ?? '');

            if($this->request["id"]  === '')
                {
                $this->errors[] = 'Missing identifier';
                $this->triggerError(400);
                }

            if($this->request["region"] == "")
                {
                // Redirect to image information document
                $redirurl = $this->rootimageurl . $this->request["id"] . '/info.json';
                if(function_exists("http_response_code"))
                    {
                    http_response_code(303);
                    }
                header ("Location: " . $redirurl);
                exit();
                }
            // Check the request parameters
            elseif($this->request["region"] != "info.json")
                {
                if(($this->request["size"] == ""
                        ||
                        !is_int_loose($this->request["rotation"])
                        ||
                        $this->request["filename"] != "default.jpg"
                        )
                    )
                    {
                    // Not request for image information document and no sizes specified
                    $this->errors[] = "Invalid image request format.";
                    $this->triggerError(400);
                    }

                $formatparts = explode(".",$this->request["filename"]);
                if(count($formatparts) != 2)
                    {
                    // Format. As we only support IIIF Image level 0 a value of 'jpg' is required
                    $this->errors[] = ["Invalid quality or format requested. Try using 'default.jpg'"];
                    $this->triggerError(400);
                    }
                else
                    {
                    $this->request["quality"] = $formatparts[0];
                    $this->request["format"] = $formatparts[1];
                    }
                }
            }
        elseif($this->request["api"] == "presentation")
            {
            // Presentation -  need
            // - identifier
            // - type (manifest/canvas/sequence/annotation
            // - typeid (manifest/canvas/sequence/annotation

            $this->request["id"] = trim($xpath[0] ?? '');
            $this->request["type"] = trim($xpath[1] ?? '');
            $this->request["typeid"] = trim($xpath[2] ?? '');
            }
        return;
        }

    /**
    * Find all the resources to generate an array of all the canvases for the identifier ready for JSON encoding
    *
    * @param boolean $sequencekeys		Get the array with each key matching the value set in the metadata field $iiif_sequence_field. By default the array will be sorted but have a 0 based index
    *
    * @return void
    *
    */
    public function getCanvases($sequencekeys=false): void
        {
        $canvases = [];
        foreach ($this->searchresults as $iiif_result)
            {
            $size = (strtolower((string)$iiif_result["file_extension"]) != "jpg") ? "hpr" : "";
            $img_path = get_resource_path($iiif_result["ref"],true,$size,false);
            $position_prefix="";

            if(!file_exists($img_path))
                {
                continue;
                }

            $position = $iiif_result["iiif_position"];
            $canvases[$position] = $this->generateCanvas($position);
            }

        if($sequencekeys)
            {
            // keep the sequence identifiers as keys so a required canvas can be accessed by sequence id
            $this->response["items"] = $canvases;
            }

        ksort($canvases);
        foreach($canvases as $canvas)
            {
            $this->response["items"][] =  $canvas;
            }
        }

    /**
    * Get  thumbnail information for the specified resource id ready for IIIF JSON encoding
    *
    * @uses get_resource_path()
    * @uses getimagesize()
    *
    * @param int $resourceid    Resource ID

    * @return array|bool        Thumbnail image data, false if not found
    */
    public function getThumbnail($resourceid)
        {
        $img_path = get_resource_path($resourceid,true,'thm',false);
        if(!file_exists($img_path))
            {
            return false;
            }

        $thumbnail = [];
        $thumbnail["id"] = $this->rootimageurl . $resourceid . "/full/thm/0/default.jpg";
        $thumbnail["type"] = "Image";
        $thumbnail["format"] = "image/jpeg";

        // Get the size of the images
        if ((list($tw,$th) = @getimagesize($img_path))!==false)
            {
            $thumbnail["height"] = (int) $th;
            $thumbnail["width"] = (int) $tw;
            }
        else
            {
            // Use defaults
            $thumbnail["height"] = 150;
            $thumbnail["width"] = 150;
            }

        $thumbnail["service"] = [$this->generateImageService($resourceid)];
        return $thumbnail;
        }

    /**
    * Get the image for the specified identifier canvas and resource id
    *
    * @param integer $resourceid  Resource ID
    * @param array $size          ResourceSpace size information. Required information: identifier and whether it
    *                             requires to return height & width back (e.g annotations don't require it).
    *                             Please note for the identifier - we use 'hpr' if the original file is not a JPG file it
    *                             will be the value of this metadata field for the given resource
    *                             Example:
    *                             $size_info = array(
    *                               'identifier'          => 'hpr',
    *                               'return_height_width' => true
    *                             );
    *
    * @return array
    */
    public function get_image($resource, array $size_info): array
        {
        // Quick validation of the size_info param
        if(empty($size_info) || (!isset($size_info['identifier']) && !isset($size_info['return_height_width'])))
            {
            return false;
            }

        $size = $size_info['identifier'];
        $return_height_width = $size_info['return_height_width'];

        $img_path = get_resource_path($resource,true,$size,false);
        if(!file_exists($img_path))
                {
                return false;
                }

        $image_size = get_original_imagesize($resource, $img_path);

        $images = [];
        $images = [];
        $images["id"] = $this->rootimageurl . $resource . "/full/max/0/default.jpg";
        $images["type"] = "Image";
        $images["format"] = "image/jpeg";
        $images["height"] = intval($image_size[2]);
        $images["width"] = intval($image_size[1]);

        $images["service"] = [$this->generateImageService($resource)];

        if($return_height_width)
            {
            $images["height"] = intval($image_size[2]);
            $images["width"] = intval($image_size[1]);
            }

        return $images;
        }

    /**
    * Handle a IIIF error.
    *
    * @param  integer $errorcode The error code
    *
    * @return void
    */
    public function triggerError($errorcode = 404)
        {
        if(function_exists("http_response_code"))
            {
            http_response_code($errorcode); # Send error status
            }
        echo json_encode($this->errors);
        exit();
        }


    /**
     * Process a IIIF presentation request
     * @param object    $iiif   The current IIIF request object generated in api/iiif/handler.php
     *
     * @return void
     *
     */
    public function processPresentationRequest(): void
        {
        if($this->request["id"] != "" && $this->request["type"] == "")
            {
            // Redirect to manifest
            $redirurl = $this->rooturl . $this->request["id"] . "/manifest";
            if(function_exists("http_response_code"))
                {
                http_response_code(303); # Send error status
                }
            header ("Location: " . $redirurl);
            exit();
            }

        $this->getResources();

        if(is_array($this->searchresults) && count($this->searchresults)>0)
            {
            if($this->request["type"] == "manifest" || $this->request["type"] == "")
                {
                $this->generateManifest();
                $this->validrequest = true;
                }
            elseif($this->request["type"] == "canvas")
                {
                $this->getResourceFromPosition($this->request["typeid"]);

                $this->response = $this->generateCanvas($this->request["typeid"]);;
                $this->validrequest = true;
                }
            elseif($this->request["type"] == "annotationpage")
                {
                $this->getResourceFromPosition($this->request["typeid"]);
                $this->response = $this->generateAnnotationPage($this->request["typeid"]);
                $this->validrequest = true;
                }
            elseif($this->request["type"] == "annotation")
                {
                $this->getResourceFromPosition($this->request["typeid"]);
                $this->response = $this->generateAnnotation($this->request["typeid"]);
                $this->validrequest = true;
                }
            } // End of valid $identifier check based on search results
        else
            {
            $this->errorcode=404;
            $this->errors[] = "Invalid identifier: " . $this->request["id"];
            }
        return;
        }


    /**
     * Generate the top level manifest - see http://iiif.io/api/presentation/3.0/#manifest
     *
     * @return void
    */
    public function generateManifest(): void
        {
        global $lang, $defaultlanguage;
        $this->response["@context"] = "http://iiif.io/api/presentation/3/context.json";
        $this->response["id"] = $this->rooturl . $this->request["id"] . "/manifest";
        $this->response["type"] = "Manifest";

        // Descriptive metadata about the object/work
        // The manifest data should be the same for all resources that are returned.
        // This is the default when using the tms_link plugin for TMS integration.
        // Therefore we use the data from the first returned result.
        $this->data = get_resource_field_data($this->searchresults[0]["ref"]);

        // Label property
        foreach($this->searchresults as $iiif_result)
            {
            // Keep on until we find a label
            $iiif_label = get_data_by_field($iiif_result["ref"], $this->title_field);
            if(trim($iiif_label) != "")
                {
                $i18n_values = i18n_get_translations($iiif_label);
                foreach($i18n_values as $langcode=>$langstring)
                    {
                    $this->response["label"][$langcode] =[$langstring];
                    }
                break;
                }
            }
        if(!$iiif_label)
            {
            $this->response["label"][$defaultlanguage] = $lang["notavailableshort"];
            }

        foreach($this->searchresults as $iiif_result)
            {
            $description = get_data_by_field($iiif_result["ref"], $this->description_field);
            if(trim($description) != "")
                {
                $i18n_values = i18n_get_translations($description);
                foreach($i18n_values as $langcode=>$langstring)
                    {
                    $this->response["summary"][$langcode] =[$langstring];
                    }
                break; // Only metadata from one resource is required
                }
            }
        // Construct metadata array from resource field data
        $this->generateMetadata();
        if($this->license_field != 0)
            {
            $licensevals = get_data_by_field($this->searchresults[0]["ref"], $this->license_field,false);
            // exit(print_r($licensevals));
            if(count($licensevals) > 0)
                {
                // Get all field title translations
                $licensefield = get_resource_type_field($this->license_field);
                $liclabel_int = i18n_get_translations($licensefield["title"]);
                $reqstatements = ["label"=>[],"value"=>[]];
                foreach($licensevals as $licenseval)
                    {
                    $licensevals_int = i18n_get_translations($licenseval["name"]);
                    foreach($licensevals_int as $langcode=>$langstring)
                        {
                        if(!isset($reqstatements["label"][$langcode]))
                            {
                            // Translated node names may include languages that are not available for the field title
                            $reqstatements["label"][$langcode][] = $liclabel_int[$langcode] ?? $licensefield["title"];
                            }
                        $reqstatements["value"][$langcode][] = $langstring;
                        }
                    }
                
                $this->response["requiredStatement"] = $reqstatements;
                }
            }
        if(isset($this->rights_statement) && $this->rights_statement != "")
            {
            $this->response["rights_statement"] = $this->rights_statement;
            }            

        // Thumbnail property
        $this->response["thumbnail"] =[];
        foreach($this->searchresults as $iiif_result)
            {
            // Keep on until we find an image
            $iiif_thumb = $this->getThumbnail($this->searchresults[0]["ref"]);
            if($iiif_thumb)
                {
                $this->response["thumbnail"][] = $iiif_thumb;
                break;
                }
            }

        // Default behavior property - not currently configurable
        $this->response["behavior"] = ["individuals"];

        // Default viewingDirection property - not currently configurable
        $this->response["viewingDirection"] = "left-to-right";

        $this->getCanvases(false);
        }


    /**
     * Generate a canvas
     *
     * @param int       $position   The canvas identifier
     *
     * @return $canvas              Canvas data for presentation API response
     *
     */
    public function generateCanvas(int $position)
        {
        // This is essentially a resource
        // {scheme}://{host}/{prefix}/{identifier}/canvas/{name}
        $canvas = [];
        $canvasidx = array_search($position,array_column($this->searchresults,"iiif_position"));
        $resource = $this->searchresults[$canvasidx];

        $size = (strtolower($resource["file_extension"]) != "jpg") ? "hpr" : "";
        $img_path = get_resource_path($resource["ref"],true,$size,false);

        if(!file_exists($img_path))
            {
            $this->errors[] = "Invalid canvas requested";
            $this->triggerError(404);
            }
        $position_prefix = "";
        $position_field=get_resource_type_field($this->sequence_field);
        if($position_field !== false)
            {
            $position_prefix = $position_field["name"] . " ";
            }

        $position = $resource["iiif_position"];
        $position_val = $resource["field" . $this->sequence_field] ?? get_data_by_field($resource["ref"], $this->sequence_field);
        $canvas["id"] = $this->rooturl . $this->request["id"] . "/canvas/" . $position;
        $canvas["type"] = "Canvas";
        $canvas["label"]["none"] = [$position_prefix . $position_val];


        // Get the size of the images
        $image_size = get_original_imagesize($resource["ref"],$img_path);
        $canvas["height"] = intval($image_size[2]);
        $canvas["width"] = intval($image_size[1]);

        // Add image (only 1 per canvas currently supported)
        $this->getResourceFromPosition($position);
        $canvas["items"][] = $this->generateAnnotationPage($position);

        return $canvas;
        }

    /**
     * Generate the AnnotationPage elements
     *
     * @param int       $position   The annotation position
     *
     * @return array    Array of annotation pages
     *
     */
    public function generateAnnotationPage(int $position=0): array
        {
        $annotationpage=[];
        $annotationpage["id"] = $this->rooturl . $this->request["id"] . "/annotationpage/" . $position;
        $annotationpage["type"] = "AnnotationPage";
        $annotationpage["items"] = [];
        $annotationpage["items"][]=$this->generateAnnotation($position);
        return $annotationpage;
        }

    /**
     * Generate the Annotation elements
     *
     * @return array    Array of annotations
     */
    public function generateAnnotation(int $position=0): array
        {
        $annotation["id"] = $this->rooturl . $this->request["id"] . "/annotation/" . $position;
        $annotation["type"] = "Annotation";
        $annotation["motivation"] = "Painting";
        $annotation["body"] = $this->get_image($this->processing["resource"], $this->processing["size_info"]);
        $annotation["target"] = $this->rooturl . $this->request["id"] . "/canvas/" . $position;
        return $annotation;
        }

    /**
     * Generates the IIIF response for the current IIIF object (presentation API)
     *
     *
     * @return void
     */
    public function generateMetadata(): void
        {
        $metadata = [];
        $n=0;
        foreach($this->data as $iiif_data_row)
            {
            if(in_array($iiif_data_row["type"],$GLOBALS["FIXED_LIST_FIELD_TYPES"]))
                {
                // Don't use the data as this has already concatentated the translations, add an entry for each node translation by building up a new array
                $resnodes = get_resource_nodes($this->searchresults[0]["ref"],$iiif_data_row["resource_type_field"],true);
                if(count($resnodes) == 0)
                    {
                    continue;
                    }
                // Add all translated field names
                $metadata[$n] = [];
                $metadata[$n]["label"] = [];
                $i18n_titles = i18n_get_translations($iiif_data_row["title"]);
                foreach($i18n_titles as $langcode=>$langstring)
                    {
                    $metadata[$n]["label"][$langcode] =[$langstring];
                    }

                // Add all translated node names
                $arr_showlangs = [];
                $arr_alllangstrings = [];
                $arr_lang_default = [];
                foreach($resnodes as $resnode)
                    {
                    $node_langs_avail = [];
                    $i18n_names = i18n_get_translations($resnode["name"]);
                    // Set default in case no translation available for any languages
                    $defaultnodename = $i18n_names[$GLOBALS["defaultlanguage"]];
                    $arr_lang_default[] =  $defaultnodename;

                    foreach($i18n_names as $langcode=>$langstring)
                        {
                        $node_langs_avail[] = $langcode;
                        if(!isset($arr_alllangstrings[$langcode]))
                            {
                            // This is the first time this language has been found for this field
                            // Initialise the language by copying the default array of values found so far
                            $arr_alllangstrings[$langcode] = $arr_lang_default;
                            }
                        // Add to array
                        $arr_alllangs[$langcode][] =$langstring;
                        $arr_showlangs[] = $langcode;
                        }

                    // Check that this node string has been added for all translations found so far
                    foreach($arr_alllangstrings as $langcode=>$strings)
                        {
                        if(!in_array($langcode,$node_langs_avail))
                            {
                            $arr_alllangstrings[$langcode][] = $defaultnodename;
                            }
                        }
                    }
                $metadata[$n]["value"] = [];
                foreach($arr_alllangstrings as $langcode=>$strings)
                    {
                    $metadata[$n]["value"][$langcode] = [implode(NODE_NAME_STRING_SEPARATOR,$strings)];
                    }
                }
            elseif(trim((string) $iiif_data_row["value"]) !== "")
                {
            $metadata[$n] = [];
            $metadata[$n]["label"] = [];
                $i18n_titles = i18n_get_translations($iiif_data_row["title"]);
                foreach($i18n_titles as $langcode=>$langstring)
                    {
                $metadata[$n]["label"][$langcode] =[$langstring];
                    }
            $metadata[$n]["value"]=[];
                $i18n_titles = i18n_get_translations($iiif_data_row["value"]);
                foreach($i18n_titles as $langcode=>$langstring)
                    {
                    $metadata[$n]["value"][$langcode] = [$langstring];
                    }
                $n++;
                }
            }
        $this->response["metadata"] = $metadata;
        }

    /**
     * Process the IIIF Image API request - see http://iiif.io/api/image/3.0/
     * The IIIF Image API URI for requesting an image must conform to the following URI Template:
     *  {scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
     *
     * @return void
     *
     */
    public function processImageRequest(): void
        {
        $this->request["getext"] = "jpg";
        if($this->request["id"] === '')
            {
            $this->errors[] = 'Missing identifier';
            $this->triggerError(400);
            }

        if($this->request["region"] == "")
            {
            // Redirect to image information document
            $redirurl = $this->rootimageurl . $this->request["id"] . '/info.json';
            if(function_exists("http_response_code"))
                {
                http_response_code(303);
                }
            header ("Location: " . $redirurl);
            exit();
            }

        if (is_numeric($this->request["id"]))
            {
            $resource =  get_resource_data($this->request["id"]);
            $resource_access =  get_resource_access($this->request["id"]);
            }
        else
            {
            $resource_access = 2;
            }
        if($resource_access==0 && !in_array($resource["file_extension"], config_merge_non_image_types()))
            {
            // Check resource actually exists and is active
            $fulljpgsize = strtolower($resource["file_extension"]) != "jpg" ? "hpr" : "";
            $img_path = get_resource_path($this->request["id"],true,$fulljpgsize,false, "jpg");
            if(!file_exists($img_path))
                {
                // Missing file
                $this->errors[] = "No image available for this identifier";
                $this->triggerError(404);
                }
            $image_size = get_original_imagesize($this->request["id"],$img_path, "jpg");
            $this->imagewidth = (int) $image_size[1];
            $this->imageheight = (int) $image_size[2];
            $portrait = ($this->imageheight >= $this->imagewidth) ? TRUE : FALSE;

            // Get all available sizes
            $sizes = get_image_sizes($this->request["id"],true,"jpg",false);
            $availsizes = [];
            if ($this->imagewidth > 0 && $this->imageheight > 0)
                {
                foreach($sizes as $size)
                    {
                    // Compute actual pixel size - use same calculations as when generating previews
                    if ($portrait)
                        {
                        // portrait or square
                        $preheight = $size['height'];
                        $prewidth = round(($this->imagewidth * $preheight + $this->imageheight - 1) / $this->imageheight);
                        }
                    else
                        {
                        $prewidth = $size['width'];
                        $preheight = round(($this->imageheight * $prewidth + $this->imagewidth - 1) / $this->imagewidth);
                        }
                    if($prewidth > 0 && $preheight > 0 && $prewidth <= $this->max_width && $preheight <= $this->max_height)
                        {
                        $availsizes[] = array("id"=>$size['id'],"width" => $prewidth, "height" => $preheight);
                        }
                    }
                }

            if($this->request["region"] == "info.json")
                {
                // Image information request. Only fullsize available in this initial version
                $this->response["@context"] = "http://iiif.io/api/image/3/context.json";
                $this->response["extraFormats"] = [
                        "jpg",
                    ];
                $this->response["extraQualities"] = [
                    "default",
                    ];
                $this->response["id"] = $this->rootimageurl . $this->request["id"];

                $this->response["height"] = $this->imageheight;
                $this->response["width"]  = $this->imagewidth;

                $this->response["type"] = "ImageService3";
                $this->response["profile"] = "level0";
                $this->response["maxWidth"] = $this->max_width;
                $this->response["maxHeight"] = $this->max_height;
                if($this->custom_sizes)
                    {
                    $this->response["extraFeatures"] = ["sizeByH","sizeByW","sizeByWh"];
                    }

                $this->response["protocol"] = "http://iiif.io/api/image";
                $this->response["sizes"] = $availsizes;
                if($this->preview_tiles)
                    {
                    $this->response["tiles"] = [];
                    $this->response["tiles"][] = array("height" => $this->preview_tile_size, "width" => $this->preview_tile_size, "scaleFactors" => $this->preview_tile_scale_factors);
                    }
                $this->headers[] = 'Link: <http://iiif.io/api/image/3/level0.json>;rel="profile"';
                $this->validrequest = true;
                }
            else
                {
                // Process requested region
                if(!isset($this->errorcode) && $this->request["region"] != "full" && $this->request["region"] != "max" && $this->preview_tiles)
                    {
                    // If the request specifies a region which extends beyond the dimensions reported in the image information document,
                    // then the service should return an image cropped at the image’s edge, rather than adding empty space.
                    // If the requested region’s height or width is zero, or if the region is entirely outside the bounds
                    // of the reported dimensions, then the server should return a 400 status code.

                    $regioninfo = explode(",",$this->request["region"]);
                    $region_filtered = array_filter($regioninfo, 'is_numeric');
                    if(count($region_filtered) != 4)
                        {
                        // Invalid region
                        $this->errors[]  = "Invalid region requested. Use 'full' or 'x,y,w,h'";
                        $this->triggerError(400);
                        }
                    else
                        {
                        $this->regionx = (int)$region_filtered[0];
                        $this->regiony = (int)$region_filtered[1];
                        $this->regionw = (int)$region_filtered[2];
                        $this->regionh = (int)$region_filtered[3];
                        debug("IIIF region requested: x:" . $this->regionx . ", y:" . $this->regiony . ", w:" .  $this->regionw . ", h:" . $this->regionh);
                        if(fmod($this->regionx,$this->preview_tile_size) != 0 || fmod($this->regiony,$this->preview_tile_size) != 0)
                            {
                            // Invalid region
                            $this->errors[]  = "Invalid region requested. Supported tiles are " . $this->preview_tile_size . "x" . $this->preview_tile_size . " at scale factors " . implode(",",$this->preview_tile_scale_factors) . ".";
                            $this->triggerError(400);
                            }
                        else
                            {
                            $tile_request = true;
                            }
                        }
                    }
                else
                    {
                    // Full image requested
                    $tile_request = false;
                    }

                // Process size
                if(strpos($this->request["size"],",") !== false)
                    {
                    // Currently support 'w,' and ',h' syntax requests
                    $getdims    = explode(",",$this->request["size"]);
                    $this->getwidth   = (int)$getdims[0];
                    $this->getheight  = (int)$getdims[1];
                    if($tile_request)
                        {
                        if(!$this->isValidTileRequest())
                            {
                            $this->errors[] = "Invalid tile size requested";
                            $this->triggerError(400);
                            }

                        $this->request["getsize"] = "tile_" . $this->regionx . "_" . $this->regiony . "_". $this->regionw . "_". $this->regionh;
                        debug("IIIF" . $this->regionx . "_" . $this->regiony . "_". $this->regionw . "_". $this->regionh);
                        }
                    else
                        {
                        if($this->getheight == 0)
                            {
                            $this->getheight = floor($this->getwidth * ($this->imageheight/$this->imagewidth));
                            }
                        elseif($this->getwidth == 0)
                            {
                            $this->getwidth = floor($this->getheight * ($this->imagewidth/$this->imageheight));
                            }
                        // Establish which preview size this request relates to
                        foreach($availsizes  as $availsize)
                            {
                            debug("IIIF - checking available size for resource " . $resource["ref"]  . ". Size '" . $availsize["id"] . "': " . $availsize["width"] . "x" . $availsize["height"] . ". Requested size: " . $this->getwidth . "x" . $this->getheight);
                            if($availsize["width"] == $this->getwidth && $availsize["height"] == $this->getheight)
                                {
                                $this->request["getsize"] = $availsize["id"];
                                }
                            }
                        if(!isset($this->request["getsize"]))
                            {
                            if(!$this->custom_sizes || $this->getwidth > $this->max_width || $this->getheight > $this->max_height)
                                {
                                // Invalid size requested
                                $this->errors[] = "Invalid size requested";
                                $this->triggerError(400);
                                }
                            else
                                {
                                $this->request["getsize"] = "resized_" . $this->getwidth . "_". $this->getheight;
                                }
                            }
                        }

                    }
                elseif($this->request["size"] == "full"  || $this->request["size"] == "max" || $this->request["size"] == "thm")
                    {
                    if($tile_request)
                        {
                        if($this->request["size"] == "full"  || $this->request["size"] == "max")
                            {
                            $this->request["getsize"] = "tile_" . $this->regionx . "_" . $this->regiony . "_". $this->regionw . "_". $this->regionh;
                            $this->request["getext"] = "jpg";
                            }
                        else
                            {
                            $this->errors[] = "Invalid tile size requested";
                            $this->triggerError(501);
                            }
                        }
                    else
                        {
                        // Full/max image region requested
                        if($this->max_width >= $this->imagewidth && $this->max_height >= $this->imageheight)
                            {
                            $isjpeg = in_array(strtolower($resource["file_extension"]),array("jpg","jpeg"));
                            $this->request["getext"] = strtolower($resource["file_extension"]) == "jpeg" ? "jpeg" : "jpg";
                            $this->request["getsize"] = $isjpeg ? "" : "hpr";
                            }
                        else
                            {
                            $this->request["getext"] = "jpg";
                            $this->request["getsize"] = count($availsizes) > 0 ? $availsizes[0]["id"] : "thm";
                            }
                        }
                    }
                else
                    {
                    $this->errors[] = "Invalid size requested";
                    $this->triggerError(400);
                    }

                if($this->request["rotation"]!=0)
                    {
                    // Rotation. As we only support IIIF Image level 0 only a rotation value of 0 is accepted
                    $this->errors[] = "Invalid rotation requested. Only '0' is permitted.";
                    $this->triggerError(404);
                    }
                if(isset($this->request["quality"]) && $this->request["quality"] != "default" && $this->request["quality"] != "color")
                    {
                    // Quality. As we only support IIIF Image level 0 only a quality value of 'default' or 'color' is accepted
                    $this->errors[] = "Invalid quality requested. Only 'default' is permitted";
                    $this->triggerError(404);
                    }
                if(isset($this->request["format"]) && strtolower($this->request["format"]) != "jpg")
                    {
                    // Format. As we only support IIIF Image level 0 only a value of 'jpg' is accepted
                    $this->errors[] = "Invalid format requested. Only 'jpg' is permitted.";
                    $this->triggerError(404);
                    }

                if(!isset($this->errorcode))
                    {
                    // Request is supported, send the image
                    $imgpath = get_resource_path($this->request["id"],true,$this->request["getsize"],false,$this->request["getext"]);
                    debug ("IIIF: image path: " . $imgpath);
                    if(file_exists($imgpath))
                        {
                        $imgfound = true;
                        }
                    elseif($this->custom_sizes && $this->request["region"] == "full" && $this->request["region"] == "max")
                        {
                        if(is_process_lock('create_previews_' . $resource["ref"] . "_" . $this->request["getsize"]))
                            {
                            $this->errors[] = "Requested image is not currently available";
                            $this->triggerError(503);
                            }
                        $imgfound = @create_previews($this->request["id"],false,"jpg",false,true,-1,true,false,false,array($this->request["getsize"]));
                        clear_process_lock('create_previews_' . $resource["ref"] . "_" . $this->request["getsize"]);
                        }
                    if($imgfound)
                        {
                        $this->validrequest = true;
                        $this->response["image"]=$imgpath;
                        }
                    else
                        {
                        $this->errorcode = "404";
                        $this->errors[] = "No image available for this identifier";
                        }
                    }
                }
            /* IMAGE REQUEST END */
            }
        else
            {
            $this->errors[] = "Missing or invalid identifier";
            $this->triggerError(404);
            }
        }


    /**
     * Send the requested image to the IIIF client
     *
     * @return void
     */
    public function renderImage(): void
        {
        // Send the image
        $file_size   = filesize_unlimited($this->response["image"]);
        $file_handle = fopen($this->response["image"], 'rb');
        header("Access-Control-Allow-Origin: *");
        header('Content-Disposition: inline;');
        header('Content-Transfer-Encoding: binary');
        $mime = get_mime_type($this->response["image"]);
        header("Content-Type: {$mime}");
        $sent = 0;
        while($sent < $file_size)
            {
            echo fread($file_handle, $this->download_chunk_size);
            ob_flush();
            flush();
            $sent += $this->download_chunk_size;
            if(0 != connection_status())
                {
                break;
                }
            }
        fclose($file_handle);
        }

    /**
     * Find all resources associated with the given identifier and adds to the $iiif object
     *
     * @return void
     *
     */
    public function getResources(): void
        {
        $iiif_field = get_resource_type_field($this->identifier_field);
        $iiif_search = $iiif_field["name"] . ":" . $this->request["id"];
        $results = do_search($iiif_search);

        if(is_array($results))
            {
            $this->searchresults = $results
            }
        else
            {
            $this->errors[] = "Missing or invalid identifier";
            $this->triggerError(404);            
            }

        // Add sequence position information
        $resultcount = count($this->searchresults);
        $iiif_results_with_position = [];
        $iiif_results_without_position = [];
        for ($n=0;$n<$resultcount;$n++)
            {
            if($this->sequence_field != 0)
                {
                if(isset($this->searchresults[$n]["field" . $this->sequence_field]))
                    {
                    $position = $this->searchresults[$n]["field" . $this->sequence_field];
                    }
                else
                    {
                    $position = get_data_by_field($this->searchresults[$n]["ref"],$this->sequence_field);
                    }

                if(!isset($position) || trim($position) == "")
                    {
                    // Processing resources without a sequence position separately
                    debug("iiif position empty for resource ref " . $this->searchresults[$n]["ref"]);
                    $iiif_results_without_position[] = $this->searchresults[$n];
                    continue;
                    }

                debug("iiif position $position found in resource ref " . $this->searchresults[$n]["ref"]);
                $this->searchresults[$n]["iiif_position"] = $position;
                $iiif_results_with_position[] = $this->searchresults[$n];
                }
            else
                {
                $position = $n;
                debug("iiif position $position assigned to resource ref " . $this->searchresults[$n]["ref"]);
                $this->searchresults[$n]["iiif_position"] = $position;
                }
            }

        // Sort by user supplied position (handle blanks and duplicates)
        if ($this->sequence_field != 0)
            {
            # First sort by ref. Any duplicate positions will then be sorted oldest resource first.
            usort($iiif_results_with_position, function($a, $b) { return $a['ref'] - $b['ref']; });
            # Sort resources with user supplied position.
            usort($iiif_results_with_position, function($a, $b)
                {
                if(is_int_loose($a['iiif_position']) && is_int_loose($b['iiif_position']))
                    {
                    return $a['iiif_position'] - $b['iiif_position'];
                    }
                return strcmp($a['iiif_position'],$b['iiif_position']);
                });

            if (count($iiif_results_without_position) > 0 && count($iiif_results_with_position) > 0)
                {
                # Sort resources without a user supplied position by resource reference.
                # These will appear at the end of the sequence after those with a user supplied position.
                # Only applies if some resources have a sequence position else return in search results order per earlier behaviour.
                usort($iiif_results_without_position, function($a, $b) { return $a['ref'] - $b['ref']; });
                }

            $this->searchresults = array_merge($iiif_results_with_position, $iiif_results_without_position);
            foreach ($this->searchresults as $result_key => $result_val)
                {
                # Update iiif_position after sorting using unique array key, removing potential user entered duplicates in sequence field.
                # getCanvases() requires unique iiif_position values.
                $this->searchresults[$result_key]['iiif_position'] = $result_key;
                debug("final iiif position $result_key given for resource ref " . $this->searchresults[$result_key]["ref"]);
                }
            }
        }

    /**
     * Update the $iiif object with the current resource at the given canvas position
     *
     * @param int       $position   The annotation position
     *
     * @return void
     *
     */
    public function getResourceFromPosition($position): void
        {
        $this->processing = [];
        foreach($this->searchresults as $iiif_result)
            {
            if($iiif_result["iiif_position"] == $position)
                {
                $this->processing["resource"] = $iiif_result["ref"];
                $this->processing["size_info"] = [
                    'identifier' => (strtolower($iiif_result['file_extension']) != 'jpg') ? 'hpr' : '',
                    'return_height_width' => false,
                    ];
                break;
                }
            }
        }

    /**
     * Generate the image API data
     *
     * @param int       $resourceid     Resource ID
     *
     * @return array
     *
     */
    public function generateImageService(int $resourceid): array
        {
        $service = [];
        $service["id"] = $this->rootimageurl . $resourceid;
        $service["type"] = "ImageService3";
        $service["profile"] = "level0";
        return $service;
        }

    /**
     * Is the tile request valid
     *
     * @return bool
     *
     */
    public function isValidTileRequest(): bool
        {
        if(($this->getwidth == $this->preview_tile_size && $this->getheight == 0) // "w,"
            || ($this->getheight == $this->preview_tile_size && $this->getwidth == 0) // ",h"
            || ($this->getheight == $this->preview_tile_size && $this->getwidth == $this->preview_tile_size)) // "w,h"
            {
            // Standard tile widths
            return true;
            }
        elseif(($this->regionx + $this->regionw) === ($this->imagewidth)
            || ((int)$this->regiony + (int)$this->regionh) === ((int)$this->imageheight)
            )
            {
            // Size specified is not the standard tile width - only valid for right side or bottom edge of image
            if(fmod($this->regionw,$this->getwidth) == 0
                && fmod($this->regionh,$this->getheight) == 0
                )
                {
                $hscale = ceil($this->regionw / $this->getwidth);
                $vscale = ceil($this->regionh / $this->getheight);
                if($hscale == $vscale && count(array_diff([$hscale,$vscale],$this->preview_tile_scale_factors)) == 0)
                    {
                    return true;
                    }
                }
            }
        return false;
        }

    /**
     * Indicate whether the response is an image file
     *
     * @return bool
     *
     */
    public function is_image_response()
        {
        return isset($this->response["image"]);
        }
    }