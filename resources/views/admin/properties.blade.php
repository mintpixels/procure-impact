@extends('admin.layout')

@section('content')

<div id="properties-page" class="padded-content" v-cloak>

  @include('admin.snippets.save-bar')

  <h1>
    Properties
    <a class="small"><i class="fa-regular fa-square-plus" v-on:click="addProperty()"></i></a>
  </h1>

  <div class="modal" :class="{ show: modalView }">

    <div class="modal-view" v-if="editProperty" :class="{ show: modalView == 'update-property' }">

      <h3 v-if="editProperty.id">Update Property</h3>
      <h3 v-else>Add Property</h3>
      <div class="field">
        <label>Name</label>
        <input type="text" v-model="editProperty.name" />
      </div>

      <div class="actions">
        <button class="primary" v-on:click="saveProperty()">Save</button>
        <button class="secondary" v-on:click="closeModal()">Cancel</button>
      </div>

    </div>

    <div class="modal-view" :class="{ show: modalView == 'property-values' }" v-if="activeProperty">
      <div class="field">
        <label>Values for <b>${ activeProperty.name }</b></label>
        <div class="input-bar thin" style="margin-bottom:10px">
          <input type="text" v-model="newValue" />
          <span class="clear-input" v-on:click="newValue = '';">
            <span v-if="newValue">x</span>
          </span>
          <button class="button" v-on:click="addValue">Add</button>
      </div>
        <ul class="tags overflow">
          <li v-for="v in valueList">
            ${ v }
            <i class="fa fa-close remove-property clickable" v-on:click="removeValue(v)"></i>
          </li>
        </ul>
        <div class="actions">
          <button v-on:click="saveValues()">Save</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

  </div>


  <div class="filter-bar">
    <div class="input-bar">
        <input type="text" v-model="search" placeholder="Search Properties..." @keyup="searchUpdated" />
        <span class="clear-input" v-on:click="search = ''; searchProperties()">
          <span v-if="search">x</span>
        </span>
        <button class="button" v-on:click="searchProperties">Search</button>
    </div>
  </div>

  <div class="section min-width">
    <div class="product-list" v-if="loaded">
      <img src="/img/loading.gif" class="loading" v-if="searching"/>
      <table>
        <thead>
          <tr :class="{ checked: numChecked > 0 }">
            <!-- <th class="text-center" style="width:50px">
              <input type="checkbox" v-model="checkAll" v-on:change="toggleChecks" />
            </th> -->
            <th>Name</th>
            <th class="text-center">Filter</th>
            <th class="text-center">PDP</th>
            <th class="text-center">Values</th>
            <th class="text-center">Products</th>
            <th></th>
          </tr>
        </head>
        <tbody>
          <tr class="product-item" v-for="property in properties" :class="{ checked: property.checked, deleted: property.deleted }">
            <!-- <td class="text-center">
              <input type="checkbox" class="property-check" v-model="property.checked" v-on:change="toggleCheck"/>
            </td> -->
            <td>
              <a v-on:click="updateProperty(property)">${ property.name }</a>
            </td>
            <td class="text-center clickable" v-on:click="toggleFilter(property)">${ property.filter ? 'Yes' : 'No' }</td>
            <td class="text-center clickable" v-on:click="togglePdp(property)">${ property.pdp ? 'Yes' : 'No' }</td>
            <td class="text-center">
              <a v-if="property.id" v-on:click="showValues(property)">${ property.values.length }</a>  
              <span v-else>0</span>
            </td>
            <td class="text-center">
              <a :href="'/admin/products?property=' + property.id">${ property.products_count }</a>  
            </td>
            <td>
              <i class="fa-regular fa-square-minus delete clickable" v-on:click="deleteProperty(property)"></i>
            </td>
          </tr>


        </tbody>
      </table>
    </div>
  </div>
</div>

@stop

