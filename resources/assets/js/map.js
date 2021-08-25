import {Map, View, Overlay} from 'ol';
import LayerTile from 'ol/layer/Tile';
import LayerGroup from 'ol/layer/Group';
import SourceOSM from 'ol/source/OSM';
import SourceXYZ from 'ol/source/XYZ';
import SourceStamen from 'ol/source/Stamen';
import LayerSwitcher from 'ol-layerswitcher';
import { BaseLayerOptions, GroupLayerOptions } from 'ol-layerswitcher';
import VectorLayer from 'ol/layer/Vector';
import VectorSource from 'ol/source/Vector';
import GeoJSON from 'ol/format/GeoJSON';
import {Circle as CircleStyle, Fill, Stroke, Style} from 'ol/style';
import {fromLonLat} from 'ol/proj';
import {FullScreen, defaults as defaultControls} from 'ol/control';


var my_map = {                       // <-- add this line to declare the object
    display: function () {           // <-- add this line to declare a method
       var styles = {
          '1000': [new Style({
              image: new CircleStyle({
               radius: 5,
               fill: new Fill({color: 'yellow'}),
               stroke: new Stroke({color: 'black', width: 0.2}),
             })
           })],
          '999': [new Style({
               image: new CircleStyle({
                radius: 5,
                fill: new Fill({color: 'red'}),
                stroke: new Stroke({color: 'black', width: 0.1}),
              })
          })],
          '101': [new Style({
              stroke: new Stroke({
                  color: 'orange',
                  width: 2
              })
          })],
          '2': [new Style({
              stroke: new Stroke({
                  color: 'rgb(127,60,141)',
                  width: 3
              }),
              fill: new Fill({
                  color: 'rgb(127,60,141, 0.1)',
              })
          })],
          '3': [new Style({
              stroke: new Stroke({
                  color: 'rgb(17,165,121)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(17,165,121, 0.1)'
              })
          })],
          '4': [new Style({
              stroke: new Stroke({
                  color: 'rgb(57,105,172)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(57,105,172, 0.1)'
              })
          })],
          '5': [new Style({
              stroke: new Stroke({
                  color: 'rgb(242,183,1)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(242,183,1, 0.1)'
              })
          })],
          '6': [new Style({
              stroke: new Stroke({
                  color: 'rgb(231,63,116)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(231,63,116, 0.1)'
              })
          })],
          '7': [new Style({
              stroke: new Stroke({
                  color: 'rgb(128,186,90)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(128,186,90, 0.1)'
              })
          })],
          '8': [new Style({
              stroke: new Stroke({
                  color: 'rgb(230,131,16)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(230,131,16, 0.1)'
              })
          })],
          '100': [new Style({
              stroke: new Stroke({
                  color: 'rgb(0,134,149)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(0,134,149, 0.2)'
              })
          })],
          '99': [new Style({
              stroke: new Stroke({
                  color: 'rgb(207,28,144)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(207,28,144, 0.1)'
              })
          })],
          '97': [new Style({
              stroke: new Stroke({
                  color: 'rgb(75,75,143)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(75,75,143, 0.1)'
              })
          })],
          '98': [new Style({
              stroke: new Stroke({
                  color: 'rgb(255, 153, 0)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(255, 153, 0, 0.1)'
              })
          })],
          '102': [new Style({
              stroke: new Stroke({
                  color: 'rgb(249,123,114)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(249,123,114, 0.5)'
              })
          })],
          '9': [new Style({
              stroke: new Stroke({
                  color: 'rgb(75,75,143)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(75,75,143, 0.1)'
              })
          })],
          '10': [new Style({
              stroke: new Stroke({
                  color: 'rgb(165,170,153)',
                  width: 2
              }),
              fill: new Fill({
                  color: 'rgb(165,170,153, 0.1)'
              })
          })]
      };
      var styleFunction = function(feature, resolution) {
        var parent_level = feature.getProperties().parent_adm_level;
        var adm_level = feature.getProperties().adm_level;
        if (adm_level==parent_level) {
              adm_level = "102";
        }
        return styles[adm_level];
       };

        var haselement = document.getElementById("location_json");
        var centroid = document.getElementById("location_centroid");
        if (centroid != null) {
            var centroid_wm = fromLonLat(centroid.split(","));
        } else {
            var centroid_wm = fromLonLat([-66,-2]);
        }


        const alocation = new VectorSource({
          features: new GeoJSON().readFeatures(haselement.value,{
                        featureProjection:  'EPSG:3857'
                    }),
        });



        const osm = new LayerTile({
          title: 'OSM',
          type: 'base',
          visible: true,
          source: new SourceOSM()
        });

        const watercolor = new LayerTile({
          title: 'Water color',
          type: 'base',
          visible: false,
          source: new SourceStamen({
            layer: 'watercolor'
          })
        });


        const topographic = new LayerTile({
          title: 'ESRI Topo',
          type: 'base',
          visible: false,
          source: new SourceXYZ({
              attributions:
                'Tiles © <a href="https://services.arcgisonline.com/ArcGIS/' +
                'rest/services/World_Topo_Map/MapServer">ArcGIS</a>',
              url:
                'https://server.arcgisonline.com/ArcGIS/rest/services/' +
                'World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
          }),
        });

        const worldimagery = new LayerTile({
          title: 'ESRI Satelite',
          type: 'base',
          visible: false,
          source: new SourceXYZ({
            attributions:
              'Tiles © <a href="https://services.arcgisonline.com/ArcGIS/' +
              'rest/services/World_Imagery/MapServer">ArcGIS</a>',
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            maxZoom: 19
          })
        });

        const baseMaps = new LayerGroup({
          title: 'Base maps',
          layers: [watercolor, worldimagery, topographic,osm]
        });


        const map = new Map({
            controls: defaultControls().extend([new FullScreen()]),
            target: 'osm_map',
            layers: [baseMaps],
            view: new View({
                center: centroid_wm,
                zoom: 0
            })
        });

        const layerSwitcher = new LayerSwitcher({
            reverse: true,
            groupSelectStyle: 'group'
        });

        var addlayers = alocation.forEachFeature(function(feature) {
            var newlayer = new VectorLayer({
              title: feature.getProperties().name,
              visible: true,
              source: new VectorSource({
                  features: [feature]
              }),
              style: styleFunction
            });
            map.addLayer(newlayer);
        });

        map.addControl(layerSwitcher);

        var features = alocation.getFeatures();
        //get last
        var feature = features[ features.length-1 ];
        //if point focus on parent
        /*
        var feature_type = feature.get('adm_level');
        if (feature_type == "999") {
          feature = features[ features.length-2 ];
        }
        if ( feature_type == "1000") {
          var parent_feature = features[ features.length-2 ];
          var parent_feature_type = parent_feature.get('adm_level');
          if (parent_feature_type == "999") {
            feature = features[ features.length-3 ];
          } else {
            feature = parent_feature;
          }
        }
        alert(feature.getProperties().adm_level + 'must be the adm level!');
        */
        //const polygon = feature.getGeometry();
        //alert(feature.getProperties().adm_level + 'must be the adm level!');
        var idx = feature.getProperties().fit_geometry;
        var focusfeature = features[idx];
        const polygon = focusfeature.getGeometry();
        let view = map.getView();
        view.fit(polygon, {padding: [100, 100, 100, 100]});


        /**
         * Popup
         **/
        var container = document.getElementById('popup');
        var content_element = document.getElementById('popup-content');
        var closer = document.getElementById('popup-closer');

        closer.onclick = function() {
            overlay.setPosition(undefined);
            closer.blur();
            return false;
        };
        var overlay = new Overlay({
            element: container,
            autoPan: true,
            offset: [0,0]
        });
        map.addOverlay(overlay);

        function getCenterOfExtent(Extent){
            var X = Extent[0] + (Extent[2]-Extent[0])/2;
            var Y = Extent[1] + (Extent[3]-Extent[1])/2;
            return [X, Y];
        }

        map.on('click', function(evt){
            var feature = map.forEachFeatureAtPixel(evt.pixel,
              function(feature, layer) {
                return feature;
              });
            if (feature) {
                var geometry = feature.getGeometry();
                var coord = getCenterOfExtent(geometry.getExtent());
                var content = '<b>' + feature.get('name') + '</b>';
                //var area = feature.get('area');
                //if (area != null) {
                //    content = content + '<br>Area: ' + area +' m<sup>2</sup>';
                //}
                content = content + '<br>Type: ' + feature.get('location_type');
                //content = content + '<br>Centroid: ' + feature.get('centroid_raw');
                content_element.innerHTML = content;
                overlay.setPosition(coord);
                console.info(feature.getProperties());
            }
        });



    }                                // <-- close the method
};                                   // <-- close the object
export default my_map;               // <-- and export the object
