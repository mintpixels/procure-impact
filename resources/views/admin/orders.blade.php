@extends('admin.layout')

@section('content')

<div id="orders-page" class="padded-content">

  <h1>
    Orders
  </h1>

  <div class="filter-tabs">
    <ul>
      <li :class="{ active: filter == 'All' }">
        <a v-on:click="filterOrders('All')">All</a>
      </li>
      <li :class="{ active: filter == 'Submitted' }">
        <a v-on:click="filterOrders('Submitted')">Submitted ({{ $counts->submitted }})</a>
      </li>
      <li :class="{ active: filter == 'Approved' }">
        <a v-on:click="filterOrders('Approved')">Approved ({{ $counts->approved }})</a>
      </li>
      <li :class="{ active: filter == 'Awaiting Fulfillment' }">
        <a v-on:click="filterOrders('Awaiting Fulfillment')">Awaiting Fulfillment ({{ $counts->waiting }})</a>
      </li>
      <li :class="{ active: filter == 'Completed' }">
        <a v-on:click="filterOrders('Completed')">Completed ({{ $counts->completed }})</a>
      </li>
    </ul>
  </div>

  <div class="filter-bar">
    <div class="input-bar">
        <input type="text" v-model="search" placeholder="Search Orders..." @keyup="searchUpdated" />
        <span class="clear-input" v-on:click="search = ''; searchOrders()">
          <span v-if="search">x</span>
        </span>
        <button class="button" v-on:click="searchOrders">Search</button>
    </div>
  </div>

  <div v-if="product" class="filters">
    <span class="filter">
      Committed orders for <b>${ product.name }</b>
      <span class="remove" v-on:click="removeProduct">x</span>
    </span>
  </div>

  <div v-if="customer" class="filters">
    <span class="filter">
      Orders for <b><a :href="'/admin/customers/' + customer.id">${ customer.first_name } ${ customer.last_name }</a> (${ customer.email })</b>
      <span class="remove" v-on:click="removeCustomer">x</span>
    </span>
  </div>

  <div class="section">

    <img src="/img/loading.gif" class="loader" v-if="loading" />  

    <div class="overflow" v-cloak>
      <table>
        <thead>
          <tr>
            <th>Order</th>
            <th>Date</th>
            <th>Customer</th>
            <th class="text-right">Total</th>
            <th class="text-center">Items</th>
            <th>Status</th>
          </tr>
        </head>
        <tbody>
          <tr class="order-item" v-for="order in orders" :class="{ fraud: order.failed_rule_id }" >
            <td>
              <a :href="'/admin/orders/' + order.id">#${ order.name }</a>
            </td>
            <td class="nowrap datetime">
              ${ formatDateTime(order.created_at) }
            </td>
            <td>
                <a :href="customerLink(order)">${ customerName(order) }</a>
                (${ order.customer.buyer.name })
            </td>
            <td class="text-right total">${ formatMoney(order.total) }</td>
            <td class="shipping text-center">${ order.items_count }</td>
            <td class="nowrap">${ order.status }</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@stop

