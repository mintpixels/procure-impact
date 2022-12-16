const { default: axios } = require("axios");

class Order {

    init() {
        var ctx = this;

        this.initVue();

        const orderId = Util.getProperty('data-order-id');
        const draftId = Util.getProperty('data-draft-id');

        if(orderId) {
            this.id = orderId;
        }
        else {
            this.id = draftId;
            this.vm.draft = true;
        }

        this.getOrder();

        this.bindEvents();
    }

    initVue() {
        let ctx = this;

        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    draft: false,
                    loaded: false,
                    changed: false,
                    readonly: false,
                    submitting: false,
                    brandId: '',
                    byBrand: {},
                    order: {
                        id: '',
                        items: [],
                        subtotal: 0,
                        shipping: 0,
                        tax: 0,
                        taxCalculated: false,
                        total: 0,
                        discount: 0,
                        insurance: 0,
                        insuranceAmount: 0,
                        acceptInsurance: false,
                        insuranceIncluded: false,
                        payments: [],
                        customer: false,
                        email: '',
                        phone: '',
                        billing: ctx.emptyAddress(),
                        customer_notes: '',
                        staff_notes: '',
                        shipping: false,
                        shipments: [ctx.emptyShipment()],
                        holdInventory: false,
                    },
                    verification: false,
                    timeline: [],
                    ready: false,
                    activeShipment: false,
                    shipmentPrice: 0,
                    resetShipmentPrice: false,
                    shippingUpdated: false,
                    modalView: '',
                    modalError: '',
                    error: '',
                    productLookup: '',
                    productMatches: [],
                    customerLookup: '',
                    customerMatches: [],
                    editAddress: {},
                    selectedAddress: '',
                    billingIndex: '',
                    customer: ctx.emptyCustomer(),
                    groups: [],
                    paymentMethod: '',
                    paymentAmount: 0,
                    paymentNotes: '',
                    manualCard: false,
                    showActions: false,
                    cancelRestock: true,
                    zoomPhoto: '',
                    due: 0,
                    paymentError: '',
                    card: {
                        number: '',
                        name: '',
                        expiry: '',
                        cvv: '',
                        accept_token: ''
                    },
                    freight: {
                        carrier: "",
                        id: "",
                        price: 0,
                        service: "Freight"
                    }
                }
            },
            methods: {
                setupPayment() {
                    this.paymentMethod = 'Credit Card'; 
                    this.paymentAmount = this.due;
                    this.paymentNotes = '';
                    this.manualCard = false;
                },

                addPayment() {
                    if(this.paymentAmount <= 0)
                        return;

                    if(this.paymentMethod == 'Credit Card' && !this.manualCard) {
                        ctx.getAuthToken(function(token) {
                            ctx.vm.order.payments.push({
                                method: 'Authorize.net',
                                amount: ctx.vm.paymentAmount,
                                note: ctx.vm.paymentNotes,
                                accept_token: token,
                                last_4: ctx.vm.card.number.substr(ctx.vm.card.number.length - 4)
                            });

                            ctx.vm.closeModal();
                            ctx.checkChanged();
                        });
                    }
                    else {
                        this.order.payments.push({
                            method: this.paymentMethod,
                            amount: this.paymentAmount,
                            note: this.paymentNotes
                        });

                        this.closeModal();
                        ctx.checkChanged();
                    }
                },

                removePayment(index) {
                    this.order.payments.splice(index, 1);
                    ctx.checkChanged();
                },

                printInvoice() {
                    window.print();
                },

                formatExpiry(e) {

                    // Prevent double slashes.
                    if(e.key == '/' && this.card.expiry.indexOf('/') >= 0) {
                        this.card.expiry = this.card.expiry.slice(0, -1);
                        e.preventDefault();
                        return false;
                    }
            
                    // Format with the slash.
                    if(this.card.expiry.length == 3 && e.key == 'Backspace') {
                        this.card.expiry = this.card.expiry[0];
                        return;
                    }
            
                    if(this.card.expiry.length == 2) {
                        this.card.expiry += '/';
                    }
                },

                deleteDraft(draft) {
                    if(window.confirm('Delete this draft order?')) {
                        axios.post(`/admin/drafts/${ctx.id}/delete`).then(function(response) {
                            window.location = '/admin/drafts';
                        });
                    }
                },

                getTax() {
                    const taxable = this.order.customer ? this.order.customer.taxable : true;
                    const params = { 
                        items: this.order.items, 
                        taxable: taxable, 
                        shipping: this.order.shipping 
                    };

                    if(this.order.shipments.length > 0 && this.order.shipments[0].address.zip)
                        params.address = this.order.shipments[0].address;

                    axios.post(`/admin/orders/tax`, params ).then(function (response) {
                        ctx.vm.order.tax = response.data.tax;
                        ctx.vm.order.taxCalculated = true;

                        ctx.totals();
                        ctx.checkChanged();
                    });
                },

                lookupCustomer(q) {
                    axios.get(`/admin/data/customers/lookup`, { params: { q: q }} ).then(function (response) {
                        ctx.vm.customerMatches = response.data.customers;
                    });
                },

                lookupProducts(q) {
                    let group = ctx.vm.order.customer ? ctx.vm.order.customer.group_id : '';
                    axios.get(`/admin/data/orders/products`, { params: { q: q, group: group }} ).then(function (response) {

                        // Move out of stock products to the end.
                        // response.data.products.sort((a, b) => {
                        //     if(a.sku.toLowerCase() == q.toLowerCase()) return -1;
                        //     return 0;
                        // });

                        ctx.vm.productMatches = response.data.products;
                    });
                },


                removeItem(index) {
                    const idx = parseInt(index);
                    this.order.items.splice(idx, 1);
                    ctx.checkChanged();
                },

                newCustomer(customer) {
                    if(this.customerComplete(customer)) {
                        axios.get(`/admin/customer/exists`, { params: { email: customer.email }}).then(function (response) {
                            if(!response.data.exists) {
                                ctx.vm.order.customer = Util.clone(customer);
                                ctx.vm.order.customer.addresses = [];
                                ctx.vm.closeModal();
                                ctx.checkChanged();
                                ctx.vm.customer = ctx.emptyCustomer();
                            }
                            else {
                                ctx.vm.modalError = 'Customer email aready exists';
                            }
                        });
                    }
                },

                editShippingAddress(shipment) {
                    this.selectedAddress = '';
                    this.activeShipment = shipment;
                    this.editAddress = Util.clone(shipment.address);
                    this.showModal('edit-address');
                },
                editBillingAddress() {
                    this.selectedAddress = '';
                    this.editAddress = this.order.billing ? Util.clone(this.order.billing) : ctx.emptyAddress(); 
                    this.showModal('edit-billing');
                },

                addressChanged() {
                    this.editAddress = this.selectedAddress ? 
                        Util.clone(this.order.customer.addresses[this.selectedAddress - 1]) : 
                        ctx.emptyAddress();
                },

                saveShippingAddress() {
                    if(this.addressComplete(this.editAddress)) {
                        this.activeShipment.address = Util.clone(this.editAddress);
                        ctx.resetShippingMethods(this.activeShipment);
                        this.closeModal();
                        ctx.checkChanged();
                        this.shippingUpdated = true;
                    }
                    else {
                        this.modalError = 'Complete all required fields';
                    }
                },

                saveBillingAddress() {
                    if(this.addressComplete(this.editAddress)) {
                        this.order.billing = Util.clone(this.editAddress);
                        this.closeModal();
                        ctx.checkChanged();
                        ctx.applyAddress();
                    }
                    else {
                        this.modalError = 'Complete all required fields';
                    }
                },

                customerComplete(customer) {
                    return ((customer.first_name && customer.last_name) || customer.company) &&
                        customer.email &&
                        customer.group_id;
                },

                addressComplete(address) {
                    return ((address.first_name && address.last_name) || address.company) &&
                        address.address1 &&
                        address.city &&
                        address.state &&
                        address.zip;
                },

                addProduct(p) {
                    // if(p.available <= 0)
                    //     return;

                    for(var i = 0; i < this.order.items.length; i++) {
                        if(this.order.items[i].product.id == p.id) {
                            Util.showAsyncStatus('Product is already in the order');
                            return;
                        }
                    }

                    let item = ctx.emptyLineItem(p);
                    ctx.setQuantityPrice(item);
                    this.order.items.push(item);

                    this.productLookup = '';
                    this.productMatches = [];
                    ctx.groupByBrand();
                    ctx.checkChanged();
                },

                setItemPrice(item, price) {
                    item.customPrice = price;
                    ctx.checkChanged();
                },

                itemUpdated(item, itemIndex) {
                    if(!this.loaded) return;

                    ctx.setQuantityPrice(item);
                    ctx.checkChanged();
                },

                addCustomer(c) {
                    this.order.customer = c;
                    this.customerLookup = '';
                    this.customerMatches = [];
                    ctx.getCustomerAddresses(c.id);
                },

                removeCustomer() {
                    this.order.customer = false;
                    ctx.checkChanged();
                },

                customerName(c) {
                    return Util.getCustomerName(c)
                },

                showModal(view) {
                    this.modalError = '';
                    this.modalView = view;
                },

                closeModal() {
                    this.modalView = '';
                },

                showAddShipment() {
                    $('.shipment-item-qty').val(0);
                    this.showModal('add-shipment');
                },

                completeDraft() {
                    this.submitting = true;
                    axios.post(`/admin/drafts/${ctx.id}/complete`).then(function(response) {
                        window.location = '/admin/orders/' + response.data.order_id;
                    }).catch(function(error) {
                        ctx.vm.submitting = false;
                        Util.showAsyncStatus(error.response.data.error, true);
                    })
                },

                updateStatus(status) {
                    ctx.updateOrderStatus(status);
                    this.showActions = false;
                },

                approveOrder() {
                    if(window.confirm('Approve this order?')) {
                        ctx.updateOrderStatus('Approved');
                    }
                    this.showActions = false;
                },

                completeOrder() {
                    if(window.confirm('Complete this order?')) {
                        ctx.updateOrderStatus('Completed');
                    }
                    this.showActions = false;
                },

                showCancelOrder() {
                    this.modalView = 'cancel-order';
                },

                cancelOrder() {
                    ctx.updateOrderStatus('Cancelled');
                },
                
                showProblem() {
                    this.modalView = 'problem';
                },

                saveProblem() {
                    ctx.saveProblem(this.problemType);
                    this.showActions = false;
                    this.closeModal();
                },

                save() {
                    if(this.draft) {
                        ctx.saveDraft();
                    }
                    else {
                        ctx.saveOrder();
                    }
                },

                discard() {
                    this.order = Util.clone(this.saved);
                    this.changed = false;
                    this.shippingUpdated = false;
                    ctx.totals();
                },

             

                readyForPayment() {
                
                    // Must be a customer assigned.
                    return this.order.customer && this.order.customer.email && // Customer is set
                        // this.order.taxCalculated && // Tax is calculated
                        this.order.items.length > 0 && // At least one item
                        this.addressComplete(this.order.billing);
                },
                complete() {
                    return this.readyForPayment() && !this.changed && (this.due <= 0 || this.order.shipments.length == 0);
                },
                capturePayment(payment) {
                    let vm = this;
                    axios.post(`/admin/orders/${ctx.id}/payments/${payment.id}/capture`).then(function(response) {
                        payment.captured_at = response.data.payment.captured_at;
                        vm.timeline = response.data.timeline;
                        Util.showAsyncStatus('Payment captured');
                    }).catch(function(error) {
                        vm.timeline = error.response.data.timeline;
                        Util.showAsyncStatus(error.response.data.error, true);
                    })
                },
                photoZoom(src) {
                    this.zoomPhoto = src;
                    this.modalView = 'zoom-photo';
                },
                formatDate(d) {
                    return Util.formatDate(d);
                },
                formatDateTime(d) {
                    return Util.formatDateTime(d);
                },
                formatMoney(p) {
                    return Util.formatMoney(p);
                }
            }
        }).mount('#order-page');
    }

    freeShipment() {
        return {
            carrier: 'FREE SHIPPING',
            service: '',
            free: true,
            price: 0
        }
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

    emptyCustomer() {
        return {
            first_name: '',
            last_name: '',
            company: '',
            email: '',
            phone: '',
            group_id: '',
            notes: '',
            addresses: [],
            taxable: 1
        }
    }

    emptyShipment() {
        return {
            method: '',
            items: [],
            address: this.emptyAddress(),
            ffl_required: false,
            carriers: false,
            free: false
        }
    }

    emptyLineItem(p) {
        return {
            quantity: 1,
            underStocked: false,
            customPrice: p.price,
            sku: p.sku,
            name: p.name,
            price: p.price,
            brand_id: p.brand_id,
            tax: 0,
            product: p
        };
    }
    applyAddress() {

        let address = false;
        let customer = this.vm.order.customer;
        if(customer && customer.addresses.length > 0) {
            address = Util.clone(customer.addresses[0]);
        }
        else if(this.vm.order.billing.address1) {
            address = Util.clone(this.vm.order.billing);
        }
        
        if(!address) return;

        if(!this.vm.order.billing.address1) {
            this.vm.order.billing = Util.clone(address);
        }
    }

    getOrder() {
        
        // If an ID isn't set then this is a new draft order.
        if(!this.id) {
            
            this.vm.loaded = true;
            this.vm.saved = Util.clone(this.vm.order);

            let ctx = this;
            // axios.get(`/admin/data/drafts/customergroups`).then(function (response) {
            //     ctx.vm.groups = response.data.groups;
            // });

            return;
        }

        const ctx = this;

        if(this.vm.draft) {
            axios.get(`/admin/data/drafts/${this.id}`).then(function (response) {
                ctx.vm.order = response.data.draft.data;
                ctx.vm.groups = response.data.groups;
                ctx.vm.verification = response.data.verification;
                ctx.vm.loaded = true;
                ctx.totals();

                if(response.data.customer)
                    ctx.vm.order.customer = response.data.customer;

                ctx.vm.saved = Util.clone(ctx.vm.order);
            });
        }
        else {

            axios.get(`/admin/data/orders/${this.id}`).then(function (response) {
                ctx.setOrder(response);
            });
        }
    }

    verifyOrder() {
        let vm = this.vm;
        axios.post(`/admin/orders/${this.id}/verify`).then(function (response) {
            vm.order.verified_at = response.data.order.verified_at;
            vm.order.verified_by = response.data.order.verified_by;
            vm.order.status = response.data.order.status;
        });
    }

    unverifyOrder() {
        let vm = this.vm;
        axios.post(`/admin/orders/${this.id}/unverify`).then(function (response) {
            vm.order.verified_at = response.data.order.verified_at;
            vm.order.verified_by = response.data.order.verified_by;
            vm.order.status = response.data.order.status;
        });
    }

    updateOrderStatus(status) {
        let ctx = this;
        axios.post(`/admin/orders/${this.id}/status`, { status: status, restock: ctx.vm.cancelRestock }).then(function (response) {
            ctx.setOrder(response);
            ctx.vm.closeModal();
        });
    }

    saveProblem(id) {
        let ctx = this;
        let params = {
            problem_id: id
        };
        if(this.vm.sendProblemEmail)
            params.send_problem_email = true;

        axios.post(`/admin/orders/${this.id}/problem`, params).then(function (response) {
            ctx.setOrder(response);
        });
    }

    saveDraft() {
        const ctx = this;
        const params = ctx.getParams(this.vm.order);
        axios.post(`/admin/drafts/${ctx.id}`, params).then(function(response) {
            ctx.vm.order = response.data.draft.data;
            ctx.vm.verification = response.data.verification;
            ctx.vm.changed = false;
            ctx.totals();
            ctx.vm.saved = Util.clone(ctx.vm.order);
            ctx.vm.error = '';

            if(!ctx.id) {
                ctx.id = response.data.draft.id;
                window.history.replaceState({}, '', '/admin/drafts/' + ctx.id);
            }
        }).catch(function(error) {
            ctx.vm.error = error.response.data.error;
        });
    }

    checkFFLStatus(shipment) {
        let skus = [];
        shipment.items.map(item => {
            skus.push(this.vm.order.items[item.idx].product.sku);
        });

        axios.get('/admin/orders/ffl', { params: { skus: skus }}).then(function (response) {
            shipment.ffl_required = response.data.ffl_required;
        });
    }

    cleanupShipments() {
        for(var i = 0; i < this.vm.order.shipments.length; i++) {
            this.vm.order.shipments[i].items = this.vm.order.shipments[i].items.filter(item => {
                return item.quantity > 0;
            });
        }

        let shipments = this.vm.order.shipments.filter(shipment => shipment.items.length > 0);
        // if(shipments.length == 0)
        //     shipments = [this.vm.order.shipments[0]];

        this.vm.order.shipments = shipments;
    }

    updateShipments() {

        // Don't try to update shipments for pickup orders.
        if(!this.vm.draft && this.vm.order.shipments.length == 0) {
            this.checkChanged();
            return;
        }

        this.vm.shippingUpdated = true;
        this.totals();

        let items = [];
        this.vm.order.items.map(item => {
            items.push({ sku: item.sku, quantity: item.quantity });
        });

        const params = {
            items: items,
            subotal: this.vm.order.subtotal
        };

        const ctx = this;
        this.vm.loadingShipments = true;
        axios.post('/admin/orders/shipments', params).then(function(response) {
            let address = ctx.emptyAddress();
            if(ctx.vm.order.shipments.length > 0) {
                address = Util.clone(ctx.vm.order.shipments[0].address);
                ctx.vm.order.shipments = [];
            }

            let shipments = response.data.shipments.shipments;
            
            shipments.map(shipment => {
                let newShipment = ctx.emptyShipment();
                newShipment.address = address;
                newShipment.ffl_required = shipment.ffl_required;
                newShipment.carriers = shipment.carriers;

                shipment.items.map(item => {
                    newShipment.items.push({
                        idx: ctx.findItemIndex(item.sku),
                        quantity: item.quantity
                    });
                });

                ctx.getShipmentWeight(newShipment);
                ctx.vm.order.shipments.push(newShipment);
            });

            ctx.checkChanged();
            ctx.vm.loadingShipments = false;
        });
    }

    findItemIndex(sku) {
        for(var i = 0; i < this.vm.order.items.length; i++) {
            if(this.vm.order.items[i].sku == sku)
                return i;
        }
    }

    updateShipmentQuantity(shipment, itemIndex, qty) {

        let updated = false;
        for(var i = 0; i < shipment.items.length; i++) {
            if(shipment.items[i].idx == itemIndex) {
                shipment.items[i].quantity = parseInt(shipment.items[i].quantity) + parseInt(qty);
                updated = true;
                break;
            }
        }

        // If the shipment doesn't already have the item
        // then we need to add it.
        if(!updated)
            shipment.items.push({ idx: itemIndex, quantity: qty });

        this.resetShippingMethods(shipment);
        this.checkFFLStatus(shipment);
    }

    getCustomerAddresses(id) {
        if(!id) {
            ctx.vm.order.customer.addresses = [];
            ctx.checkChanged();
            return;
        }
        
        let ctx = this;
        axios.get(`/admin/data/customers/${id}/addresses`).then(function (response) {
            ctx.vm.order.customer.addresses = response.data.addresses;
            ctx.applyAddress();
            ctx.checkChanged();
        });
    }

    removeItemFromShipments(itemIndex) {
        for(var i = 0; i < this.vm.order.shipments.length; i++) {
            var shipment = this.vm.order.shipments[i];

            // Remove the item from the shipment.
            const items = shipment.items.filter(item => item.idx != itemIndex);
            if(items.length != shipment.items.length) {
                shipment.method = false;
                shipment.methods = [];
            }
            shipment.items = items;

            // Update the item index for any items that came after the
            // removed item.
            shipment.items.map(item => {
                if(item.idx > itemIndex) item.idx--;
            });
        }

        this.cleanupShipments();
    }

    addItemToBrand(map, item, index) {
        let brandId = item.product.brand_id;
        if(!map[brandId]) {
            map[brandId] = {
                brand: item.product.brand,
                items: []
            };
        }

        map[brandId].items.push({
            index: index,
            item: item
        });
    }

    groupByBrand() {
        let byBrand = {};
        for(var i = 0;  i < this.vm.order.items.length; i++) {
            let item = this.vm.order.items[i];
            this.addItemToBrand(byBrand, item, i);
        }

        this.vm.byBrand = byBrand;
    }

    setOrder(response) {
        this.vm.brandId = response.data.brand_id;
        this.vm.order = response.data.order;
        this.vm.loaded = true;
        this.vm.timeline = response.data.timeline;

        // Format shipping data to match expected format.
        const ctx = this;
        for(var i = 0;  i < this.vm.order.items.length; i++) {
            let item = this.vm.order.items[i];
            item.originalQty = item.quantity;
            if(!item.customPrice) {
                item.customPrice = item.price;
            }
        }

        this.groupByBrand();

        this.vm.order.taxCalculated = true;
        this.totals();
        
        this.vm.saved = Util.clone(this.vm.order);
        this.vm.changed = false;
        this.vm.readonly = ['Completed', 'In Shipping', 'Awaiting Fulfillment', 'Cancelled'].indexOf(this.vm.order.status) >= 0;

        // Allow editing completed pickup orders.
        if(this.vm.order.status == 'Completed' && this.vm.order.shipments.length == 0)
            this.vm.readonly = false;
    }

    resetShippingMethods(shipment) {
        this.vm.shippingUpdated = true;
        shipment.method = false;
        shipment.methods = [];
    }

    applyItemUpdates(item, itemIndex) {

        // Find the quantity already assigned to shipments and modify
        // the quantities if needed.
        let remaining = item.quantity;
        for(var i = 0; i < this.vm.order.shipments.length; i++) {
            let shipment = this.vm.order.shipments[i];
            for(var j = 0; j < shipment.items.length; j++) {
                let item = shipment.items[j];
                if(item.idx == itemIndex) {
                    if(remaining < item.quantity) {
                        item.quantity = remaining;
                        remaining = 0;
                        this.resetShippingMethods(shipment);
                    }
                    else {
                        remaining -= item.quantity;
                    }
                }
            }

            this.getShipmentWeight(shipment);
        }

        // If there is any remaining quantity then add it to the
        // first shipment.
        if(remaining > 0)
            this.updateShipmentQuantity(this.vm.order.shipments[0], itemIndex, remaining);
    }

    updateItemShipments(itemIndex) {
        for(var i = 0; i < this.vm.order.shipments.length; i++) {
            var shipment = this.vm.order.shipments[i];
            if(shipment.items.indexOf(itemIndex) >= 0) {
                shipment.method = false;
                shipment.methods = [];
            }
            this.getShipmentWeight(shipment);
        }
    }

    getParams(order) {
        return Util.clone({
            items: order.items,
            email: order.email,
            phone: order.phone,
            customer_id: order.customer ? order.customer.id : null,
            customer: order.customer,
            customer_notes: order.customer_notes,
            staff_notes: order.staff_notes,
            shipping: order.shipping,
            billing: order.billing,
            acceptInsurance: order.acceptInsurance,
            subtotal: order.subtotal,
            tax: order.tax,
            taxCalculated: order.taxCalculated,
            total: order.total,
            payments: order.payments
        });
    }

    bindEvents() {
        let ctx = this;
        $('body').on('keyup change', function() {
            ctx.checkChanged();
        });

        // Close popups when clicking away from them.
        $(window).on('click', function() {
            ctx.vm.productMatches = [];
            ctx.vm.customerMatches = [];
        });
        $('body').on('click', '.matches', function(e) {
            e.stopPropagation();
        });

        $(window).on('beforeunload', function(e) {
            if(ctx.vm.changed) {
                return "Leave without saving changes?"
            }
        });
    }

    totals() {

        // Make sure inputs are numbers.
        this.vm.order.subtotal = parseFloat(this.vm.order.subtotal);

        // Item subtotal
        let subtotal = 0;
        for(var i = 0; i < this.vm.order.items.length; i++) {
            let item = this.vm.order.items[i];
            if(!this.vm.brandId || this.vm.brandId == item.brand_id) {
                subtotal += parseFloat(item.customPrice) * parseInt(item.quantity);
                subtotal = Math.round(subtotal * 100) / 100;
            }
        }

        // If the subtotals have changed then we need to recalculate the tax.
        if(subtotal != this.vm.order.subtotal) {
            this.vm.order.tax = 0;
            this.vm.order.taxCalculated = false;
        }

        this.vm.order.subtotal = subtotal;
        this.vm.order.total = subtotal + parseFloat(this.vm.order.tax);
        
        if(this.vm.order.total == 0) {
            this.vm.order.taxCalculated = true;
        }

        // Get the amount still due.
        this.vm.due = this.vm.order.total;
        for(var i in this.vm.order.payments) {
            this.vm.due -= this.vm.order.payments[i].amount;
        }
        this.vm.due = Math.round(this.vm.due * 100) / 100;
    }

    saveOrder() {
        const ctx = this;
        const params = { fields: ctx.getParams(this.vm.order) };
        
        axios.post(`/admin/orders/${ctx.id}`, params).then(function(response) {

            ctx.setOrder(response);
            
        }).catch(function(error) {
            ctx.vm.error = error.response.data.error;
        });
    }

    setQuantityPrice(item) {
        // item.customPrice = parseFloat(item.product.lowest_price);
        // for(var qty in item.product.qty_prices) {
        //     var price = parseFloat(item.product.qty_prices[qty]);
        //     if(qty <= item.quantity && price < item.customPrice) {
        //         item.customPrice = price;
        //     }
        // }
    }

    getAuthToken(callback){
        var authData = {};
        authData.clientKey = "5ML34a9EQm3mhwka575399CJgUtRyE4jKA2agaYhuQ9Szxq8A498b73C2qEN2TDd";
        authData.apiLoginID = "2r42zFvWA";

        var cardData = {};
        let exp = this.vm.card.expiry.split('/');
        cardData.cardNumber = this.vm.card.number;
        cardData.month = exp[0];
        cardData.year = exp[1];
        cardData.cardCode = this.vm.card.cvv;
        cardData.zip = this.vm.order.billing.zip;

        var secureData = {};
        secureData.authData = authData;
        secureData.cardData = cardData;
        this.vm.paymentError = '';
		Accept.dispatchData(secureData, response => {
            if(response.messages.resultCode == 'Ok') {
                callback(response.opaqueData.dataValue);
            }
            else {
                this.vm.paymentError = response.messages.message[0].text;
            }
        });
    }

    checkChanged() {
        if(!this.vm.loaded) return;

        this.totals();

        this.vm.changed = Util.checkChanged(
            this.getParams(this.vm.saved),
            this.getParams(this.vm.order)
        );
    }
}

if($('#order-page').length > 0) {
    window.order = new Order;
    window.order.init();
}