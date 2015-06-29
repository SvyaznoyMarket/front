define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'module/config', 'module/form-validator', 'module/order/analytics.google', 'jquery.ui', 'jquery.maskedinput'
    ],
    function(
        require, $, _, mustache, util, config, formValidator, analytics
    ) {

        var
            $body = $('body'),
            $onlinePaymentPopupTemplate = $('#tpl-order-complete-onlinePayment-popup'),// TODO: перенести на 3-й шаг
            $modalWindowTemplate        = $('#tpl-modalWindow'),

            showOnlinePaymentPopup = function(e) {
                var
                    $el = $(this),
                    $modalWindow  = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle    = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position'),
                    data = $.parseJSON($($el.data('storageSelector')).html())
                ;
                console.info(data);

                e.stopPropagation();

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
                    fullScreen: true,
                    modal: false,
                    onLoad: function() {
                        $modalWindow.find('.js-modal-content').append(mustache.render($onlinePaymentPopupTemplate.html(), data));
                    },
                    beforeClose: function() {}
                });
            },

            applyOnlinePayment = function(e) {
                var
                    $el = $(this),
                    $container = $($el.data('containerSelector')),
                    url = $container.data('url'),
                    data = $el.data('value')
                ;

                e.stopPropagation();


                $.ajax({
                    url: url,
                    data: data,
                    type: 'post',
                    timeout: 40000
                }).done(function(response) {
                    $container.html(response.form)
                }).always(function() {
                    console.info('unblock screen');
                }).error(function(xhr, textStatus, error) {
                    var
                        response,
                        redirect
                        ;

                    if (xhr && (302 === xhr.status)) {
                        response = $.parseJSON(xhr.responseText) || {};
                        redirect = response.redirect || '/cart';

                        window.location.href = redirect;
                    }
                });
            }
        ;

        $body.on('click', '.js-order-complete-onlinePayment-link', showOnlinePaymentPopup);
        $body.on('click', '.js-order-complete-onlinePayment-radio', applyOnlinePayment);

        try {
            analytics.push(['16 Вход_Оплата_ОБЯЗАТЕЛЬНО']);

            $body.on('click', '.js-order-complete-onlinePayment-link', function(e) {
                analytics.push(['17 Оплатить_онлайн_вход_Оплата']);
            });
            $body.on('click', '.js-order-complete-onlinePayment-radio', function(e) {
                var $el = $(this);

                if ('5' === $el.val()) {
                    analytics.push(['17_1 Оплатить_онлайн_Оплата', 'Онлайн-оплата']);
                } else if ('8' === $el.val()) {
                    analytics.push(['17_1 Оплатить_онлайн_Оплата', 'Psb']);
                }
            });
        } catch (error) { console.error(error); }
    }
);
