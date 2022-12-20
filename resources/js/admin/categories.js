const { default: axios } = require("axios");
import nestedDraggable from "./components/categories.vue";
import draggable from "vuedraggable";

class Categories {

    init() {
        this.initVue();
        this.getCategories();
    }

    initVue() {
        const ctx = this;
        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            components: {
                draggable,
                nestedDraggable
            },
            data() {
                return {
                    changed: false,
                    error: '',
                    categories: [],
                    properties: [],
                    saved: [],
                    filter: '',
                    filterText: '',
                    modalView: '',
                    activeParent: false,
                    activeCategory: false,
                    editCategory: false,
                    activeProperty: false,
                    filters: []
                }
            },
            methods: {
                addCategory(parent) {
                    const category = {
                        id: '',
                        name: 'New Category',
                        handle: '',
                        products: 0,
                        nested: 0,
                        visible: false,
                        filterMatch: true,
                        children: [],
                        propertyValues: [],
                        properties: []
                    }

                    if(parent == null) {
                        this.activeParent = false;
                    }
                    else {
                        this.activeParent = parent;
                        parent.expanded = true;
                    }

                    this.updateCategory(category);
                },
                updateCategory(category) {
                    this.activeCategory = category;
                    this.editCategory = Util.clone(category);
                    this.showModal('update-category');
                },
                showDeleteCategory(category) {
                    this.activeCategory = category;
                    this.showModal('delete-category');
                },
                deleteCategory() {
                    this.activeCategory.deleted = true;
                    this.closeModal();
                    this.checkChanged();
                },
                showProperties(category) {
                    console.log('sp', category);
                    this.activeCategory = category;
                    if(this.properties.length > 0)  {
                        this.activeProperty = this.properties[0];
                    
                        this.properties.map(p => {
                            p.selected = false;
                            category.properties.map(prop => {
                                if(prop.property_id == p.id) {
                                    p.selected = true;
                                }
                            })

                            p.values.map(v => {
                                category.propertyValues.map(value => {
                                    if(value.value_id == v.id) {
                                        v.selected = true;
                                    }
                                });
                            })
                        });

                        this.showModal('show-properties');
                    }
                },
                saveProperties() {

                    let properties = [];
                    let values = [];

                    this.properties.map(p => {
                        
                        p.values.map(v => {
                            if(v.selected) {
                                values.push({
                                    property_id: p.id,
                                    value_id: v.id
                                });
                            }
                            v.selected = false;
                        })
                    });

                    this.activeCategory.propertyValues = values;
                    ctx.checkChanged();
                    this.closeModal();
                },
                selectedValues(property) {
                    let values = [];
                    for(var i = 0; i < property.values.length; i++) {
                        if(property.values[i].selected)
                            values.push(property.values[i]);
                    }

                    return values;
                },
                togglePropertyValue(value) {

                },
                selectedProperty() {

                },
                showFilters(category) {
                    let vm = this;
                    axios.get(`/admin/data/categories/${category.id}/filters`).then(function (response) {
                        vm.activeCategory = category;
                        vm.filters.map(filter => {
                            filter.included = false;
                            filter.position = 1000;
                            response.data.filters.map(f => {
                                if(f.property_id == filter.id) {
                                    filter.included = true;
                                    filter.position = f.position;
                                }
                            })
                        });

                        vm.filters.sort((a, b) => {
                            return a.position < b.position ? -1 : 1;
                        });

                        vm.showModal('show-filters');
                    });
                },
                saveFilters() {
                    let included = this.filters.filter(f => f.included);
                    included.map((f, i) => f.position = i);
                    ctx.saveFilters(this.activeCategory, included);
                    this.closeModal();
                },
                saveCategory() {
                    this.activeCategory.name = this.editCategory.name;
                    this.activeCategory.handle = this.editCategory.handle;

                    // If it's a new category then add it to tree.
                    if(!this.activeCategory.id) {
                        if(this.activeParent)
                            this.activeParent.children.push(this.activeCategory);
                        else 
                            this.categories.unshift(this.activeCategory);
                    }

                    this.closeModal();
                    this.checkChanged();
                },
                showModal(view) {
                    this.modalView = view;
                },
                closeModal() {
                    this.modalView = '';
                },
                filterUpdated() {
                    let f = this.filter.trim();
                    ctx.filterCategories(this.categories, f);
                },
                toggleCategory(category) {
                    category.expanded = !category.expanded;
                    console.log('toggle', category);
                },
                toggleVisible(category) {
                    console.log('tv', category);
                    category.visible = !category.visible;
                    ctx.checkChanged();
                },
                filterMatch(category) {
                    return this.filter.length == 0 || category.filterMatch;
                },
                expanded(category) {
                    return this.filter.length > 0 || category.expanded;
                },
                filtersSorted() {
                    ctx.checkChanged();
                },
                checkChanged() {
                    ctx.checkChanged();
                },
                save() {
                    ctx.saveCategories();
                },
                discard() {
                    this.categories = Util.clone(this.saved);
                    this.changed = false;
                    this.$forceUpdate();
                },
            }
        }).mount('#categories-page');
    }

    filterCategories(categories, filter) {

        filter = filter.toLowerCase();
        for(var i = 0; i < categories.length; i++) {
            const category = categories[i];
            
            let childMatches = 0;
            this.filterCategories(category.children, filter);
            for(var j = 0; j < category.children.length; j++) {
                if(category.children[j].filterMatch)
                    childMatches++;
            }
            
            category.filterMatch = false;
            if(filter == '' || childMatches > 0 || category.name.toLowerCase().indexOf(filter) >= 0) {
                category.filterMatch = true;
            }
        }
    }

    getCategories() {
        var vm = this.vm;
        var ctx = this;
        axios.get('/admin/data/categories').then(function (response) {
            vm.categories = response.data.categories;
            vm.properties = response.data.properties;

            vm.properties.map(p => {
                p.selected = false;
            });
            
            // let filters = [];
            // response.data.properties.map(p => {
            //     filters.push({
            //         id: p.id,
            //         name: p.name,
            //         position: 10000,
            //         included: false
            //     });
            // });
            // vm.filters =  Util.clone(filters);
            

            ctx.filterCategories(vm.categories, '');
            vm.saved = Util.clone(vm.categories);
            vm.loaded = true;
        });
        
    }

    getParams(categories) {
        let params = Util.clone({
            categories: categories
        });
        
        return params;
    }

    saveFilters(category, included) {
        const ctx = this;
        axios.post(`/admin/categories/${category.id}/filters`, { filters: included }).then(function (response) {
          
        });
    }

    saveCategories() {
        const ctx = this;
        axios.post('/admin/categories', { categories: this.vm.categories }).then(function (response) {
            ctx.vm.categories = response.data.categories;
            ctx.filterCategories(ctx.vm.categories, '');
            ctx.vm.saved = Util.clone(ctx.vm.categories);
            ctx.vm.changed = false;
        });
    }

    checkChanged() {
        if(!this.vm.loaded) return;

        console.log(this.vm.saved);
        console.log(this.vm.categories);

        this.vm.changed = Util.checkChanged(
            this.getParams(this.vm.saved),
            this.getParams(this.vm.categories),
            ['expanded']
        );
    }
}

if($('#categories-page').length > 0) {
    window.Categories = new Categories;
    window.Categories.init();
}
