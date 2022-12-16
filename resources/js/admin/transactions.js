const { default: axios } = require("axios");
const { at } = require("lodash");

function addDays(date, days) {
    var result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
}

class Transactions {

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
                dueDate(order) {
                    return addDays(order.completed_at, 30);
                },
                buyerPaid(order) {
                    let paid = 0;
                    order.payments.forEach(p => {
                        paid += parseFloat(p.amount);
                    });
                    
                    return paid >= parseFloat(order.total);
                },
                vendorsPaid(order) {
                    let paidCnt = 0;
                    order.brand_payments.forEach(p => {
                        if(p.paid_at) paidCnt++;
                    });
                    
                    if(paidCnt == 0) return 'No';
                    if(paidCnt == order.brand_payments.length) return 'Yes';
                    return 'Partial';
                },
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
        }).mount('#transactions-page');
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
        axios.get('/admin/data/transactions', { params: params }).then(function (response) {
            vm.orders = response.data.orders;
            vm.loading = false;
        });

        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

if($('#transactions-page').length > 0) {
    window.Transactions = new Transactions;
    window.Transactions.init();
}

class Transaction {

    init() {
        const orderId = Util.getProperty('data-order-id');
        this.id = orderId;


        this.initVue();
        this.getOrder();
    }

    initVue() {
        let ctx = this;

        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    order: {},
                }
            },
            methods: {
                makePayment(payment) {
                    payment.net = this.net(payment);
                    ctx.makePayment(payment);
                },
                net(payment) {
                    let subtotal = parseFloat(payment.subtotal);
                    let fee = parseFloat(payment.fee);
                    let shipping = parseFloat(payment.shipping);
                    return subtotal + shipping - (subtotal*fee/100);
                },
                dueDate(order) {
                    return addDays(order.completed_at, 30);
                },
                buyerPaid(order) {
                    let paid = 0;
                    order.payments.forEach(p => {
                        paid += parseFloat(p.amount);
                    });
                    
                    return paid >= parseFloat(order.total);
                },
                vendorsPaid(order) {
                    let paidCnt = 0;
                    order.brand_payments.forEach(p => {
                        if(p.paid_at) paidCnt++;
                    });
                    
                    if(paidCnt == 0) return 'No';
                    if(paidCnt == order.brand_payments.length) return 'Yes';
                    return 'Partial';
                },
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
        }).mount('#transaction-page');
    }

    getOrder() {
        let ctx = this;
        axios.get(`/admin/data/transactions/${this.id}`).then(function (response) {
            ctx.vm.order = response.data.order;
            // ctx.setOrder(response);
        });
    }

    makePayment(payment) {
        let ctx = this;
        axios.post(`/admin/transactions/${this.id}/pay`, payment).then(function (response) {
            ctx.getOrder();
        });
    }
}

if($('#transaction-page').length > 0) {
    window.Transaction = new Transaction;
    window.Transaction.init();
}