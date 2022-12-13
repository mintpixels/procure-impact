<template>
  <draggable
    tag="ul"
    :group="{ name: 'g1' }"
    :list="categories"
    item-key="id"
    @end="checkChanged"
  >
    <template #item="{ element }">
      <li class="category" v-if="element.filterMatch && !element.deleted" :class="{ expanded: expanded(element) }">
        <div class="row">
          <span class="toggle" v-if="element.children.length > 0" @click="toggleCategory(element)">
            <span v-if="expanded(element)">-</span>
            <span v-else>+</span>
          </span>
          <span class="toggle" v-else></span>
          <span class="name">
            <div class="parent">{{ element.breadcrumb }}</div>
            <a v-on:click="updateCategory(element)">{{ element.name }}</a>
          </span>
          <span class="products"><a :href="'/admin/products?categoryId=' + element.id">{{ element.products }}</a></span>
          <span class="products">{{ element.nested }}</span>
          <span class="products" v-on:click="showProperties(element)"><span class="clickable">{{ element.properties.length }}</span></span>
          <span class="visible" @click="toggleVisible(element)">
            <span class="yes" v-if="element.visible">Yes</span>
            <span class="no" v-else>No</span>
          </span>
          <span class="actions">
            <i class="fa-regular fa-square-plus" v-on:click="addCategory(element)"></i>
            <i class="fa-regular fa-square-minus delete" v-on:click="showDeleteCategory(element)"></i>
          </span>
        </div>
        <nested-draggable 
          v-if="element.children"
          :categories="element.children" 
          @toggle-visible="toggleVisible" 
          @toggle-category="toggleCategory" 
          @check-changed="checkChanged"
          @update-category="updateCategory" 
          @add-category="addCategory" 
          @delete-category="showDeleteCategory"
          @filters="showFilters"
          @properties="showProperties" />
      </li>
    </template>
  </draggable>
</template>
<script>
import draggable from "vuedraggable";
export default {
  props: {
    categories: {
      required: true,
      type: Array
    }
  },
  components: {
    draggable
  },
  methods: {
    toggleCategory(category) {
        this.$emit('toggle-category', category);
    },
    toggleVisible(category) {
        this.$emit('toggle-visible', category);
    },
    expanded(category) {
        return category.expanded || this.$root.filter.length > 0
    },
    checkChanged() {
      this.$emit('check-changed');
    },
    updateCategory(category) {
      this.$emit('update-category', category);
    },
    addCategory(parent) {
      this.$emit('add-category', parent);
    },
    showDeleteCategory(category) {
      this.$emit('delete-category', category);
    },
    showFilters(category) {
      this.$emit('filters', category);
    },
    showProperties(category) {
      this.$emit('properties', category);
    }
  },
  name: "nested-draggable"
};
</script>
<style scoped>

</style>