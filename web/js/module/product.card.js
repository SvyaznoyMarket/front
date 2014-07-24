define(
    [
        'require', 'jquery', 'underscore', 'mustache', 'module/util',
        'jquery.enterslide', 'jquery.photoswipe', 'module/product.card.tab'
    ],
    function (
        require, $, _, mustache, util
    ) {
        var $body = $('body');

        // запрос слайдеров
        var recommendedUrls = [];
        $('.js-productSlider').each(function(i, el) {
            var url = $(el).data('url');
            if (!url) return; // continue

            recommendedUrls.push(url);
        });
        recommendedUrls = _.uniq(recommendedUrls);

        _.each(recommendedUrls, function(url) {
            $.get(url).done(function(response) {
                _.each(response.result.widgets, function(templateData, widgetSelector) {
                    if (!_.isObject(templateData) || !widgetSelector) {
                        console.warn('slider', widgetSelector, templateData);
                        return;
                    }

                    var $widget = $(widgetSelector);

                    if (templateData.count <= 0 && $widget) {
                        $widget.remove();
                        return;
                    }

                    console.info('slider', templateData, $widget);

                    $widget.trigger('render', templateData);
                    $body.trigger('render');

                    var $parent = $widget.parents('.js-container');
                    if ($parent.length) {
                        $parent.show();
                        $($parent.data('tabSelector')).animate({width: 'show'});
                    }
                });

                $('.js-productSliderList').enterslide();
            });
        });


        // direct-credit
        $creditPayment = $('.js-creditPayment');
        console.info('creditPayment', $creditPayment);
        var dataValue = $creditPayment.data('value');
        _.isObject(dataValue) && require(['module/direct-credit', 'direct-credit'], function(directCredit) {
            dataValue.product.quantity = 1;

            directCredit.getPayment(
                { partnerId: dataValue.partnerId },
                $body.data('user'),
                dataValue.product,
                function (result) {
                    var $template = $($creditPayment.data('templateSelector')),
                        $price = $($creditPayment.data('priceSelector')),
                        price = Math.ceil(result.payment);

                    $price.html(mustache.render($template.html(), {
                        shownPrice: util.formatCurrency(price)
                    }));

                    $creditPayment.show();
                }
            );
        });

        var body = $('body'),
            w = $(window),

            fullImgW = $('.js-fullimg-wrap'),
            fullImgLk = $('.js-fullimg-open'),

            fullImg = $('.js-fullimg-img'),
            thmbW = $('.js-fullimg-thmb-wrap'),
            fullImgThmbImg = $('.js-fullimg-thmb-img');

        thmbW.css({ 'width' : fullImgW.width() });

        var 
            fullImgScale = function fullImgScale() {
                fullImgW.css({ 'height' : body.height() });
                thmbW.css({ 'width' : fullImgW.width() });
            },

            fullImgShow = function fullImgShow() {
                $('html, body').animate({scrollTop:0}, 'fast');
        
                fullImgW.slideDown();
                return false;
            },

            fullImgSrc = function fullImgSrc() {
                console.log('fullImgSrc');

                var $self = $(this),
                    imgSrc = $self.attr('src');

                fullImgThmbImg.parent().parent().removeClass('fullImgCnt_thmb_i-act');

                $self.parent().parent().addClass('fullImgCnt_thmb_i-act');
                fullImg.fadeOut(100, function(){
                    fullImg.attr("src", imgSrc);
                    fullImg.fadeIn(100);
                });
            },

            thmbSlide = function thmbSlide() {
                console.log('thmbSlide');

                var thmb = $('.js-fullimg-thmb'),
                    thmbW = $('.js-fullimg-thmb-wrap'),
                    item = thmb.find('.js-fullimg-thmb-i'),

                    btnL = $('.js-fullimg-thmb-btn-l'),
                    btnR = $('.js-fullimg-thmb-btn-r'),

                    itemW = item.width() + parseInt(item.css('marginLeft'),10) + parseInt(item.css('marginRight'),10),
                    thmbL = item.length,
                    elementOnSlide = parseInt(thmbW.width()/itemW, 10),

                    nowLeft = 0;

                    console.log('elementOnSlide ' + thmbW.width())
                    console.log('elementOnSlide ' + elementOnSlide)

                thmb.css({'width' : itemW * thmbL }); 

                var nextSlide = function nextSlide() {

                    if ( $(this).hasClass('fullImgCnt_thmb_btn-disbl') ) {
                        return false;
                    }

                    btnL.removeClass('fullImgCnt_thmb_btn-disbl');

                    if ( thmb.width() - ( thmbW.width() + nowLeft ) <= itemW ) {
                        nowLeft = nowLeft + ( thmb.width() - ( thmbW.width() + nowLeft ) );
                        btnR.addClass('fullImgCnt_thmb_btn-disbl');
                    }
                    else {
                        nowLeft = nowLeft + itemW;
                        btnR.removeClass('fullImgCnt_thmb_btn-disbl');
                    }

                    console.info(itemW);
                    console.log(elementOnSlide);
                    console.log(nowLeft);

                    thmb.animate({'left': -nowLeft });

                    return false;
                },

                prevSlide = function prevSlide() {
                    if ( $(this).hasClass('fullImgCnt_thmb_btn-disbl') ) {
                        return false;
                    }

                    btnR.removeClass('fullImgCnt_thmb_btn-disbl');

                    if ( nowLeft - itemW <= 0 ) {
                        nowLeft = 0;
                        btnL.addClass('fullImgCnt_thmb_btn-disbl');
                    }
                    else {
                        nowLeft = nowLeft - itemW;
                        btnL.removeClass('fullImgCnt_thmb_btn-disbl');
                    }

                    thmb.animate({'left': -nowLeft });

                    return false;
                },

                resizeW = function resizeW() {
                    nowLeft = 0;

                    btnL.addClass('fullImgCnt_thmb_btn-disbl');
                    btnR.addClass('fullImgCnt_thmb_btn-disbl');

                    if ( thmbL > elementOnSlide ) {
                        btnR.removeClass('fullImgCnt_thmb_btn-disbl');
                    }

                    elementOnSlide = parseInt(thmbW.width()/itemW, 10);

                    console.log('elementOnSlide' + elementOnSlide)
                }

                btnL.on('click', prevSlide);
                btnR.on('click', nextSlide);

                resizeW();
                w.on('resize', resizeW);
            };

        thmbSlide();
        fullImgScale();
        w.on('resize', fullImgScale);
        fullImgLk.on('click', fullImgShow);
        fullImgThmbImg.on('click', fullImgSrc);
        
    }
);