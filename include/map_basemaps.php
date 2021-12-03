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
                <?php if (isset($map_osm) && $map_osm) { ?> "<?php echo $lang['map_osm'];?>" : osm_mapnik, <?php } ?>
                <?php if (isset($map_osmde) && $map_osmde) { ?> "<?php echo $lang['map_osmde'];?>" : osm_de, <?php } ?>
                <?php if (isset($map_osmfr) && $map_osmfr) { ?> "<?php echo $lang['map_osmfr'];?>" : osm_fr, <?php } ?>
                <?php if (isset($map_osmbzh) && $map_osmbzh) { ?> "<?php echo $lang['map_osmbzh'];?>" : osm_bzh, <?php } ?>
                <?php if (isset($map_osmhot) && $map_osmhot) { ?> "<?php echo $lang['map_osmhot'];?>" : osm_hot, <?php } ?>
                <?php if (isset($map_osmmtb) && $map_osmmtb) { ?> "<?php echo $lang['map_osmmtb'];?>" : osm_mtb, <?php } ?>
                <?php if (isset($map_osmhikebike) && $map_osmhikebike) { ?> "<?php echo $lang['map_osmhikebike'];?>" : osm_hikebike, <?php } ?>
                <?php if (isset($map_otm) && $map_otm) { ?> "<?php echo $lang['map_otm'];?>" : osm_otm, <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_usgs_group'];?>", <!--USGS The National Map group-->
            expanded: true,
            layers: {
                <?php if (isset($map_usgstopo) && $map_usgstopo) { ?> "<?php echo $lang['map_usgstopo'];?>" : usgs_topo, <?php } ?>
                <?php if (isset($map_usgsimagery) && $map_usgsimagery) { ?> "<?php echo $lang['map_usgsimagery'];?>" : usgs_imagery, <?php } ?>
                <?php if (isset($map_usgsimagerytopo) && $map_usgsimagerytopo) { ?> "<?php echo $lang['map_usgsimagerytopo'];?>" : usgs_imagerytopo <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_esri_group'];?>", <!--ESRI group-->
            expanded: true,
            layers: {
                <?php if (isset($map_esristreet) && $map_esristreet) { ?> "<?php echo $lang['map_esristreet'];?>" : esri_street, <?php } ?>
                <?php if (isset($map_esridelorme) && $map_esridelorme) { ?> "<?php echo $lang['map_esridelorme'];?>" : esri_delorme, <?php } ?>
                <?php if (isset($map_esritopo) && $map_esritopo) { ?> "<?php echo $lang['map_esritopo'];?>" : esri_topo, <?php } ?>
                <?php if (isset($map_esriimagery) && $map_esriimagery) { ?> "<?php echo $lang['map_esriimagery'];?>" : esri_imagery, <?php } ?>
                <?php if (isset($map_esriterrain) && $map_esriterrain) { ?> "<?php echo $lang['map_esriterrain'];?>" : esri_terrain, <?php } ?>
                <?php if (isset($map_esrirelief) && $map_esrirelief) { ?> "<?php echo $lang['map_esrirelief'];?>" : esri_relief, <?php } ?>
                <?php if (isset($map_esriphysical) && $map_esriphysical) { ?> "<?php echo $lang['map_esriphysical'];?>" : esri_physical, <?php } ?>
                <?php if (isset($map_esriocean) && $map_esriocean) { ?> "<?php echo $lang['map_esriocean'];?>" : esri_ocean, <?php } ?>
                <?php if (isset($map_esrinatgeo) && $map_esrinatgeo) { ?> "<?php echo $lang['map_esrinatgeo'];?>" : esri_natgeo, <?php } ?>
                <?php if (isset($map_esrigray) && $map_esrigray) { ?> "<?php echo $lang['map_esrigray'];?>" : esri_gray <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_stamen_group'];?>", <!--Stamen group-->
            expanded: true,
            layers: {
                <?php if (isset($map_stamentoner) && $map_stamentoner) { ?> "<?php echo $lang['map_stamentoner'];?>" : stamen_toner, <?php } ?>
                <?php if (isset($map_stamentonerlt) && $map_stamentonerlt) { ?> "<?php echo $lang['map_stamentonerlt'];?>" : stamen_tonerlt, <?php } ?>
                <?php if (isset($map_stamentonerback) && $map_stamentonerback) { ?> "<?php echo $lang['map_stamentonerback'];?>" : stamen_tonerback, <?php } ?>
                <?php if (isset($map_stamenterrain) && $map_stamenterrain) { ?> "<?php echo $lang['map_stamenterrain'];?>" : stamen_terrain, <?php } ?>
                <?php if (isset($map_stamenterrainback) && $map_stamenterrainback) { ?> "<?php echo $lang['map_stamenterrainback'];?>" : stamen_terrainback, <?php } ?>
                <?php if (isset($map_stamenrelief) && $map_stamenrelief) { ?> "<?php echo $lang['map_stamenrelief'];?>" : stamen_relief, <?php } ?>
                <?php if (isset($map_stamenwatercolor) && $map_stamenwatercolor) { ?> "<?php echo $lang['map_stamenwatercolor'];?>" : stamen_watercolor <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_hydda_group'];?>", <!--Hydda group-->
            expanded: true,
            layers: {
                <?php if (isset($map_hyddafull) && $map_hyddafull) { ?> "<?php echo $lang['map_hyddafull'];?>" : hydda_full, <?php } ?>
                <?php if (isset($map_hyddabase) && $map_hyddabase) { ?> "<?php echo $lang['map_hyddabase'];?>" : hydda_base <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_nasagibs_group'];?>", <!--NASA GIBS group-->
            expanded: true,
            layers: {
                <?php if (isset($map_nasagibscolor) && $map_nasagibscolor) { ?> "<?php echo $lang['map_nasagibscolor'];?>" : nasa_gibscolor, <?php } ?>
                <?php if (isset($map_nasagibsfalsecolor) && $map_nasagibsfalsecolor) { ?> "<?php echo $lang['map_nasagibsfalsecolor'];?>" : nasa_gibsfalsecolor, <?php } ?>
                <?php if (isset($map_nasagibsnight) && $map_nasagibsnight) { ?> "<?php echo $lang['map_nasagibsnight'];?>" : nasa_gibsnight <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_tf_group'];?>", <!--Thunderforest group-->
            expanded: true,
            layers: {
                <?php if (isset($map_tfocm) && $map_tfocm) { ?> "<?php echo $lang['map_tfocm'];?>" : tf_ocm, <?php } ?>
                <?php if (isset($map_tftransport) && $map_tftransport) { ?> "<?php echo $lang['map_tftransport'];?>" : tf_transport, <?php } ?>
                <?php if (isset($map_tftransportdark) && $map_tftransportdark) { ?> "<?php echo $lang['map_tftransportdark'];?>" : tf_transportdark, <?php } ?>
                <?php if (isset($map_tflandscape) && $map_tflandscape) { ?> "<?php echo $lang['map_tflandscape'];?>" : tf_landscape, <?php } ?>
                <?php if (isset($map_tfoutdoors) && $map_tfoutdoors) { ?> "<?php echo $lang['map_tfoutdoors'];?>" : tf_outdoors, <?php } ?>
                <?php if (isset($map_tfpioneer) && $map_tfpioneer) { ?> "<?php echo $lang['map_tfpioneer'];?>" : tf_pioneer, <?php } ?>
                <?php if (isset($map_tfmobileatlas) && $map_tfmobileatlas) { ?> "<?php echo $lang['map_tfmobileatlas'];?>" : tf_mobileatlas, <?php } ?>
                <?php if (isset($map_tfneighbourhoodX) && $map_tfneighbourhood) { ?> "<?php echo $lang['map_tfneighbourhood'];?>" : tf_neighbourhood <?php } ?>
            }
        },

        { groupName: "<?php echo $lang['map_mapbox_group'];?>", <!--Mapbox group-->
            expanded: true,
            layers: {
                <?php if (isset($map_mapbox) && $map_mapbox) { ?> "<?php echo $lang['map_mapbox'];?>" : mapbox <?php } ?>
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
    foreach($geo_leaflet_sources as $leaflet_source)
        {
        echo "{ groupName: \"" .  htmlspecialchars(i18n_get_translated($leaflet_source["name"])) . "\",
            expanded: true,
            layers: {\n";
        foreach($leaflet_source["variants"] as $variant=>$varopts)
            {                
            $variantname =  isset($varopts["name"]) ? $varopts["name"] : $leaflet_source["name"];      
            echo "\"" . $variantname . "\" : " . mb_strtolower($leaflet_source["code"] . "_" . $variant) . ",\n";
            }               
        echo "}},\n";
        }
    // Add in the high level tiles included with RS
    echo "{ groupName: \"ResourceSpace\", expanded: true, layers: {\"OSM\" : rs_default}},\n";

    echo "];\n";
}
