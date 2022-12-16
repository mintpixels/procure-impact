@extends('admin.layout')

@section('content')

<div id="transaction-page" class="padded-content" data-order-id="{{ $order->id }}" v-cloak>

  <h1 class="with-subtitle">
    #${ order.id }
    <span v-if="order.buyer">${ order.buyer.name }</span>
  </h1>
  <div class="subtitle">
    <span>${ order.email}</span>
    <span>${ order.phone }</span>
  </div>

  <div class="columns layout">
      
      <div class="column">

        <div class="section">

          <table>
            <thead>
              <tr>
                <th>Vendor</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">Fee</th>
                <th class="text-right">Shipping</th>
                <th class="text-right">Net</th>
                <th class="text-right">Paid</th>
                <th class="text-center">Paid Date</th>
              </tr>
            </head>
            <tbody>
              <tr v-for="payment in order.brand_payments">
                <td>${ payment.brand.name }</td>
                <td class="text-right">${ formatMoney(payment.subtotal) }</td>
                <td class="text-right">
                  <div class="input-with-label" style="width:80px;float:right">
                    <input :readonly="payment.paid_at" type="text" v-model="payment.fee" class="currency light" />
                    <span>%</span>
                  </div>
                </td>
                <td class="text-right">${ formatMoney(payment.shipping) }</td>
                <td class="text-right">${ formatMoney(net(payment)) }</td>
                <td class="text-right">
                  ${ formatMoney(payment.paid) }
                </td>
                <td class="text-center">
                  <span v-if="payment.paid_at">
                    ${ formatDate(payment.paid_at) }
                  </span>
                  <span v-else>
                    <button class="button small" v-if="!payment.paid_at" v-on:click="makePayment(payment)">Pay</button>
                  </span>
                </td>
              </tr>
            </tbody>
          </table>

        </div>
      </div>
  </div>

</div>

@stop

