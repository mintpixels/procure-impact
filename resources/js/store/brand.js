const { default: axios } = require("axios");

class Brand {

    constructor() {
        this.handle = Util.getProperty('data-brand');
    }

    init() {
        this.initVue();
        this.bindEvents();
        this.loadBrand();
    }

    initVue() {
        
        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    brand: {},
                    brand_products: [],
                    sections: []
                }
            },
            methods: {
                getImagePath(path) {
                    return `https://images.takeshape.io/${path}`;
                },
                getButtonTarget(button) {
                    if(!button) return '';
                    return button.openNewWindow ? '_blank' : '';
                },
                getButtonUrl(button) {
                    if(!button) return '';
                    return button.url;
                },
                getButtonText(button) {
                    if(!button) return '';
                    return button.text;
                },
                formatMoney(price) {
                    return Util.formatMoney(price);
                },
            }
        }).mount(Brand.page);
    }

    bindEvents() {
    }

    loadBrand() {
        const vm = this.vm;
        axios.get(`/data/brands/${this.handle}`).then(function (response) {
            console.log('response', response.data);
            vm.brand = response.data;
            vm.sections = response.data.sections;
            vm.brand_products = response.data.brand_products;
        });
    }
}

Brand.page = '#brand-page';

if($(Brand.page).length > 0) {
    window.Brand = new Brand;
    window.Brand.init();    
}