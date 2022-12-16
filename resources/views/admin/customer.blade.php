@extends('admin.layout')

@section('content')

<div id="customer-page" class="padded-content" data-customer-id="{{ $customer->id ?? '' }}" v-cloak>

  @include('admin.snippets.save-bar')

  <h1 class="with-subtitle">
    ${ getName(customer) }
  </h1>
  <!-- <div class="subtitle">Customer since ${ formatDate(customer.created_at) }</div> -->

  <div class="modal" :class="{ show: modalView }">

    <!------------------------------------------------------------------------------>

    <div class="modal-view" :class="{ show: modalView == 'edit-address' }">
      <h3 v-if="modalView == 'edit-address'">Edit Address</h3>
      <div>
        @include('admin.snippets.address', ['address' => 'editAddress'])
      </div>

      <div class="error" v-if="modalError">
        ${ modalError }
      </div>

      <div class="actions">
        <button class="primary"  v-if="modalView == 'edit-address'" v-on:click="saveAddress()">Save</button>
        <button class="secondary" v-on:click="closeModal()">Cancel</button>
      </div>
    </div>

    <!------------------------------------------------------------------------------>

  </div>


  <div class="columns">

    <div class="primary column">

      <div class="section">
        <h5>Customer Information</h5>

        <div class="columns">
          <div class="field column">
            <label>First Name</label>
            <input type="text" v-model="customer.first_name" autocomplete="dontdoit" />
          </div>

          <div class="field column">
            <label>Last Name</label>
            <input type="text" v-model="customer.last_name" autocomplete="dontdoit" />
          </div>
        </div>

        <div class="columns">
          <div class="field column">
            <label>Company</label>
            <input type="text" v-model="customer.company" autocomplete="dontdoit" />
          </div>


          <div class="field column">
            <label>Phone</label>
            <input type="text" v-model="customer.phone" autocomplete="dontdoit" />
          </div>
        </div>

        <div class="field">
          <label>Email</label>
          <input type="text" v-model="customer.email" autocomplete="dontdoit" />
        </div>

        <div class="field">
          <label>Buyer</label>
          <select v-model="customer.buyer_id">
            <option v-for="buyer in buyers" :value="buyer.id">${ buyer.name }</option>
          </select>
        </div>

        <div class="field">
            <label for="name">Type</label>
            <select name="type" required v-model="customer.type">
                <option>Wholesale</option>
                <option>Retail</option>
            </select>
        </div>

        <div class="field">
          <label>Password</label>
          <input type="text" v-model="customer.password" autocomplete="dontdoit" />
        </div>

      </div>

      <div class="section">
        <h5>Customer Notes</h5>
        <textarea v-model="customer.notes"></textarea>
      </div>

      <div class="section" v-if="customer.recent_orders">
        <h5>
          Recent Orders
          <a class="small" :href="'/admin/orders?customer_id=' + customer.id">View All</a>
        </h5>

        <table v-if="customer.recent_orders.length > 0">
          <thead>
            <tr>
              <th>Order</th>
              <th>Date</th>
              <th class="text-right">Total</th>
              <th>Status</th>
            </tr>
          </head>
          <tbody>
            <tr class="order-item" v-for="order in customer.recent_orders">
              <td>
                <a :href="'/admin/orders/' + order.id">${ order.name }</a>
              </td>
              <td class="nowrap">${ formatDate(order.created_at) }</td>
              <td class="text-right">${ formatMoney(order.total) }</td>
              <td class="nowrap">${ order.status }</td>
            </tr>
          </tbody>
        </table>
        <div v-else>No recent orders</div>

      </div>

    </div>  

    <div class="secondary column">

      <div class="section">
          <h5>Addresses</h4>
          <div v-if="customer.addresses.length == 0">
            No addresses.
          </div>
          <div v-for="(address, i) in customer.addresses">
            <template v-if="i == 0 || showAddresses" >
              <div class="subsection">
                ${ address.first_name } ${ address.last_name }<br>
                ${ address.address1 } ${ address.address2 }<br>
                ${ address.city }, ${ address.state } ${ address.zip }<br>
                <a v-on:click="showAddressEdit(i)">Edit Address</a>
                <i class="fa fa-close delete" v-on:click="deleteAddress(i)"></i>
              </div>
            </template>
          </div>
          <div v-if="customer.addresses.length > 1">
            <span class="clickable" v-on:click="showAddresses = !showAddresses">
              ${ showAddresses ? 'Hide addresses' : 'Show ' + customer.addresses.length + ' addresses' }
            </span>
          </div>

          <div class="field text-right">
            <a v-on:click="addAddress">Add Address</a>
          </div>
      </div>

    </div>

  </div>

</div>

@stop