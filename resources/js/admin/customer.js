const { default: axios } = require("axios");

class Customer {

    init() {
        this.vm = this.initVue();
        this.id = Util.getProperty('data-customer-id');
        
        this.getCustomer();
        this.bindEvents();
    }

    initVue() {
        let ctx = this;
        return Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    loaded: false,
                    changed: false,
                    showAddresses: false,
                    modalView: '',
                    modalError: '',
                    error: '',
                    saved: {},
                    editAddress: {},
                    selectedAddress: '',
                    customer: {
                        first_name: '',
                        last_name: '',
                        email: '',
                        addresses: [],
                        taxable: 1
                    },
                    groups: [],
                    metrics: {
                        cancelled: 0,
                        orders: 13,
                        spend: 0,
                        spend_year: 0
                    },
                    dealers: []
                }
            },
            methods: {
                formatDate(d) {
                    return Util.formatDate(d);
                },
                formatMoney(price) {
                    return Util.formatMoney(price);
                },
                getName(c) {
                    return Util.getCustomerName(c);
                },
                showModal(view) {
                    this.modalError = '';
                    this.modalView = view;
                },

                closeModal() {
                    this.modalView = '';
                },
                save() {
                    if(!((this.customer.first_name && this.customer.last_name) || this.customer.company)) {
                        this.error = "Missing required fields";
                        return;
                    }

                    let params = ctx.getParams(this.customer);
                    axios.post(`/admin/customers/${ctx.id}`, params).then(function(response) {
                        ctx.loadCustomer(response.data.customer);
                        ctx.vm.changed = false;
                    }).catch(function(error) {
                        ctx.vm.error = error.response.data.error;
                    });
                },
                showAddressEdit(index) {
                    this.selectedAddress = index;
                    this.editAddress = Util.clone(this.customer.addresses[index]);
                    this.showModal('edit-address');
                },
                addAddress() {
                    this.editAddress = ctx.emptyAddress();
                    this.showModal('edit-address');
                },
                saveAddress() {
                    if(this.addressComplete(this.editAddress)) {
                        if(this.editAddress.id) {
                            this.customer.addresses[this.selectedAddress] = Util.clone(this.editAddress);
                        }
                        else {
                            this.customer.addresses.push(Util.clone(this.editAddress));
                        }
                        this.closeModal();
                        ctx.checkChanged();
                    }
                    else {
                        this.modalError = 'Complete all required fields';
                    }
                },
                deleteAddress(index) {
                    this.customer.addresses.splice(index, 1);
                    ctx.checkChanged();
                },
                addressComplete(address) {
                    return ((address.first_name && address.last_name) || address.company) &&
                        address.address1 &&
                        address.city &&
                        address.state &&
                        address.zip;
                },
                discard() {
                    this.customer = Util.clone(this.saved);
                    this.changed = false;
                    this.error = '';
                }

            }
        }).mount('#customer-page');
    }

    bindEvents() {
        let ctx = this;
        $('body').on('keyup change', function() {
            ctx.checkChanged();
        });
    }

    emptyAddress() {
        return {
            first_name: '',
            last_name: '',
            company: '',
            address1: '',
            address2: '',
            city: '',
            state: '',
            zip: ''
        }
    }

    getCustomer() {
        if(!this.id) {
            this.vm.loaded = true;
            return;
        }

        let ctx = this;
        axios.get(`/admin/data/customers/${this.id}`).then(function (response) {
            ctx.loadCustomer(response.data.customer);
            ctx.vm.groups = response.data.groups;
            ctx.vm.metrics = response.data.metrics;
            ctx.vm.dealers = response.data.dealers;
            ctx.vm.loaded = true;
        });
    }

    loadCustomer(customer) {
        this.vm.customer = customer;

        // Checkboxes need true/false values.
        this.vm.customer.taxable = customer.taxable ? true : false;
        this.vm.customer.accepts_emails = customer.accepts_emails ? true : false;
        this.vm.customer.pay_later = customer.pay_later ? true : false;
        this.vm.customer.disabled = customer.disabled ? true : false;

        // Save to check for changes later.
        this.vm.saved = Util.clone(customer);

        console.log('id', this.id);
        if(!this.id) {
            console.log('no id');
            this.id = customer.id;
            window.history.replaceState({}, '', '/admin/customers/' + this.id);
        }
    }

    getParams(customer) {
        return {
            first_name: customer.first_name,
            last_name: customer.last_name,
            company: customer.company,
            phone: customer.phone,
            email: customer.email,
            notes: customer.notes,
            group_id: customer.group_id,
            taxable: customer.taxable,
            accepts_emails: customer.accepts_emails,
            pay_later: customer.pay_later,
            disabled: customer.disabled,
            password: customer.password,
            addresses: customer.addresses
        }
    }

    checkChanged() {
        if(!this.vm.loaded) return;

        this.vm.changed  = Util.checkChanged(
            this.getParams(this.vm.saved),
            this.getParams(this.vm.customer)
        );
    }
}

if($('#customer-page').length > 0) {
    window.Customer = new Customer;
    window.Customer.init();
}