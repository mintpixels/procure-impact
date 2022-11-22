<template>
  <draggable
    tag="ul"
    :group="{ name: 'g1' }"
    :list="categories"
    item-key="id"
  >
    <template #item="{ element }">
      <li class="category" :class="{ expanded: expanded(element) }">
        <div class="row">
          <span class="toggle" v-if="element.children.length > 0" @click="toggleCategory(element)">
            <span v-if="expanded(element)">-</span>
            <span v-else>+</span>
          </span>
          <span class="toggle" v-else></span>
          <span class="name">
            <div class="parent">{{ element.breadcrumb }}</div>
            <a v-on:click="selectCategory(element)">{{ element.name }}</a>
          </span>
          <span class="products">{{ element.products }}</span>
        </div>
        <product-categories
          v-if="element.children"
          :categories="element.children" 
          @toggle-category="toggleCategory" 
          @select-category="selectCategory" />
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
    expanded(category) {
        return category.expanded;
    },
    selectCategory(category) {
      this.$emit('select-category', category);
    }
  },
  name: "product-categories"
};
</script>
<style scoped>

</style>