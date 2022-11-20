const { default: axios } = require("axios");

class Customers {

    init() {
        this.initVue();
        this.getCustomers();
    }

    initVue() {
        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    customers: []
                }
            },
            methods: {
                getName(c) {
                    return Util.getCustomerName(c);
                }
            }
        }).mount('#customers-page');
    }

    getCustomers() {
        var vm = this.vm;
        axios.get('/admin/data/customers').then(function (response) {
            vm.customers = response.data.customers;
        });
    }
}

if($('#customers-page').length > 0) {
    window.Customers = new Customers;
    window.Customers.init();
}