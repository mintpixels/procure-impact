const { default: axios } = require("axios");
const { sort } = require("semver");
import draggable from 'vuedraggable'

class Product {

    constructor() {
        this.desc = false;
        this.short = false;
        this.specs = false;
        this.other = false;
    }
    
    init() {
        this.initVue();
        this.bindEvents();

        this.id = $('#product-page').attr('data-product-id');
        this.getProduct();
    }

    initVue() {
        var ctx = this;
        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            components: {
                draggable,
            },
            data() {
                return {
                    loaded: false,
                    changed: false,
                    uploading: false,
                    error: '',
                    modalView: '',
                    previewImage: false,
                    showDiscounts: true,
                    showInventory: true,
                    showSlideIn: false,
                    showProperties: true,
                    hasVariants: false,
                    inheritPricing: true,
                    activePrices: false,
                    activeVariant: {},
                    saved: {},
                    timeline: [],
                    adjustments: [],
                    groups: [],
                    groupsMap: [],
                    product: ctx.emptyProduct(),
                    newProperty: '',
                    newPriceGroup: '',
                    newTag: '',
                    newVideo: '',
                    categories: [],
                    categorySearch: '',
                    categoryMatches: [],
                    productSearch: '',
                    productMatches: [],
                    activeProperty: '',
                    products: [],
                    addonSearch: '',
                    addonMatches: [],
                    addons: [],
                    propertyMatches: [],
                    properties: [],
                    productType: '',
                    productTypes: [],
                    typeMatches: [],
                    activeOptionIndex: 0,
                    optionName: '',
                    optionValues: '',
                    committed: false,
                    tab: 'short'
                }
            },
            methods: {
                showTab(t) {
                    this.tab = t;
                },
                formatNumber(n) {
                    return Util.formatNumber(n);
                },
                formatDateTime(d) {
                    return Util.formatDateTime(d);
                },
                copy() {
                    ctx.copyProduct();
                },
                showHistory() {
                    this.showSlideIn = true;
                    console.log('show history');
                },
                imagesSorted() {
                    ctx.checkChanged();
                },
                addVideo(code) {
                    this.product.images.push({
                        master: code,
                        embed: code
                    });
                    ctx.checkChanged();
                    this.closeModal();
                },
                youtubeThumbnail(code) {
                    return `https://img.youtube.com/vi/${code}/hqdefault.jpg`;
                },
                youtubeUrl(code) {
                    return `https://www.youtube.com/embed/${code}`;
                },
                uploadImage() {
                    this.uploading = true;
                    ctx.uploadImage();
                },
                removeImage(image) {
                    let index = -1;
                    for(var i = 0; i < this.product.images.length; i++) {
                        if(this.product.images[i].master == image)
                            index = i;
                    }
                    if(index >= 0) {
                        this.product.images.splice(index, 1);
                        ctx.checkChanged();
                    }
                },
                save() {

                    // Calculate any adjustments that need to be made to inventory.
                    let adjust = parseInt(this.product.inventory.warehouse) - parseInt(this.saved.inventory.warehouse);
                    adjust += parseInt(this.product.adjust.warehouse);

                    let params = {
                        fields: ctx.getParams(this.product),
                        inventoryAdjustment: adjust
                    };

                    axios.post(`/admin/products/${ctx.id}`, params).then(function(response) {
                        ctx.loadProduct(response.data.product);
                        ctx.vm.timeline = response.data.timeline;
                        ctx.vm.changed = false;
                    });
                },
                discard() {
                    this.product = Util.clone(this.saved);
                    $('#description [contenteditable]').html(this.product.description);
                    $('#short_desc [contenteditable]').html(this.product.short_desc);
                    $('#specs [contenteditable]').html(this.product.specs);
                    $('#other [contenteditable]').html(this.product.other);
                    this.changed = false;
                },
                removeTag(index) {
                    this.product.tags.splice(index, 1);
                    ctx.checkChanged();
                },
                removeCategory(index) {
                    this.product.categories.splice(index, 1);
                    ctx.checkChanged();
                },
                removeProduct(index) {
                    this.product.related.splice(index, 1);
                    ctx.checkChanged();
                },
                removeAddon(index) {
                    this.product.addons.splice(index, 1);
                    ctx.checkChanged();
                },
                removePrice(index, prices) {
                    prices.additional.splice(index, 1);
                    ctx.checkChanged();
                },
                removeProperty(index) {
                    this.product.properties.splice(index, 1);
                    ctx.checkChanged();
                },
                showModal(view) {
                    this.modalView = view;
                },
                closeModal() {
                    this.modalView = '';
                },
                matchTypes() {
                    let search = this.productType.toLowerCase();
                    this.typeMatches = [];

                    if(search.length == 0)
                        return;

                    this.productTypes.forEach( t => {
                        if(t.name.toLowerCase().indexOf(search) == 0)
                            this.typeMatches.push(t);
                    });
                },
                setType(t) {
                    this.product.type = t;
                    this.typeMatches = [];
                    ctx.checkChanged();
                },
                matchCategories() {
                    let search = this.categorySearch.toLowerCase();
                    this.categoryMatches = [];

                    if(search.length == 0)
                        return;

                    this.categories.forEach( c => {
                        if(c.name.toLowerCase().indexOf(search) >= 0)
                            this.categoryMatches.push(c);
                    });
                },
                matchProducts() {
                    let search = this.productSearch.toLowerCase();
                    this.productMatches = [];

                    if(search.length == 0)
                        return;

                    this.products.forEach(p => {
                        if(p.sku && p.sku.toLowerCase().indexOf(search) >= 0)
                            this.productMatches.push(p);
                    });
                },

                matchProperties(property) {
                    let search = property.property.name.toLowerCase();
                    this.propertyMatches = [];

                    if(search.length == 0)
                        return;

                    this.activeProperty = property;
                    this.properties.forEach(p => {
                        if(p.name.toLowerCase().indexOf(search) >= 0)
                            this.propertyMatches.push(p);
                    });
                },

                selectProperty(property) {
                    this.activeProperty.property.name = property.name;
                    this.propertyMatches = [];
                },

                matchAddons() {
                    let search = this.addonSearch.toLowerCase();
                    this.addonMatches = [];

                    if(search.length == 0)
                        return;

                    this.products.forEach(p => {
                        if(p.sku && p.sku.toLowerCase().indexOf(search) >= 0)
                            this.addonMatches.push(p);
                    });
                },
                addCategory(c) {
                    this.product.categories.push(c);
                    this.categorySearch = '';
                    this.categoryMatches = [];
                    ctx.checkChanged();
                },
                addRelated(p) {
                    this.product.related.push({
                        id: this.product.id,
                        related_id: p.id,
                        product: {
                            name: p.name,
                            sku: p.sku
                        }
                    });
                    this.productSearch = '';
                    this.productMatches = [];
                    ctx.checkChanged();
                },
                addAddon(p) {
                    this.product.addons.push({
                        id: this.product.id,
                        addon_id: p.id,
                        product: {
                            name: p.name,
                            sku: p.sku
                        }
                    });
                    this.addonSearch = '';
                    this.addonMatches = [];
                    ctx.checkChanged();
                },
                addTag() {
                    if(this.newTag.trim()) {
                        this.product.tags.push(this.newTag);
                        this.newTag = '';
                        ctx.checkChanged();
                    }
                },
                showAddPrice(prices) {
                    this.activePrices = prices; 
                    this.showModal('add-price');
                },
                addPrice() {
                    this.activePrices.additional.push({
                        group_id: this.newPriceGroup, 
                        quantity: 0, 
                        percent: '', 
                        price: ''
                    });
                    this.$forceUpdate();
                    this.closeModal();
                    ctx.checkChanged();
                },
                addProperty() {
                    this.product.properties.push({ 
                        property: { name: '' },
                        value: '',
                        pdp: true
                    });
                    
                    this.newProperty = '';
                    this.closeModal();
                    ctx.checkChanged();
                },
                editOption(index) {
                    index = typeof(index) === 'undefined' ? -1 : index;
                    this.activeOptionIndex = index;
                    let option = index >= 0 ? Util.clone(this.product.options[index]) : ctx.emptyOption();
                    this.optionName = option.name;
                    this.optionValues = option.values.join(',');
                    this.showModal('edit-option');
                },
                removeOption(index) {
                    this.product.options.splice(index,1);
                    ctx.checkChanged();
                },
                saveOption() {
                    const option = ctx.emptyOption();
                    option.name =  this.optionName;
                    option.values = this.optionValues.split(',').map((value) => value.trim());

                    if(this.activeOptionIndex >= 0) {
                        this.product.options[this.activeOptionIndex] = option;
                    }
                    else {
                        this.product.options.push(option);
                    }

                    this.product.options.sort((a, b) => {
                        return a.name.localeCompare(b.name);
                    });

                    ctx.checkChanged();
                    this.closeModal();
                },
                clearHandle() {
                    this.product.handle = '';
                    ctx.checkChanged();
                },
                deleteProduct(product) {
                    if(window.confirm('Delete this product?')) {
                        axios.post(`/admin/products/${ctx.id}/delete`).then(function(response) {
                            window.location = '/admin/products';
                        });
                    }
                },
                archiveProduct(product) {
                    if(window.confirm('Archive this product?')) {
                        axios.post(`/admin/products/${ctx.id}/archive`).then(function(response) {
                            window.location = '/admin/products';
                        });
                    }
                },
            }
        }).mount('#product-page');
    }

    getProduct() {
        let ctx = this;
        axios.get(`/admin/data/products/${this.id}`).then(function (response) {
            ctx.vm.groups = response.data.groups;
            ctx.vm.groupsMap = response.data.groupsMap;
            ctx.vm.productTypes = response.data.productTypes;
            ctx.vm.committed = response.data.committed;
            ctx.vm.timeline = response.data.timeline;
            ctx.vm.adjustments = response.data.adjustments;
            ctx.loadProduct(response.data.product);

            ctx.vm.loaded = true;
            // Set the description manually so the editor doesn't get
            // messed up on an update.
            $('#description').html(response.data.product.description);
            $('#short_desc').html(response.data.product.short_desc);
            $('#specs').html(response.data.product.specs);
            $('#other').html(response.data.product.other);
            ctx.initWysiwg();
        });

        axios.get(`/admin/data/products/categories`).then(function (response) {
            ctx.vm.categories = response.data.categories;
        });

        axios.get(`/admin/data/products/lookup`).then(function (response) {
            ctx.vm.products = response.data.products;
        });

        axios.get(`/admin/data/products/properties`).then(function (response) {
            ctx.vm.properties = response.data.properties;
        });
    }

    loadProduct(product) {
        this.vm.product = product;
        this.vm.product.status = product.published_at ? 'active' : 'draft';
        this.vm.product.adjust = { warehouse: 0, showroom: 0, hold: 0 };
        this.mapFields(this.vm.product);

        // Sort the additional prices so it's easier to see how
        // bulk discounts will apply.
        let ctx = this;
        this.vm.product.prices.additional.sort(function (a, b) {
            let groupA = ctx.vm.groupsMap[a.group_id];
            let groupB = ctx.vm.groupsMap[b.group_id];
            if(groupA == groupB) {
                return parseInt(a.quantity) - parseInt(b.quantity);
            }
            return groupA > groupB ? 1 : -1;
        });

        this.vm.product.properties.map((p) => {
            p.pdp = p.pdp ? true : false;
        });

        this.vm.product.properties.sort((a, b) => {
            if(a.property.name < b.property.name)
                return -1;

            return 1;
        })

        this.vm.saved = Util.clone(product);
        this.vm.activeVariant.price = product.price;
        this.vm.activeVariant.cost = product.cost;
        this.vm.activeVariant.prices = Util.clone(product.prices);
    }


    initWysiwg() {
        this.desc = new Quill('#description', { theme: 'snow' });
        this.short = new Quill('#short_desc', { theme: 'snow' });
        this.specs = new Quill('#specs', { theme: 'snow' });
        this.other = new Quill('#other', { theme: 'snow' });
        
        let ctx = this;
        this.desc.on('text-change', function() {
            ctx.vm.product.description = $('#description [contenteditable]').html();
            ctx.checkChanged();
        });
        this.short.on('text-change', function() {
            ctx.vm.product.short_desc = $('#short_desc [contenteditable]').html();
            ctx.checkChanged();
        });
        this.specs.on('text-change', function() {
            ctx.vm.product.specs = $('#specs [contenteditable]').html();
            ctx.checkChanged();
        });
        this.other.on('text-change', function() {
            ctx.vm.product.other = $('#other [contenteditable]').html();
            ctx.checkChanged();
        });
    }

    mapFields(product) {
      const empty = this.emptyProduct();
      for(var field in empty) {
          if(!product.hasOwnProperty(field) || product[field] == null) {
              product[field] = empty[field];
          }
      }
    }

    emptyOption() {
        return {
            name: '',
            values: []
        };
    }

    emptyProduct() {
        return {
            type: { name: '' }, 
            inventory: { warehouse: 0, showroom: 0, hold: 0 },
            adjust: {}, 
            dimensions: { width: '', height: '', depth: '' },
            prices: { sale: 0, msrp: 0, additional: [] }, 
            properties: [], 
            shipping: { require_signature: false },
            additional: { min_qty: 1, max_qty: '', notify_qty: 5, sort_order: 5, price_in_cart: false },
            options:[],
            variants: [{}]
        }
    }

    copyProduct() {
        axios.post(`/admin/products/${this.id}/copy`).then(function (response) {
            window.location = '/admin/products/' + response.data.id;
        });
    }

    bindEvents() {
        let ctx = this;
        $('body').on('keyup change', function() {
            ctx.checkChanged();
        });
    }

    formatCurrency(num) {
        num = parseFloat(num);
        return isNaN(num) ? '' : num.toFixed(2);
    }

    uploadImage() {
        let ctx = this;
        let data = new FormData();
        let input = $('#image-upload')[0];
        for(var i = 0; i < input.files.length; i++) {
            data.append('images[]', input.files[i]);
        }

        axios.post(`/admin/products/${this.id}/images`, data, {
            headers: {'Content-Type': 'multipart/form-data'}
        }).then(function(response) {
            if(!ctx.vm.product.images)
                ctx.vm.product.images = [];
            
            response.data.images.map(image => {
                ctx.vm.product.images.push(image);
            });
            ctx.checkChanged();
            input.value = '';
            ctx.vm.closeModal();
            ctx.vm.uploading = false;
        }).catch(function(error) {
            ctx.vm.uploading = false;
            console.log('error', error);
        });
    }

    getParams(product) {
        let params = Util.clone({
            name: product.name,
            description: product.description,
            short_desc: product.short_desc,
            specs: product.specs,
            other: product.other,
            sku: product.sku,
            upc: product.upc,
            type: product.type,
            brand: product.brand,
            price: product.price,
            cost: product.cost,
            location: product.location,
            status: product.status,
            inventory: product.inventory,
            weight: product.weight,
            dimensions: product.dimensions,
            shipping: product.shipping,
            additional: product.additional,
            options: product.options,
            adjust: product.adjust,
            prices: product.prices,
            properties: product.properties,
            tags: product.tags,
            categories: product.categories,
            images: product.images,
            handle: product.handle,
            related: product.related,
            addons: product.addons,
            variants: product.variants
        });

        // // Format currency fields.
        // params.prices.sale = this.formatCurrency(params.prices.sale);
        // params.prices.msrp = this.formatCurrency(params.prices.msrp);
        // for(let i = 0; i < params.prices.additional.length; i++)
        //     params.prices.additional[i].price = this.formatCurrency(params.prices.additional[i].price);
        
        return params;
    }

    checkChanged() {
        if(!this.vm.loaded) return;

        this.vm.changed = Util.checkChanged(
            this.getParams(this.vm.saved),
            this.getParams(this.vm.product)
        );
    }
}

if($('#product-page').length > 0) {
    window.Product = new Product;
    window.Product.init();
}