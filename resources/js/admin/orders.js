const { default: axios } = require("axios");

class Orders {

    init() {
        this.query = new URLSearchParams(window.location.search)

        this.initVue();
        this.getOrders();
    }

    initVue() {
        let ctx = this;

        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    product_id: ctx.query.get('product_id'),
                    customer_id: ctx.query.get('customer_id'),
                    search: '',
                    filter: 'All',
                    orders: [],
                    loading: false,
                    product: false,
                    customer: false,
                }
            },
            methods: {
                formatDate(d) {
                    return Util.formatDate(d);
                },
                formatTime(d) {
                    return Util.formatTime(d);
                },
                formatDateTime(d) {
                    return Util.formatDateTime(d);
                },
                formatMoney(price) {
                    return Util.formatMoney(price);
                },
                searchUpdated(e) {
                    if(e.key == 'Enter')
                    ctx.getOrders();
                },
                searchOrders() {
                    ctx.getOrders();
                },
                filterOrders(filter) {
                    this.filter = filter;
                    ctx.getOrders();
                },
                customerName(order) {
                    if(order.customer) {
                        return order.customer.first_name + ' ' + order.customer.last_name
                    }
                    return order.email;
                },
                customerLink(order) {
                    if(order.customer)
                        return '/admin/customers/' + order.customer.id;

                    return false;
                },
                removeProduct() {
                    this.product = false;
                    this.product_id = '';
                    ctx.getOrders();
                },
                removeCustomer() {
                    this.customer = false;
                    this.customer_id = '';
                    ctx.getOrders();
                }
            }
        }).mount('#orders-page');
    }

    getOrders() {
        const vm = this.vm;
        const params = {
            search: vm.search,
            filter: vm.filter,
            product_id: this.vm.product_id,
            customer_id: this.vm.customer_id,
        }
        vm.loading = true;
        vm.orders = [];
        axios.get('/admin/data/orders', { params: params }).then(function (response) {
            vm.orders = response.data.orders;
            vm.product = response.data.product;
            vm.customer = response.data.customer;
            vm.loading = false;
        });

        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

if($('#orders-page').length > 0) {
    window.Orders = new Orders;
    window.Orders.init();
}