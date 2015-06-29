define(
    ['direct-credit'],
    function () {
        return {
            getPayment: function(partnerId, sessionId, products, done) {
                dc_getCreditForTheProduct (
                    partnerId,
                    sessionId,
                    'getPayment',
                    {
                        products: products
                    },
                    function(result) {
                        console.info('dc_getCreditForTheProduct', result);
                        if (!'payment' in result || (result.payment <= 0)) {
                            return;
                        }

                        done(result);
                    }
                );
            }
        }
    }
);