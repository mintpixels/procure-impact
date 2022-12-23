@extends('store.layout')

@section('content')

<div class="padded-content page">
    @if($page->title)
    <h1>{{ $page->title }}</h1>
    @endif
    
    <div class="page-body">
    {!! Str::markdown($page->content) !!}
    </div>
</div>

@stop

