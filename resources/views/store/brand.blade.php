@extends('store.layout')

@section('content')
<div class="page-content" id="brand-page" data-brand="{{ $brand->handle }}" v-cloak>

    <div class="breadcrumb">
        Home / <span>${ brand.name }</span>
    </div>

    <template v-for="section in sections">

        section: ${ section.__typename }<br>

        <div class="hero" v-if="section.__typename == 'SectionMerchantHero'">

            <img :src="getImagePath(section.desktopImage.path)" />
    
        </div>

    </template>


</div>


@stop