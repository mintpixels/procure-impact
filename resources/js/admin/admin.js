window.Vue = require('vue');

require('../bootstrap');
require('../util');
require('./product');
require('./products');
require('./categories');
require('./order');
require('./orders');
require('./customer');
require('./customers');

$('body').on('click', '.toggle-changes', function() {
    $(this).closest('.timeline-item').toggleClass('show-changes');
});
