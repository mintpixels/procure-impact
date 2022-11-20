const { default: axios } = require("axios");
// import productCategories from "./components/product-categories.vue";
import draggable from "vuedraggable";
import { filter, isDate } from "lodash";

class Products {

    init() {
        this.query = new URLSearchParams(window.location.search)

        this.initVue();
        this.getProducts();

    }

    initVue() {
        const ctx = this;
        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            components: {
                draggable,
                // productCategories
            },
            data() {
                return {
                    loaded: false,
                    changed: false,
                    searching: false,
                    modalView: '',
                    error: '',
                    search: '',
                    activeSearch: '',
                    status: '',
                    categoryId: ctx.query.get('categoryId'),
                    category: false,
                    priceRuleId: ctx.query.get('pricerule'),
                    propertyId: ctx.query.get('property'),
                    values: ctx.query.get('values'),
                    priceRule: false,
                    property: false,
                    categories: [],
                    categoryList: [],
                    filteredCategories: [],
                    categoryFilter: '',
                    filterExclude: false,
                    products: [],
                    rules: [],
                    tags: [],
                    allTags: [],
                    filteredTags: [],
                    tag: '',
                    tagEditList: [],
                    propertyValue: '',
                    propertyEditList: [],
                    ruleEditList: [],
                    categoryEditList: [],
                    checkAll: false,
                    numChecked: 0,
                    sortBy: '',
                    sortDir: 1,
                }
            },
            methods: {
                showModal(view) {
                    this.modalView = view;
                },
                closeModal() {
                    this.modalView = '';
                },
                toggleChecks() {
                    this.products.map(p => p.checked = this.checkAll);
                    this.countChecked();
                },
                toggleCheck(product) {
                    product.checked = !product.checked;
                    this.countChecked();
                },
                toggleCategory(category) {
                    category.expanded = !category.expanded;
                    console.log('toggle', category);
                },
                countChecked() {
                    let cnt = 0;
                    this.products.map(p => cnt += p.checked ? 1 : 0);
                    this.numChecked = cnt;
                },
                formatMoney(p) {
                    return Util.formatMoney(p);
                },
                searchUpdated(e) {
                    if(e.key == 'Enter') 
                        ctx.getProducts();
                },
                searchProducts() {
                    ctx.getProducts();
                },
                statusChanged() {
                    ctx.getProducts();
                },
                selectCategory(category) {
                    this.categoryId = category.id;
                    ctx.getProducts();
                    this.closeModal();
                },
                toggleRule(rule) {
                    const idx = this.ruleEditList.indexOf(rule);
                    if(idx >= 0) this.ruleEditList.splice(idx, 1);
                    else this.ruleEditList.push(rule);
                },
                selectProperty(property) {
                    console.log('property', property);
                    this.propertyEditList = [property];
                },
                toggleCategoryChoice(category) {
                    const idx = this.categoryEditList.indexOf(category);
                    if(idx >= 0) this.categoryEditList.splice(idx, 1);
                    else this.categoryEditList.push(category);
                },
                showRemoveRules() {
                    this.ruleEditList = [];
                    this.showModal('remove-rules')
                },
                updateRules(mode) {
                    if(this.ruleEditList.length > 0) {
                        let params = {
                            mode: mode,
                            rules: this.ruleEditList,
                            products:  ctx.getCheckedIds()
                        };
                    
                        axios.post(`/admin/products`, params).then(function(response) {
                            ctx.vm.closeModal();
                            Util.showAsyncStatus('The rules have been updated');
                        });
                    }
                },
                updateProperties(mode) {
                    if(this.propertyEditList.length > 0) {
                        let params = {
                            mode: mode,
                            property: this.propertyEditList[0].id,
                            value: this.propertyValue,
                            products:  ctx.getCheckedIds()
                        };
                    
                        axios.post(`/admin/products`, params).then(function(response) {
                            ctx.vm.closeModal();
                            Util.showAsyncStatus('The properties have been updated');
                        });
                    }
                },
                showRemoveCategories() {
                    this.categoryEditList = [];
                    this.showModal('remove-categories')
                },
                updateCategories(mode) {
                    if(this.categoryEditList.length > 0) {
                        let params = {
                            mode: mode,
                            categories: this.categoryEditList,
                            products:  ctx.getCheckedIds()
                        };
                    
                        axios.post(`/admin/products`, params).then(function(response) {
                            ctx.vm.closeModal();
                            Util.showAsyncStatus('The categories have been updated');
                        });
                    }
                },
                toggleTag(tag) {
                    const idx = this.tagEditList.indexOf(tag);
                    if(idx >= 0) this.tagEditList.splice(idx, 1);
                    else this.tagEditList.push(tag);
                },
                showRemoveTags() {
                
                    let filtered = [];
                    this.products.map(p => 
                    {
                        if(p.checked) {
                            for(var i in p.tags) {
                                let tag = p.tags[i].name;
                                if(filtered.indexOf(tag) < 0)
                                    filtered.push(tag);
                            }
                        }
                    });

                    this.filteredTags = filtered.sort();
                    this.tagEditList = [];
                    this.showModal('remove-tags')
                },
                removeTags() {
                    console.log('remove', this.tagEditList);
                },
                updateTags(mode) {
                    if(this.tagEditList.length > 0) {
                        let params = {
                            mode: mode,
                            tags: this.tagEditList,
                            products:  ctx.getCheckedIds()
                        };
                    
                        axios.post(`/admin/products`, params).then(function(response) {
                            ctx.vm.closeModal();
                            Util.showAsyncStatus('The tags have been updated');
                        });
                    }
                },
                filterTag(tag) {
                    this.tag = tag;
                    ctx.getProducts();
                    this.closeModal();
                },
                removeTag() {
                    this.tag = '';
                    ctx.getProducts();
                },
                removeCategory() {
                    this.category = false;
                    this.categoryId = '';
                    ctx.getProducts();
                },
                updateCategoryFilter() {
                    let filter = this.categoryFilter.toLowerCase();
                    this.filteredCategories  = this.categoryList.filter(c => {
                        return c.name.toLowerCase().indexOf(filter) >= 0
                    });
                },
                removePriceRule() {
                    this.priceRule = false;
                    this.priceRuleId = '';
                    ctx.getProducts();
                },
                removeProperty() {
                    this.property = false;
                    this.propertyId = '';
                    ctx.getProducts();
                },
                removeValues() {
                    this.values = '';
                    ctx.getProducts();
                },
                availableChanged(product) {
                    product.adjust = product.available - product.originalInventory;
                    ctx.checkChanged();
                },
                adjustChanged(product) {
                    product.available = parseInt(product.adjust) + product.originalInventory;
                    ctx.checkChanged();
                },
                discard() {
                    let vm = this;
                    this.products.map(p => {
                        p.available = p.originalInventory;
                        p.adjust = 0;
                        p.checked = false;
                        vm.countChecked();
                        vm.changed = false;
                    });
                },
                save() {
                    let changes = [];
                    for(var i = 0; i < this.products.length; i++) {
                        let product = this.products[i];
                        if(product.checked) {
                            changes.push({ id: product.id, update: product.available - product.originalInventory });
                        }
                    }
                    let params = {
                        changes: changes
                    };
                    axios.post(`/admin/products`, params).then(function(response) {
                        ctx.vm.changed = false;
                        ctx.getProducts();
                    });
                },
                sortTable(field) {
                    if(field == this.sortBy) 
                        this.sortDir *= -1;

                    this.sortBy = field;

                    // If there no filters then do sort on all data.
                    // Otherwise only sort the loaded data.
                    if(this.search || this.category || this.tag) {
                        let dir = this.sortDir;
                        this.products.sort(function(a, b) {
                            let val = 0;
                            if(field == 'name') val = a.name < b.name ? -1 : 1
                            else if(field == 'sku') val = a.sku < b.sku ? -1 : 1
                            else if(field == 'available') val = a.available < b.available ? -1 : 1
                            return val * dir;
                        });
                    }
                    else {
                        ctx.getProducts();
                    }
                }
            }
        }).mount('#products-page');
    }

    getCheckedIds() {
        const ids = [];
        this.vm.products.map(p => {
            if(p.checked) ids.push(p.id);
        });

        return ids;
    }

    getProducts() {
        var vm = this.vm;
        vm.activeSearch = vm.search;
        
        const params = {
            search: vm.search,
            category: vm.categoryId,
            pricerule: vm.priceRuleId,
            sortBy: vm.sortBy,
            sortDir: vm.sortDir,
            tag: vm.tag,
            property: vm.propertyId,
            exclude: vm.filterExclude ? true : '',
            values: vm.values,
            status: vm.status
        };

        vm.searching = true;
        vm.products = [];
        axios.get('/admin/data/products', { params: params }).then(function (response) {
            vm.products = response.data.products;
            vm.category = response.data.category;
            vm.priceRule = response.data.priceRule;
            vm.property = response.data.property;
            vm.categories = response.data.categories;
            vm.categoryList = response.data.categoryList;
            vm.filteredCategories = response.data.categoryList;
            vm.tags = response.data.tags;
            vm.allTags = response.data.allTags;
            vm.rules = response.data.rules;
            vm.properties = response.data.properties;
            vm.loaded = true;
            vm.searching = false;

            // Save original inventory so we can show it when editing
            // the product inventory;
            vm.products.map(p => {
                p.originalInventory = p.available;
                p.adjust = 0;
                vm.countChecked();
            });
        });
    }

    checkChanged() {
        if(!this.vm.loaded) return;

        for(var i = 0; i < this.vm.products.length; i++) {
            let product = this.vm.products[i];
            if(product.checked && product.originalInventory != product.available) {
                this.vm.changed = true;
                return;
            }
        }
    }
}

if($('#products-page').length > 0) {
    window.Products = new Products;
    window.Products.init();
}