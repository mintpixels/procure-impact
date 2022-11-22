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
                }
            }
        }).mount(Brand.page);
    }

    bindEvents() {
    }

    loadBrand() {
        const vm = this.vm;
        axios.get(`/data/brands/${this.handle}`).then(function (response) {
            vm.brand = response.data.brand;
            vm.sections = response.data.sections;
        });
    }
}

Brand.page = '#brand-page';

if($(Brand.page).length > 0) {
    window.Brand = new Brand;
    window.Brand.init();    
}