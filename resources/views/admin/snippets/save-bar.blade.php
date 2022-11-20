<div id="changed" :class="{ show: changed }">
    <div class="container">
        <span>Unsaved Changes</span>

        <button class="save" v-on:click="save()">Save</button>
        <button class="discard" v-on:click="discard()">Discard</button>
    </div>
    <span class="error" v-if="error">${ error }</span>
</div>
