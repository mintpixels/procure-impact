@extends('store.layout')

@section('head')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />

@stop

@section('content')

<h1 style="display:none">{{ $product->name }}</h1>
<div style="display:none">{{ $product->description }}</div>

<div id="pdp" data-product-id="{{ $product->id }}">
	
    <div class="product-container" :class="{ show: loaded }">

		<div class="breadcrumb">
			${ product.brand.name } / ${ product.name }
		</div>

        <div class="product-info columns">

            <div class="product-media column">

                <div class="image-view" v-if="product.images && product.images.length > 0">
					<img :src="activeImage" v-on:click="zoomImage()"/>
                </div>
                <div class="image-view" v-else>
                    <img src="{{ asset('img/no-image.jpg') }}" />
                </div>

                <div class="image-thumbnails" v-if="product.images && product.images.length > 0">
                    <div class="thumbs">
                        <span class="thumb" v-for="(image, i) in product.images" v-on:click="setActiveImage(image, i)" :class="{ active: activeImageIndex == i }">
							<img :src="image" />
                        </span>
                    </div>
                </div>

            </div>

            <div class="fancybox">
				<template v-for="(image, i) in product.images">
					<a data-fancybox="gallery" v-if="image.x1200" :data-index="i" :href="image" >
						<img :src="image" />
					</a>
				</template>
            </div>

            <div class="product-details column">

				<div class="properties">
					<span v-for="prop in product.properties">
						${ prop.value}
					</span>
				</div>
				
                <h3>${ product.name }</h3>

				<div class="product-price">
					${ formatMoney(variant.wholesale_price) } WSP /
					${ formatMoney(variant.price) } Retail
				</div>

				<div class="location" v-if="product.brand.location">
					Ships from ${ product.brand.location }
				</div>

				<div class="product-description" v-html="product.short_desc"></div>

				<div class="seller-info" v-if="brand">
					<div class="seller-logo">
						<img :src="getImagePath(brand.logo.path)" />
					</div>
					<div class="info">
						<div class="seller-name" style="margin-bottom:10px"><b>${ brand.merchantName }</b></div>
						<div class="seller-desc">
							${ brand.missionStatement }
						</div>
					</div>
				</div>

                <!-- <div class="review-summary" v-if="product.review_score">
                    <span class="review-stars">
                        <i class="fas fa-star" v-for="(n, index) in getFullStars(product.review_score)" ></i>
                        <i class="fas fa-star-half-alt" v-for="(n, index) in getHalfStars(product.review_score)"></i>
                        <i class="far fa-star" v-for="(n, index) in getEmptyStars(product.review_score)"></i>
                    </span>
                    (${ product.review_count } ${ product.review_count == 1 ? 'Review' : 'Reviews' })
                </div> -->

                <!-- <div class="product-field" v-if="product.sku">
                    <label>Item #:</label>
                    ${ product.sku }
                </div> -->

                <!-- <div class="product-field" v-if="product.brand">
                    <label>Brand:</label>
                    <a :href="'/search?qs=' + product.brand.name">${ product.brand.name }</a>
                </div> -->

                <!-- <div class="product-field price" v-if="product.prices.msrp > product.price">
                    <label>MSRP:</label>
                    <span class="was">${ formatMoney(product.prices.msrp) }</span>
                </div> -->

				<!-- <div v-if="product.qty_prices">
					<template v-for="(price, qty) in product.qty_prices">
						<div v-if="qty > 1">Buy ${ qty } at ${ formatMoney(price) }</div>
					</template>
				</div> -->

                <!-- <div class="product-price">
					<div class="was" v-if="product.lowest_price < product.price">${ formatMoney(variant.price) }</div>
                    <span class="sale-price" v-if="product.lowest_price < product.price">Sale Price:</span> ${ formatMoney(product.lowest_price) } 
                </div> -->

                <!-- <div class="amount-saved" v-if="product.prices.price > product.price">
                    (You saved ${ formatMoney(product.prices.msrp - product.price) })
                </div> -->

				<!-- <div class="pdp-options" v-if="product.options && product.options.length > 0">
					<div v-for="option in product.options" class="option">
						<h5>${ option.name }</h5>
						<select :name="option.name" class="option-value">
							<option v-for="value in option.values" :value="value.trim()">${ value.trim() }</option>
						</select>
					</div>
				</div> -->

                <!-- <div class="product-quantity">

                    <div class="quantity column">
                        <input v-model="quantity" type="number" :min="1" v-on:change="qtyChanged" />
                    </div>
				</div> -->

				<div class="variants">
					<div class="variant" v-for="v in product.variants">
						<span class="image" v-on:click="setActiveImage(product.images[v.image], v.image)">
							<img v-if="v.image != NULL" :src="product.images[v.image]" />
							<img v-else src="/img/no-image.jpg" />
						</span>
						<span>
							<span v-if="v.name">${ v.name }</span>
							<span v-else>${ product.name }</span>
							<div v-if="v.case_quantity > 1" style="color:#888;font-size:12px">Sold in quantities of ${v.case_quantity}</div>
						</span>
						<span class="text-right">
							<div class="quantity">
								<img src="/img/minus.svg" v-on:click="v.quantity = v.quantity == 0 ? 0 : v.quantity - 1"  />
								<input min="0" v-model="v.quantity" type="number" v-on:change="qtyChanged(v)" />
								<img src="/img/plus.svg" v-on:click="v.quantity++" />
							</div>
							<div style="padding:10px;color: #888;font-weight:600" v-if="v.case_quantity > 1">
								${ v.case_quantity * v.quantity } Total Units
							</div>
						</span>
					</div>
				
				</div>
				<div class="column">
					<button v-on:click="addToCart(product)">Add To Purchase Order</button>

					<div class="min-order" v-if="product.brand.order_min > 0">
						Minimum total purchase from ${ product.brand.name } is ${ formatMoney(product.brand.order_min) }.
					</div>
				</div>
                
				<div v-if="error" class="error">
					${ error }
				</div>



            </div>

        </div>

	</div>

	<div class="tab-container padded-content">
		<div class="tabs">
			<span :class="{ active: tab == 'description' }" v-on:click="tab ='description';" v-if="!emptyValue(product.description)">Description</span>
			<span :class="{ active: tab == 'specs' }" v-on:click="tab = 'specs'" v-if="product.specs">Specifications</span>
			<span :class="{ active: tab == 'other' }" v-on:click="tab ='other'" v-if="product.other">Other</span>
		</div>
		<div class="tab-contents">
			<div class="tab-content" :class="{ show: tab == 'description' }" v-if="product.description">
				<div v-html="product.description"></div>
			</div>
			<div class="tab-content" :class="{ show: tab == 'specs' }" v-if="product.specs">
				<div v-html="product.specs"></div>
			</div>
			<div class="tab-content" :class="{ show: tab == 'other' }" v-if="product.other">
				<div v-html="product.other"></div>
			</div>
		</div>
	</div>

	<div class="brand-products columns">
		<div class="column brand-info" v-if="brand">

			<div class="seller-logo">
				<img :src="getImagePath(brand.logo.path)" />
			</div>
			<div class="info">
				<div class="seller-name" style="margin-bottom:10px"><b>${ brand.merchantName }</b></div>
				<div class="seller-desc">
					${ brand.missionStatement }
				</div>
			</div>

		</div>

		<div class="column products">
			<div class="brand-product" v-for="p in brand_products">
				<a :href="'/products/' + p.handle">
					<div class="thumbnail">
						<img :src="p.thumbnail" />
					</div>
					<div class="product-details">
						<div class="name">${ p.name }</div>
						<div class="brand">by ${ p.brand.name }</div>
						<div class="price">
							${ formatMoney(p.variants[0].price) }
						</div>
					</div>
					
				</a>
			</div>
		</div>
	</div>

	<div class="reviews section padded-content">

		<h5 style="margin-bottom:20px;">3 Product Reviews</h5>

		<div class="summary">
			<img src="/img/star.svg" />
			<img src="/img/star.svg" />
			<img src="/img/star.svg" />
			<img src="/img/star.svg" />
			<img src="/img/star.svg" />

		</div>

		<div class="review">
			<div class="name">Emily Selman</div>
			<div class="date">September 16, 2022</div>
			<div>
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
			</div>
			<div class="desc">
				Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco
			</div>

		</div>

		<div class="review">
			<div class="name">Emily Selman</div>
			<div class="date">September 16, 2022</div>
			<div>
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
				<img src="/img/star-empty.svg" />
			</div>
			<div class="desc">
				Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco
			</div>

		</div>

		<div class="review">
			<div class="name">Emily Selman</div>
			<div class="date">September 16, 2022</div>
			<div>
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
				<img src="/img/star.svg" />
			</div>
			<div class="desc">
				Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco
			</div>

		</div>

	</div>

    <div id="reviews" data-product-id="{{ $product->id }}" style="display:none">
			
			<div class="review-total">
				<span :class="{ active: showReviews }" v-on:click="showReviews = true; showQuestions = false;">
					${ total } ${ total == 1 ? 'Review' : 'Reviews' }
				</span>
				|
				<span :class="{ active: showQuestions }" v-on:click="showReviews = false; showQuestions = true;">
					${ questions.length } ${ questions.length == 1 ? 'Question' : 'Questions' }
				</span>
			</div>

			<div class="review-container" v-if="showReviews">

				<div v-if="total == 0">
					There are no reviews for this product.
				</div>

				<button class="add-review-button button button--primary" v-on:click="addingReview = true">Add Review</button>
				<div class="clear"></div>

				<div class="review-form" v-if="addingReview">

					<div class="columns">
						<div class="field column">
							<label>Name *</label>
							<input type="text" v-model="addReview.name" />
						</div>

						<div class="field column">
							<label>Email *</label>
							<input type="text" v-model="addReview.email" />
						</div>

						<div class="field column">
							<label>Rating *</label>
							<select v-model="addReview.score">
								<option value="5">5 stars</option>
								<option value="4">4 stars</option>
								<option value="3">3 stars</option>
								<option value="2">2 stars</option>
								<option value="1">1 stars</option>
							</select>
						</div>
					</div>

					<div class="field">
						<label>Title</label> 
						<input type="text" v-model="addReview.title" />
					</div>

					<div class="field">
						<label>Review *</label>
						<textarea v-model="addReview.content"></textarea>
					</div>

					<div class="cancel" v-on:click="addingReview = false">cancel</div>
					
					<button class="button button--primary" v-on:click="submitReview">Submit Review</button>
					<div class="submit-error" v-if="submitError">
						Please fill out all required fields.
					</div>
				</div>

				<div class="submit-confirm" v-if="reviewSubmitted">
					Your review has been submitted. Thank you for your feedback!
				</div>

				<div class="sorting" v-if="total > 0">
					<div class="option">
						<div class="option-name">Sort By</div>
						<select v-model="sortBy" @change="updateFilter">
							<option value="popular">Top Reviews</option>
							<option value="recent">Most Recent</option>
						</select>
					</div>

					<div class="option">
						<div class="option-name">Rating</div>
						<select v-model="ratingFilter" @change="updateFilter">
							<option value="">All stars</option>
							<option value="5">5 star only</option>
							<option value="4">4 star only</option>
							<option value="3">3 star only</option>
							<option value="2">2 star only</option>
							<option value="1">1 star only</option>
						</select>
					</div>

					<div class="option">
						<div class="option-name">Images</div>
						<select v-model="imagesFilter" @change="updateFilter">
							<option value="">All Reviews</option>
							<option value="images">Only With Images</option>
						</select>
					</div>

					<div class="option">
						<div class="option-name">
							Search
							<span class="hint">(press enter to submit)</span>
						</div>
						<input type="text" v-model="searchFilter" @change="updateFilter" />
					</div>
				</div>

				<div v-if="reviews.length == 0 && total > 0">
					No reviews match the current filters.
				</div>

				<div class="reviews">
					<div class="review" v-for="review in reviews">
						<div class="review-name">
							${ review.name }
							<span class="review-verified" v-if="review.is_verified">Verified Buyer</span>
						</div>
						<div class="review-date">
							${ formatDate(review.published_at) }
						</div>
						<div class="review-stars">
							<i class="fas fa-star" v-for="(n, index) in review.score" ></i>
							<i class="far fa-star" v-for="(n, index) in (5 - review.score)"></i>
						</div>
						<div class="review-title" v-if="review.content.indexOf(review.title) != 0">
							${ review.title }
						</div>
						<div class="review-body">
							${ review.content }
						</div>
						<div class="review-images" v-if="review.images.length > 0">
							<img v-for="(image, index) in review.images" :src="image.url" v-on:click="viewImages(review, index)" />
						</div>
					</div>
				</div>

				<div class="pagination">
					<span class="prev-page" v-if="page > 0" v-on:click="showPage(page-1)">&lt;</span>
					<span class="page-number" 
						v-for="(n, index) in pageCount">
						<span v-on:click="showPage(index)" v-if="index < 15" :class="{ active: index == page }">
                        ${ index + 1 }
                        </span>
					</span>
					<span class="next-page" v-if="page < pageCount - 1" v-on:click="showPage(page+1)">&gt;</span>
				</div>

			</div>

		</div>

</div>



@stop

@section('scripts')

<script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>

@stop
