const { default: axios } = require("axios");

class Cart {

    init() {
        this.bindEvents();
        if($('#side-cart').length > 0) {
            const root = this;
            this.vm = Vue.createApp({
                delimiters: ['${', '}'],
                data() {
                    return {
                        view: 'cart',
                        cart: { items: [], subtotal: 0, total: 0, ffl_required: false },
                        error: '',
                        minimums: []
                    }
                },
                methods: {
                    setItemQuantity(item, quantity) {
                        let vm = this;
                        window.Cart.updateItem(item.guid, quantity, function(response) {
                            vm.error = response.error;
                        });
                    },
                    goToCheckout() {
                        let vm = this;
                        root.goToCheckout(function(response) {
                            vm.error = response.error;
                        });
                    },
                    formatMoney(price) {
                        return Util.formatMoney(price);
                    },
                    
                    hideSideCart() {
                        root.hideSideCart();
                    },
                }
            }).mount('#side-cart');

            // Load cart data.
            this.get();
        }
    }

    get(callback) {
        console.log('get');
        var ctx = this;
        this.vm.error = '';
        axios.get('/cart/json').then(function (response) {
            ctx.vm.cart = response.data.cart;
            ctx.vm.suggested = response.data.suggested;
            ctx.setItemCount(ctx.vm.cart.items.length);

            ctx.checkMinimums(ctx.vm.cart.items);

            if(callback) callback(ctx.vm.cart);
        });
    }

    checkMinimums(items) {
        
        let totals = {};
        items.forEach(i => {
            if(!totals[i.product.brand.id]) {
                totals[i.product.brand.id] = {
                    brand: i.product.brand,
                    min: parseFloat(i.product.brand.order_min),
                    total: 0
                };
            }

            totals[i.product.brand.id].total += parseFloat(i.price) * i.quantity;
        });

        let minimums = [];
        for([i, t] of Object.entries(totals)) {
            if(t.min > t.total) minimums.push(t);
        }
        this.vm.minimums = minimums;
    }

    addVariants(variants, errorHandler) {
        this.vm.error = '';
        axios.post('/cart/items', { 
            variants: variants
        })
        .then(function (response) {
            window.Cart.showSideCart();
        })
        .catch(function (error) {
            if(errorHandler)
                errorHandler(error.response.data);
        });
    }

    addItemWithOptions(id, quantity, options, errorHandler) {
        this.vm.error = '';
        axios.post('/cart/items', { 
            id: id, 
            quantity: quantity,
            options: options
        })
        .then(function (response) {
            window.Cart.showSideCart();
        })
        .catch(function (error) {
            if(errorHandler)
                errorHandler(error.response.data);
        });
    }

    addItem(id, quantity, errorHandler) {
        this.addItemWithOptions(id, quantity, [], errorHandler);
    }

    updateItem(id, quantity, errorHandler) {
        var self = this;
        this.vm.error = '';
        axios.post('/cart/update', { 
            id: id, 
            quantity: quantity 
        })
        .then(function (response) {
            self.get();
        })
        .catch(function (error) {
            if(errorHandler)
                errorHandler(error.response.data);
        });
    }

    removeItem(id, errorHandler) {
        const self = this;
        this.vm.error = '';
        axios.post('/cart/remove', { 
            id: id
        })
        .then(function (response) {
            this.get();
        })
        .catch(function (error) {
            if(errorHandler)
                errorHandler(error.response.data);
        });
    }

    showSideCart() {
        console.log('show cart');
        $(Common.sel.sideCart).addClass('show');
        Common.showOverlay();
        this.get();
    }

    goToCheckout(errorHandler) {
        axios.post('/cart/checkout')
        .then(function (response) {
            window.location = '/checkout';
        })
        .catch(function (error) {
            if(errorHandler)
                errorHandler(error.response.data);
        });
    }

    hideSideCart() {
        $(Common.sel.sideCart).removeClass('show');
        this.vm.view = 'cart';
        Common.hideOverlay();
    }

    bindEvents() {
        var self = this;
        $('body').on('click', Common.sel.sideCartTrigger, function() {
            self.showSideCart();
        });
    }

    setItemCount(count) {
        $('.cart-item-count').text(count);
    }

    setDealer(dealer) {
        var self = this;
        axios.post('/cart/dealer', { id: dealer.id })
        .then(function (response) {
            self.vm.cart = response.data.cart;
            self.scrollToTop();
        });
    }

    scrollToTop() {
        $('#choose-dealer').animate({
            scrollTop: 0
        }, 500, 'linear');
    }
}

window.Cart = new Cart;
window.Cart.init();