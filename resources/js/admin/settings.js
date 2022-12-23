const { default: axios } = require("axios");

class Settings {

    init() {
        this.fields = [];

        this.initVue();
        this.initWysiwg();
        
    }

    initVue() {
        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    tab: '',
                }
            },
            methods: {
                showTab(t) {
                    this.tab = t;
                },
            }
        }).mount('#settings-page');
    }

    initWysiwg() {
        
        let ctx = this;
        $('.content.wysiwyg').each(function() {
            let name = $(this).attr('id');
            let id = '#' + name;
            let q = new Quill(id, { theme: 'snow' });
            
            q.on('text-change', function() {
                let $input = $('[name="' + name + '"]');
                $input.val($(id + ' [contenteditable]').html());
            });

            ctx.fields.push(q);
        });
    }
}

if($('#settings-page').length > 0) {
    window.Settings = new Settings;
    window.Settings.init();
}