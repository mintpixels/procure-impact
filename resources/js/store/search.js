const { default: axios } = require("axios");


Array.prototype.remove = function() {
    var what, a = arguments, L = a.length, ax;
    while (L && this.length) {
        what = a[--L];
        while ((ax = this.indexOf(what)) !== -1) {
            this.splice(ax, 1);
        }
    }
    return this;
};


class Search {

    init() {

        if($('#search-results').length > 0) {
            this.initSearchPage();

            this.vm.q = Util.getParam('qs');
            this.vm.categoryId = Util.getParam('category');
            if($('[data-category-id]').length > 0) {
                this.vm.mode = 'category';
                this.vm.categoryId = $('[data-category-id]').attr('data-category-id');
            }

            this.search();
        }
    }
    initSearchPage() {

        const root = this;
        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    pageSize:24,
                    featured: false,
                    total: 0,
                    page: 1,
                    pages: 1,
                    q: '',
                    categoryId: '',
                    products: [],
                    facets: [],
                    category: [],
                    chosenOptions: [],
                    sortBy: 'best',
                    loading: true,
                    mode: 'search',
                    mobileFilter: false
                }
            },
            methods: {
                formatMoney(price) {
                    return Util.formatMoney(price);
                },
                toggleMobileFilter: function() {
                    this.mobileFilter = !this.mobileFilter;
                    this.$forceUpdate();
                },
                toggleShowAll: function(item) {
                    item.showAll = !item.showAll;
                    this.$forceUpdate();
                },
                toggleFacet: function(facet) {
                    facet.collapsed = !facet.collapsed;
                    this.$forceUpdate();
                },
                toggleOption: function(option) {
                    this.page = 1;
                    option.chosen = !option.chosen;
                    if(option.chosen) 
                        this.chosenOptions.push(option.name);
                    else
                        this.chosenOptions.remove(option.name);

                    this.$forceUpdate();
                    root.search(true);
                },
                changePageSize() {
                    this.page = 1;
                    root.search(true);
                },
                changeCategory: function(option) {
                    this.page = 1;
                    this.categoryId = option ? option.id : '';
                    this.$forceUpdate();
                    root.search(true);
                },
                removeOption: function(name) {
                    this.page = 1;
                    this.chosenOptions.remove(name);

                    for(var i = 0; i < this.facets.length; i++) {
                        for(var j = 0; j < this.facets[i].options.length; j++) {
                            if(this.facets[i].options[j].name == name)
                            this.facets[i].options[j].chosen = false;
                        }
                    }

                    this.$forceUpdate();
                    root.search(true);
                },
                changeSort: function(event) {
                    this.page = 1;
                    this.sortBy = event.target.value;
                    root.search();
                },
                updateQuery: function() {

                },
                changePage: function(page) {
                    this.page = page;
                    root.search();
                },
                addToCart: function(id) {
                    Cart.addItem(id, 1);
                }
            }
        }).mount('#search-results');
    }

    search(skip) {
        this.vm.loading = true;

        var q = Util.getParam('qs');
        this.vm.q = q;
        console.log('q', q);

        var filters = [];

        for(var i = 0; i < this.vm.facets.length; i++) {
            var values = [];
            for(var j = 0; j < this.vm.facets[i].options.length; j++) {
                if(this.vm.facets[i].options[j].chosen) {
                    values.push(this.vm.facets[i].options[j].name);
                }
            }

            if(values.length > 0) {
                filters.push(this.vm.facets[i].name + ":" + values.join(','));
            }
        }
        filter = filters.join('@');

        var g = '';
        if($('[data-customer-group]').length > 0)
            g = $('[data-customer-group]').attr('data-customer-group');

        var q = '?qs=' + encodeURIComponent(this.vm.q) + '&g=' + g + '&category=' + this.vm.categoryId + '&filter=' + encodeURIComponent(filter) + "&sort_by=" + this.vm.sortBy +  "&pagesize=" + this.vm.pageSize + "&page=" + this.vm.page + '&mode=' + this.vm.mode;
        
        if(this.vm.mode == 'search')
            window.history.pushState({}, '', q);

        var self = this;
        $.get('/data/search' + q, function(response) {
            self.vm.products = response.products;
            self.vm.facets = response.facets;
            self.vm.total = response.total;
            self.vm.featured = response.featured;
            self.vm.loading = false;
            self.vm.category = response.category;
            self.vm.pages = response.pages

            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }
            window.scrollTo(0,0);

            var products = localStorage.getItem("restricted-products");
            if(products) {       
                products = JSON.parse(products);
                for(i = 0; i < self.vm.products.length; i++) {
                    self.vm.products[i].restrictions = [];
                    for(var j in products) {
                        if(self.vm.products[i].id == j) {
                            for(var k = 0; k < products[j].length; k++)
                                self.vm.products[i].restrictions.push(products[j][k]);
                        }
                    }
                }
            }
            
        }, 'json');
    }
}

window.search = new Search;
window.search.init();