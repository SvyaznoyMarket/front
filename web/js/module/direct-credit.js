define(
    ['direct-credit'],
    function () {
        return {
            getPayment: function(partnerId, sessionId, products, done) {
                window.DCLoans(partnerId, 'getPayment', { products : products }, function(response) {
                    var result = {
                        payment: null
                    };

                    console.info('DCLoans', partnerId, 'getPayment', { products : products }, response);

                    try {
                        for (var i in response.payment) {
                            result.payment = response.payment[i].payment;
                            break;
                        }

                        if (result.payment) {
                            done(result);
                        }
                    } catch (error) { console.error(error); }
                }, debug);
            }
        }
    }
);