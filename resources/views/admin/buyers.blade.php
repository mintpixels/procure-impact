@extends('admin.layout')

@section('content')

<div id="buyers-page" class="padded-content crud">

  <h1>
    Purchasers
    <div class="actions">
      <a href="/admin/buyers/create" class="button small">Create Purchaser</a>
    </div>
  </h1>

  <div class="filter-bar">
    <form method="get" action="/admin/buyers">
        <input type="text" name="search" value="{{ isset($_GET['search']) ? $_GET['search'] : '' }}" placeholder="Search purchasers..." />
    </form>
  </div>

  <div class="section">

    <div class="overflow">
        <table style="margin-top:20px">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Type</th>
            </tr>
            </thead>
            
            <tbody>
            @foreach($buyers as $buyer)

                <tr>
                    <td>
                        <a href="/admin/buyers/{{ $buyer->id }}">{{ $buyer->name }}</a>
                    </td>
                    <td>
                        {{ $buyer->email }}
                    </td>
                    <td>
                        {{ $buyer->type }}
                    </td>
                </tr>

            @endforeach
            </tbody>
        </table>
    </div>

</div>

@stop
