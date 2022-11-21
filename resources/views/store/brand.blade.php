@extends('store.layout')

@section('content')
<div class="page-content no-max" id="brand-page" data-brand="{{ $handle }}" v-cloak>

    <div class="breadcrumb max-w">
        Home / <span>${ brand }</span>
    </div>

    <template v-for="section in sections">

        <div class="hero max-w" v-if="section.__typename == 'SectionMerchantHero'">

            <div class="image">
                <img :src="getImagePath(section.desktopImage.path)" />
                <img class="logo" :src="getImagePath(section.logo.path)" />
            </div>

            <div class="columns">
                <div class="column" style="flex: 0 0 400px">
                    <div class="headline">${ section.headline }</div>
                    <div class="location">${ section.cityAndState }</div>
                    <div class="shipping">${ section.shippingCityAndState }</div>
                    <div class="timeline">${ section.fulfillmentTimeline }</div>
                    <div class="tags">
                        <span class="tag" v-for="tag in section.tags">
                            ${ tag.tag }
                        </span>
                    </div>
                </div>

                <div class="column" style="flex: 0 0 250px">
                    Follow
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
                <div class="column w-100">
                    <img src="/img/arrow-left.svg" />
                </div>
                <div class="column">
                    <div class="images">
                        <img v-for="image in section.images" :src="getImagePath(image.image.path)" />
                    </div>
                </div>
                <div class="column w-100">
                    <img src="/img/arrow-right.svg" />
                </div>
            </div>

        </div>

        <div class="split-video max-w" v-if="section.__typename == 'SectionSplitWithVideo'">
            <div class="columns centered">
                <div class="column">
                    <video controls>
                    <source :src="section.videoUrl">
                    </video>
                </div>
                <div class="column">

                    <div class="headline">
                        ${ section.headline }
                    </div>

                    <div class="statement">
                        ${ section.statement }
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


</div>


@stop