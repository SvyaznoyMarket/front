/**
 * Created by alexandr.anpilogov on 11.03.16.
 */

define(
    [
        'jquery'
    ],
    function ($) {
        var $body = $('body'),
            menuCategoryToggleBtn = $('.js-menu-categoryToggle'),
            menuCategoryItems = menuCategoryToggleBtn.siblings(),
            isActive = 'is-active',
            isHide = 'is-hide';

        $('.js-menu-scroll').on('scroll', function(e){
            var $this = $(this),
                menuBlock = $('.js-menu-block'),
                menuBlockName = menuBlock.find('.js-menu-block-name'),
                isClass = 'is-fixed';


            if(menuBlock.offset().top < 50){
                menuBlock.each(function(ind, elem){

                    if($(elem).offset().top <= 50 && !($(elem).find(menuBlockName).hasClass(isClass))){

                        console.log(1);

                        $(elem).find(menuBlockName).addClass(isClass);
                        $(elem).siblings().find(menuBlockName).removeClass(isClass);
                    }
                });
            }else if(menuBlock.offset().top > 50){
                $('.js-menu-block-name').removeClass(isClass);
            }
        });

        $(document).ready(function(){
            if(localStorage.getItem('menuActive') === 'false'){
                menuCategoryToggleBtn.removeClass(isActive);

                menuCategoryItems.addClass(isHide);
            }
        });

        $body.on('click', '.js-menu-categoryToggle', function(e){
            e.preventDefault();

            menuCategoryItems.toggleClass(isHide);

            menuCategoryToggleBtn.toggleClass(isActive);

            localStorage.setItem('menuActive', menuCategoryToggleBtn.hasClass(isActive)); //запоминаем состояние menu
        });

        $body.on('click', '.js-historyBack', function(e){
            e.preventDefault();
            
            history.back();
        });
    }
);