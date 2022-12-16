@extends('admin.layout')

@section('content')

<div id="transactions-page" class="padded-content">

  <h1>
    Transactions
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
      <li :class="{ active: filter == 'Buyer Unpaid' }">
        <a v-on:click="filterOrders('Buyer Unpaid')">Buyer Unpaid ({{ $counts->completed }})</a>
      </li>
      <li :class="{ active: filter == 'Vendor Unpaid' }">
        <a v-on:click="filterOrders('Vendor Unpaid')">Vendor Unpaid ({{ $counts->completed }})</a>
      </li>
    </ul>
  </div>

  <div class="filter-bar">
    <div class="input-bar">
        <input type="text" v-model="search" placeholder="Search Transactions..." @keyup="searchUpdated" />
        <span class="clear-input" v-on:click="search = ''; searchOrders()">
          <span v-if="search">x</span>
        </span>
        <button class="button" v-on:click="searchOrders">Search</button>
    </div>
  </div>

  <div class="section">

    <img src="/img/loading.gif" class="loader" v-if="loading" />  

    <div class="overflow" v-cloak>
      <table>
        <thead>
          <tr>
            <th>Order</th>
            <th>Date</th>
            <th>Buyer</th>
            <th>Customer</th>
            <th class="text-right">Total</th>
            <th class="text-center">Items</th>
            <th>Status</th>
            <th>Buyer Payment</th>
            <th>Vendor Payment</th>
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
              <a :href="'/admin/buyers/' + order.customer.buyer.id">${ order.customer.buyer.name }</a>
            <td>
                <a :href="customerLink(order)">${ customerName(order) }</a>
            </td>
            <td class="text-right total">${ formatMoney(order.total) }</td>
            <td class="shipping text-center">${ order.items_count }</td>
            <td class="nowrap">${ order.status }</td>
            <td></td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@stop

