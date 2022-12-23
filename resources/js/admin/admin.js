window.Vue = require('vue');

require('../bootstrap');
require('../util');
require('./product');
require('./products');
require('./categories');
require('./properties');
require('./order');
require('./orders');
require('./customer');
require('./customers');
require('./transactions');
require('./settings');

$('body').on('click', '.toggle-changes', function() {
    $(this).closest('.timeline-item').toggleClass('show-changes');
});
