@extends('store.layout')

@section('content')

<div class="padded-content">
    <div data-category-id="{{ $category->id }}">
        @include('store.snippets.search')
    </div>
</div>


@stop
