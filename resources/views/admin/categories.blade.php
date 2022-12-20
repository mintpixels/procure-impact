@extends('admin.layout')

@section('content')

<div id="categories-page" class="padded-content" v-cloak>

  @include('admin.snippets.save-bar')

  <div class="modal" :class="{ show: modalView }">

    <div class="modal-view" v-if="editCategory" :class="{ show: modalView == 'update-category' }">

      <h3 v-if="editCategory.id">Update Category</h3>
      <h3 v-else>Add Category</h3>
      <div v-if="editCategory.id" class="category-path">
        <a target="_blank" :href="'/categories/' + editCategory.path"><i class="fal fa-external-link"></i> ${ editCategory.path }</a>
      </div>
      <div class="field">
        <label>Name</label>
        <input type="text" v-model="editCategory.name" />
      </div>
      <div class="field">
        <label>Handle</label>
        <input type="text" v-model="editCategory.handle" placeholder="[auto generate]" />
      </div>

      <div class="actions">
        <button class="primary" v-on:click="saveCategory()">Save</button>
        <button class="secondary" v-on:click="closeModal()">Cancel</button>
      </div>
      
    </div>

    <div class="modal-view" v-if="activeCategory" :class="{ show: modalView == 'delete-category' }">
      
      <h3>Delete Category</h3>
      <p>Delete category <b>${ activeCategory.name }</b>?</p>

      <div class="actions">
        <button class="primary" v-on:click="deleteCategory()">Delete</button>
        <button class="secondary" v-on:click="closeModal()">Cancel</button>
      </div>
      
    </div>

    <div class="modal-view" v-if="activeCategory" :class="{ show: modalView == 'show-filters' }">
      
      <h3>Search filters</h3>
      <p>Filtering options for <b>${ activeCategory.name }</b></p>

      <input type="text" v-model="filterText" placeholder="Filter properties" />
      <div class="filters overflow">
        <draggable v-model="filters" item-key="master" @end="filtersSorted">
          <template #item="{element}">
            <div class="row" v-if="filterText == '' || (element.name && element.name.toLowerCase().indexOf(filterText.toLowerCase()) >= 0)">
              <input type="checkbox" v-model="element.included" />
              ${ element.name }
            </div>
          </template>
        </draggable>
      </div>

      <div class="actions">
        <button class="primary" v-on:click="saveFilters()">Save</button>
        <button class="secondary" v-on:click="closeModal()">Cancel</button>
      </div>
      
    </div>

    <div class="modal-view" v-if="activeCategory" :class="{ show: modalView == 'show-properties' }">
      
      <h3>Category Properties</h3>
      <p>Property values for <b>${ activeCategory.name }</b></p>

      <div class="columns properties">
        <div class="column">
          <h5>Property</h5>
          <ul>
            <li v-for="property in properties" :class="{ selected: selectedValues(property).length > 0, active: property.id == activeProperty.id }" v-on:click="activeProperty = property;">
            ${ property.name }
            </li>
          </ul>
        </div>
        <div class="column">
          <h5>Values</h5>
          <ul>
            <li v-for="v in activeProperty.values" :class="{ selected: v.selected }" v-on:click="v.selected = !v.selected">
              ${ v.value }
            </li>
          </ul>
        </div>
      </div>

      <div class="actions">
        <button class="primary" v-on:click="saveFilters()">Save</button>
        <button class="secondary" v-on:click="closeModal()">Cancel</button>
      </div>
      
    </div>

  </div>

  <h1>
    Categories
  </h1>

  <div class="filter-bar">
    <div class="input-bar small no-button">
      <input type="text" placeholder="Filter categories..." v-model="filter" @keyup="filterUpdated()"/>
      <span class="clear-input" v-on:click="filter = '';filterUpdated()">
        <span v-if="filter">x</span>
      </span>
    </div>
  </div>


  <div class="section">
    <div class="categories">
      <div class="row">
        <span class="toggle">
          <i class="fa-regular fa-square-plus" v-on:click="addCategory()"></i>
        </span>
        <span class="name">Name</span>
        <span class="products"># Products</span>
        <span class="products"># Nested</span>
        <span class="products"># Properties</span>
        <span class="visible">Visible?</span>
        <span class="actions"></span>
      </div>

      <nested-draggable 
        :categories="categories" 
        item-key="id" 
        @toggle-visible="toggleVisible"
        @toggle-category="toggleCategory"
        @check-changed="checkChanged"
        @update-category="updateCategory"
        @add-category="addCategory"
        @delete-category="showDeleteCategory"
        @filters="showFilters"
        @properties="showProperties"  />
    </div>
  </div>

</div>

@stop

