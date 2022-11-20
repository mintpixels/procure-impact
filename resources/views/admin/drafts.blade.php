@extends('admin.layout')

@section('content')

<div id="draft-page" class="padded-content">

  <h1>
    Order Drafts
    <div class="actions">
      <a href="/admin/drafts/create" class="button small">Create Order</a>
    </div>
  </h1>

  <div class="section wait" :class="{ loaded: loaded }" >
    <div v-if="drafts.length == 0">
      There are no draft orders.
    </div>
    <div class="overflow" v-else v-cloak>
      <table>
        <thead>
          <tr>
            <th></th>
            <th>Date</th>
            <th>User</th>
            <th>Customer</th>
            <th class="text-center">Items</th>
            <th class="text-right">Total</th>
            <th>Notes</th>
          </tr>
        </head>
        <tbody>
          <tr class="order-item" v-for="draft in drafts">
            <td class="nowrap">
              <a :href="'/admin/drafts/' + draft.id">Open</a>
            </td>
            <td class="nowrap">
              ${ formatDate(draft.created_at) }
            </td>
            <td>
              ${ draft.user.name }
            </td>
            <td>
                <span v-if="draft.data.customer">${ customerName(draft.data.customer) }</span>
                <span v-else>${ draft.data.email }</span>
            </td>
            <td class="text-center">${ draft.data.items.length }</td>
            <td class="text-right">${ formatMoney(draft.data.total) }</td>
            <td style="max-width:200px">${ draft.data.staff_notes }</td>

          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@stop

