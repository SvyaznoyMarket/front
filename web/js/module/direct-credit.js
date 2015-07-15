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
                        console.info('dc_getCreditForTheProduct', {partnerId: partnerId, sessionId: sessionId}, result);
                        if ((!'payment' in result) || !result.payment) {
                            return;
                        }

                        done(result);
                    }
                );
            }
        }
    }
);