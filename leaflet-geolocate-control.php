<?php
/*
Plugin Name: Leaflet Geolocate Control
Plugin URI: https://wordpress.org/plugins/leaflet-geolocate-control/
Description: Geolocation button with tactile toggle, edit mode by longpress (orange after vibration), persistent offset, copyable coordinates, typographic outline and floating messages. Uses zoom 16 in normal mode and zoom 18 in edit mode. Uses emoji pin as control icon.
Version: 3.3.3
Author: SiO₂
Author URI: https://github.com/siO2dev
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: leaflet-geolocate-control
*/


add_action('wp_footer', function () {
    ?>
    <style>
        .leaflet-control-geolocate.leaflet-bar {
            background-color: #f4f4f4;
            border-bottom: 1px solid #ccc;
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; margin-top: 4px;
            transition: background-color 0.2s ease, transform 0.1s ease;
            font-size: 20px; line-height: 1;
        }
        .leaflet-control-geolocate.leaflet-bar:hover { background-color: #e0e0e0; }
        .leaflet-control-geolocate:active { transform: scale(0.95); }
        .leaflet-control-geolocate.activa { background-color: #9CE500 !important; color: white; }
        .leaflet-control-geolocate.modo-edicion { background-color: #FF7B00 !important; color: white; }
        .leaflet-popup-content {
            user-select: none; text-align: center; font-size: 0.95em;
        }
        .leaflet-popup-content button {
            background-color: #FF7B00; color: #954800;
            border: none; border-radius: 50px;
            padding: 4px 12px; margin: 4px 6px;
            font-size: 0.85em; font-weight: bold;
            cursor: pointer; transition: background-color 0.2s ease;
        }
        .leaflet-popup-content button:hover { background-color: #954800; color: #FF7B00; }
        .texto-outline {
            font-weight: bold; color: #954800;
            -webkit-text-stroke: 0.6px #FF7B00;
        }
        #geo-msg {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: #333; color: #fff;
            padding: 6px 12px; border-radius: 6px;
            font-size: 0.85em; z-index: 9999;
            opacity: 0; transition: opacity 0.3s ease;
            pointer-events: none;
            white-space: nowrap; display: inline-block;
            max-width: 90vw; overflow: hidden; text-overflow: ellipsis;
        }
        .leaflet-control-geolocate .pin-emoji {
            font-size: 20px; line-height: 1; user-select: none;
        }
    </style>
    <script>
    (function(){
        let mapInstance, geoMarker, geoCircle;
        let originalLat, originalLng;
        let longpressTimer, longpressDetected = false;
        let geoButton, locationActive = false;

        // Use emoji pin as icon content
        const pinEmoji = '📍';

        function applyOffset(lat, lng) {
            const o = JSON.parse(localStorage.getItem("geoOffset")||"{}");
            return [lat + (o.offsetLat||0), lng + (o.offsetLng||0)];
        }

        function showMessage(text) {
            let msg = document.getElementById("geo-msg");
            if (!msg) {
                msg = document.createElement("div");
                msg.id = "geo-msg";
                document.body.appendChild(msg);
            }
            msg.textContent = text;
            msg.style.opacity = "1";
            setTimeout(() => msg.style.opacity = "0", 2000);
        }

        function showPopup(lat, lng, mode="normal") {
            mapInstance.closePopup();
            const latStr = lat.toFixed(6), lngStr = lng.toFixed(6);
            let html = `
                <div>
                    <span class="texto-outline">
                        ${mode==="edit"
                            ? "✏️ Edit mode. Drag the marker to calibrate your location"
                            : "📍 You are here"}
                    </span><br>
                    <span class="texto-outline" style="font-size:0.85em; cursor:pointer;"
                          onclick="navigator.clipboard.writeText('${latStr}, ${lngStr}');
                                   this.innerText='✅ Copied';
                                   setTimeout(()=>this.innerText='(${latStr}, ${lngStr})',1500);">
                        (${latStr}, ${lngStr})
                    </span><br>`;
            if (mode==="edit") {
                html += `<button onclick="resetOffset()">Reset</button>
                         <button onclick="saveOffset()">Save</button>`;
            }
            html += "</div>";
            geoMarker.bindPopup(html).openPopup();
        }

        window.saveOffset = function(){
            const nue = geoMarker.getLatLng();
            const oLat = nue.lat - originalLat, oLng = nue.lng - originalLng;
            localStorage.setItem("geoOffset", JSON.stringify({offsetLat:oLat, offsetLng:oLng}));
            geoMarker.dragging.disable();
            showPopup(nue.lat, nue.lng, "normal");
            geoButton.classList.remove("modo-edicion");
            geoButton.classList.add("activa");
            locationActive = true;
            showMessage("✅ Calibration saved");
            navigator.vibrate?.(70);
        };

        window.resetOffset = function(){
            localStorage.setItem("geoOffset", JSON.stringify({offsetLat:0, offsetLng:0}));
            geoMarker.setLatLng([originalLat, originalLng]);
            geoCircle.setLatLng([originalLat, originalLng]);
            geoMarker.dragging.disable();
            showPopup(originalLat, originalLng, "normal");
            geoButton.classList.remove("modo-edicion");
            geoButton.classList.add("activa");
            locationActive = true;
            navigator.vibrate?.(50);
        };

        function insertGeolocateButton(map) {
            mapInstance = map;
            const geoIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-orange.png',
                iconSize: [25,41], iconAnchor: [12,41],
                popupAnchor: [1,-34],
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                shadowSize: [41,41]
            });

            L.Control.GeoLocate = L.Control.extend({
                options: { position: 'topleft' },
                onAdd: function(){
                    const c = L.DomUtil.create('div','leaflet-control-geolocate leaflet-bar');
                    c.title = "Locate me";
                    // Use the emoji inside a span for accessibility
                    c.innerHTML = '<span class="pin-emoji" role="img" aria-label="location pin">'+ pinEmoji +'</span>';
                    geoButton = c;
                    c.addEventListener("contextmenu", e => e.preventDefault());
                    L.DomEvent.disableClickPropagation(c);

                    function startTimer(e){
                        e.preventDefault();
                        longpressDetected = false;
                        if (longpressTimer) clearTimeout(longpressTimer);
                        longpressTimer = setTimeout(()=>{
                            longpressDetected = true;
                            navigator.vibrate?.(200);
                            geoButton.classList.add("modo-edicion");
                            geoButton.classList.remove("activa");
                            showMessage("✏️ Edit mode activated");
                        }, 1200);
                    }

                    function cancelTimer(){
                        if (longpressTimer) {
                            clearTimeout(longpressTimer);
                            longpressTimer = null;
                        }
                    }

                    function executeLocate(){
                        cancelTimer();
                        const edit = longpressDetected;
                        longpressDetected = false;

                        if (!navigator.geolocation) {
                            alert("⚠️ Your browser does not support geolocation.");
                            return;
                        }

                        // Toggle on short-press: if already active, deactivate
                        if (!edit && locationActive) {
                            navigator.vibrate?.(100);
                            geoMarker?.closePopup?.();
                            mapInstance.removeLayer(geoMarker);
                            mapInstance.removeLayer(geoCircle);
                            geoMarker = geoCircle = null;
                            geoButton.classList.remove("activa");
                            locationActive = false;
                            showMessage("❌ Location deactivated");
                            return;
                        }

                        // On short-press and not yet active, visual change on finger up
                        if (!edit && !locationActive) {
                            navigator.vibrate?.(100);
                            geoButton.classList.remove("modo-edicion");
                            geoButton.classList.add("activa");
                            locationActive = true;
                            showMessage("📍 Location activated");
                        }

                        navigator.geolocation.getCurrentPosition(
                            pos => {
                                originalLat = pos.coords.latitude;
                                originalLng = pos.coords.longitude;
                                const [latC, lngC] = applyOffset(originalLat, originalLng);
                                const latFinal = edit ? originalLat : latC;
                                const lngFinal = edit ? originalLng : lngC;

                                geoMarker && mapInstance.removeLayer(geoMarker);
                                geoCircle && mapInstance.removeLayer(geoCircle);

                                geoMarker = L.marker([latFinal, lngFinal], {
                                    icon: geoIcon,
                                    draggable: edit
                                }).addTo(mapInstance);

                                geoCircle = L.circle([latFinal, lngFinal], {
                                    radius: pos.coords.accuracy||30,
                                    color: "#FF7B00",
                                    fillColor: "#FF7B00",
                                    fillOpacity: 0.3
                                }).addTo(mapInstance);

                                const zoomLevel = edit ? 18 : 16;
                                showPopup(latFinal, lngFinal, edit ? "edit" : "normal");
                                mapInstance.setView([latFinal, lngFinal], zoomLevel);

                                if (edit) {
                                    geoMarker.on('dragend', ()=>{
                                        const n = geoMarker.getLatLng();
                                        geoCircle.setLatLng(n);
                                        showPopup(n.lat, n.lng, "edit");
                                    });
                                }
                            },
                            err => {
                                // Error: revert visual state
                                geoButton.classList.remove("activa","modo-edicion");
                                locationActive = false;
                                showMessage("❌ Could not triangulate location");
                            },
                            { enableHighAccuracy:true, timeout:10000, maximumAge:0 }
                        );
                    }

                    c.addEventListener("pointerdown", startTimer);
                    c.addEventListener("pointercancel", cancelTimer);
                    c.addEventListener("pointerleave", cancelTimer);
                    c.addEventListener("pointerup", executeLocate);
                    return c;
                }
            });

            new L.Control.GeoLocate().addTo(mapInstance);
            console.log("✅ Geolocate button inserted");
        }

        function waitForMap() {
            const mapa = WPLeafletMapPlugin.getCurrentMap();
            console.log('[Geo] Attempting to get map...', mapa);

            if (!mapa) {
                setTimeout(waitForMap, 300);
                return;
            }

            console.log('[Geo] Map detected, inserting button...');
            insertGeolocateButton(mapa);
        }

        document.addEventListener("DOMContentLoaded", () => {
            if (typeof L === "undefined") return;
            waitForMap();
        });
    })();
    </script>
    <?php
});
