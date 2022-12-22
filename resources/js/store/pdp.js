const { default: axios } = require("axios");

const page = '#pdp';

class PDP {

    init() {
        if($(page).length == 0) return;

        this.id = Util.getProperty('data-product-id');

        this.initVue();
        this.getProduct();
        this.getReviews();
        this.bindEvents();

        // Check if we've come here from a review email.
        if(window.location.hash.indexOf('email') > 0) {
            let ctx = this;
            setTimeout(function() {
                var fields = window.location.hash.substr(1).split('&');
                var email = '', name = '';
                for(var i = 0; i < fields.length; i++) {
                    if(fields[i].indexOf('email') >= 0) 
                        ctx.vm.addReview.email = fields[i].substr(6).replace('%40', '@');
                    else if(fields[i].indexOf('name') >= 0)
                        ctx.vm.addReview.name = fields[i].substr(5);
                }
                ctx.vm.addingReview = true;
                window.location.hash = '';
                $('html, body').animate({
                    scrollTop: $('.review-container').offset().top - 200,
                }, 500);
            }, 1000);
        }

        // Check if we've come here from a submitted review
        if(window.location.hash.indexOf('reviewsubmitted') > 0) {
            setTimeout(function() {
                window.reviews.reviewSubmitted = true;
                window.location.hash = '';
                $('html, body').animate({
                    scrollTop: $('.review-container').offset().top - 200,
                }, 500);
            }, 1000);
        }
    }

    initVue() {
        let root = this;
        let ctx = this;
        this.vm = Vue.createApp({
            delimiters: ['${', '}'],
            data() {
                return {
                    loaded: false,
                    brand: false,
                    product: { price: 0, properties: [], prices: [], tags: [], brand: {} },
                    variant: {},
                    quantity: 1,
                    reviews: [],
                    questions: [],
                    brand_products: [],
                    related: [],
                    shipZip: '',
                    shipRules: [],
                    restrictions: [],
                    shipMessage: '',
                    notifyEmail: '',
                    activeImage: '',
                    tab: 'description',
                    defaults: {
                        properties: []
                    },
                    error: '',
                    page: 0,
                    pageCount: 1,
                    sortBy: 'popular',
                    total: 0,
                    ratingFilter: '',
                    imagesFilter: '',
                    searchFilter: '',
                    questions:[],
                    showReviews: true,
                    showQuestions: false,
                    addingReview:false,
                    addReview: {
                        name: '',
                        email: '',
                        title: '',
                        content: '',
                        score: ''
                    },
                    reviewSubmitted: false,
                    submitError: false,
                    addingQuestion:false,
                    addQuestion: {
                        name: '',
                        email: '',
                        question: ''
                    },
                    questionSubmitted: false,
                    modalView: '',
                    wishlists: [],
                    wishlistName: '',
                    wishlistId: '',
                    wishlistMessage: '',
                    wishlistAvailable: false
                }
            },
            methods: {
                emptyValue(val) {
                    if(!val) return true;
                    if(val.trim().length == 0) return true;
                    let stripped = val.replace(/(<([^>]+)>)/gi, "");
                    return stripped.length == 0;
                },
                setActiveImage(image, i) {
                    this.activeImage = image;
                    this.activeImageIndex = i;
                },
                zoomImage() {
                    $('.fancybox a[data-index=' + this.activeImageIndex + ']')[0].click();
                },
                showTab(t) {
                    this.tab = t;
                },
                getImagePath(path) {
                    return `https://images.takeshape.io/${path}`;
                },
                addToCart(product, quantity) {
                    
                    let variants = [];
                    product.variants.map(v => {
                        this.error = '';
                        if(v.quantity > 0) {
                            variants.push({
                                id: product.id, 
                                variantId: v.id,
                                quantity: v.quantity
                            });

                            v.quantity = 0;
                        }
                    })

                    if(variants.length > 0) {
                        Cart.addVariants(variants, function(error) {
                            ctx.vm.error = error.error;
                        });
                    }
                    // Does this item have options?
                    // let options = [];
                    // $('.pdp-options .option-value').each(function() {
                    //     options.push({
                    //         name: $(this).attr('name'),
                    //         value: $(this).val()
                    //     });
                    // });

                    // this.error = '';
                    // Cart.addItemWithOptions(product.id, quantity, options, function(error) {
                    //     ctx.vm.error = error.error;
                    // });
                },
                youtubeThumbnail(code) {
                    return `https://img.youtube.com/vi/${code}/hqdefault.jpg`;
                },
                youtubeUrl(code) {
                    return `https://www.youtube.com/embed/${code}`;
                },
                checkShip(zip) {
                    root.canShip(zip, root.id);
                },
                formatMoney(price) {
                    return Util.formatMoney(price);
                },
                getFullStars: function(avg) {
                    return Math.floor(avg);
                },
                getHalfStars: function(avg) {
                    var diff = parseFloat(avg) - this.getFullStars(avg);
                    if(diff > .25 && diff < .9)
                        return 1;

                    return 0;
                },
                getEmptyStars: function(avg) {
                    return 5 - this.getFullStars(avg) - this.getHalfStars(avg);
                },
                notifyMe: function() {
                    if(this.notifyEmail) {
                        root.notifyMe(this.notifyEmail, this.product.id, this.product.sku);
                    } 
                },
                showPage: function(page) {
                    this.page = page;
                    root.getReviews(this.page, '', '', '', '', function() {

                        // Move back to the top of the reviews.
                        var position = $("#reviews").offset().top - 160;
                        $("html, body").animate({
                            scrollTop: position
                        }, 500);
                    });
                },
                qtyChanged() {
                    ctx.setQuantityPrice();
                },
                viewImages: function(review, index) {
                    window.reviewImages.images = review.images;
                    window.reviewImages.image = review.images[index];
                    window.reviewImages.active = index;
                    window.reviewImages.show = true;
                },
                updateFilter: function() {
                    this.page = 0;
                    root.getReviews(this.page, this.sortBy, this.ratingFilter, this.imagesFilter, this.searchFilter);
                },
                submitReview: function() {
                    if(this.addReview.name && this.addReview.email && this.addReview.content && this.addReview.score) {
                        root.addReview(
                            ctx.id,
                            this.addReview.name,
                            this.addReview.email,
                            this.addReview.title,
                            this.addReview.content,
                            this.addReview.score
                        );

                        // Reset the form.
                        this.addingReview = false;
                        this.addReview.name = '';
                        this.addReview.email = '';
                        this.addReview.title = '';
                        this.addReview.content = '';
                        this.addReview.score = '';
                        this.submitError = false;
                        this.reviewSubmitted = true;
                    }
                    else {
                        this.submitError = true;
                    }
                },
                submitQuestion: function() {
                    if(this.addQuestion.name && this.addQuestion.email && this.addQuestion.question) {
                        root.addQuestion(
                            this.productId,
                            this.addQuestion.name,
                            this.addQuestion.email,
                            this.addQuestion.question
                        );

                        // Reset the form.
                        this.addingQuestion= false;
                        this.addQuestion.name = '';
                        this.addQuestion.email = '';
                        this.addQuestion.question = '';
                        this.submitError = false;
                        this.questionSubmitted = true;
                    }
                    else {
                        this.submitError = true;
                    }
                },
                getShortDesc(desc) {
                    // return desc;
                    if(!desc) return '';

                    return desc.substring(0, 200) + '...';
                },
                formatDate: function(date) {
                    return Util.formatDate(date);
                },
                showFFL: function() {
                    Cart.showDealers();
                },
                closeModal() {
                    this.modalView = '';
                },
                addWishlistItem() {
                    if(this.wishlistName) {
                        let vm = this;
                        let params = {
                            name: this.wishlistName,
                            id: this.wishlistId,
                            product: ctx.id
                        };

                        axios.post('/products/wishlistitems', params).then(function(response) {
                            vm.wishlists = response.data.wishlists;
                            vm.closeModal();
                            vm.wishlistMessage = "This product has been added to your wishlist.";
                        });
                    }
                },
                showAddToWishlist(product) {
                    this.wishlistName = 'My Wishlist';
                    this.modalView = 'add-wishlist-item';
                },
            }
        }).mount(page);
    }

    getProduct() {
        let vm = this.vm;
        let ctx = this;
        axios.get(`/data/products/${this.id}`).then(function (response) {
            vm.product = response.data.product;
            vm.variant = vm.product.variants[0];
            vm.brand_products = response.data.brand_products;
            vm.related = response.data.related;
            vm.loaded = true;

            vm.product.variants.map(v => {
                v.quantity = 0
            });

            if(vm.product.images.length > 0) {
                vm.activeImage = vm.product.images[0];
                vm.activeImageIndex = 0;
            }

            ctx.loadBrand();
        });
    }

    loadBrand() {
        const vm = this.vm;
        axios.get(`/data/brands/${vm.product.brand.handle}`).then(function (response) {
            vm.brand = response.data;
        });
    }

    setQuantityPrice() {
        this.vm.product.lowest_price = this.vm.product.original_lowest_price
        for(var qty in this.vm.product.qty_prices) {
            var price = this.vm.product.qty_prices[qty];
            if(qty <= this.vm.quantity && price < this.vm.product.lowest_price) {
                this.vm.product.lowest_price = price;
            }
        }
    }

    addReview(productId, name, email, title, content, rating) {

        var params = {
            product_id: productId,
            name: name,
            email: email,
            title: title,
            content: content,
            rating: rating
        };

        $.post(`/products/${this.id}/reviews`, params, function(response) {});
    }

    getReviews(page, sortBy, rating, images, search, callback) {

        var params = {
            product_id: this.id,
            page: page ? page : 0,
            sort: sortBy ? sortBy : 'popular',
            rating: rating,
            images: images,
            search: search
        };

        let self = this;
        axios.get(`/data/products/${this.id}/reviews`, { params: params }).then(function (response) {
            self.vm.reviews = response.data.reviews;
            self.vm.questions = response.data.questions;
            self.vm.total = response.data.total;
            self.vm.pageCount = Math.floor(response.data.filteredTotal / 5 + 1);

            if(response.data.total % 5 == 0)
                self.vm.pageCount--;

            if(callback) {
                callback();
            }
        });
    }

    canShip(zip, product) {
        this.vm.shipMessage = '';
        this.vm.shipRules = [];
        this.vm.restrictions = [];
        let vm = this.vm;
        if(zip.length == 5) {
            $.get('/api/shippable', { zip: zip, products: [product] }, function(response) {
                var message = 'Yes! This item can be shipped to ' + zip + '.';
                if(response.shippable != 'Yes') {
                    message = "The following shipping restrictions apply for this product to " + zip + ':';
                    vm.shipRules = response.rules;
                }

                vm.shipMessage = message;
            });
        }
    }

    notifyMe(email, product, sku, callback) {
        $.post('/api/notify', { email: email, product: product, sku: sku }, function(response) {
            $('.stock-notify').hide();
            $('.notify-status').fadeIn();
        }, 'json');
    }

    bindEvents() {
        $('body').on('click', '.review-summary', function() {
            var position = $("#reviews").offset().top - 160;
            $("html, body").animate({
                scrollTop: position
            }, 500);
        })
    }
}

window.pdp = new PDP;
window.pdp.init();