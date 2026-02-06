jQuery(function ($) {
    if (typeof MercadoPago === 'undefined' || typeof smpCheckout === 'undefined') {
        return;
    }

    const mp = new MercadoPago(smpCheckout.publicKey);

    function parseExpiration(value) {
        const parts = value.split('/');
        return {
            month: parts[0] ? parts[0].trim() : '',
            year: parts[1] ? parts[1].trim() : '',
        };
    }

    function tokenize(form) {
        const cardNumber = $('#smp_card_number').val();
        const cardholderName = $('#smp_cardholder_name').val();
        const expiration = parseExpiration($('#smp_card_expiration').val());
        const securityCode = $('#smp_card_cvv').val();

        return mp.createCardToken({
            cardNumber: cardNumber,
            cardholderName: cardholderName,
            cardExpirationMonth: expiration.month,
            cardExpirationYear: expiration.year,
            securityCode: securityCode,
        }).then(function (result) {
            if (result && result.id) {
                $('#smp_token').val(result.id);
                $('#smp_payment_method_id').val(result.payment_method_id || '');
                form.submit();
            }
        });
    }

    $('form.checkout').on('checkout_place_order_smp_gateway', function () {
        const form = this;
        if ($('#smp_token').val()) {
            return true;
        }
        tokenize(form);
        return false;
    });
});
