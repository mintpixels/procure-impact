<div class="columns">
    <div class="column">
    <div class="field">
        <label>Retail Price</label>
        <div class="input-with-label">
        <span>$</span>
        <input type="text" v-model="{{ $variant }}.price" class="currency light" />
        </div>
    </div>
    </div>
    <div class="column">
    <div class="field">
        <label>Wholesale Price</label>
        <div class="input-with-label">
        <span>$</span>
        <input type="text" v-model="{{ $variant }}.wholesale_price" class="currency light" />
        </div>
    </div>
    </div>
    <div class="column">
    <div class="field">
        <label>MSRP</label>
        <div class="input-with-label">
        <span>$</span>
        <input type="text" v-model="{{ $variant }}.msrp" class="currency light" />
        </div>
    </div>
    </div>
    <div class="column">
    <div class="field">
        <label>Per Case Quantity</label>
        <div class="input-with-label">
            <input type="text" v-model="{{ $variant }}.case_quantity" class="currency light text-right" />
            <span>cnt</span>
        </div>
    </div>
    </div>
    <!-- <div class="column">
    <div class="field">
        <label>Cost</label>
        <div class="input-with-label">
        <span>$</span>
        <input type="text" v-model="{{ $cost }}" class="currency light" />
        </div>
    </div>
    </div> -->
</div>

<!-- <a v-on:click="showDiscounts = !showDiscounts">
    <span v-if="!showDiscounts">Show</span>
    <span v-else>Hide</span>
    Additional Pricing
</a> -->