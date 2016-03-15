var everypayTokenCallback = function(msg) {
    var everypayToken = $$('#everypay-token')[0];
    everypayToken.value = msg.token;
    everypayToken.removeAttribute('disabled');
    EverypayMage.showCheckoutButton();
    review.save();
};

var EverypayMage = (function() {
    var opts = {};
    var buttonClass = '.everypay-button';

    return {
        setOptions: function(options) {
            opts = options || {};
            this.hideCheckoutButton();
        },
        render: function() {
            opts.callback = 'everypayTokenCallback';
            EverypayButton.jsonInit(opts, '#everypay-form')
        },
        showModal: function() {
            if ($$(buttonClass).length == 1) {
                $$(buttonClass)[0].click();
            }
        },
        renderAndShow: function() {
            this.render();
            this.showModal();
        },

        showCheckoutButton: function () {
            var checkoutOrderBtn = this.getCheckoutButton();
            checkoutOrderBtn.removeAttribute("style");
        },
        getCheckoutButton: function () {
            var checkoutOrderBtn = $$("button.btn-checkout");
            if (checkoutOrderBtn.length == 0)  {
                checkoutOrderBtn = $$("button:contains(\'Place Order\')");
            }

            return checkoutOrderBtn[0];
        },
        hideCheckoutButton: function () {
            var checkoutOrderBtn = this.getCheckoutButton();

            checkoutOrderBtn.setAttribute("style", "display: none");
        }
    }
}());
