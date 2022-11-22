const { default: axios } = require("axios");
const { mapValues } = require("lodash");

class Checkout {

    constructor() {
        this.guid = $('[data-checkout-id]').attr('data-checkout-id');
        this.debounceTimer = false;
    }


    init() {
        const ctx = this;

        if($('#checkout').length > 0) {
            this.vm = Vue.createApp({
                delimiters: ['${', '}'],
                data() {
                    return {
                        checkout: { 
                            email: '',
                            customer_id: false,
                            is_pickup: false,
                            accepts_insurance: 0,
                            use_billing: true,
                            addresses: [],
                            shipments: [{
                                id: '',
                                methodId: '',
                                methods: [],
                                address: {
                                    index: '',
                                    first_name: '',
                                    last_name: ''
                                },
                                addressValid: false,
                                addressKey: '',
                                loading: false,
                            }],
                            billing: { id: '' },
                            cart: {}
                        },
                        billingAddressIndex: '',
                        insuranceChosen: false,
                        paymentError: '',
                        showSignIn: false,
                        signinEmail: '',
                        signinPassword: '',
                        signinError: '',
                        loaded: false,
                        errorMessage: '',
                        errorProducts: [],
                        submitting: false,
                        houseAccount: false,
                        hasAmmo: false,
                        step: 'billing',
                        stepIndex: 2,
                        termsAccepted: false,
                        newsletter: true,
                        card: {
                            number: '',
                            name: '',
                            expiry: '',
                            cvv: '',
                            token: ''
                        }
                    }
                },
                methods: {
                    validShippingAddress(shipment) {
                        if(this.shipToDealer(shipment))
                            return true;

                        if(this.checkout.use_billing) 
                            return true;

                        return (shipment.address.first_name &&
                            shipment.address.last_name &&
                            shipment.address.address1 &&
                            shipment.address.city &&
                            shipment.address.state &&
                            shipment.address.zip &&
                            shipment.address.phone);
                    },
                    selectBillingAddress() {
                        let address = ctx.lookupAddress(this.checkout.billing.id);
                        this.checkout.billing = ctx.getAddressParams(address);
                    },
                    selectShippingAddress(index) {
                        let shipment = this.checkout.shipments[index];
                        let address = ctx.lookupAddress(shipment.address.id);
                        shipment.address = ctx.getAddressParams(address);

                        ctx.addressChanged(index);
                        
                    },
                    shipToDealer(shipment) {
                        return shipment.ffl_required && this.checkout.dealer;
                    },
                    removeDealer() {

                    },
                    updateInsurance() {
                        this.insuranceChosen = true;
                        ctx.saveInsurance();
                    },
                    formatExpiry(e) {
                        return ctx.formatExpiry(e);
                    },
                    validShipping() {
                        return ctx.validShipping();
                    },
                    validAddress(address) {
                        return ctx.validAddress(address);
                    },
                    validCustomer() {
                        return ctx.validCustomer();
                    },
                    validCard() {
                        return this.card.number && this.card.expiry.length == 5 && this.card.name && this.card.cvv;
                    },
                    validCheckout() {
                        return true; // this.termsAccepted && (this.houseAccount || this.checkout.is_pickup || this.validCard());
                    },
                    addressChanged(shipment) {
                        ctx.addressChanged(shipment);
                    },
                    formatMoney(price) {
                        return Util.formatMoney(price);
                    },
                    placeOrder() {
                        console.log('place order');
                        ctx.completeCheckout();
                    },
                    saveCustomer() {
                        ctx.saveCustomer();
                        this.step = 'billing';
                        this.stepIndex = 2;
                    },
                    saveShipping() {
                        // ctx.saveTax();
                        this.step = 'payment';
                        this.stepIndex = 4;
                    },
                    savePickup() {
                        ctx.savePickup();
                    },
                    saveUseBilling() {
                        ctx.saveUseBilling();
                    },
                    saveBilling(billing) {
                        ctx.setBillingAddress(billing);
                        this.step = 'shipping';
                        this.stepIndex = 3;
                    },
                    attemptSignin() {
                        ctx.attemptSignin(this.signinEmail, this.signinPassword);
                    },
                    signout() {
                        ctx.signOut();
                    },
                    methodChanged(shipment, index) {
                        ctx.saveMethod(shipment, index);
                    },
                    shippingCanBeDifferent() {
                        return true;
                        if(this.checkout.customer) {
                            return true;
                        }
                        return false;
                    },
                    removeDealer() {
                        ctx.removeDealer();
                    },
                    deliveryDate(days, carrier) {
                        if(!days) return '';

                        const d = new Date();
                        const day = d.getDay();

                        // Add padding for shipping delay. With extra
                        // delay around the weekend.
                        let pad = 1;
                        if(day == 5) pad = 3;
                        else if(day == 6) pad = 2;
                        days += pad;

                        const newDay = (day + days) % 7;

                        // USPS doesn't deliver on Sunday.
                        if(carrier == 'USPS' && newDay == 0) {
                            days++;
                        }
                        
                        // UPS doesn't deliver on Saturday or Sunday.
                        else if(carrier == 'UPS') {
                            if(newDay == 6) days += 2;
                            else if(newDay == 0) days += 1;
                        }

                        d.setDate(d.getDate() + days)
                        return d.toDateString().substring(0, 10);
                    }
                }
            }).mount('#checkout');

            this.get();
        }
    }

    validCustomer() {
        if(!this.vm) return;
        return this.vm.checkout.customer || this.checkEmail(this.vm.checkout.email)
    }

    validAddress(address) {
        console.log('address', address);
        return address &&
            address.first_name && 
            address.last_name &&
            address.address1 &&
            address.city && 
            address.state &&
            address.zip;
    }

    lookupAddress(id) {
        return this.vm.checkout.addresses.filter(address => address.id == id)[0];
    }

    validateAddresses() {
        let shipments = this.vm.checkout.shipments;
        for(var i = 0; i < shipments.length; i++) {

            let address = shipments[i].address;
            this.setAddressKey(address);

            shipments[i].addressValid = (address.first_name &&
                address.last_name &&
                address.address1 &&
                address.city &&
                address.state &&
                address.zip &&
                address.phone) ? true : false;
        }
    }

    addressChanged(index)
    {
        // Since an address changed, we need to validate the 
        // address and check if shipping methods need to be
        // recalculated.
        let address = this.vm.checkout.shipments[index].address;
        this.setAddressKey(address);

        this.vm.checkout.shipments[index].addressValid = (address.first_name &&
            address.last_name &&
            address.address1 &&
            address.city &&
            address.state &&
            address.zip &&
            address.phone) ? true : false;

        if(this.debounceTimer)
            return;

        // Check if we need to reload shipping methods.
        let self = this;
        this.debounceTimer = setTimeout(function() {
            self.reloadAllMethods([index]);
            self.debounceTimer = false;
        }, 500)
    }

    validShipping() {
        if(!this.vm) return;

        return true;
    }

    reloadAllMethods(indexes) {
        return;
        let shipments = [];

        for(var i = 0; i < indexes.length; i++) {

            let index = indexes[i];
            let shipment = this.vm.checkout.shipments[index];
            console.log('reload ' + indexes[i], shipment);
        
            if(this.vm.validShippingAddress(shipment) && (this.vm.shipToDealer(shipment) || shipment.addressKey != shipment.address.key)) {
                
                let address = this.getAddressParams(shipment.address);
                if(this.vm.shipToDealer(shipment)) {
                    address = this.getAddressParams(this.vm.checkout.dealer, this.vm.checkout.dealer.name);
                }
                else if(this.vm.checkout.use_billing) {
                    address = this.getAddressParams(this.vm.checkout.billing);
                }

                shipment.addressKey = shipment.address.key;

                shipments.push({
                    index: index,
                    address: address
                });
            }
        }

        if(shipments.length > 0) {

            const params = {
                shipments: shipments
            };

            for(var i = 0 ; i < shipments.length; i++)
                this.vm.checkout.shipments[shipments[i].index].loading = true;

            let ctx = this;
            axios.post(`/data/checkout/${this.guid}/shipments/methods`, params).then(function (response) {
                ctx.setCheckout(response.data.checkout);

                for(var i = 0 ; i < shipments.length; i++)
                    ctx.vm.checkout.shipments[shipments[i].index].loading = false;
            });
        }
    }

    getAddressParams(address, company) {
        return {
            id: address.id,
            first_name: address.first_name,
            last_name: address.last_name,
            company: company === undefined ? address.company : company,
            address1: address.address1,
            address2: address.address2,
            city: address.city,
            state: address.state,
            zip: address.zip,
            phone: address.phone
        };
    }

    formatExpiry(e) {

        // Prevent double slashes.
        if(e.key == '/' && this.vm.card.expiry.indexOf('/') >= 0) {
            this.vm.card.expiry = this.vm.card.expiry.slice(0, -1);
            e.preventDefault();
            return false;
        }

        // Format with the slash.
        if(this.vm.card.expiry.length == 3 && e.key == 'Backspace') {
            this.vm.card.expiry = this.vm.card.expiry[0];
            return;
        }

        if(this.vm.card.expiry.length == 2) {
            this.vm.card.expiry += '/';
        }
    }

    setAddressKey(address) {
        address.key = this.getAddressKey(address);
    }

    getAddressKey(address) {
        return address.address1 +
            address.address2 + 
            address.city +
            address.state +
            address.zip;
    }

    get() {
        let ctx = this;
        axios.get(`/data/checkout/${this.guid}`).then(function (response) {
            ctx.setCheckout(response.data.checkout);
            if(ctx.vm.checkout.customer) {
                ctx.vm.stepIndex = 2;
                ctx.vm.step = 'billing'
            }

            if(response.data.checkout.approved) {
                ctx.vm.stepIndex = 4;
                ctx.vm.step = 'payment';
            }

            ctx.vm.loaded = true;
        });
    }

    signOut() {
        const ctx = this;
        axios.post(`/checkout/${this.guid}/signout`).then(function (response) {
            ctx.setCheckout(response.data.checkout);
            ctx.vm.stepIndex = 1;
            ctx.vm.step = 'customer'
        });
    }

    attemptSignin(email, password) {
        const params = {
            email: email,
            password: password
        };

        let vm = this.vm;
        const ctx = this;
        vm.signinError = '';
        axios.post(`/checkout/${this.guid}/signin`, params).then(function (response) {
            if(response.data.error) {
                console.log('error');
                vm.signinError = 'Invalid email / password combination';
            }
            else {
                ctx.setCheckout(response.data.checkout);
                vm.showSignIn = false;
                vm.stepIndex = 2;
                ctx.vm.step = 'billing'
            }
        });
    }

    getAuthToken() {
        var authData = {};
        authData.clientKey = "5ML34a9EQm3mhwka575399CJgUtRyE4jKA2agaYhuQ9Szxq8A498b73C2qEN2TDd";
        authData.apiLoginID = "2r42zFvWA";

        var cardData = {};
        let exp = this.vm.card.expiry.split('/');
        cardData.cardNumber = this.vm.card.number;
        cardData.month = exp[0];
        cardData.year = exp[1];
        cardData.cardCode = this.vm.card.cvv;
        cardData.zip = this.vm.checkout.billing.zip;

        var secureData = {};
        secureData.authData = authData;
        secureData.cardData = cardData;
		Accept.dispatchData(secureData, response => {
            if(response.messages.resultCode == 'Ok') {
                this.vm.card.token = response.opaqueData.dataValue;
                this.completeCheckout();
            }
            else {
                this.vm.paymentError = response.messages.message[0].text;
            }
        });
    }

    completeCheckout() {
        if(this.vm.submitting)
            return;


        let vm = this.vm;
        vm.errorMessage = '';
        vm.submitting = true;
        vm.errorProducts = [];
        
        axios.post(`/checkout/${this.guid}/complete`).then(function (response) {
            window.location.reload();
        }).catch(function(error) {
            vm.errorMessage = error.response.data.error;
            vm.submitting = false;
            if(error.response.data.products)
                vm.errorProducts = error.response.data.products;    
        });
    }

    saveCustomer() {
        const params = {
            email: this.vm.checkout.email
        }
        
        let self = this;
        axios.post(`/checkout/${this.guid}/customer`, params).then(function (response) {
            self.setCheckout(response.data.checkout);
        });
    }
    
    savePickup() {

        const params = {
            is_pickup: this.vm.checkout.is_pickup
        }
        
        let self = this;
        axios.post(`/checkout/${this.guid}/pickup`, params).then(function (response) {
            self.setCheckout(response.data.checkout);
        });
    }

    saveUseBilling() {
        const params = {
            use_billing: this.vm.checkout.use_billing
        }
        
        let ctx = this;
        axios.post(`/checkout/${this.guid}/usebilling`, params).then(function (response) {
            ctx.setCheckout(response.data.checkout);

            let shipments = [];
            for(var i = 0; i < ctx.vm.checkout.shipments.length; i++) {
                if(!(ctx.vm.checkout.shipments[i].ffl_required && ctx.vm.checkout.dealer))
                    shipments.push(i);
            }
            ctx.reloadAllMethods(shipments);
        });
    }

    saveInsurance() {
        const params = {
            accepts_insurance: this.vm.checkout.accepts_insurance
        }
        
        let self = this;
        axios.post(`/checkout/${this.guid}/insurance`, params).then(function (response) {
            self.setCheckout(response.data.checkout);
        });
    }

    saveTax() {
        let ctx = this;
        axios.post(`/checkout/${this.guid}/tax`).then(function (response) {
            ctx.setCheckout(response.data.checkout);
        });
    }

    checkEmail(email) {
        const re = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
        return re.test(String(email).toLowerCase());
    }

    setBillingAddress(billing) {
        let ctx = this;
        const params = this.getAddressParams(billing);
        axios.post(`/checkout/${this.guid}/billing`, params).then(function (response) {
            ctx.setCheckout(response.data.checkout);
            let checkout = response.data.checkout;
            let shipments = [];
            for(var i = 0; i < checkout.shipments.length; i++) {
                if(checkout.use_billing || (checkout.shipments[i].ffl_required && checkout.dealer))
                    shipments.push(i);
            }
            
            ctx.reloadAllMethods(shipments);
        });
    }

    setCheckout(checkout) {
        this.vm.checkout = checkout;
        this.vm.checkout.is_pickup = checkout.is_pickup ? true : false;
        this.vm.checkout.use_billing = checkout.use_billing ? true : false;
        this.validateAddresses();

        for(var i = 0; i < this.vm.checkout.shipments.length; i++) {
            if(!this.vm.checkout.shipments[i].address.id)
                this.vm.checkout.shipments[i].address.id = '';
        }

        if(checkout.accepts_insurance)
            this.vm.insuranceChosen = true;

        if(!this.vm.insuranceChosen) 
            checkout.accepts_insurance = '';
    }

    saveMethod(shipment, index)
    {
        const params = {
            methodId: shipment.methodId
        };

        let self = this;
        axios.post(`/data/checkout/${this.guid}/shipments/${index}/method`, params).then(function (response) {
            self.setCheckout(response.data.checkout);
        });
    }

    setShippingAddress() {

    }

    addInsurance() {

    }

    removeInsurance() {

    }

    addDealer() {

    }

    removeDealer() {
        let ctx = this;
        axios.post(`/checkout/${this.guid}/removedealer`).then(function (response) {
            ctx.setCheckout(response.data.checkout);
            let shipments = [];
            for(var i = 0; i < ctx.vm.checkout.shipments.length; i++) {
                if(ctx.vm.checkout.shipments[i].ffl_required)
                    shipments.push(i);
            }
            ctx.reloadAllMethods(shipments);
        });
    }
}

window.checkout = new Checkout;
window.checkout.init();