window.addEventListener('DOMContentLoaded', function() {
    (function($) {

    /* Tabs */

    $.fn.pslTabs = function() {
        this.each(function() {
            var container = $(this);
            container.children('ul').find('a').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).parents('li').first();
                var isActive = tab.hasClass('psl-tab-active');

                container.children('div').hide();
                container.find('.psl-tab-active').removeClass('psl-tab-active');

                if (!isActive) {
                    container.children('div' + $(this).attr('href')).show();
                    tab.addClass('psl-tab-active');
                    if (typeof window.pslTabMap !== 'undefined' && tab.hasClass('psl-tab-map')) {
                        window.pslTabMap.invalidateSize();
                    }
                }
            });

            container.children('div').hide();
        });
    };

    /* Markers */

    var defaultLocation = [50.695, -5.537];
    var defaultZoom = 3;

    var markers = [];

    var selectMarker = function(marker) {
        marker.selected = true;
        marker.setOpacity(1);
    };
    var deselectMarker = function(marker) {
        marker.selected = false;
        marker.setOpacity(0.5);
    };
    var deselectAllMarkers = function() {
        for (name in markers) {
            deselectMarker(markers[name]);
        }
    };

    var spatialCoverageInput = $('#psl-search-form input[name="map[spatial-coverage]"]');
    spatialCoverageInput.on('change', function() {
        var name = $(this).val();
        deselectAllMarkers();
        if (markers[name]) {
            selectMarker(markers[name]);
        }
    });

    // TODO Make locations dynamic or configurable.
    var locations = typeof searchLocations === 'undefined' ? {} : searchLocations;

    // TODO Make map dynamic or configurable.
    var map = L.map('psl-search-form-leaflet-map', {
        scrollWheelZoom: false,
        attributionControl: false
    }).setView(defaultLocation, defaultZoom);
    window.pslTabMap = map;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    for (name in locations) {
        var coords = locations[name]['coords'];
        var count = locations[name]['count']

        if (coords.indexOf(',') === -1) {
            continue;
        }

        var marker = L.marker(coords.split(','), {
            title: name,
            opacity: 0.5
        }).addTo(map);

        marker.selected = false;
        marker.name = name;

        marker.bindPopup(name + ' (' + count + ')');
        marker.on('click', function(e) {
            var m = e.target;
            spatialCoverageInput.val(m.name).change();
        });
        marker.on('mouseover', function(e) {
            var m = e.target;
            if (!m.selected) {
                m.setOpacity(0.75);
            }
        });
        marker.on('mouseout', function(e) {
            var m = e.target;
            if (!m.selected) {
                m.setOpacity(0.5);
            }
        });

        markers[name] = marker;
    }

    spatialCoverageInput.trigger('change');

    /* Date range */

    var dateFrom = $('#psl-search-form input[name="date[from]"]');
    var dateTo = $('#psl-search-form input[name="date[to]"]');
    var dateMin = 1300;
    var dateMax = 2100;

    // FIXME Incompatibility with UniversalViewer (slider comes from jQuery-UI).
    if ($.isFunction($.fn.slider)) {

    $('#psl-search-form-date-slider').slider({
        range: true,
        min: dateMin,
        max: dateMax,
        step: 100,
        values: [dateFrom.val() || dateMin, dateTo.val() || dateMax],
        change: function(event, ui) {
            var from = ui.values[0] > dateMin ? ui.values[0] + 1 : '';
            var to = ui.values[1] < dateMax ? ui.values[1] : '';
            if (from != dateFrom.val()) {
                dateFrom.val(from).change();
            }
            if (to != dateTo.val()) {
                dateTo.val(to).change();
            }
        }
    });

    }

    dateFrom.parents('.field').hide();
    dateTo.parents('.field').hide();

    /* Filter management */

    $('#psl-search-form .psl-add-filter').on('click', function() {
        var filters = $(this).parents('.filters');
        var count = filters.children('.filter').length;
        var template = filters.children('span').attr('data-template');
        template = template.replace(/__index__/g, count);
        filters.children('.filter').last().after(template);
    });

    /* Form and tab management */

    var form = $('#psl-search-form');

    form.pslTabs();

    var mapInputsSelector = '#psl-search-form-map input';
    var dateInputsSelector = '#psl-search-form-date input';
    var itemsetInputsSelector = '#psl-search-form-itemset input';
    var searchInputsSelector = '.psl-tab-search input';
    var textInputsSelector = '#psl-search-form-text input';

    var tabs = {
        map: {
            inputsSelector: mapInputsSelector,
            isPopulated: function() {
                var inputs = form.find(mapInputsSelector).toArray();
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].value !== '') {
                        return true;
                    }
                }
            },
            reset: function() {
                form.find(mapInputsSelector).val('').change();
            }
        },
        date: {
            inputsSelector: dateInputsSelector,
            isPopulated: function() {
                var inputs = form.find(dateInputsSelector).toArray();
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].value !== '') {
                        return true;
                    }
                }
            },
            reset: function() {
                var dateSlider = $('#psl-search-form-date-slider');
                var min = dateSlider.slider('option', 'min');
                var max = dateSlider.slider('option', 'max');
                dateSlider.slider('values', [min, max]);
            }
        },
        itemset: {
            inputsSelector: itemsetInputsSelector,
            isPopulated: function() {
                var inputs = form.find(itemsetInputsSelector).toArray();
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].checked) {
                        return true;
                    }
                }
            },
            reset: function() {
                form.find(itemsetInputsSelector).prop('checked', false).change();
            }
        },
        search: {
            inputsSelector: searchInputsSelector,
            isPopulated: function() {
                var inputs = form.find(searchInputsSelector).toArray();
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].value !== '') {
                        return true;
                    }
                }
            },
            reset: function() {
                form.find(searchInputsSelector).val('').change();
            }
        },
        text: {
            inputsSelector: textInputsSelector,
            isPopulated: function() {
                var inputs = form.find(textInputsSelector).toArray();
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].value !== '') {
                        return true;
                    }
                }
            },
            reset: function() {
                form.find(textInputsSelector).parents('.filter').slice(2).remove();
                form.find(textInputsSelector).val('').change();
                $('#psl-search-form-text select').each(function() {
                    this.selectedIndex = 0;
                });
            }
        }
    };

    var formIsPopulated = function() {
        return form.find('.psl-tab-populated').length > 0;
    };

    // Populated tab markers
    for (name in tabs) {
        var tab = tabs[name];
        var handler = (function(name) {
            return function() {
                var li = form.find('li.psl-tab-' + name);
                var div = $('#psl-search-form-' + name);
                if (tabs[name].isPopulated()) {
                    li.addClass('psl-tab-populated');
                    div.addClass('psl-tab-populated');
                } else {
                    li.removeClass('psl-tab-populated');
                    div.removeClass('psl-tab-populated');
                }

                var newSearchButton = $('#psl-search-form-wrapper .psl-new-search');
                if (formIsPopulated()) {
                    newSearchButton.css('visibility', 'visible');
                } else {
                    newSearchButton.css('visibility', 'hidden');
                }
            };
        })(name);

        form.on('change keyup', tab.inputsSelector, handler);
        form.find(tab.inputsSelector).trigger('change');
    }

    // Reset buttons
    form.find('button.reset-tab').on('click', function(e) {
        e.preventDefault();
        var name = $(this).attr('data-tab');
        tabs[name].reset();
    });

    $('#psl-search-form-wrapper .psl-new-search').on('click', function(e) {
        e.preventDefault();
        for (name in tabs) {
            tabs[name].reset();
        }
    });

    })(jQuery);
});
