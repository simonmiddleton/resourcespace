<?php
// Leaflet.js Basemap Selections

if($geo_leaflet_maps_sources)
    {
    ?>
    <!--Determine basemaps and map groups for user selection-->
    var baseMaps = [
        { groupName: "<?php echo $lang['map_osm_group'];?>", <!--OSM group-->
            expanded: true,
            layers: {
                <?php if ($map_osm) { ?> "<?php echo $lang['map_osm'];?>" : osm_mapnik, <?php } ?>
                <?php if ($map_osmde) { ?> "<?php echo $lang['map_osmde'];?>" : osm_de, <?php } ?>
                <?php if ($map_osmfr) { ?> "<?php echo $lang['map_osmfr'];?>" : osm_fr, <?php } ?>
                <?php if ($map_osmbzh) { ?> "<?php echo $lang['map_osmbzh'];?>" : osm_bzh, <?php } ?>
                <?php if ($map_osmhot) { ?> "<?php echo $lang['map_osmhot'];?>" : osm_hot, <?php } ?>
                <?php if ($map_osmmtb) { ?> "<?php echo $lang['map_osmmtb'];?>" : osm_mtb, <?php } ?>
                <?php if ($map_osmhikebike) { ?> "<?php echo $lang['map_osmhikebike'];?>" : osm_hikebike, <?php } ?>
                <?php if ($map_otm) { ?> "<?php echo $lang['map_otm'];?>" : osm_otm, <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_usgstnm_group'];?>", <!--USGS The National Map group-->
            expanded: true,
            layers: {
                <?php if ($map_usgstopo) { ?> "<?php echo $lang['map_usgstopo'];?>" : usgs_topo, <?php } ?>
                <?php if ($map_usgsimagery) { ?> "<?php echo $lang['map_usgsimagery'];?>" : usgs_imagery, <?php } ?>
                <?php if ($map_usgsimagerytopo) { ?> "<?php echo $lang['map_usgsimagerytopo'];?>" : usgs_imagerytopo <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_esri_group'];?>", <!--ESRI group-->
            expanded: true,
            layers: {
                <?php if ($map_esristreet) { ?> "<?php echo $lang['map_esristreet'];?>" : esri_street, <?php } ?>
                <?php if ($map_esridelorme) { ?> "<?php echo $lang['map_esridelorme'];?>" : esri_delorme, <?php } ?>
                <?php if ($map_esritopo) { ?> "<?php echo $lang['map_esritopo'];?>" : esri_topo, <?php } ?>
                <?php if ($map_esriimagery) { ?> "<?php echo $lang['map_esriimagery'];?>" : esri_imagery, <?php } ?>
                <?php if ($map_esriterrain) { ?> "<?php echo $lang['map_esriterrain'];?>" : esri_terrain, <?php } ?>
                <?php if ($map_esrirelief) { ?> "<?php echo $lang['map_esrirelief'];?>" : esri_relief, <?php } ?>
                <?php if ($map_esriphysical) { ?> "<?php echo $lang['map_esriphysical'];?>" : esri_physical, <?php } ?>
                <?php if ($map_esriocean) { ?> "<?php echo $lang['map_esriocean'];?>" : esri_ocean, <?php } ?>
                <?php if ($map_esrinatgeo) { ?> "<?php echo $lang['map_esrinatgeo'];?>" : esri_natgeo, <?php } ?>
                <?php if ($map_esrigray) { ?> "<?php echo $lang['map_esrigray'];?>" : esri_gray <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_stamen_group'];?>", <!--Stamen group-->
            expanded: true,
            layers: {
                <?php if ($map_stamentoner) { ?> "<?php echo $lang['map_stamentoner'];?>" : stamen_toner, <?php } ?>
                <?php if ($map_stamentonerlt) { ?> "<?php echo $lang['map_stamentonerlt'];?>" : stamen_tonerlt, <?php } ?>
                <?php if ($map_stamentonerback) { ?> "<?php echo $lang['map_stamentonerback'];?>" : stamen_tonerback, <?php } ?>
                <?php if ($map_stamenterrain) { ?> "<?php echo $lang['map_stamenterrain'];?>" : stamen_terrain, <?php } ?>
                <?php if ($map_stamenterrainback) { ?> "<?php echo $lang['map_stamenterrainback'];?>" : stamen_terrainback, <?php } ?>
                <?php if ($map_stamenrelief) { ?> "<?php echo $lang['map_stamenrelief'];?>" : stamen_relief, <?php } ?>
                <?php if ($map_stamenwatercolor) { ?> "<?php echo $lang['map_stamenwatercolor'];?>" : stamen_watercolor <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_hydda_group'];?>", <!--Hydda group-->
            expanded: true,
            layers: {
                <?php if ($map_hyddafull) { ?> "<?php echo $lang['map_hyddafull'];?>" : hydda_full, <?php } ?>
                <?php if ($map_hyddabase) { ?> "<?php echo $lang['map_hyddabase'];?>" : hydda_base <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_nasagibs_group'];?>", <!--NASA GIBS group-->
            expanded: true,
            layers: {
                <?php if ($map_nasagibscolor) { ?> "<?php echo $lang['map_nasagibscolor'];?>" : nasa_gibscolor, <?php } ?>
                <?php if ($map_nasagibsfalsecolor) { ?> "<?php echo $lang['map_nasagibsfalsecolor'];?>" : nasa_gibsfalsecolor, <?php } ?>
                <?php if ($map_nasagibsnight) { ?> "<?php echo $lang['map_nasagibsnight'];?>" : nasa_gibsnight <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_tf_group'];?>", <!--Thunderforest group-->
            expanded: true,
            layers: {
                <?php if ($map_tfocm) { ?> "<?php echo $lang['map_tfocm'];?>" : tf_ocm, <?php } ?>
                <?php if ($map_tftransport) { ?> "<?php echo $lang['map_tftransport'];?>" : tf_transport, <?php } ?>
                <?php if ($map_tftransportdark) { ?> "<?php echo $lang['map_tftransportdark'];?>" : tf_transportdark, <?php } ?>
                <?php if ($map_tflandscape) { ?> "<?php echo $lang['map_tflandscape'];?>" : tf_landscape, <?php } ?>
                <?php if ($map_tfoutdoors) { ?> "<?php echo $lang['map_tfoutdoors'];?>" : tf_outdoors, <?php } ?>
                <?php if ($map_tfpioneer) { ?> "<?php echo $lang['map_tfpioneer'];?>" : tf_pioneer, <?php } ?>
                <?php if ($map_tfmobileatlas) { ?> "<?php echo $lang['map_tfmobileatlas'];?>" : tf_mobileatlas, <?php } ?>
                <?php if ($map_tfneighbourhood) { ?> "<?php echo $lang['map_tfneighbourhood'];?>" : tf_neighbourhood <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_mapbox_group'];?>", <!--Mapbox group-->
            expanded: true,
            layers: {
                <?php if ($map_mapbox) { ?> "<?php echo $lang['map_mapbox'];?>" : mapbox <?php } ?>
            }
        }
    ];
    <?php
    }
else
    {        
    ?>
    var baseMaps = [
    <?php
    foreach(array_unique(array_column($geo_leaflet_sources,"group")) as $group)
        {
        echo "{ groupName: \"" .  htmlspecialchars($group) . "\",
            expanded: true,
            layers: {\n";

        foreach($geo_leaflet_sources as $leaflet_source)
            {
            if($leaflet_source["group"] == $group)
                {
                foreach($leaflet_source["variants"] as $variant=>$varopts)
                    {
                    $geolang = $leaflet_source["name"];
                    if(isset($lang['map_' . $leaflet_source["code"] . mb_strtolower($variant)]))
                        {
                        $geolang = $lang['map_' . $leaflet_source["code"] . mb_strtolower($variant)];
                        }
                    elseif(isset($lang['map_' . $leaflet_source["code"]]))
                        {
                        $geolang =  $lang['map_' . $leaflet_source["code"]];
                        }                        
                    echo "\"" . $geolang . "\" : " . $leaflet_source["code"] . "_" . mb_strtolower($variant) . ",\n";
                    }               
                }
            }
        echo "}},\n";
        }
    echo "];\n";
}
