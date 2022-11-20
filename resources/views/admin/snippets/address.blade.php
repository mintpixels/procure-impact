<div class="columns">
    <div class="field column">
        <label>First Name</label>
        <input type="text" v-model="{{ $address }}.first_name" autocomplete="dontdoit"/>
    </div>
    <div class="field column">
        <label>Last Name</label>
        <input type="text" v-model="{{ $address }}.last_name" autocomplete="dontdoit"/>
    </div>
</div>
<div class="columns">
    <div class="field column">
        <label>Company</label>
        <input type="text" v-model="{{ $address }}.company" autocomplete="dontdoit"/>
    </div>
    <div class="field column">
        <label>Phone</label>
        <input type="text" v-model="{{ $address }}.phone" autocomplete="dontdoit"/>
    </div>
</div>
<div class="columns">
    <div class="field column">
        <label>Address</label>
        <input type="text" v-model="{{ $address }}.address1" autocomplete="dontdoit" />
    </div>
    <div class="field column">
        <label>Apt</label>
        <input type="text" v-model="{{ $address }}.address2" autocomplete="dontdoit"/>
    </div>
</div>
<div class="columns">
    <div class="field column">
        <label>City</label>
        <input type="text" v-model="{{ $address }}.city" autocomplete="dontdoit"/>
    </div>
    <div class="field column">
        <label>State</label>
        <select v-model="{{ $address }}.state">
            @include('admin.snippets.states')
        </select>
    </div>
    <div class="field column">
        <label>Zip</label>
        <input type="text" v-model="{{ $address }}.zip" autocomplete="dontdoit"/>
    </div>
</div>