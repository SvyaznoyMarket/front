define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util', 'module/config', 'jquery.ui', 'jquery.maskedinput',
        'module/order/user.form', 'module/order/common', 'module/order/toggle'
    ],
    function(
        require, $, _, mustache, util, config
    ) {

        var
            $body = $('body'),
            $onlinePaymentPopupTemplate = $('#tpl-order-delivery-onlinePayment-popup'),// TODO: перенести на 3-й шаг
            $modalWindowTemplate        = $('#tpl-modalWindow'),

            showOnlinePaymentPopup = function(e) {
                var
                    $el = $(this),
                    $modalWindow  = $($modalWindowTemplate.html()).appendTo($body),
                    modalTitle    = $el.data('modal-title'),
                    modalPosition = $el.data('modal-position'),
                    data = Storage.get($el.data('storageSelector'))
                ;

                e.stopPropagation();

                $modalWindow.find('.js-modal-title').text(modalTitle);
                $modalWindow.addClass(modalPosition);

                $modalWindow.lightbox_me({
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
                    url = $container.data('url')
                ;

                e.stopPropagation();

                console.info($el, $container);

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


        $body.on('click', '.js-order-delivery-onlinePayment-link', showOnlinePaymentPopup);
        $body.on('click', '.js-order-delivery-onlinePayment-radio', applyOnlinePayment);
    }
);
