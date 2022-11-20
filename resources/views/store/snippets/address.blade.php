<div class="columns">
    <div class="column field">
        <label>First Name</label>
        <input type="text" name="first_name" v-model="{{ $address }}.first_name" @change="{{ $onChange }}({{ $shipment }})">
    </div>
    <div class="column field">
        <label>Last Name</label>
        <input type="text" name="last_name" v-model="{{ $address }}.last_name" @change="{{ $onChange }}({{ $shipment }})">
    </div>
</div>

<div class="field">
    <label>Company <span class="optional">(Optional)</span></label>
    <input type="text" name="company" v-model="{{ $address }}.company" @change="{{ $onChange }}({{ $shipment }})">
</div>

<div class="columns">
    <div class="column field">
        <label>Address</label>
        <input type="text" name="address1" v-model="{{ $address }}.address1" @change="{{ $onChange }}({{ $shipment }})">
    </div>
    <div class="column field">
        <label>Apartment <span class="optional">(Optional)</span></label>
        <input type="text" name="address2" v-model="{{ $address }}.address2" @change="{{ $onChange }}({{ $shipment }})">
    </div>
</div>

<div class="columns">
    <div class="column field">
        <label>City</label>
        <input type="text" name="city" v-model="{{ $address }}.city" @change="{{ $onChange }}({{ $shipment }})">
    </div>
    <div class="column field">
        <label>State</label>
        <select name="state" v-model="{{ $address }}.state" @change="{{ $onChange }}({{ $shipment }})">
            @include('store.snippets.states')
        </select>
    </div>
</div>

<div class="columns">
    <div class="column field">
        <label>Zip Code</label>
        <input name="zip" type="text" v-model="{{ $address }}.zip" @change="{{ $onChange }}({{ $shipment }})">
    </div>
    <div class="column field">
        <label>Phone</label>
        <input name="phone" type="text" v-model="{{ $address }}.phone" @change="{{ $onChange }}({{ $shipment }})">
    </div>
</div>