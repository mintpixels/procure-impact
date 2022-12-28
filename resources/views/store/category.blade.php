@extends('store.layout')

@section('content')

<div class="padded-content">
    <div data-brand-id="{{ $brand->id ?? '' }}" data-category-id="{{ $category->id ?? '' }}">
        @include('store.snippets.search')
    </div>
</div>


@stop
