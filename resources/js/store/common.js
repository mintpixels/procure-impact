const { default: axios } = require("axios");

class Common {

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
        this.initFooterVue();
        this.initMenuVue();
        this.initData();
        this.bindEvents();

        $(document).ready(function() {
            if(window.location.hash == '#cart') {
                window.location.hash = '';
                window.Cart.showSideCart();
            }
        });
    }

    initFooterVue() {
        if($('footer').length > 0) {
            this.footerVm = Vue.createApp({
                delimiters: ['${', '}'],
                data() {
                    return {
                        data: false
                    }
                },
                methods: {
                    
                }
            }).mount('footer');
        }
    }

    initMenuVue() {
        if($('#menu-bar').length > 0) {
            this.menuVm = Vue.createApp({
                delimiters: ['${', '}'],
                data() {
                    return {
                        data: false
                    }
                },
                methods: {
                    getImagePath(path) {
                        return `https://images.takeshape.io/${ path}`;
                    }
                }
            }).mount('#menu-bar');
        }
    }

    initData() {
        const ctx = this;
        axios.get('/data/common').then(function (response) {
            ctx.footerVm.data = response.data.footer;
            ctx.menuVm.data = response.data.header;
        });
    }

    initMenu() {
        const root = this;

        // if($('#menu').length > 0) {
        //     this.menu = Vue.createApp({
        //         delimiters: ['${', '}'],
        //         data() {
        //             return {
        //                 activeCategory: false,
        //                 activeChildren: [],
        //                 categories: [],
        //                 nested: [],
        //                 restrictedProducts: [],
        //             }
        //         },
        //         methods: {
        //             pop() {
        //                 if(this.nested.length == 0) {
        //                     root.hideMenu();
        //                 }
        //                 else {
        //                     this.activeCategory = this.nested.pop();
        //                     this.activeChildren = this.activeCategory ? this.activeCategory.children : this.categories;
        //                 }
                        
        //             },
        //             showCategory(category) {
        //                 this.nested.push(this.activeCategory);
        //                 this.activeCategory = category;
        //                 this.activeChildren = category.children;
        //             }
        //         }
        //     }).mount('#menu');

        //     const menu = this.menu;
        //     axios.get('/data/common').then(function (response) {
        //         menu.categories = response.data.categories;
        //         menu.activeChildren = menu.categories;
        //     });
        // }
    }


    showOverlay() {
        $(this.sel.overlay).addClass('show');
        $('body').addClass('no-scroll');
    }

    hideOverlay() {
        $(this.sel.overlay).removeClass('show');
        $('body').removeClass('no-scroll');
    }

    showMenu() {
        $(this.sel.overlay).addClass('show');
        $(this.sel.menu).addClass('show');
    }

    hideMenu() {
        $(this.sel.overlay).removeClass('show');
        $(this.sel.menu).removeClass('show');

        this.menu.activeCategory = false;
        this.menu.activeChildren = this.menu.categories;
        this.menu.nested = [];
    }

    bindEvents() {
        const root = this;

        // Hide overlay and any overlaid items.
        $('body').on('click', this.sel.overlay, function() {
            $(root.sel.overlay).removeClass('show');
            Cart.hideSideCart();
        });

    }
}

window.Common = new Common;
window.Common.init();