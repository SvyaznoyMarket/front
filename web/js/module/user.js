define(
    [
        'jquery', 'underscore', 'mustache', 'module/user.profile'
    ],
    function (
        $, _, mustache
    ) {

        var $body = $('body'),
            $modalWindowTemplate = $('#tpl-modalWindow')
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

        $body.on('click', '.js-menuHide', function(e){
            var $this = $(this),
                url = document.location.pathname;
            console.log(123);
            if(url == '/private'){
                e.preventDefault();
                console.log(123);
                $('.js-content').removeClass('private-index');
            }

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