@extends('admin.layout')

@section('content')

@if($type == 'draft')
<div id="order-page" class="padded-content printable" data-draft-id="{{ $draft->id ?? ''}}" v-cloak>
  @include('admin.snippets.save-bar')
  <h1>
    Order Draft

    <div class="actions">
      <button v-on:click="completeDraft" class="button small" :disabled="!complete() || submitting">Complete</button>
    </div>
  </h1>
@else
<div id="order-page" class="padded-content printable" data-order-id="{{ $order->id }}" v-cloak>
  @include('admin.snippets.save-bar')
  <h1 class="with-subtitle">
      #${ order.id }
      <span>
        ${ order.status }
      </span>

      <div class="actions" v-if="!changed && ['Cancelled'].indexOf(order.status) < 0">
        <span style="font-size:12px" v-on:click="showActions = !showActions">Actions <i class="fa-solid fa-circle-chevron-down"></i></span>
        <div class="action-list">
          <div v-if="['Held', 'Problem', 'Shipping Problem', 'Pending Service'].indexOf(order.status) >= 0 && ['Awaiting Pickup'].indexOf(order.status) < 0">
            <span v-on:click="updateStatus('Awaiting Fulfillment')">Send to Picking</span>
            <span v-on:click="updateStatus('In Shipping')">Send to Shipping</span>
          </div>
          <div v-if="['Cancelled', 'Completed', 'Awaiting Pickup'].indexOf(order.status) < 0">
            <span v-on:click="holdOrder" v-if="['Held', 'Problem'].indexOf(order.status) < 0">Hold Order</span>
            <span v-on:click="showProblem" v-if="['Problem'].indexOf(order.status) < 0">Mark as Problem</span>
          </div>
          <div>
            <span v-on:click="completeOrder">Complete Order</span>
          </div>
          <span v-if="['In Shipping'].indexOf(order.status) < 0" v-on:click="showCancelOrder">Cancel Order</span>

          <span v-on:click="printInvoice()">Print Invoice</span>
        </div>
      </div>
  </h1>
  <div class="subtitle">
    ${ formatDate(order.created_at) } 
    <span v-if="order.ip_address" class="ip">${ order.ip_address }</span>
  </div>
@endif

  <div class="modal" :class="{ show: modalView }">

    <!------------------------------------------------------------------------------>

    <div class="modal-view"  :class="{ show: modalView == 'zoom-photo' }">
      <img :src="zoomPhoto" />
      <i class="fal fa-times" v-on:click="modalView = '';"></i>
    </div>

    <!------------------------------------------------------------------------------>

    <div class="modal-view payments-view" :class="{ show: modalView == 'add-payment' }">
      <h3>Add Payment</h3>
      <div class="field">
        <label>Payment Amount</label>
        <div class="item-price input-with-label">
            <span>$</span>
            <input type="text" v-model="paymentAmount"/>
        </div>
      </div>
      <div class="field">
        <label>Payment Method</label>
        <select v-model="paymentMethod">
          <option selected>Credit Card</option>
          <option>Purchase Order</option>
          <option v-if="order.customer && order.customer.pay_later">House Account</option>
        </select>
      </div>
      <div class="field cc-inputs" v-if="paymentMethod == 'Credit Card'">
        <div class="columns" v-if="!manualCard">
            <div class="column">
                <label>Credit Card Number</label>
                <input type="text" name="ccNumber" v-model="card.number" />
            </div>
            <div class="column small">
                <label>Expiration</label>
                <input type="text" name="ccExpiry" v-model="card.expiry" @keyup="formatExpiry" placeholder="MM/YY" maxlength="5" class="text-center" />
            </div>
            <div class="column small">
                <label>CVV</label>
                <input type="text" name="ccCvv" v-model="card.cvv"  class="text-center"/>
            </div>
        </div>
        <div class="manual-card">
          <input v-model="manualCard" type="checkbox" /> Process card manually
        </div>
      </div>

      <div class="field">
        <label>Payment Notes</label>
        <textarea v-model="paymentNotes"></textarea>
      </div>

      <div v-if="paymentError" class="error">
        ${ paymentError }
      </div>

      <div class="actions">
          <button class="primary" v-on:click="addPayment()">Add</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      
    </div>

    <!------------------------------------------------------------------------------>

    <div class="modal-view" :class="{ show: modalView == 'cancel-order' }">
      <h3>Cancel Order</h3>

      <div class="actions">
        <button class="primary wide" v-on:click="cancelOrder()">Cancel Order</button>
        <button class="secondary" v-on:click="closeModal()">Cancel</button>
      </div>
    </div>

    <!------------------------------------------------------------------------------>

    <div class="modal-view" :class="{ show: modalView == 'edit-address' || modalView == 'edit-billing' }">
      <h3 v-if="modalView == 'edit-billing'">Edit Billing Address</h3>
      <h3 v-if="modalView == 'edit-address'">Edit Shipping Address</h3>
      <div class="field">
        <select v-model="selectedAddress" v-if="order.customer" v-on:change="addressChanged">
          <option value="">New Address</option>
          <option v-for="(address, i) in order.customer.addresses" :value="i+1">
            ${ address.first_name } ${ address.last_name } ${ address.company} , ${ address.address1 } ${ address.address2 }, ${ address.city }, ${ address.state }, ${ address.zip }
          </option> 
        </select>
      </div>
        
      <div>
        @include('admin.snippets.address', ['address' => 'editAddress'])
      </div>

      <div class="actions">
        <button class="primary"  v-if="modalView == 'edit-billing'" v-on:click="saveBillingAddress()">Save</button>
        <button class="primary"  v-if="modalView == 'edit-address'" v-on:click="saveShippingAddress()">Save</button>
        <button class="secondary" v-on:click="closeModal()">Cancel</button>
      </div>
    </div>

    <!------------------------------------------------------------------------------>

    <div class="modal-view" :class="{ show: modalView == 'add-customer' }">
      <h3>Add Customer</h5>

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
        <label>Customer Group</label>
        <select v-model="customer.group_id">
          <option v-for="group in groups" :value="group.id">${ group.name }</option>
        </select>
      </div>

      <div class="field">
        <label>Notes</label>
        <textarea v-model="customer.notes"></textarea>
      </div>

      <div class="error" v-if="modalError">
        ${ modalError }
      </div>

      <div class="actions">
        <button class="primary" v-on:click="newCustomer(customer)">Save</button>
        <button class="secondary" v-on:click="closeModal()">Cancel</button>
      </div>

    </div>

    <!------------------------------------------------------------------------------>

  </div>

  <div class="columns layout" v-if="loaded">
      
    <div class="primary column">

      <!------------------------------------------------------------------------------>

      <div class="section order-details">
        <h5>
          Order Items
        </h5>

        <input type="text" v-model="productLookup" @keyup="lookupProducts(productLookup)" v-if="!readonly" />
        <ul class="matches" v-if="productMatches.length > 0">
          <li v-for="product in productMatches" v-on:click="addProduct(product)">
            <div><img v-if="product.thumbnail" :src="product.thumbnail.indexOf('http') == 0 ? product.thumbnail : '{{ env('AWS_CDN_PRODUCTS_PATH') }}' + product.thumbnail" /></div>
            <div>${ product.brand.name }</div>
            <div class="name">${ product.name }<br>${ product.sku }</div>
            <div class="price text-right">${ formatMoney(product.price) }</div>    
            <!-- <div class="available text-right">${ product.available } available</div>     -->
          </li>
        </ul>


        <div class="line-items">
          <div class="line-item" v-for="(item, i) in order.items">

            <div class="item-quantity" :class="{ underStocked: item.underStocked }">
                <input type="text" name="quantity" v-model="item.quantity" v-on:change="itemUpdated(item, i)" min="0" :readonly="readonly" autocomplete="dontdoit" :disabled="readonly" />
                <div class="qty-warning">max: <span>${ item.min }</span></div>
            </div>

            <div class="item-image">
              <img v-if="item.product.thumbnail" :src="item.product.thumbnail.indexOf('http') == 0 ? item.product.thumbnail : '{{ env('AWS_CDN_PRODUCTS_PATH') }}' + item.product.thumbnail" />
            </div>

            <div class="item-info">
                                
                <div class="item-name">
                  <a :href="'/admin/products/' + item.product.id">${ item.product.name }</a>
                  <div class="variant" v-if="item.variant">${ item.variant.name }</div>
                  <div class="brand">${ item.product.brand.name }</div>
                </div>

                <div class="item-sku">
                    ${ item.sku }
                    <br>
                    <span class="clickable" v-on:click="setItemPrice(item, item.price)">${ formatMoney(item.price) }</span>
                    <span v-if="item.customPrice && item.price != item.customPrice">
                        / <b>${ formatMoney(item.customPrice) }</b>
                    </span>
                    <div class="item-options" v-if="item.properties && item.properties.length > 0">
                        <div class="item-option" v-for="prop in item.properties">
                            <span class="option-name">${ prop.name }:</span>
                            <span class="option-value">${ prop.value }</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="break"></div>

            <div class="item-price input-with-label">
                <span>$</span>
                <input type="text" name="price" v-model="item.customPrice" :readonly="readonly" :disabled="readonly" />
            </div>
            <div class="item-total-quantity">
              x ${ item.quantity }
            </div>

            <div class="item-total">
                ${ formatMoney(item.customPrice * item.quantity) }
            </div>

            <div class="remove-item" v-if="!readonly">
                <i class="fal fa-times" v-on:click="removeItem(i)"></i>
            </div>

          </div>

        </div>
      
      </div>

      <!------------------------------------------------------------------------------>
      
      <div class="section">
        <h5>Customer Notes</h5>
        <textarea v-model="order.customer_notes"></textarea>
      </div>

      <div class="section">
        <h5>Staff Notes</h5>
        <textarea v-model="order.staff_notes"></textarea>
      </div>

      <div class="section" v-if="timeline.length > 0">
        <h3>Timeline</h3>
        <div class="timeline">
            <div v-for="(item, i) in timeline">
              <div class="timeline-date" v-if="i == 0 || item.sdate != timeline[i-1].sdate">${ item.sdate }</div>
              <div class="timeline-item">
                  <span class="timeline-time">${ item.time }</span>
                  ${ item.summary }
                  <span class="timeline-source">${ item.source }</span>
                  
                  <span v-if="item.changes.length > 0 || item.note">
                      <span class="toggle-changes">
                          <i class="fas fa-caret-right"></i>
                          <i class="fas fa-caret-down"></i>
                      </span>

                      <div class="timeline-changes">
                          <div class="timeline-note">${ item.note }</div>
                          
                          <div class="timeline-change" v-for="change in item.changes">
                              <span class="timeline-field">${ change.field }</span>
                              <span class="timeline-value old">${ change.old_value }</span>
                              <span class="timeline-value">${ change.new_value }</span>
                          </div>
                      </div>
                  </span>
              </div>
            </div>
        </div>
      </div>

    </div>

    <div class="secondary column">

    <!------------------------------------------------------------------------------>

      <div class="section summary">
        <h5>Summary</h5>

        <table>
          <tbody>
            <tr>
              <td>Subtotal</td>
              <td class="text-right">${ formatMoney(order.subtotal) }</td>
            </tr>
            <tr v-if="order.shipping > 0">
              <td>Shipping</td>
              <td class="text-right">${ formatMoney(order.shipping) }</td>
            </tr>
            <tr v-if="order.insurance">
              <td>Insurance</td>
              <td class="text-right" >
                ${ formatMoney(order.insurance) }
                <span v-if="order.insuranceIncluded">(inc)</span>
              </td>
            </tr>
            <!-- <tr v-if="order.taxCalculated">
              <td>Tax</td>
              <td class="text-right">${ formatMoney(order.tax) }</td>
            </tr>
            <tr v-else>
              <td>Tax</td>
              <td class="text-right"><a v-on:click="getTax">calculate</a></td>
            </tr> -->
            <tr>
              <td>Tax</td>
              <td class="text-right">${ formatMoney(0) }</td>
            </tr>
            <tr class="total">
              <td>Total</td>
              <td class="text-right">${ formatMoney(order.total) }</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="section summary payments">
        <h5>Payments</h5>
        <table>
          <tbody>
            <tr v-for="(payment, i) in order.payments">
              <td>
                ${ payment.method } 
                <span v-if="payment.avs">(${ payment.avs })</span>
                <i class="fa fa-times" v-on:click="removePayment(i)"></i>
                <div class="capture-payment" v-if="order.status == 'Completed' && payment.transaction_id && !payment.captured_at">
                  <a v-on:click="capturePayment(payment)">Capture Payment</a>
                </div>
                <div class="payment-notes" v-if="payment.last_4">x${payment.last_4}</div>
                <div class="payment-notes">${ payment.note }</div>
 
              </td>
              <td class="text-right">
                ${ formatMoney(payment.amount) }
              </td>
            </tr>
            <tr v-if="due != 0">
              <td><b>Due</b></td>
              <td class="text-right"><b>${ formatMoney(due) }</b></td>
            </tr>
          </tbody>
        </table>

        <div class="field text-right" v-if="due > 0 && readyForPayment()">
          <a v-on:click="setupPayment();showModal('add-payment')">Add Payment</a>
        </div>
      </div>

      <!------------------------------------------------------------------------------>


      <div class="section">
        <h5>Customer</h5>

        <div v-if="order.customer">
          <a v-if="order.customer.id" :href="'/admin/customers/' + order.customer.id">${ customerName(order.customer) }</a>
          <span v-else>${ customerName(order.customer) }</span>
          <br>
          ${ order.customer.email }<br>
          <div v-if="order.customer.phone">
            ${ order.customer.phone }
          </div>
          <a v-on:click="removeCustomer" v-if="!readonly">remove</a>
        </div>
        <div class="relative" v-else>
          <input type="text" class="light" v-model="customerLookup" @keyup="lookupCustomer(customerLookup)" :readonly="readonly" placeholder="Search customers" />
          <div v-if="customerMatches.length > 0">
            <div class="close-lookup" v-on:click="customerMatches = [];customerLookup = '';">
              <i class="fal fa-times" aria-hidden="true"></i>
            </div>
            <ul class="matches">
              <li v-for="customer in customerMatches" v-on:click="addCustomer(customer)">
                <div class="name">${ customerName(customer) }</div>
                <div class="breadcrumb">${ customer.email }</div>    
              </li>
            </ul>
          </div>
          <div class="field text-right">
            <a v-on:click="showModal('add-customer')">Add Customer</a>
          </div>

          <div class="grouped-inputs" v-if="!draft">
            <h4>Contact Information</h4>
            <div class="field">
              <label>Email</label>
              <input type="text" class="light" v-model="order.email" autocomplete="dontdoit" :readonly="readonly" />
            </div>
            <div class="field">
              <label>Phone</label>
              <input type="text" class="light" v-model="order.phone" autocomplete="dontdoit" :readonly="readonly" />
            </div>
          </div>
        </div>

      </div>

      <!------------------------------------------------------------------------------>

      <div class="section summary">
        <h5>Billing Address</h5>
        <div v-if="!order.billing || !order.billing.address1">No billing address</div>
        <div v-else>
            <div v-if="order.billing.first_name">${ order.billing.first_name } ${ order.billing.last_name }</div>
            <div v-if="order.billing.company">${ order.billing.company }</div>
            ${ order.billing.address1 } ${ order.billing.address2 }<br>
            ${ order.billing.city }, ${ order.billing.state } ${ order.billing.zip }<br>
            ${ order.billing.phone }
        </div>
        <div class="field text-right">
            <a v-on:click="editBillingAddress">Edit Billing</a>
          </div>
      </div>

      <!------------------------------------------------------------------------------>

       <!-- <div class="section">
        <h5>Tags</h4>
        <div class="field">
          <input type="text" v-model="newTag" @keyup.enter.native="addTag" placeholder="Add a tag" />
          <ul class="order-tags">
            <li v-for="(tag, i) in order.tags" class="columns nopad">
              <div class="column">
                ${ tag }
              </div>
              <div class="column remove">
                <span v-on:click="removeTag(i)">x</span>
              </div>
            </li>
          </ul>
        </div>
      </div> -->

      <!------------------------------------------------------------------------------>

    </div>

  </div>



  <div class="bottom-actions" v-if="loaded && draft">
    <button class="button alert small" v-on:click="deleteDraft(draft)">Delete</button>
  </div>

</div>

@stop