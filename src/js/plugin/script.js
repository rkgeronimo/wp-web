/* global rkgScript */
jQuery(document).ready(($) => {
    const loginStatus = {action: 'is_user_logged_in'};
    const croDate = (str) => {
        const strArray = str.split('-');
        return `${strArray[2]}.${strArray[1]}.${strArray[0]}.`;
    };
    const layer = L.tileLayer.grayscale(
        'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
        {
            maxZoom: 18,
            attribution:
            '<a href="https://wikimediafoundation.org/wiki/Maps_Terms_of_Use">Wikimedia</a>  &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        },
    );

    // const layer = L.tileLayer(
    //    'http://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png',
    //    {
    //        maxZoom: 18,
    //        attribution:
    //        '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    //    },
    // );

    let coordinates = null;
    let zoom = 7.5;
    if (typeof rkgScript !== 'undefined') {
        const response = document.body.classList.contains('logged-in');
        if (response === true) {
            coordinates = [44.30, 16.5];
        } else {
            coordinates = [44.30, 15];
        }
        if ($(window).width() <= 1080) {
            coordinates = [44.40, 16.3];
            zoom = 6.5;
        }

        try {

            if ($('#rkg-map').length) {
                const map = L.map('rkg-map', {
                    attributionControl: false,
                    // zoomControl: false,
                    zoomSnap: 0.25,
                    gestureHandling: true,
                    gestureHandlingOptions: {
                        text: {
                            touch: 'Koristi dva prsta za mapu',
                            scroll: 'ctrl + scroll',
                            scrollMac: '\u2318 + scroll',
                        },
                    },
                }).setView(coordinates, zoom);
                L.control.attribution({position: 'bottomleft'}).addTo(map);
                map.addLayer(layer);

                const excursionsNew = $('#excursions').data('new');
                const excursionsNow = $('#excursions').data('now');
                const excursionsOld = $('#excursions').data('old');
                const nowIcon = L.divIcon({
                    className: 'now-div-icon',
                    iconSize: [14, 14],
                });
                const newIcon = L.divIcon({
                    className: 'new-div-icon',
                    iconSize: [14, 14],
                });
                const oldIcon = L.divIcon({
                    className: 'old-div-icon',
                    iconSize: [14, 14],
                });
                const newIconActive = L.divIcon({
                    className: 'new-div-icon-active',
                    iconSize: [20, 20],
                });
                const nowIconActive = L.divIcon({
                    className: 'now-div-icon-active',
                    iconSize: [20, 20],
                });
                const oldIconActive = L.divIcon({
                    className: 'old-div-icon-active',
                    iconSize: [20, 20],
                });
                const nowLayer = L.layerGroup();
                const newLayer = L.layerGroup();
                const oldLayer = L.layerGroup();
                const markers = {};
                let bounds;
                let searchLayer =  L.layerGroup();
                let searchEdgeMarkers = L.edgeMarker({
                    icon: oldIcon,
                    rotateIcons: true,
                    layerGroup: searchLayer,
                    findEdge() {
                        return L.bounds([0, 0], bounds);
                    },
                });

                for (let i = 0; i < excursionsOld.length; i++) {
                    if ((excursionsOld[i].longitude !== '')
                        && (excursionsOld[i].longitude !== '0')
                    ) {
                        markers[excursionsOld[i].id] = L.marker(
                            [excursionsOld[i].latitude, excursionsOld[i].longitude],
                            {
                                icon: oldIcon,
                                id: excursionsOld[i].id,
                                old: true,
                                now: false,
                            },
                        );
                        markers[excursionsOld[i].id].bindPopup(
                            `<b class="leaflet-popup-bold-old">${excursionsOld[i].post_title}</b><br>
                    ${croDate(excursionsOld[i].starttime)} - ${croDate(excursionsOld[i].endtime)}<br>
                    Izlet organizira: ${excursionsOld[i].display_name}<br>
                    Planirano osoba: ${excursionsOld[i].limitation}`,
                        ).on('mouseover', () => {
                            markers[excursionsOld[i].id].setIcon(oldIconActive);
                        }).on('mouseout', () => {
                            markers[excursionsOld[i].id].setIcon(oldIcon);
                        });
                        oldLayer.addLayer(markers[excursionsOld[i].id]);
                    }
                }
                oldLayer.addTo(map);

                for (let i = 0; i < excursionsNow.length; i++) {
                    if ((excursionsNow[i].longitude !== '')
                        && (excursionsNow[i].longitude !== '0')
                    ) {
                        markers[excursionsNow[i].id] = L.marker(
                            [excursionsNow[i].latitude, excursionsNow[i].longitude],
                            {
                                icon: nowIcon,
                                id: excursionsNow[i].id,
                                old: false,
                                now: true,
                            },
                        );
                        markers[excursionsNow[i].id].bindPopup(
                            `<b class="leaflet-popup-bold-now">${excursionsNow[i].post_title}</b><br>
                    ${croDate(excursionsNow[i].starttime)} - ${croDate(excursionsNow[i].endtime)}<br>
                    Izlet organizira: ${excursionsNow[i].display_name}<br>
                    Planirano osoba: ${excursionsNow[i].limitation}`,
                        ).on('mouseover', () => {
                            markers[excursionsNow[i].id].setIcon(nowIconActive);
                        }).on('mouseout', () => {
                            markers[excursionsNow[i].id].setIcon(nowIcon);
                        });
                        nowLayer.addLayer(markers[excursionsNow[i].id]);
                    }
                }
                nowLayer.addTo(map);
                
                for (let i = 0; i < excursionsNew.length; i++) {
                    if ((excursionsNew[i].longitude !== '')
                        && (excursionsNew[i].longitude !== '0')
                    ) {
                        markers[excursionsNew[i].id] = L.marker(
                            [excursionsNew[i].latitude, excursionsNew[i].longitude],
                            {
                                icon: newIcon,
                                id: excursionsNew[i].id,
                                old: false,
                                now: false,
                            },
                        );
                        markers[excursionsNew[i].id].bindPopup(
                            `<b class="leaflet-popup-bold-new">${excursionsNew[i].post_title}</b><br>
                    ${croDate(excursionsNew[i].starttime)} - ${croDate(excursionsNew[i].endtime)}<br>
                    Izlet organizira: ${excursionsNew[i].display_name}<br>
                    Planirano osoba: ${excursionsNew[i].limitation}`,
                        ).on('mouseover', () => {
                            markers[excursionsNew[i].id].setIcon(newIconActive);
                        }).on('mouseout', () => {
                            markers[excursionsNew[i].id].setIcon(newIcon);
                        });
                        newLayer.addLayer(markers[excursionsNew[i].id]);
                    }
                }
                newLayer.addTo(map);
                const eOffset = $('#excursions').offset();

                bounds = map.getSize();
                if ($(window).width() > 1080) {
                    if (response === true) {
                        bounds = [eOffset.left, map.getSize().y];
                    }
                }
                L.edgeMarker({
                    icon: nowIcon,
                    rotateIcons: true,
                    layerGroup: nowLayer,
                    findEdge() {
                        return L.bounds([0, 0], bounds);
                    },
                }).addTo(map);
                L.edgeMarker({
                    icon: newIcon,
                    rotateIcons: true,
                    layerGroup: newLayer,
                    findEdge() {
                        return L.bounds([0, 0], bounds);
                    },
                }).addTo(map);

                const oldEdgeMarkers = L.edgeMarker({
                    icon: oldIcon,
                    rotateIcons: true,
                    layerGroup: oldLayer,
                    findEdge() {
                        return L.bounds([0, 0], bounds);
                    },
                });
                oldEdgeMarkers.addTo(map);

                map.on('popupopen', (e) => {
                    const marker = e.popup._source.options;
                    const target = $(`#excursion-${marker.id}`);
                    console.log(target);
                    target.addClass('active');
                    if (marker.old) {
                        $('.excursion-new-container').hide();
                        $('.excursion-old-container').show();
                        $('.button-excursion-old').addClass('active');
                        $('.button-excursion-new').removeClass('active');
                        $('.excursion-gradient').addClass('old');
                        $('#excursions').addClass('excursions-old');
                        const topPos = target.position().top;
                        $('.excursion-old-container').scrollTop(topPos);
                    } else {
                        $('.excursion-old-container').hide();
                        $('.excursion-new-container').show();
                        $('.button-excursion-new').addClass('active');
                        $('.button-excursion-old').removeClass('active');
                        $('.excursion-gradient').removeClass('old');
                        $('#excursions').removeClass('excursions-old');
                        const topPos = target.position().top;
                        $('.excursion-new-container').scrollTop(topPos);
                    }
                });
                map.on('popupclose', () => {
                    $('.excursion').removeClass('active');
                });
                let panTimeout;
                $('.excursion').hover(
                    (e) => {
                        const getMarker = $(e.currentTarget).data('marker');
                        const marker = markers[getMarker.id];
                        if (marker.options.old) {
                            marker.setIcon(oldIconActive);
                        } else if (marker.options.now) {
                            marker.setIcon(nowIconActive);
                        } else {
                            marker.setIcon(newIconActive);
                        }
                        panTimeout = setTimeout(() => {
                            map.panTo(marker.getLatLng());
                        }, 1000);
                    },
                    (e) => {
                        const getMarker = $(e.currentTarget).data('marker');
                        const marker = markers[getMarker.id];
                        if (marker.options.old) {
                            marker.setIcon(oldIcon);
                        } else if (marker.options.now) {
                            marker.setIcon(nowIcon);
                        } else {
                            marker.setIcon(newIcon);
                        }
                        clearTimeout(panTimeout);
                    },
                );

                $('.excursion-block-btn').on('click', (e) => {
                    $(e.currentTarget).hide();
                    $('.excursion-block-search-btn').css('display', 'block');
                    $('.excursion-block-search').animate({
                        height: 'toggle',
                        opacity: 'toggle',
                    }, 'fast');
                });

                $('#excursion-block-search-form').submit((e) => {
                    e.preventDefault();
                    const form = (e.currentTarget);
                    const formData = new FormData(form);
                    formData.append('action', 'excursion_search');
                    oldLayer.remove();
                    oldEdgeMarkers.destroy();
                    searchLayer.remove();
                    searchEdgeMarkers.destroy();

                    jQuery.ajax({
                        url: rkgScript.ajaxUrl,
                        type: 'POST',
                        contentType: false,
                        processData: false,
                        dataType: 'json',
                        data: formData,
                        success(response) {
                            console.log(response);
                            $('.excursion-old-container-list-serarch').html(response.html);
                            $('.excursion-old-container-list-serarch').show();
                            $('.excursion-old-container-list').hide();
                            searchLayer = L.layerGroup();
                            for (let i = 0; i < response.cords.length; i++) {
                                markers[response.cords[i].id] = L.marker(
                                    [response.cords[i].latitude, response.cords[i].longitude],
                                    {
                                        icon: oldIcon,
                                        id: response.cords[i].id,
                                        old: true,
                                        now: false,
                                    },
                                );
                                markers[response.cords[i].id].bindPopup(
                                    `<b class="leaflet-popup-bold-old">${response.cords[i].post_title}</b><br>
                    ${croDate(response.cords[i].starttime)} - ${croDate(response.cords[i].endtime)}<br>
                    Izlet organizira: ${response.cords[i].display_name}<br>
                    Planirano osoba: ${response.cords[i].limitation}`,
                                ).on('mouseover', () => {
                                    markers[response.cords[i].id].setIcon(oldIconActive);
                                }).on('mouseout', () => {
                                    markers[response.cords[i].id].setIcon(oldIcon);
                                });
                                searchLayer.addLayer(markers[response.cords[i].id]);
                            }
                            searchLayer.addTo(map);
                            searchEdgeMarkers = L.edgeMarker({
                                icon: oldIcon,
                                rotateIcons: true,
                                layerGroup: searchLayer,
                                findEdge() {
                                    return L.bounds([0, 0], bounds);
                                },
                            });
                            searchEdgeMarkers.addTo(map);
                        },
                    });
                });

                $('.button-excursion-new').on('click', () => {
                    oldLayer.addTo(map);
                    oldEdgeMarkers.addTo(map);
                    searchLayer.remove();
                    searchEdgeMarkers.destroy();
                    $('.excursion-block-btn').css('display', 'block');
                    $('.excursion-block-search-btn').css('display', 'none');
                    $('.excursion-block-search').hide();
                    $('.excursion-old-container-list-serarch').hide();
                    $('.excursion-old-container-list').show();
                    $('.excursion-old-container').hide();
                    $('.excursion-new-container').show();
                    $('.button-excursion-new').addClass('active');
                    $('.button-excursion-old').removeClass('active');
                    $('.excursion-gradient').removeClass('old');
                    $('#excursions').removeClass('excursions-old');
                });

                $('.button-excursion-old').on('click', () => {
                    oldLayer.addTo(map);
                    oldEdgeMarkers.addTo(map);
                    searchLayer.remove();
                    searchEdgeMarkers.destroy();
                    $('.excursion-block-btn').css('display', 'block');
                    $('.excursion-block-search-btn').css('display', 'none');
                    $('.excursion-block-search').hide();
                    $('.excursion-old-container-list-serarch').hide();
                    $('.excursion-old-container-list').show();
                    $('.excursion-new-container').hide();
                    $('.excursion-old-container').show();
                    $('.button-excursion-old').addClass('active');
                    $('.button-excursion-new').removeClass('active');
                    $('.excursion-gradient').addClass('old');
                    $('#excursions').addClass('excursions-old');
                });

                $('.buton-excursion-mobile').on('click', (e) => {
                    oldLayer.addTo(map);
                    oldEdgeMarkers.addTo(map);
                    searchLayer.remove();
                    searchEdgeMarkers.destroy();
                    $('.excursion-block-btn').css('display', 'block');
                    $('.excursion-block-search-btn').css('display', 'none');
                    $('.excursion-block-search').hide();
                    $('.excursion-old-container-list-serarch').hide();
                    if ($(e.currentTarget).hasClass('old')) {
                        $(e.currentTarget).removeClass('old');
                        $(e.currentTarget).addClass('new');
                        $('.excursion-old-container-list').show();
                        $('.excursion-new-container').hide();
                        $('.excursion-old-container').show();
                        $('.button-excursion-old').addClass('active');
                        $('.button-excursion-new').removeClass('active');
                        $('.excursion-gradient').addClass('old');
                        $('#excursions').addClass('excursions-old');
                    } else {
                        $(e.currentTarget).removeClass('new');
                        $(e.currentTarget).addClass('old');
                        $('.excursion-old-container-list').show();
                        $('.excursion-old-container').hide();
                        $('.excursion-new-container').show();
                        $('.button-excursion-new').addClass('active');
                        $('.button-excursion-old').removeClass('active');
                        $('.excursion-gradient').removeClass('old');
                        $('#excursions').removeClass('excursions-old');
                    }
                });
            }
        } catch (e) {
            console.error("Error while handling excursion map: ", e.message);
        }
    }

    if ($('#rkg-admin-map').length) {
        const newIcon = L.divIcon(
            {className: 'leaflet-div-icon', iconSize: [14, 14]},
        );
        let mapAdmin;
        let marker;

        if ($('input[name=latitude]').val()) {
            mapAdmin = L.map('rkg-admin-map').setView(
                [$('input[name=latitude]').val(), $('input[name=longitude]').val()],
                15,
            );
            mapAdmin.addLayer(layer);
            marker = L.marker(
                [$('input[name=latitude]').val(), $('input[name=longitude]').val()],
                {icon: newIcon},
            ).addTo(mapAdmin);
            mapAdmin.addLayer(marker);
        } else {
            mapAdmin = L.map('rkg-admin-map').setView([44.7, 15], 6);
            mapAdmin.addLayer(layer);
        }

        const geocoderControlOptions = {
            bounds: false, // To not send viewbox
            markers: false, // To not add markers when we geocoder
            panToPoint: false, // Since no maps, no need to pan the map to the geocoded-selected location
        };

        L.control.geocoder('bd35a36b680bdf', geocoderControlOptions).addTo(mapAdmin)
            .on('select', (e) => {
                if (marker) {
                    mapAdmin.removeLayer(marker);
                }
                mapAdmin.setView(e.latlng, 15);
                marker = new L.Marker(e.latlng, {icon: newIcon});
                mapAdmin.addLayer(marker);
                $('input[name=latitude]').val(e.latlng.lat);
                $('input[name=longitude]').val(e.latlng.lng);
            });

        mapAdmin.on('click', (e) => {
            if (marker) {
                mapAdmin.removeLayer(marker);
            }
            marker = new L.Marker(e.latlng, {icon: newIcon});
            mapAdmin.addLayer(marker);
            $('input[name=latitude]').val(e.latlng.lat);
            $('input[name=longitude]').val(e.latlng.lng);
        });

        mapAdmin.setMinZoom(3);
        mapAdmin.setZoom(8);
    }

    if ($('#rkg-excursion-map').length) {
        const lat = $('#rkg-excursion-map').data('lat');
        const long = $('#rkg-excursion-map').data('long');
        const newIcon = L.divIcon(
            {className: 'new-div-icon', iconSize: [14, 14]},
        );

        const mapExcursion = L.map('rkg-excursion-map', {
            attributionControl: false,
            zoomControl: false,
            zoomSnap: 0.5,
            gestureHandling: true,
            gestureHandlingOptions: {
                text: {
                    touch: 'Koristi dva prsta za mapu',
                    scroll: 'ctrl + scroll',
                    // scrollMac: "\u2318 + scroll"
                },
            },
        }).setView(
            [lat, long],
            15,
        );
        const marker = L.marker(
            [lat, long],
            {icon: newIcon},
        ).addTo(mapExcursion);
        mapExcursion.addLayer(layer);
        mapExcursion.addLayer(marker);
    }

    $('#rkg_category').on('change', () => {
        $('#title-prompt-text').hide();
        const template = $('#rkg_category option:selected').data('template');
        const category = $('#rkg_category option:selected').val();
        $('#title').val(template.name);
        $('#rkg-location').val(template.location);
        $('#rkg-terms').val(template.terms);
        $('#rkg-price').val(template.price);
        $('#rkg-limitation').val(template.limitation);
        if ($('#wp-content-wrap').hasClass('html-active')) {
            $('#content').val(template.description);
        } else {
            const activeEditor = tinyMCE.get('content');
            if (activeEditor !== null) {
                activeEditor.setContent(template.description
                    .replace(/\n\s*\n/g, '\n').replace(
                        // eslint-disable-next-line no-control-regex
                        new RegExp('\r?\n', 'g'), '<br />',
                    ));
            }
        }

        $('#rkg_organiser').children().removeAttr('selected');
        $('.instructor-option').hide();
        $(`#instructor-${category}`).show();
        $('#instructor-none').attr('selected', 'selected');
    });

    $('.course-terms-control').on('click', () => {
        $('.course-terms').toggle();
        $('.course-terms-up').toggle();
        $('.course-terms-down').toggle();
    });

    $('.rkg-course-slick').slick({
        infinite: true,
        slidesToShow: 3,
        slidesToScroll: 3,
        prevArrow: $('.rkg-course-chevron-left'),
        nextArrow: $('.rkg-course-chevron-right'),
        responsive: [
            {
                breakpoint: 1081,
                settings: {
                    // centerMode: true,
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    // centerPadding: '25%',
                },
            },
        ],
    });

    $('#rkg-starttime').on('change', (e) => {
        const min = $(e.currentTarget).val();
        $('#rkg-endtime').attr('min', min);
    });

    $('.course-block-select').on('click', (e) => {
        e.preventDefault();
        const target = $(e.currentTarget).data('target');
        $('.course-block-terms').not(`#course-block-terms-${target}`).hide();
        $(`#course-block-terms-${target}`).animate({
            height: 'toggle',
            opacity: 'toggle',
        }, 'fast');
    });

    $(document).on('mouseup', (e) => {
        if (!$('.course-block-select').is(e.target)
            && $('.course-block-terms').has(e.target).length === 0) {
            $('.course-block-terms').hide();
        }
    });

    $('.course-block-term').on('click', (e) => {
        e.preventDefault();
        const target = $(e.currentTarget).data('target');
        const id = $(e.currentTarget).data('id');
        const link = $(e.currentTarget).data('link');
        const date = $(e.currentTarget).data('date');
        const title = $(e.currentTarget).data('title');
        const content = $(e.currentTarget).data('content');
        const signdate = $(e.currentTarget).data('signdate');
        const signclass = $(e.currentTarget).data('signclass');
        const signtext = $(e.currentTarget).data('signtext');
        $(`#course-block-link-${target}`).attr('href', link);
        $(`#course-block-link-more-${target}`).attr('href', link);
        $(`#course-block-date-${target}`).text(date);
        $(`#course-block-title-${target}`).text(title);
        $(`#course-block-content-${target}`).text(content);
        $(`#course-block-signup-${target}`).removeClass();
        $(`#course-block-signup-${target}`).addClass(signclass);
        $(`#course-block-signup-${target}`).text(signtext);
        $(`#course-block-signup-${target}`).attr('data-course', id);
        $(`#course-block-signup-${target}`).attr('data-name', title);
        $(`#course-block-signup-${target}`).attr('data-date', signdate);
        $(`#course-block-terms-${target}`).animate({
            height: 'toggle',
            opacity: 'toggle',
        }, 'fast');
        window.location = link;
    });

    $('body').on('change', '#file', (e) => {
        const fileData = $(e.currentTarget).prop('files')[0];
        const userData = $('#userId').val();
        const formData = new FormData();
        formData.append('file', fileData);
        formData.append('user', userData);
        formData.append('action', 'file_upload');

        jQuery.ajax({
            url: rkgScript.ajaxUrl,
            type: 'POST',
            contentType: false,
            processData: false,
            data: formData,
            success(response) {
                $('#brevetShow').attr('src', response);
            },
        });
    });

    $('body').on('click', '.rkg-news-category', (e) => {
        const category = $(e.currentTarget).attr('data-id');
        const formData = new FormData();
        formData.append('id', category);
        formData.append('action', 'news_block_update');

        jQuery.ajax({
            url: rkgScript.ajaxUrl,
            type: 'POST',
            contentType: false,
            processData: false,
            data: formData,
            dataType: 'json',
            success(response) {
                $('.rkg-category-container').html(response.category);
                $('.news-content-container').fadeOut('fast').promise().done(() => {
                    $('.news-content-container').html(response.content).fadeIn('fast');
                });
            },
            error(error) {
                console.log(error);
            },
        });
    });

    $('.rkg-category-container').on('click', '.rkg-news-category-select', (e) => {
        e.preventDefault();
        const target = $(e.currentTarget).data('target');
        $('.rkg-news-category-select-arrow').toggleClass('up');
        $('.rkg-news-category, .rkg-news-category-link').animate({
            height: 'toggle',
            opacity: 'toggle',
        }, 'fast');
    });

    $('#misc-publishing-actions').after($('#cancle_excursion'));

    $('#titlediv').before($('#rkg_category_table'));

    $('#rkg_excursion_data_metabox, #rkg_course_data_metabox').detach().appendTo('#prenormal-sortables');

    //// ***** Reservation / Inventory

    $('.new-inventory-icon').on('click', (e) => {
        $(e.currentTarget).hide();
        $(e.currentTarget).next().show();
    });

    // Reservation and inventory validation
    let inventoryHasValidationErrors = false;

    $('.reservations-blocklet input[type=text]').on('focus', (e) => {
        $(e.currentTarget).removeClass('hasErrors');
        $(e.currentTarget).removeClass('noErrors');
    }).on('blur', (e) => {
        const id = $(e.currentTarget).val();
        if (id.trim().length === 0) {
            // User probably misclicked, string is empty
            return;
        }

        const type = $(e.currentTarget).attr('name');
        if (type === "lead") {
            return;
        }

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: rkgScript.ajaxUrl,
            data: {
                action: 'check_inventory',
                id,
                type,
            },
            success: (data) => {
                if (data !== "ok") {
                    inventoryHasValidationErrors = true;
                    $(e.currentTarget).addClass('hasErrors');
                } else {
                    $(e.currentTarget).removeClass('hasErrors'); 
                    $(e.currentTarget).addClass('noErrors');
                    inventoryHasValidationErrors = false;
                }
            },
            error(error) {
                console.log(error);
            },
        });
    });
    $('label[for=role]').parent().parent().remove();

    // Saving equipment reservations
    $('.reservation-save').on('click', (e) => {
        if (inventoryHasValidationErrors) {
            return;
        }
        e.target.disabled = true;

        const reservationId = e.target.getAttribute("data-id");
        const data = new FormData();
        data.append('reservation', reservationId);

        const reservationEl = $(e.target).parent().parent();
        data.append('user_id', reservationEl.children('.column-user_id').text());
        const definitions = ['mask', 'regulator', 'suit', 'boots', 'gloves', 'fins', 'bcd', 'lead'];
        definitions.forEach(item => {
            const itemEl = reservationEl.find('.column-'+item+' input');
            if (itemEl.is(":visible")) {
                // Write new input value
                data.append(item, itemEl.val());
            } else {
                // Write return status 
                const statusEl = reservationEl.find('.column-'+item+' select');
                data.append(item+'_returned', statusEl.val());
            }
        });

        const comment = reservationEl.find('.column-comment textarea');;
        data.append('other', comment.val());

        data.append('action', 'edit_reservation');
        jQuery.ajax({
            url: rkgScript.ajaxUrl,
            type: 'POST',
            contentType: false,
            processData: false,
            dataType: 'json',
            data,
            success: () => {
                location.reload();
            },
            error(response) {
                if (response != "ok") {
                    $('#error-message').removeClass("hidden");
                    $('#error-text').append(response.responseText);
                    $('#error-text').removeClass("hidden");
                    $('input.button').attr('disabled', false);   
                }
            }
        });
    });

    // Deleting equipment reservations (soft delete)
    $(document).on('click', '.reservation-delete', (e) => {
        e.preventDefault();

        // Get the button element (could be the button itself or clicked on the span inside)
        const button = $(e.target).closest('.reservation-delete');
        const reservationId = button.attr('data-id');

        // Show confirmation dialog
        const confirmMessage = 'Jeste li sigurni da želite obrisati ovu rezervaciju?\n\n' +
            'Sva oprema koja je trenutno izdana bit će vraćena u dostupno stanje.\n' +
            'Izgubljena oprema će ostati označena kao izgubljena.\n\n' +
            'Ova akcija se ne može poništiti. Potrebno je dodati novu rezervaciju.';

        if (!confirm(confirmMessage)) {
            return;
        }

        button.prop('disabled', true);

        const data = new FormData();
        data.append('reservation_id', reservationId);
        data.append('action', 'delete_reservation');

        jQuery.ajax({
            url: rkgScript.ajaxUrl,
            type: 'POST',
            contentType: false,
            processData: false,
            dataType: 'json',
            data,
            success: (response) => {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Greška: ' + (response.data.message || 'Nepoznata greška'));
                    button.prop('disabled', false);
                }
            },
            error(response) {
                alert('Došlo je do greške prilikom brisanja rezervacije.');
                console.error(response);
                button.prop('disabled', false);
            }
        });
    });

    $('#custom-new-reservation').on('submit', (e) => {
        e.preventDefault();

        if (!inventoryHasValidationErrors) {
            $('input.button').attr('disabled', true);

            const form = (e.currentTarget);
            const formData = new FormData(form);
            formData.append('action', 'add_custom_reservation');

            jQuery.ajax({
                url: rkgScript.ajaxUrl,
                type: 'POST',
                contentType: false,
                processData: false,
                dataType: 'json',
                data: formData,
                success() {
                    window.location.href="/wp/wp-admin/admin.php?page=reservations";
                },
                error(response) {
                    $('#error-message').removeClass("hidden");
                    $('#error-text').append(response.responseText);
                    $('#error-text').removeClass("hidden");
                    $('input.button').attr('disabled', false);
                }
            });
        }
    });

    if ($('#guest_meta_check').prop('checked') == true) {
        $('#guest_count_row').show();
    }

    $('#guest_meta_check').on('change', (e) => {
        if ($(e.currentTarget).prop('checked') == true) {
            $('#guest_count_row').show();
        } else if ($(e.currentTarget).prop('checked') == false) {
            $('#guest_count_row').hide();
        }
    });

    $('.rkg-popover-control').on('click', (e) => {
        $('.rkg-popover').not($(e.currentTarget).next()).hide();
        $(e.currentTarget).next().toggle();
    });

    $('#niid, .preSignupSelect').select2();

    $('.rkg-admin-switch').on('click', (e) => {
        const id = $(e.currentTarget).attr('id');
        $(e.currentTarget).toggleClass(
            'dashicons-arrow-down-alt2 dashicons-arrow-up-alt2',
        );
        $(`#${id}-block`).toggle();
    });

    $('.rkg-show-map').on('click', (e) => {
        e.preventDefault();
        $(e.currentTarget).toggleClass('on off');
        $('#excursions').toggleClass('excursions-map-off');
    });

    $('.rkg-toggler, .rkg-toggler-title').on('click', (e) => {
        if ($(e.currentTarget).css('cursor') === 'pointer') {
            const toggle = $(e.currentTarget).data('toggle');
            $(e.currentTarget).find('i').toggleClass(
                'fa-chevron-down fa-chevron-up',
            );
            $(`#${toggle}`).toggle();
        }
    });

    let submittedReport = false;
    $('#generate-report').on('submit', (e) => {
        $('#HRS-loader').show();
        $('#HRS-loader').css('padding', '2em 2em');
        $('#HRS-loader').addClass('rotating-object');
        
        setTimeout(() => {
            submittedReport = true;
            $('#generate-report').submit();
        }, 2000);

        if (!submittedReport) {
            e.preventDefault();
        }
    });

    // Course unregister functionality
    let currentUserId = null;
    let currentCourseId = null;

    function showUnregisterDialog(userId, courseId, userName) {
        currentUserId = userId;
        currentCourseId = courseId;
        $('#rkg-unregister-user-name').text(userName);
        $('#rkg-dialog-overlay').show();
        $('#rkg-unregister-dialog').show();
    }

    function hideUnregisterDialog() {
        $('#rkg-dialog-overlay').hide();
        $('#rkg-unregister-dialog').hide();
        currentUserId = null;
        currentCourseId = null;
    }

    // Handle unregister button clicks
    $(document).on('click', '.rkg-unregister-btn', function () {
        const userId = $(this).attr('data-user-id');
        const courseId = $(this).attr('data-course-id');
        const userName = $(this).attr('data-user-name');
        showUnregisterDialog(userId, courseId, userName);
    });

    // Handle cancel button and overlay clicks
    $(document).on('click', '#rkg-cancel-btn, #rkg-dialog-overlay', hideUnregisterDialog);

    // Handle confirm button
    $(document).on('click', '#rkg-confirm-btn', () => {
        if (!currentUserId || !currentCourseId) return;

        const formData = new FormData();
        formData.append('action', 'rkg_unregister_user');
        formData.append('user_id', currentUserId);
        formData.append('course_id', currentCourseId);

        $.ajax({
            url: rkgScript.ajaxUrl,
            type: 'POST',
            processData: false,
            contentType: false,
            data: formData,
            success(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(`Greška: ${response.data}`);
                }
            },
            error() {
                alert('Dogodila se greška prilikom odjave korisnika.');
            },
        });

        hideUnregisterDialog();
    });

    $(document).on('click', '#apply-trash-actions', function(e) {
        var form = $(this).closest('form');
        var select = form.find('select[name="action"]');
        
        if (select.val() === 'hard_delete') {
            e.preventDefault();
            
            var selectedIds = [];
            form.find('input[name="ids[]"]:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                alert('Molimo odaberite stavke za brisanje');
                return false;
            }
            
            if (confirm('Jeste li sigurni da želite trajno obrisati odabranu opremu? Ova akcija se ne može poništiti.')) {
                var formData = new FormData();
                formData.append('action', 'rkg_inventory_hard_delete');
                selectedIds.forEach(id => formData.append('ids[]', id));

                $.ajax({
                    url: rkgScript.ajaxUrl,
                    type: 'POST',
                    processData: false,
                    contentType: false,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Greška: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Dogodila se greška prilikom brisanja.');
                    }
                });
            }
            return false;
        }
    });

    // Course reservation info updater for excursion admin
    if ($('#excursion-course-select').length) {
        var limitationInput = $('input[name="limitation"]');
        var leadersInput = $('select[name="leaders"]');
        var courseSelect = $('#excursion-course-select');
        var infoDiv = $('#reservation-info');

        function updateReservationInfo() {
            var selectedOption = courseSelect.find('option:selected');
            var courseId = selectedOption.val();
            var limitation = parseInt(limitationInput.val()) || 0;
            var leadersNeeded = parseInt(leadersInput.val()) || 0;
            
            if (courseId) {
                var participantsCount = parseInt(selectedOption.data('participants')) || 0;
                var generalSpots = limitation - participantsCount - leadersNeeded;
                generalSpots = generalSpots < 0 ? 0 : generalSpots;
                
                $('#reserved-count').text(participantsCount);
                $('#leaders-count').text(leadersNeeded);
                $('#general-count').text(generalSpots);
                infoDiv.show();
            } else if (leadersNeeded > 0) {
                var generalSpotsNoReservation = limitation - leadersNeeded;
                generalSpotsNoReservation = generalSpotsNoReservation < 0 ? 0 : generalSpotsNoReservation;
                
                $('#reserved-count').text(0);
                $('#leaders-count').text(leadersNeeded);
                $('#general-count').text(generalSpotsNoReservation);
                infoDiv.show();
            } else {
                infoDiv.hide();
            }
        }
        
        courseSelect.on('change', updateReservationInfo);
        limitationInput.on('input', updateReservationInfo);
        leadersInput.on('change', updateReservationInfo);
        
        updateReservationInfo();
    }
});
