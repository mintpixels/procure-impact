@extends('admin.layout')

@section('content')

<div id="customers-page" class="padded-content crud">

    <h1>
        Customers
        <div class="actions">
            <a href="/admin/customers/create" class="button small">Create Customer</a>
        </div>
    </h1>

    <div class="filter-bar">
        <form method="get" action="/admin/customers">
            <input type="text" name="search" value="{{ isset($_GET['search']) ? $_GET['search'] : '' }}" placeholder="Search customers..." />
        </form>
    </div>

    <div class="section">

        <div class="overflow">
            <table style="margin-top:20px">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Buyer</th>
                    <th>Type</th>
                    <th>Phone</th>
                    <th>Orders</th>
                </tr>
                </thead>
                
                <tbody>
                @foreach($entities as $customer)

                    <tr>
                        <td>
                            <a href="/admin/customers/{{ $customer->id }}">
                                {{ $customer->first_name }} {{ $customer->last_name }}
                                @if($customer->company)
                                ({{ $customer->company }})
                                @endif
                            </a>
                        </td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->buyer->name }}</td>
                        <td>{{ $customer->type }}</td>
                        <td>{{ $customer->phone }}</td>
                        <td>{{ $customer->orders_count }}</td>
                    </tr>

                @endforeach
                </tbody>
            </table>
        </div>

    </div>

</div>

@stop