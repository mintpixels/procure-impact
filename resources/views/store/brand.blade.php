@extends('store.layout')

@section('content')
<div class="page-content no-max padded-content" id="brand-page" data-brand="{{ $handle }}" v-cloak>

    <div class="breadcrumb max-w">
        Home / <span>${ brand.merchantName }</span>
    </div>

    <template v-for="section in sections">

        <div class="hero max-w" v-if="section.__typename == 'SectionMerchantHero'">

            <div class="image">
                <img :src="getImagePath(section.desktopImage.path)" />
                <div class="logo">
                    <img :src="getImagePath(section.logo.path)" />
                </div>
            </div>

            <div class="columns">
                <div class="column" style="flex: 0 0 400px">
                    <div class="headline">${ section.headline }</div>
                    <div class="location">${ section.cityAndState }</div>
                    <!-- <div class="shipping">${ section.shippingCityAndState }</div> -->
                    <div class="timeline">${ section.fulfillmentTimeline }</div>
                    <div class="tags">
                        <span class="tag" v-for="tag in section.tags">
                            ${ tag.tag }
                        </span>
                    </div>
                </div>

                <div class="column" style="flex: 0 0 250px">
                    Follow Us

                    <ul class="follow-us" v-if="section.socialLinks && section.socialLinks.length > 0">
                        <li v-for="link in section.socialLinks">
                            <a target="_blank" :href="link.urlLink.url">${ link.urlLink.channelName }</a>
                        </li>
                    </ul>
                </div>

                <div class="column">
                    <div class="mission-headline">
                        ${ section.missionHeadline }
                    </div>

                    <div class="mission-statement">
                        ${ section.missionStatement }
                    </div>
                </div>

            </div>
    
        </div>

        <div class="stats" v-if="section.SectionStatsSimpleInCard_stats">
            <div class="section-content">
                <h2>${ section.headline }</h2>
                <p>${ section.statement }</p>
                <div class="box columns">
                    <div v-for="s in section.SectionStatsSimpleInCard_stats" class="column">
                        <h5>${ s.stat.headline }</h5>
                        <label>${ s.stat.statement }</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="gallery max-w" v-if="section.__typename == 'SectionGalleryCarousel'">

            <div class="columns centered">
                <div class="column w-100 absolute" v-if="section.images.length > 1">
                    <img src="/img/arrow-left.svg" />
                </div>
                <div class="column">
                    <div class="images">
                        <img v-for="image in section.images" :src="getImagePath(image.image.path)" />
                    </div>
                </div>
                <div class="column w-100 absolute" v-if="section.images.length > 1">
                    <img src="/img/arrow-right.svg" />
                </div>
            </div>

        </div>

        <div class="split-video max-w" v-if="section.__typename == 'SectionSplitWithVideo'">
            <div class="columns centered">
                <div class="column" v-html="section.videoEmbedCode">
                </div>
                <div class="column" style="padding:50px;padding-top:50px;">

                    <div class="headline">
                        ${ section.headline }
                    </div>

                    <div class="statement">
                        ${ section.content }
                    </div>
                    
                </div>

            </div>
        </div>

        <div class="general-content max-w" v-if="section.__typename == 'SectionGeneralContent'">
            
            <div class="headline">
                ${ section.headline }
            </div>

            <div class="statement">
                ${ section.content }
            </div>
        </div>

        <div class="testimonial max-w" v-if="section.__typename == 'SectionTestimonialWithLargeAvatar'">
            <div class="section-content">
                <div class="avatar">
                    <div class="image">
                        <img :src="getImagePath(section.avatar.path)" />
                    </div>
                </div>
                <div class="content">
                    <div>
                        <div class="test">${ section.testimonial }</div>
                        <div class="name">${ section.name }</div>
                        <div class="title">${ section.title }</div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <div class="brand-products columns">
		<div class="column brand-info" v-if="brand && brand.logo">

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


</div>


@stop