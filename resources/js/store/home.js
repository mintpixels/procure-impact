const { default: axios } = require("axios");

class Home {

    constructor() {
        this.sel = {
            overlay: '.overlay',
            menu: '#menu',
            menuTrigger: '.menu-toggle',
            sideCart: '#side-cart',
            sideCartTrigger: '.side-cart-trigger'
        };
    }

    init() {
        this.initVue();
        this.bindEvents();

        $(document).ready(function() {
            if(window.location.hash == '#cart') {
                window.location.hash = '';
                window.Cart.showSideCart();
            }
        });
    }

    initVue() {
        if($('#homepage').length > 0) {
            this.vm = Vue.createApp({
                delimiters: ['${', '}'],
                data() {
                    return {
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
            }).mount('#homepage');
            
            const vm = this.vm;
            axios.get('/data/home').then(function (response) {
                vm.sections = response.data.sections;
                console.log('sections', vm.sections);
            });
        }
    }

    bindEvents() {
    }
}

window.Home = new Home;
window.Home.init();