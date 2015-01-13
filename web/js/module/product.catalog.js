define(
    [
        'jquery', 'underscore', 'mustache'
    ],
    function (
        $, _, mustache
    ) {
        require(['jquery.deparam'], function(){
            function setParamToUrl(name, value) {
                if ('undefined' == typeof history.pushState) {
                    return;
                }

                var queryStringObject = $.deparam((location.search || '').slice(1)) || {};

                if (null === value) {
                    delete queryStringObject[name];
                } else {
                    queryStringObject[name] = value;
                }

                history.pushState({}, '', '?' + $.param(queryStringObject));
            }

            var $body = $('body'),
                $listContainer = $('.js-productList-container'), // FIXME: хардкод
                previousCheckedElements = {},
                setFilter = function(e, data) {
                    e.stopPropagation();

                    if (data && data.disableFiltering) {
                        return;
                    }

                    var $el = $(e.target),
                        dataValue = $listContainer.data('value'),
                        elName = $el.attr('name')
                    ;

                    console.info('setFilter', $el);

                    if ($el.is(':radio')) {
                        if ($el.is(':checked') && previousCheckedElements[elName] !== $el[0]) {
                            dataValue[elName] = $el.val();
                            setParamToUrl(elName, $el.val());
                            previousCheckedElements[elName] = $el[0];
                        } else {
                            delete dataValue[elName];
                            setParamToUrl(elName, null);
                            $el.removeAttr('checked');
                            previousCheckedElements[elName] = null;
                        }
                    } else if ($el.is(':checkbox')) {
                        if ($el.is(':checked')) {
                            dataValue[elName] = $el.val();
                            setParamToUrl(elName, $el.val());
                        } else {
                            delete dataValue[elName];
                            setParamToUrl(elName, null);
                        }
                    } else if ($el.is(':text')) {
                        dataValue[elName] = $el.val();
                        setParamToUrl(elName, $el.val());
                    }

                    dataValue.page = 1;
                    loadProducts({clear: true});
                },

                deleteFilter = function(e) {
                    e.stopPropagation();

                    var $el = $(e.target),
                        currentName = $el.data('name'),
                        dataValue = $listContainer.data('value')
                    ;

                    console.info('deleteFilter', e, dataValue, currentName);

                    if (currentName) {
                        _.each(dataValue, function(value, name) {
                            if (name == currentName) {
                                delete dataValue[currentName];
                                setParamToUrl(currentName, null);
                                return true;
                            }
                        });

                        var $filter = $('.js-productFilter-set').filter('[name="' + currentName + '"]');
                        if ($filter.length) {
                            if ($filter.is(':radio')) {
                                $filter.removeAttr('checked');
                                previousCheckedElements[$filter.attr('name')] = null;
                            } else if ($filter.is(':checkbox')) {
                                $filter.removeAttr('checked');
                            } else if ($filter.is(':text')) {
                                $filter.val($filter.data('value'));
                                $filter.trigger('change', {disableFiltering: true});
                            }
                        }

                        dataValue.page = 1;
                        loadProducts({clear: true});

                        e.preventDefault();
                    }
                },

                clearFilter = function(e) {
                    e.stopPropagation();

                    var dataValue = $listContainer.data('value'),
                        dataReset = $listContainer.data('reset')
                    ;

                    console.info('clearFilter', e, dataValue);

                    // FIXME
                    dataReset.sort = dataValue.sort;

                    $listContainer.data('value', _.extend({}, dataReset));

                    $('.js-productFilter-set').each(function(i, el) {
                        var $el = $(el);

                        if ($el.is(':radio, :checkbox')) {
                            $el.removeAttr('checked');
                        } else if ($el.is(':text')) {
                            $el.val($el.data('value'));
                            $el.trigger('change', {disableFiltering: true});
                        }
                    });

                    _.each(previousCheckedElements, function(value, name) {
                        previousCheckedElements[name] = null;
                    });

                    if ('undefined' != typeof history.pushState) {
                        if (dataReset.sort) {
                            history.pushState({}, '', '?' + $.param({sort: dataReset.sort}));
                        } else {
                            history.pushState({}, '', '?');
                        }
                    }

                    dataValue.page = 1;
                    loadProducts({clear: true});

                    e.preventDefault();
                },

                setSorting = function(e) {
                    e.stopPropagation();

                    var $el = $(e.target),
                        sortingValue = $el.data('value'),
                        dataValue = $listContainer.data('value')
                    ;

                    console.info('setSorting', $el);

                    if (_.isObject(sortingValue)) {
                        _.each(sortingValue, function(value, name) {
                            dataValue[name] = value;
                            setParamToUrl(name, value);
                        });

                        dataValue.page = 1;
                        loadProducts({clear: true});

                        e.preventDefault();
                    }
                },

                loadMoreProducts = function(e) {
                    e.stopPropagation();

                    console.info('loadMoreProducts', e);

                    loadProducts({clear: false});

                    e.preventDefault();
                },

                loadProducts = function(options) {
                    var $moreLink = $('.js-productList-more'),
                        //$container = $($el.data('containerSelector')),
                        url = $listContainer.data('url'),
                        dataValue = $listContainer.data('value')
                    ;

                    options = _.extend({clear: false}, options);

                    console.info('loadProduct', $moreLink, $listContainer, dataValue);

                    if (url && (true !== $moreLink.data('disabled'))) {
                        $.get(url, dataValue)
                            .done(function(response) {
                                if (_.isObject(response.result) && dataValue && $listContainer.length) {
                                    if (true === options.clear) {
                                        $listContainer.empty();
                                    }


                                    dataValue.page = response.result.page;
                                    dataValue.count = response.result.count;

                                    if (dataValue.count <= dataValue.page * dataValue.limit) {
                                        $moreLink.hide();
                                    } else {
                                        $moreLink.show();
                                    }

                                    if (0 == dataValue.count) {
                                        if (!$('.js-noProducts').length) {
                                            $listContainer.after(mustache.render($('#tpl-productList-noProducts').html()));
                                        }
                                    } else {
                                        $('.js-noProducts').remove();
                                    }

                                    _.each(response.result.productCards, function(content) {
                                        $listContainer.append(content);
                                    });

                                    if (_.isObject(response.result.widgets)) {
                                        $body.data('widget', response.result.widgets);
                                        $body.trigger('render');
                                    }
                                }
                            })
                            .always(function() {
                                $moreLink.data('disabled', false);
                            })
                        ;

                        $moreLink.data('disabled', true);
                    }
                }
            ;


            $body
                .on('click dblclick', '.js-productList-more', loadMoreProducts)
                .on('click', 'input[type="radio"].js-productFilter-set', setFilter)
                .on('change', 'input[type="checkbox"].js-productFilter-set', setFilter)
                .on('change', 'input[type="text"].js-productFilter-set', setFilter)
                .on('click', '.js-productFilter-delete', deleteFilter)
                .on('click', '.js-productFilter-clear', clearFilter)
                .on('click', '.js-productFilter-sort', setSorting);
        });
    }
);