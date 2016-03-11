var getCheckoutButton = function () {
    var checkoutOrderBtn = $$("button.btn-checkout");
    if (checkoutOrderBtn.length == 0)  {
        checkoutOrderBtn = $$("button:contains(\'Place Order\')");
    }

    return checkoutOrderBtn[0];
}

var tokenCallback = function (msg) {
    var everypayToken = $$('#everypay-token')[0];
    everypayToken.value = msg.token;
    everypayToken.removeAttribute('disabled');
}

var disablePlaceOrderButton = function () {
    var checkoutOrderBtn = getCheckoutButton();

    checkoutOrderBtn.removeAttribute("onclick");
}

var everypay_options = {
    'key': $$('#public-key')[0].value,
    'amount': $$('#amount')[0].value,
    'description': 'Order# ' + $$('#description')[0].value,
    'currency': $$('#currency')[0].value,
    'sandbox': $$('#sandbox')[0].value,
    'callback': 'tokenCallback',
    //'max_installments': $$('#max_installments')[0].value
};
var loadButton = setInterval(function () {
      try {
        EverypayButton.jsonInit(everypay_options, '#everypay-form');
        clearInterval(loadButton);
      } catch (err) {}
}, 100);

var checkButton = setInterval(function () {
    try {
        if ($$('.everypay-button').length == 1) {
            $$('.everypay-button')[0].click();
        }
        clearInterval(checkButton);
    } catch (err) {}
}, 500);
