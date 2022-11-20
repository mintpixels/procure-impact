@extends('store.layout')

@section('content')

<div id="homepage" >

    <template v-for="section in sections">

        <div class="hero" v-if="section.SectionHero_type">
            <div v-if="section.SectionHero_type == 'split'" class="columns">
                <div class="column cta">
                    <div class="content">
                        <h1>${ section.headline }</h1>
                        <p>${ section.statement}</p>

                        <div class="buttons">
                            <a class="button" :href="b.button.url" v-for="b in section.buttons">${ b.button.text}</a>
                        </div>
                    </div>
                </div>
                <div class="column image" style="background-image:url(https://images.takeshape.io/a81826c6-44c5-4725-8844-1402105665dd/dev/90ba7ddb-10b6-4fcd-8cb6-55a971edb353/Banner%20image.png?auto=format%2Ccompress&w=600)">
                </div>
            </div>
        </div>

        <div class="carousel" v-if="section.SectionProductOrCategoryCarousel_cards">
            <div class="section-content">
                <h4>${ section.headline }</h4>
                <div class="cards">
                    <template v-for="c in section.SectionProductOrCategoryCarousel_cards">
                        <a :href="c.card.url">
                        <div class="card">
                            <div class="title">${ c.card.headline }</div>
                            <img :src="getImagePath(c.card.featuredImage.path)" />
                        </div>
                        </a>
                    </template>
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

        <div class="carousel" v-if="section.SectionProductCarouselWithTags_cards">
            <div class="section-content">
                <h4>${ section.headline }</h4>
                <div class="cards">
                    <template v-for="c in section.SectionProductCarouselWithTags_cards">
                        <a :href="c.card.url">
                            <div class="card">
                                <img :src="getImagePath(c.card.featuredImage.path)" />
                            </div>
                            <div class="columns card-row">
                                <div class="column b">${ c.card.productName }</div>
                                <div class="column text-right b">$${ c.card.price }</div>
                            </div>
                            <div class="columns card-row">
                                <div class="column">${ c.card.vendor }</div>
                                <div class="column text-right">${ c.card.location }</div>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <div class="testimonial" v-if="section.testimonial">
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

        <div class="features" v-if="section.SectionFeaturesAlternatingWithOptionalTestimonial_button">
            <div class="section-content columns">
                <div class="column">
                    <div class="icon">
                        <img :src="getImagePath(section.icon.path)" />
                    </div>
                    <div class="headline">
                        ${ section.headline }
                    </div>
                    <div class="statement">
                        ${ section.statement }
                    </div>
                    <div>
                        <a class="button" 
                            :target="getButtonTarget(section.SectionFeaturesAlternatingWithOptionalTestimonial_button)" 
                            :href="getButtonUrl(section.SectionFeaturesAlternatingWithOptionalTestimonial_button)">
                            ${ getButtonText(section.SectionFeaturesAlternatingWithOptionalTestimonial_button) }
                        </a>
                    </div>
                </div>

                <div class="column">
                    <img :src="getImagePath(section.featuredImage.path)" />
                </div>
            </div>
        </div>
<!-- 
        <div class="filters" v-if="section.filters">
            filters
        </div>

        <div class="heading" v-if="section.heading">
            searchterms
        </div>

        <div class="search-terms" v-if="section.searchTerms">
            searchterms
        </div>

        <div class="statement" v-if="section.SectionFeaturesCenteredGrid_features">
            statement
        </div>

        <div class="cta" v-if="section.SectionCtaSplitWithImage_button">
            cta
        </div> -->

    </template>
    
</div>


@stop