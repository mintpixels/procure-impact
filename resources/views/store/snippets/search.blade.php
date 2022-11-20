
<div id="search-results" v-if="!loading" v-bind:class="{ loading: loading }" v-cloak>
    
    <div class="page-content">

        <div class="breadcrumb">
            Home / ${ mode == 'search' ? q : category.name }
        </div>

        <div id="search-content" :class="{ loading: loading }">

            <h4>
                <span v-if="mode == 'search'">
                    Results for <b>${ q }</b>
                </span>
                <span v-else>
                    ${ category.name }
                </span>
            </h4>
            <p>Discover new products and enjoy free returns on all opening orders.</p>

            <div class="filters">

                <span class="sort-icon">
                    <img src="/img/sort.svg" />

                    <div class="sort-options">
                        <select v-on:change="changeSort($event)" style="border: 0">
                            <option value="best" :selected="sortBy == 'best'">Sort By: Best Match</option>
                            <option value="popular" :selected="sortBy == 'popular'">Sort By: Popular</option>
                            <option value="price_low_to_high" :selected="sortBy == 'price_low_to_high'">Sort By: Price - Low to High</option>
                            <option value="price_high_to_low" :selected="sortBy == 'price_high_to_low'">Sort By: Price - High to Low</option>
                            <option value="age_low_to_high" :selected="sortBy == 'age_low_to_high'">Sort By: Newest Arrivals</option>
                            <option value="age_high_to_low" :selected="sortBy == 'age_high_to_low'">Sort By: Oldest Products</option>
                        </select>
                    </div>
                </span>

                <span class="filter-icon">
                    <img src="/img/filter.svg" />
                </span>

                <span class="filter" v-for="facet in facets">
                    ${ facet.name }
                    <img src="/img/arrow-down.svg" />

                    <div class="filter-options">
                        <div class="filter-option" v-for="option in facet.options">
                            <input type="checkbox" v-on:click="toggleOption(option)" /> ${ option.name }
                        </div>
                    </div>
                            
                </span>

            </div>

            <div class="columns results" v-if="products.length > 0">
                <div class="column result-container" v-for="product in products" :product_id="product.id">
                    <a :href="product.url">
                        <div class="thumbnail">
                            <img v-if="product.thumbnail" :src="product.thumbnail.indexOf('http') == 0 ? product.thumbnail : '{{ env('AWS_CDN_PRODUCTS_PATH') }}' + product.thumbnail" />
                            <img v-if="!product.thumbnail" src="{{ url('img/missing.gif') }}" />
                        </div>
                        <div class="product-details">
                            <div class="name">${ product.name }</div>
                            <div class="brand">by ${ product.brand }</div>
                            <div class="price">
                                ${ formatMoney(product.variants[0].price) }
                            </div>
                        </div>
                        <div class="review-info" v-if="product.review_count > 0">
                            <span class="review-score">
                                <img v-for="n in Math.floor(product.review_score)" src="{{ url('img/rating-full.png') }}" />
                                <img v-if="product.review_score % 1 > .25 && product.review_score % 1 < .75" src="{{ url('img/rating-half.png') }}" />
                            </span>
                            <span class="review-count">(${ product.review_count })</span>
                        </div>
                    </a>

                    <!-- <button class="add-to-cart" v-on:click="addToCart(product.id)" v-if="product.available > 0">Add To Cart</button> -->
                </div>
            </div>

            <div class="pagination" v-if="pages > 1">
                <span class="page-number" v-if="page > 1" v-on:click="changePage(page-1)">«</span>
                <span class="page-number" :class="{ active: page == n }" v-for="n in (pages > page + 4 ? page + 4 : pages )" v-on:click="changePage(n)">${ n }</span>
                <span class="page-number" v-if="page < pages" v-on:click="changePage(page+1)">»</span>
            </div>

            <div class="column left-pane facets" :class="{ active: mobileFilter }" style="display:none">
                <div class="mobile-filter-trigger" v-if="mobileFilter" v-on:click="toggleMobileFilter()">Hide Filters</div>
                <h1>
                    <span v-if="mode == 'search'">
                        Results for ${ q }
                    </span>
                    <span v-else>
                        ${ category.name }
                    </span>
                </h1>

                <div class="facet category">
                    <div class="facet-name" :class="{ collapsed: category.collapsed }" v-on:click="toggleFacet(category)">Categories</div>
                    <div class="facet-options" :class="{ showAll: category.showAll }">
                        <div v-if="mode == 'search' && category.name">‹ <a v-on:click="changeCategory(false)">Any Category</a></div>
                        <div v-for="parent in category.parents">
                            ‹  <a :href="'/categories/' + parent.path" v-if="mode == 'category'">${ parent.name }</a>
                                <a v-on:click="changeCategory(parent)" v-if="mode == 'search'">${ parent.name }</a>
                        </div>
                        <label>${ category.name }</label>
                        <div class="facet-option-list">
                            <div class="facet-option" v-for="option in category.children">
                                <a :href="'/categories/' + option.path" v-if="mode == 'category'">${ option.name }</a>
                                <a v-on:click="changeCategory(option)" v-if="mode == 'search'">${ option.name }</a>
                                <span class="facet-count" v-if="mode == 'search'">(${ option.total })</span>
                            </div>
                        </div>

                        <div class="show-more" v-if="category.children && category.children.length > 5" v-on:click="toggleShowAll(category)">
                            <span v-if="!category.showAll">+ Show more</span>
                            <span v-if="category.showAll">- Show less</span>
                        </div>
                    </div>
                </div>

                <div class="facet" v-for="facet in facets">
                    <div class="facet-name" :class="{ collapsed: facet.collapsed }" v-on:click="toggleFacet(facet)">${ facet.display_name }</div>
                    <div class="facet-options" :class="{ showAll: facet.showAll }">
                        <div class="facet-option-list">
                            <div class="facet-option" v-for="option in facet.options" v-on:click="toggleOption(option)" :class="{ chosen: option.chosen }">
                                <span class="facet-check"></span>
                                ${ option.name }
                                <span class="facet-count">(${ option.products })</span>
                            </div>
                        </div>

                        <div class="show-more" v-if="facet.options.length > 5" v-on:click="toggleShowAll(facet)">
                            <span v-if="!facet.showAll">+ Show more</span>
                            <span v-if="facet.showAll">- Show less</span>
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="column right-pane" style="display:none">
                <div class="summary">
                    <span v-if="!loading">
                        <span class="result-count">${ total }</span>
                        ${ total == 1 ? 'Result' : 'Results' }
                        <span v-if="mode == 'search'">for <b><i>${ q }</i></b></span>
                    </span>
                    <span v-if="loading">Loading Results...</span>

                    <span class="chosen-options">
                        <span class="chosen-option" v-for="option in chosenOptions">
                            ${ option } <span class="remove-option" v-on:click="removeOption(option)">x</span>
                        </span> 
                    </span>
                    
                    <span class="sort-by" v-if="!loading">
                        <select v-on:change="changeSort($event)">
                            <option value="best" :selected="sortBy == 'best'">Sort By: Best Match</option>
                            <option value="popular" :selected="sortBy == 'popular'">Sort By: Popular</option>
                            <option value="price_low_to_high" :selected="sortBy == 'price_low_to_high'">Sort By: Price - Low to High</option>
                            <option value="price_high_to_low" :selected="sortBy == 'price_high_to_low'">Sort By: Price - High to Low</option>
                            <option value="age_low_to_high" :selected="sortBy == 'age_low_to_high'">Sort By: Newest Arrivals</option>
                            <option value="age_high_to_low" :selected="sortBy == 'age_high_to_low'">Sort By: Oldest Products</option>
                        </select>
                    </span>

                    <span class="page-size" v-if="!loading">
                        <select v-on:change="changePageSize" v-model="pageSize">
                            <option value="12">Per Page: 12</option>
                            <option value="24">Per Page: 24</option>
                            <option value="36">Per Page: 36</option>
                            <option value="48">Per Page: 48</option>
                            <option value="96">Per Page: 96</option>
                        </select>
                    </span>

                    <div style="clear:both"></div>

                    <div class="mobile-filter-trigger" v-if="!mobileFilter" v-on:click="toggleMobileFilter()">Refine Search</div>
                </div>
                <div class="columns results" v-if="products.length > 0">
                    <div class="column result-container" v-for="product in products" :product_id="product.id">
                        <div v-if="product.available <= 0" class="sold-out-container">
                            <div class="sold-out">SOLD OUT</div>
                        </div>
                        <a :href="product.url">
                            <div class="thumbnail">
                                <img v-if="product.thumbnail" :src="product.thumbnail.indexOf('http') == 0 ? product.thumbnail : '{{ env('AWS_CDN_PRODUCTS_PATH') }}' + product.thumbnail" />
                                <img v-if="!product.thumbnail" src="{{ url('img/missing.gif') }}" />
                            </div>
                            <div class="name">${ product.name }</div>
                            <div class="price">
                                <!-- <span v-if="product.prices && product.prices.msrp > product.price" class="onsale">${ formatMoney(product.prices.msrp) }</span> -->
                                <!-- <div v-if="product.price_in_cart">
                                    Add item to cart to see price.
                                </div>
                                <div v-else>
                                    <span v-if="parseFloat(product.prices.sale) && parseFloat(product.prices.sale) < product.price">
                                        <span class="sale-price">SALE: ${ formatMoney(product.prices.sale) }</span>
                                        <span class="was">${ formatMoney(product.price) }</span>
                                    </span>
                                    <span v-else>
                                        ${ formatMoney(product.price) }
                                    </span>
                                </div> -->
                            </div>
                            <div class="review-info" v-if="product.review_count > 0">
                                <span class="review-score">
                                    <img v-for="n in Math.floor(product.review_score)" src="{{ url('img/rating-full.png') }}" />
                                    <img v-if="product.review_score % 1 > .25 && product.review_score % 1 < .75" src="{{ url('img/rating-half.png') }}" />
                                </span>
                                <span class="review-count">(${ product.review_count })</span>
                            </div>
                            <div class="restricted-note" v-if="product.restrictions && product.restrictions.length > 0">
                                Shipping restrictions may apply <i class="far fa-question-circle"></i>
                                <ul class="rules">
                                    <li class="rule" v-for="restriction in product.restrictions">${ restriction }</li>
                                </ul>
                            </div>
                        </a>

                        <button class="add-to-cart" v-on:click="addToCart(product.id)" v-if="product.available > 0">Add To Cart</button>
                        <button class="add-to-cart" v-on:click="addToCart(product.id)" v-else disabled>Out of Stock</button>
                    </div>
                </div>

                <div class="pagination" v-if="pages > 1">
                    <span class="page-number" v-if="page > 1" v-on:click="changePage(page-1)">«</span>
                    <span class="page-number" :class="{ active: page == n }" v-for="n in (pages > page + 4 ? page + 4 : pages )" v-on:click="changePage(n)">${ n }</span>
                    <span class="page-number" v-if="page < pages" v-on:click="changePage(page+1)">»</span>
                </div>
            </div>
        </div>
    </div>
</div>