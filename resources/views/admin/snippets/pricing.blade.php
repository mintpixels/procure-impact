<div class="columns">
    <div class="column">
    <div class="field">
        <label>Price</label>
        <div class="input-with-label">
        <span>$</span>
        <input type="text" v-model="{{ $price }}" class="currency light" />
        </div>
    </div>
    </div>
    <div class="column">
    <div class="field">
        <label>Sale Price</label>
        <div class="input-with-label">
        <span>$</span>
        <input type="text" v-model="{{ $prices }}.sale" class="currency light" />
        </div>
    </div>
    </div>
    <div class="column">
    <div class="field">
        <label>MSRP</label>
        <div class="input-with-label">
        <span>$</span>
        <input type="text" v-model="{{ $prices }}.msrp" class="currency light" />
        </div>
    </div>
    </div>
    <div class="column">
    <div class="field">
        <label>Cost</label>
        <div class="input-with-label">
        <span>$</span>
        <input type="text" v-model="{{ $cost }}" class="currency light" />
        </div>
    </div>
    </div>
</div>

<!-- <a v-on:click="showDiscounts = !showDiscounts">
    <span v-if="!showDiscounts">Show</span>
    <span v-else>Hide</span>
    Additional Pricing
</a> -->