const { default: axios } = require("axios");
const { withCtx } = require("vue");

const sel = '#account-page';

class Account {

    init() {

        this.page = $('#account-page').attr('page');
        this.orderId = $('#account-page').attr('order');

        this.initVue();
        this.bindEvents();

        if(this.page == 'orders') 
            this.getOrders();

        else if(this.page == 'order')
            this.getOrder();

        else if(this.page == 'wishlists') 
            this.getWishlists();

        else if(this.page == 'returns') 
            this.getReturns();

        else if(this.page == 'return') 
            this.getReturnItems();
    }

    initVue() {
        const ctx = this;
        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    view: 'Orders',
                    title: 'Orders',
                    order: false,
                    loaded: false,
                    submitting: false,
                    orders: [],
                    page: 1,
                    pages: 1,
                    transfers: [],
                    returnItems: [],
                    returnReason: 'Received Wrong Product',
                    returnComments: '',
                    returnSuccess: false,
                    returns: [],
                    messages: [],
                    addresses: [],
                    wishlists: [],
                    wishlist: false,
                    modalView: '',
                    modalError: '',
                    wishlistName: '',
                    wishlistPublic: false,
                    editingWishlist: false
                }
            },
            methods: {
                itemSelected() {
                    const returnItems = this.returnItems.filter(item => item.return_quantity > 0);
                    return returnItems.length > 0;
                },
                formatMoney(price) {
                    return Util.formatMoney(price);
                },
                formatDate(d) {
                    return Util.formatDate(d);
                },
                submitReturn() {
                    ctx.submitReturn();
                },
                changeOrderPage(page) {
                    this.page = page;
                    ctx.getOrders();
                },
                addToCart: function(id) {
                    Cart.addItem(id, 1);
                },
                closeModal() {
                    this.modalView = '';
                },
                saveWishlist() {
                    if(this.wishlistName) {
                        let vm = this;
                        let params = {
                            id: this.editingWishlist ? this.editingWishlist.id : '',
                            name: this.wishlistName,
                            is_public: this.wishlistPublic
                        };

                        axios.post('/account/wishlists', params).then(function(response) {
                            vm.wishlists = response.data.wishlists;
                            vm.closeModal();
                        });
                    }
                },
                addWishlist() {
                    this.wishlistName = '';
                    this.editingWishlist = false;
                    this.modalView = 'edit-wishlist';
                },
                editWishlist(wishlist) {
                    this.editingWishlist = wishlist;
                    this.wishlistName = wishlist.name;
                    this.wishlistPublic = wishlist.is_public ? true : false;
                    this.modalView = 'edit-wishlist';
                },
                removeWishlistItem(id) {
                    let vm = this;
                    axios.post(`/account/wishlists/${this.wishlist.id}/items/${id}/delete`).then(function(response) {
                        vm.wishlists = response.data.wishlists;
                        for(let i = 0; i < vm.wishlists.length; i++) {
                            if(vm.wishlists[i].id == vm.wishlist.id) {
                                vm.wishlist = vm.wishlists[i];
                            }
                        }
                    });
                },
                deleteWishlist(wishlist) {
                    let vm = this;
                    if(window.confirm('Are you sure you want to delete this wishlist?')) {
                        axios.post(`/account/wishlists/${wishlist.id}/delete`).then(function(response) {
                            vm.wishlists = response.data.wishlists;
                        });
                    }
                }
            }
        }).mount(sel);
    }

    getOrders() {
        let ctx = this;
        axios.get(`/account/data/orders`, { params: { page: ctx.vm.page }}).then(function (response) {
            ctx.vm.orders = response.data.orders;
            ctx.vm.pages = response.data.pages;
            ctx.vm.loaded = true;
            window.scrollTo(0,0);
        });
    }

    getOrder() {
        let ctx = this;
        axios.get(`/account/data/orders/${ctx.orderId}`).then(function (response) {
            ctx.vm.order = response.data.order;
            ctx.vm.loaded = true;
        });
    }

    getWishlists() {
        let ctx = this;
        axios.get(`/account/data/wishlists`).then(function (response) {
            ctx.vm.wishlists = response.data.wishlists;
            ctx.vm.loaded = true;
        });
    }

    getReturns() {
        let ctx = this;
        axios.get(`/account/data/returns`).then(function (response) {
            ctx.vm.returns = response.data.returns;
            ctx.vm.loaded = true;
        });
    }

    getReturnItems() {
        let ctx = this;
        axios.get(`/account/data/orders/${ this.orderId }/returnitems`).then(function (response) {
            ctx.vm.returnItems = response.data.items;
            ctx.vm.returnItems.map(item => item.return_quantity = 0);
            ctx.vm.loaded = true;
        });
    }

    submitReturn() {
        let ctx = this;

        const returnItems = this.vm.returnItems.filter(item => item.return_quantity > 0);
        const params = {
            items: returnItems,
            reason: this.vm.returnReason,
            comments: this.vm.returnComments
        }

        axios.post(`/account/orders/${ this.orderId }/return`, params).then(function (response) {
            if(response.data.id) {
                ctx.vm.returnSuccess = true;
            }
        });
    }

    scrollToTop() {
        $('#account-page').animate({
            scrollTop: 0
        }, 500, 'linear');
    }

    bindEvents() {

    }
}

if($('#account-page').length > 0) {
    window.Account = new Account;
    window.Account.init();
}