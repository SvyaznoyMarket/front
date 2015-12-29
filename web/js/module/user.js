define(
    [
        'jquery', 'underscore', 'mustache', 'module/user.profile'
    ],
    function (
        $, _, mustache
    ) {

        var
            $body = $('body'),
            $modalWindowTemplate = $('#tpl-modalWindow'),

            toggleMenu = function() {
                var
                    url = document.location.pathname,
                    urlHash = document.location.hash;

                console.info('hideMenu');
                if (url == '/private') {
                    //e.preventDefault();
                    if (urlHash == '#active') {
                        console.info('hideMenu', 'OK');
                        $('.js-content').removeClass('private-index')
                    } else {
                        console.info('hideMenu', 'NO');
                        $('.js-content').addClass('private-index');
                    }
                }
            }
        ;

        /*
        $body.on('click', '.js-user-menu', function(e) {
            var
                $el = $(this),
                url = $el.attr('href'),
                $container = $('.id-content-container')
            ;

            try {
                if (!url) {
                    throw {message: 'Не задан url'}
                }

                $.get(url).done(function(response) {
                    if (response.content) {
                        $container.html(response.content);
                    }
                });

                e.preventDefault();
            } catch (error) { console.error(error); }
        });
        */

        toggleMenu();

        window.addEventListener('popstate', function(e){
            toggleMenu();
        }, false);

        $body.on('click', '.js-menuHide', function(e){
            toggleMenu();
        });

        $body.on('click', '.js-user-subscribe-input', function() {
            var
                $el = $(this),
                data = $el.data('value'),
                isChecked = !!$el.is(':checked'),
                url = isChecked ? $el.data('setUrl') : $el.data('deleteUrl')
            ;

            try {
                if (!url) {
                    throw {message: 'Нет url'};
                }

                $.post(url, data).done(function(response) {
                    if (!response.success) {
                    }
                });
            } catch(error) { console.error(error) };
        });

        $body.on('click', '.js-user-address-delete', function(e) {

            var
                $el = $(this),
                $modalWindow,
                $deleteFormTemplate = $('#tpl-deleteForm'),
                modalPosition = $el.data('modal-position'),
                templateData = $el.data('value') || {},
                content
            ;
            console.info($modalWindowTemplate.html());

            e.stopPropagation();

            content = mustache.render($deleteFormTemplate.html(), templateData);
            $modalWindow = $(mustache.render($modalWindowTemplate.html(), {'title': 'Удалить адрес?', content: content})).appendTo($body);
            $modalWindow.addClass(modalPosition);

            $modalWindow.lightbox_me({
                onLoad: function() {
                    $modalWindow.find('.js-modal-content').append(content);
                },
                beforeClose: function() {}
            });

            e.preventDefault();
        });

        $body.on('click', '.js-user-favorite-delete', function(e) {
            var
                $el = $(this),
                $modalWindow,
                $deleteFormTemplate = $('#tpl-deleteForm'),
                modalPosition = $el.data('modal-position'),
                templateData = $el.data('value') || {},
                content
                ;
            console.info($modalWindowTemplate.html());

            e.stopPropagation();

            content = mustache.render($deleteFormTemplate.html(), templateData);
            $modalWindow = $(mustache.render($modalWindowTemplate.html(), {'title': 'Удалить товар?', content: content})).appendTo($body);
            $modalWindow.addClass(modalPosition);

            $modalWindow.lightbox_me({
                onLoad: function() {
                    $modalWindow.find('.js-modal-content').append(content);
                },
                beforeClose: function() {}
            });

            e.preventDefault();
        });
    }
);