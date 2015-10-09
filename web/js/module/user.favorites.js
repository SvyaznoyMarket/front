define(
    [
        'jquery', 'underscore'
    ],
    function (
        $, _
    ) {

        (function removeFavoriteItem(){

            var emptyFavoritesText = 'У вас нет избранных товаров';
            var $favoritesSection = $('.favorit-section');
            var $deleteFavoriteButton = $('.js-favorites-delete');

            $deleteFavoriteButton.click(deleteFavoriteProductHandler);

            function deleteFavoriteProductHandler(evt) {
                evt.preventDefault();

                var $product = $(this).parents('.favorit-item');
                var productUi = $(this).data('productUi');

                $.post('/ajax/favorite/delete', {productUi: productUi}, function(result) {

                    if (result.data.success) {
                        $product.remove();
                    }

                    if ($('.favorit-item').length === 0) {
                        $favoritesSection.append('<p>' + emptyFavoritesText + '</p>');
                    }
                });
            }

        })();

    }
);