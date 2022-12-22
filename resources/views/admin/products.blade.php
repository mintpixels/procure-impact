@extends('admin.layout')

@section('content')

<div id="products-page" class="padded-content" v-cloak>

  @include('admin.snippets.save-bar')

  <div class="modal" :class="{ show: modalView }">

    <div class="modal-view category-view" :class="{ show: modalView == 'select-category' }">
      <div class="field">
        <label>Filter by category</label>
        <div class="categories overflow">
          <product-categories
            :categories="categories" 
            item-key="id" 
            @toggle-category="toggleCategory"
            @select-category="selectCategory"
            />
        </div>
        <div class="actions">
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'add-categories' }">
      <div class="field">
        <label>
          Add
          <b v-if="categoryEditList.length == 1">${ categoryEditList[0].name }</b>
          <b v-if="categoryEditList.length > 1">${ categoryEditList.length } categories</b>
           to ${ numChecked} product(s)</label>
        <input type="text" placeholder="Filter categories" v-model="categoryFilter" @keyup="updateCategoryFilter"/>
        <ul class="tags overflow">
          <li v-for="category in filteredCategories" class="clickable" :class="{ selected: categoryEditList.indexOf(category) >= 0 }" v-on:click="toggleCategoryChoice(category)">
            ${ category.name }
            <div class="path">${ category.path }</div>
          </li>
        </ul>
        <div class="actions">
          <button v-on:click="updateCategories('add')">Add</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>


    <div class="modal-view" :class="{ show: modalView == 'remove-categories' }">
      <div class="field">
        <label>
          Remove
          <b v-if="categoryEditList.length == 1">${ categoryEditList[0].name }</b>
          <b v-if="categoryEditList.length > 1">${ categoryEditList.length } categories</b>
           from ${ numChecked} product(s)
        </label>
        <input type="text" placeholder="Filter categories" v-model="categoryFilter" @keyup="updateCategoryFilter"/>
        <ul class="tags overflow">
          <li v-for="category in filteredCategories" class="clickable" :class="{ selected: categoryEditList.indexOf(category) >= 0 }" v-on:click="toggleCategoryChoice(category)">
            ${ category.name }
            <div class="path">${ category.path }</div>
          </li>
        </ul>
        <div class="actions">
          <button v-on:click="updateCategories('remove')">Remove</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'select-tag' }">
      <div class="field">
        <label>Filter by tag</label>
        <div class="filter-exclude">
          <input type="checkbox" v-model="filterExclude" /> Does not have tag
        </div>
        <ul class="tags overflow">
          <li v-for="tag in tags" class="clickable" v-on:click="filterTag(tag)">${ tag }</li>
        </ul>
        <div class="actions">
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'select-brand' }">
      <div class="field">
        <label>Filter by brand</label>
        <ul class="tags overflow">
          <li v-for="brand in brands" class="clickable" v-on:click="filterBrand(brand)">${ brand.name }</li>
        </ul>
        <div class="actions">
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'add-tags' }">
      <div class="field">
        <label>
          Add
          <b v-if="tagEditList.length == 1">${ tagEditList[0] }</b>
          <b v-if="tagEditList.length > 1">${ tagEditList.length } tags</b>
           to ${ numChecked} product(s)</label>
        <ul class="tags overflow">
          <li v-for="tag in allTags" class="clickable" :class="{ selected: tagEditList.indexOf(tag) >= 0 }" v-on:click="toggleTag(tag)">${ tag }</li>
        </ul>
        <div class="actions">
          <button v-on:click="updateTags('add')">Add</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'remove-tags' }">
      <div class="field">
        <label>
          Remove
          <b v-if="tagEditList.length == 1">${ tagEditList[0] }</b>
          <b v-if="tagEditList.length > 1">${ tagEditList.length } tags</b>
           from ${ numChecked} product(s)</label>
        <ul class="tags overflow">
          <li v-for="tag in filteredTags" class="clickable" :class="{ selected: tagEditList.indexOf(tag) >= 0 }" v-on:click="toggleTag(tag)">${ tag }</li>
        </ul>
        <div class="actions">
          <button v-on:click="updateTags('remove')">Remove</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'add-property' }">
      <div class="field">
        <label>
          Add
          <b v-if="propertyEditList.length == 1">${ propertyEditList[0].name }</b>
          to ${ numChecked} product(s)</label>
        <ul class="tags overflow" style="margin-bottom:10px">
          <li v-for="property in properties" class="clickable" :class="{ selected: propertyEditList.indexOf(property) >= 0 }" v-on:click="selectProperty(property)">${ property.name }</li>
        </ul>

        <label>Property Value</lable>
        <input type="text" type="propertyValue" v-model="propertyValue" />
        <div class="actions">
          <button v-on:click="updateProperties('add')">Add</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'remove-property' }">
      <div class="field">
        <label>
          Remove
          <b v-if="propertyEditList.length == 1">${ propertyEditList[0].name }</b>
          from ${ numChecked} product(s)</label>
        <ul class="tags overflow" style="margin-bottom:10px">
          <li v-for="property in properties" class="clickable" :class="{ selected: propertyEditList.indexOf(property) >= 0 }" v-on:click="selectProperty(property)">${ property.name }</li>
        </ul>

        <div class="actions">
          <button v-on:click="updateProperties('remove')">Remove</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'add-rules' }">
      <div class="field">
        <label>
          Add
          <b v-if="ruleEditList.length == 1">${ ruleEditList[0].name }</b>
          <b v-if="ruleEditList.length > 1">${ ruleEditList.length } rules</b>
           to ${ numChecked} product(s)</label>
        <ul class="tags overflow">
          <li v-for="rule in rules" class="clickable" :class="{ selected: ruleEditList.indexOf(rule) >= 0 }" v-on:click="toggleRule(rule)">${ rule.name }</li>
        </ul>
        <div class="actions">
          <button v-on:click="updateRules('add')">Add</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'remove-rules' }">
      <div class="field">
        <label>
          Remove
          <b v-if="ruleEditList.length == 1">${ ruleEditList[0].name }</b>
          <b v-if="ruleEditList.length > 1">${ ruleEditList.length } rules</b>
           from ${ numChecked} product(s)
        </label>
        <ul class="tags overflow">
          <li v-for="rule in rules" class="clickable" :class="{ selected: ruleEditList.indexOf(rule) >= 0 }" v-on:click="toggleRule(rule)">${ rule.name }</li>
        </ul>
        <div class="actions">
          <button v-on:click="updateRules('remove')">Remove</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

  </div>
  

  <h1>
    Products
    <a class="small" href="/admin/products/create">Add Product</a>
  </h1>

  <div class="filter-bar">
    <div class="input-bar">
        <input type="text" v-model="search" placeholder="Search Products..." @keyup="searchUpdated" />
        <span class="clear-input" v-on:click="search = ''; searchProducts()">
          <span v-if="search">x</span>
        </span>
        <button class="button" v-on:click="searchProducts">Search</button>
    </div>

    <div class="filters">

      <span class="actions-menu" v-if="numChecked > 0">
        <span class="filter action actions-trigger">
          <i class="fa-solid fa-bars"></i>
        </span>

        <div class="menu-items">
          <ul>
            <li v-on:click="categoryEditList = [];categoryFilter = '';showModal('add-categories')">
              Add Categories
            </li>

            <li v-on:click="showRemoveCategories">
              Remove Categories
            </li>

            <li v-on:click="tagEditList = [];showModal('add-tags')">
              Add Tags
            </li>

            <li v-on:click="showRemoveTags">
              Remove Tags
            </li>

            <li v-on:click="propertyEditList = [];propertyValue='';showModal('add-property')">
              Add Property
            </li>

            <li v-on:click="propertyEditList = [];propertyValue='';showModal('remove-property')">
              Remove Property
            </li>


            <li v-on:click="showRemoveTags">
              Remove Tags
            </li>

            <li v-on:click="ruleEditList = [];showModal('add-rules')">
              Price Rule
            </li>
            <li v-on:click="showRemoveRules">
              Remove Price Rule
            </li>
          </ul>
        </div>
      </span>

      <select v-model="status" @change="statusChanged">
        <option value="">All</option>
        <option value="Draft">Draft</option>
        <option value="Live">Live</option>
      </select>
      
      <span class="filter" v-if="priceRule">
        Price Rule: <b>${ priceRule.name }</b>
        <span class="remove" v-on:click="removePriceRule">x</span>
      </span>

      <span class="filter" v-if="property">
        Property: <b>${ property.name }</b>
        <span class="remove" v-on:click="removeProperty">x</span>
      </span>

      <span class="filter" v-if="values && values.length > 0">
        Values: <b>${ values }</b>
        <span class="remove" v-on:click="removeValues">x</span>
      </span>

      <span class="filter" v-if="category">
        Category: <b>${ category.name }</b>
        <span class="remove" v-on:click="removeCategory">x</span>
      </span>
      
      <!-- <span class="filter clickable" v-else v-on:click="showModal('select-category')">
        All Categories
      </span> -->

      <!-- <span class="filter" v-if="property">
        Property: <b>${ property.name }</b>
        <span class="remove" v-on:click="removeProperty">x</span>
      </span>
      <span class="filter clickable" v-on:click="showModal('select-category')">
        All Properties
      </span> -->

      <span class="filter" v-if="brand">
        Brand: <b>${ brand.name }</b>
        <span class="remove" v-on:click="removeBrand()">x</span>
      </span>
      <span class="filter clickable" v-else v-on:click="showModal('select-brand')">
        All Brands
      </span>

      <!-- <span class="filter" v-if="tag">
        Tag: <b>${ tag }</b>
        <span v-if="filterExclude"> (does not have)</span>
        <span class="remove" v-on:click="removeTag">x</span>
      </span>
      <span class="filter clickable" v-else v-on:click="filterExclude = false; showModal('select-tag')">
        All Tags
      </span> -->

      <span class="filter" v-if="activeSearch">
        Search: <b>${ activeSearch }</b>
        <span class="remove" v-on:click="search = ''; searchProducts()">x</span>
      </span>


      <!-- <span class="filter action" v-if="numChecked > 0">
        Add Property
      </span> -->

    </div>
  </div>

  <div class="section min-width">
    <div class="product-list" v-if="loaded">
      <img src="/img/loading.gif" class="loading" v-if="searching"/>
      <table>
        <thead>
          <tr :class="{ checked: numChecked > 0 }">
            <!-- <th>
              <input type="checkbox" v-model="checkAll" v-on:change="toggleChecks" />
            </th> -->
            <th></th>
            <th v-on:click="sortTable('name')" class="clickable">Name</th>
            <th>Brand</th>
            <th class="clickable text-center" v-on:click="sortTable('category_map_count')">Categories</th>
            <th class="clickable text-center">Variants</th>
            <th class="text-right">Price</th>
            <th class="text-center">Status</th>
          </tr>
        </head>
        <tbody>
          <tr class="product-item" v-for="product in products" :class="{ checked: product.checked }">
            <!-- <td class="text-center">
              <input type="checkbox" class="product-check" v-model="product.checked" v-on:change="toggleCheck"/>
            </td> -->
            
            <td class="image">
              <img v-if="product.thumbnail" :src="product.thumbnail.indexOf('http') == 0 ? product.thumbnail : '{{ env('AWS_CDN_PRODUCTS_PATH') }}' + product.thumbnail" />
            </td>
            <td>
              <a :href="'/admin/products/' + product.id">${ product.name }</a>
            </td>
            <td>${ product.brand.name }</td>
            <td class="text-center">${ product.category_map_count }</td>
            <td class="text-center">${ product.variants.length }</td>
            <td class="text-right">${ formatMoney(product.variants[0].price) }</td>
            <td class="text-center">
              <span class="live" v-if="product.published_at">Live</span>
              <span class="draft" v-else>Draft</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@stop

