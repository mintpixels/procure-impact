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
        <a v-on:click="filterOrders('Submitted')">Submitted</a>
      </li>
      <li :class="{ active: filter == 'Reviewed' }">
        <a v-on:click="filterOrders('Reviewed')">Reviewed</a>
      </li>
      <li :class="{ active: filter == 'Approved' }">
        <a v-on:click="filterOrders('Approved')">Approved</a>
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
            <th>Phone</th>
            <th class="text-right">Total</th>
            <th>Status</th>
            <th class="text-center">Items</th>
            <th class="text-center">Notes</th>
          </tr>
        </head>
        <tbody>
          <tr class="order-item" v-for="order in orders" :class="{ fraud: order.failed_rule_id }" >
            <td>
              <a :href="'/admin/orders/' + order.id">${ order.name }</a>
            </td>
            <td class="nowrap datetime">
              ${ formatDateTime(order.created_at) }
            </td>
            <td>
                <a v-if="order.customer" :href="customerLink(order)">${ customerName(order) }</a>
                <span v-else>${ customerName(order) }</span>
            </td>
            <td class="phone">
              ${ order.billing ? order.billing.phone : '' }
            </td>
            <td class="zip">
              ${ order.billing ? order.billing.zip : '' }
            </td>
            <td class="text-right total">${ formatMoney(order.total) }</td>
            <td class="nowrap">${ order.status }</td>
            <td class="shipping text-center">${ order.items_count }</td>
            <td>
              <div v-if="order.source == 'POS'">POS</div>
              <div v-else>
                <div v-if="order.draft">Draft</div>
                <div v-else>Online</div>
              </div>
            </td>
            <td class="text-center">${ order.payments.length > 0 ? order.payments[0].avs : '' }</td>
            <td class="notes-indicator">
              <div v-if="order.customer_notes"><i class="fa fa-check" aria-hidden="true"></i> Customer</div>
              <div v-if="order.staff_notes"><i class="fa fa-check" aria-hidden="true"></i> Staff</div>
            </div>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@stop

