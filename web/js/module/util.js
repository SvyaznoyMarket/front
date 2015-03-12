define(
    [],
    function () {

        return {
            formatCurrency: function (price) {
                price = String(price);
                price = price.replace(',', '.');
                price = price.replace(/\s/g, '');
                price = String(Number(price).toFixed(2));
                price = price.split('.');

                if (price[0].length >= 5) {
                    price[0] = price[0].replace(/(\d)(?=(\d\d\d)+([^\d]|$))/g, '$1 ');
                }

                if (price[1] == 0) {
                    price = price.slice(0, 1);
                }

                return price.join('.');
            }
        };

    }
);