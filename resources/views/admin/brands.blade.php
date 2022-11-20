@extends('admin.layout')

@section('content')

<div id="brands-page" class="padded-content crud">

  <h1>
    Social Enterprises
    <div class="actions">
      <a href="/admin/brands/create" class="button small">Create Social Enterprise</a>
    </div>
  </h1>

  <div class="filter-bar">
    <form method="get" action="/admin/brands">
        <input type="text" name="search" value="{{ isset($_GET['search']) ? $_GET['search'] : '' }}" placeholder="Search social enterprises..." />
    </form>
  </div>

  <div class="section">

    <div class="overflow">
        <table style="margin-top:20px">
            <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Location</th>
            </tr>
            </thead>
            
            <tbody>
            @foreach($brands as $brand)

                <tr>
                    <td>
                        <a href="/admin/brands/{{ $brand->id }}">{{ $brand->name }}</a>
                    </td>
                    <td>
                        {{ $brand->description }}
                    </td>
                    <td>
                        {{ $brand->location }}
                    </td>
                </tr>

            @endforeach
            </tbody>
        </table>
    </div>

</div>

@stop
