const { default: axios } = require("axios");

class Properties {

    init() {
        this.initVue();
        this.getProperties();

    }

    initVue() {
        const ctx = this;
        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    modalView: '',
                    loaded: false,
                    changed: false,
                    saved: [],
                    searching: false,
                    search: '',
                    checkAll: false,
                    numChecked: 0,
                    error: '',
                    activeProperty: false,
                    editProperty: false,
                    properties: [],
                    values: [],
                    valueList: [],
                    newValue: ''
                }
            },
            methods: {
                addValue() {
                    if(this.valueList.indexOf(this.newValue) < 0) {
                        this.valueList.push(this.newValue);
                    }
                    this.newValue = '';
                },
                removeValue(value) {
                    this.valueList = this.valueList.filter(v => v != value);
                },
                saveValues() {
                    ctx.saveValues(this.activeProperty, this.valueList);
                    this.closeModal();
                },
                showValues(property) {
                    this.valueList = property.values.map(v => v.value);
                    this.activeProperty = property;
                    this.showModal('property-values');
                },
                searchUpdated(e) {
                    if(e.key == 'Enter') 
                        ctx.getProperties();
                },
                searchProperties() {
                    ctx.getProperties();
                },
                toggleValue(value) {
                    const idx = this.valueList.indexOf(value);
                    if(idx >= 0) this.valueList.splice(idx, 1);
                    else this.valueList.push(value);
                },
                addProperty() {
                    const property = {
                        id: '',
                        name: 'New Property',
                        products_count: 0,
                        pdp: 0,
                        filter: 0,
                        values: []
                    }

                    this.updateProperty(property);
                },
                updateProperty(property) {
                    this.activeProperty = property;
                    this.editProperty = Util.clone(property);
                    this.showModal('update-property');
                },
                saveProperty()
                {
                    this.activeProperty.name = this.editProperty.name;
                    
                    if(!this.activeProperty.id) {
                        this.properties.unshift(this.activeProperty);
                    }

                    this.closeModal();
                    this.checkChanged();
                },
                deleteProperty(p)
                {
                    p.deleted = true;
                    ctx.checkChanged();
                },
                togglePdp(p) {
                    p.pdp = !p.pdp;
                    ctx.checkChanged();
                },
                toggleFilter(p) {
                    p.filter = !p.filter;
                    ctx.checkChanged();
                },
                toggleChecks() {
                    this.properties.map(p => p.checked = this.checkAll);
                    this.countChecked();
                },
                toggleCheck(property) {
                    property.checked = !property.checked;
                    this.countChecked();
                },
                countChecked() {
                    let cnt = 0;
                    this.properties.map(p => cnt += p.checked ? 1 : 0);
                    this.numChecked = cnt;
                },
                viewProducts() {
                    let url = `/admin/products?property=${this.activeProperty.id}&values=${encodeURIComponent(this.valueList.join('__'))}`;
                    window.location.href = url;
                },
                showModal(view) {
                    this.modalView = view;
                },
                closeModal() {
                    this.modalView = '';
                },
                checkChanged() {
                    ctx.checkChanged();
                },
                save() {
                    ctx.saveProperties();
                },
                discard() {
                    this.properties = Util.clone(this.saved);
                    this.changed = false;
                },
            }
        }).mount('#properties-page');
    }

    getCheckedIds() {
        const ids = [];
        this.vm.products.map(p => {
            if(p.checked) ids.push(p.id);
        });

        return ids;
    }

    getProperties() {
        var vm = this.vm;
        
        const params = {
            search: vm.search
        };

        vm.searching = true;
        vm.products = [];
        axios.get('/admin/data/properties', { params: params }).then(function (response) {
            vm.properties = response.data.properties;
            vm.values = response.data.values;
            vm.loaded = true;
            vm.searching = false;
            vm.saved = Util.clone(vm.properties);
            vm.changed = false;
        });
    }

    getParams(properties) {
        let params = Util.clone({
            properties: properties
        });
        
        return params;
    }

    saveProperties() {
        const ctx = this;
        axios.post('/admin/properties', { properties: this.vm.properties }).then(function (response) {
            ctx.getProperties();
        });
    }

    saveValues(property, values) {
        const ctx = this;
        axios.post(`/admin/properties/${property.id}/values`, { values: values }).then(function (response) {
            property.values = response.data.property.values;
        });
    }

    checkChanged() {
        if(!this.vm.loaded) return;

        this.vm.changed = Util.checkChanged(
            this.getParams(this.vm.saved),
            this.getParams(this.vm.properties)
        );
    }
}

if($('#properties-page').length > 0) {
    window.Properties = new Properties;
    window.Properties.init();
}