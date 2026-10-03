(function () {
    let leafletPromise = null;

    function loadLeaflet() {
        if (window.L) {
            return Promise.resolve(window.L);
        }
        if (leafletPromise) {
            return leafletPromise;
        }

        leafletPromise = new Promise(function (resolve, reject) {
            const stylesheet = document.createElement('link');
            stylesheet.rel = 'stylesheet';
            stylesheet.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            stylesheet.crossOrigin = '';
            document.head.appendChild(stylesheet);

            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.crossOrigin = '';
            script.onload = function () {
                if (!window.L) {
                    leafletPromise = null;
                    reject(new Error('The free map library did not initialize. Refresh the page and try again.'));
                    return;
                }
                resolve(window.L);
            };
            script.onerror = function () {
                leafletPromise = null;
                reject(new Error('The free map could not be loaded. Check your internet connection and try again.'));
            };
            document.head.appendChild(script);
        });

        return leafletPromise;
    }

    window.createDeliveryLocationPicker = function (options) {
        const mapElement = document.getElementById(options.mapId);
        const statusElement = document.getElementById(options.statusId);
        const streetInput = document.getElementById(options.streetId);
        const latitudeInput = document.getElementById(options.latitudeId);
        const longitudeInput = document.getElementById(options.longitudeId);
        const confirmButton = document.getElementById(options.confirmButtonId);
        let map = null;
        let marker = null;
        let confirmed = false;
        let requestId = 0;
        let searchController = null;
        let reverseController = null;
        let reverseGeocodeTimer = null;

        function setStatus(message, isError) {
            statusElement.textContent = message;
            statusElement.className = 'small mt-2 ' + (isError ? 'text-danger' : 'text-muted');
        }

        function setConfirmed(value) {
            confirmed = value;
            mapElement.classList.toggle('d-none', value);
            if (!value && map) {
                window.setTimeout(function () {
                    map.invalidateSize();
                }, 100);
            }
            options.onConfirmationChange(value);
        }

        function resetSelection() {
            requestId++;
            window.clearTimeout(reverseGeocodeTimer);
            searchController?.abort();
            reverseController?.abort();
            marker?.remove();
            marker = null;
            latitudeInput.value = '';
            longitudeInput.value = '';
            streetInput.value = '';
            streetInput.disabled = true;
            confirmButton.disabled = true;
            confirmButton.classList.remove('btn-success');
            confirmButton.classList.add('btn-outline-success');
            setConfirmed(false);
            statusElement.textContent = '';
        }

        function reverseGeocode(position) {
            reverseController?.abort();
            window.clearTimeout(reverseGeocodeTimer);
            const currentRequestId = ++requestId;
            latitudeInput.value = position.lat.toFixed(7);
            longitudeInput.value = position.lng.toFixed(7);
            streetInput.value = '';
            streetInput.disabled = false;
            confirmButton.disabled = true;
            confirmButton.classList.remove('btn-success');
            confirmButton.classList.add('btn-outline-success');
            setConfirmed(false);
            setStatus('Finding the address for the selected pin. If lookup fails, enter the street or landmark below.', false);

            reverseGeocodeTimer = window.setTimeout(function () {
                reverseController = new AbortController();
                fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18&addressdetails=1&lat='
                    + encodeURIComponent(position.lat) + '&lon=' + encodeURIComponent(position.lng), {
                        signal: reverseController.signal,
                        headers: { Accept: 'application/json' }
                    })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Address lookup is temporarily unavailable.');
                        return response.json();
                    })
                    .then(function (result) {
                        if (currentRequestId !== requestId) return;
                        if (!result.display_name) throw new Error('No address was found for this pin.');
                        if (!streetInput.value.trim()) {
                            streetInput.value = result.display_name;
                        }
                        confirmButton.disabled = !streetInput.value.trim();
                        setStatus('Address found. Add a house number or landmark if needed, then confirm.', false);
                    })
                    .catch(function (error) {
                        if (error.name === 'AbortError' || currentRequestId !== requestId) return;
                        confirmButton.disabled = !streetInput.value.trim();
                        setStatus(error.message + ' Enter the street or landmark manually to continue.', true);
                    });
            }, 1100);
        }

        function placeMarker(position) {
            if (!marker) {
                marker = window.L.marker(position, { draggable: true }).addTo(map);
                marker.on('dragend', function (event) {
                    reverseGeocode(event.target.getLatLng());
                });
            } else {
                marker.setLatLng(position);
            }
            reverseGeocode(position);
        }

        function ensureMap() {
            return loadLeaflet().then(function () {
                if (!map) {
                    map = window.L.map(mapElement).setView([12.8797, 121.774], 6);
                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
                    }).addTo(map);
                    map.on('click', function (event) {
                        placeMarker(event.latlng);
                    });
                    map.on('tileerror', function () {
                        setStatus('Map tiles could not be loaded. Check your internet connection and try again.', true);
                    });
                }
                return map;
            });
        }

        function show(addressParts) {
            const currentRequestId = ++requestId;
            setStatus('Loading the map and locating the selected barangay...', false);
            return ensureMap().then(function () {
                if (currentRequestId !== requestId) return;
                window.setTimeout(function () {
                    map.invalidateSize();
                }, 100);

                searchController?.abort();
                searchController = new AbortController();
                const address = addressParts.filter(Boolean).concat('Philippines').join(', ');
                return fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q='
                    + encodeURIComponent(address), {
                        signal: searchController.signal,
                        headers: { Accept: 'application/json' }
                    })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Unable to locate the selected barangay.');
                        return response.json();
                    })
                    .then(function (results) {
                        if (currentRequestId !== requestId) return;
                        if (!results.length) {
                            setStatus('Barangay not found automatically. Zoom or pan the map, then click the exact delivery point.', true);
                            return;
                        }
                        const latitude = Number(results[0].lat);
                        const longitude = Number(results[0].lon);
                        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                            throw new Error('The barangay lookup returned an invalid map location.');
                        }
                        map.setView([latitude, longitude], 16);
                        setStatus('Barangay located. Click the exact point or drag the pin after placing it.', false);
                    });
            }).catch(function (error) {
                if (error.name === 'AbortError' || currentRequestId !== requestId) return;
                setStatus(error.message || 'Unable to load the map or selected barangay.', true);
            });
        }

        function setError(message) {
            setStatus(message, true);
        }

        function confirm() {
            if (!latitudeInput.value || !longitudeInput.value || !streetInput.value.trim()) {
                setStatus('Choose a pin on the map and enter its street address or landmark before confirming.', true);
                return;
            }
            confirmButton.classList.remove('btn-outline-success');
            confirmButton.classList.add('btn-success');
            setConfirmed(true);
            setStatus('Delivery location confirmed.', false);
        }

        streetInput.addEventListener('input', function () {
            confirmButton.disabled = !latitudeInput.value
                || !longitudeInput.value
                || !streetInput.value.trim();
            if (confirmed) {
                confirmButton.classList.remove('btn-success');
                confirmButton.classList.add('btn-outline-success');
                setConfirmed(false);
                setStatus('Address changed. Confirm the delivery location again.', false);
            }
        });
        confirmButton.addEventListener('click', confirm);

        const picker = {
            show: show,
            resetSelection: resetSelection,
            setError: setError,
            isConfirmed: function () { return confirmed; }
        };
        options.onConfirmationChange(false);
        return picker;
    };
})();
