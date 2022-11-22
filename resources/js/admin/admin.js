require('../bootstrap');
require('../util');
require('./product');
require('./products');
require('./order');
require('./orders');
require('./customer');
require('./customers');
require('./categories');

$('body').on('click', '.toggle-changes', function() {
    $(this).closest('.timeline-item').toggleClass('show-changes');
});
