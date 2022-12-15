@extends('admin.layout')

@section('content')

<div id="product-page" class="padded-content" class="{ noScroll: showSlideIn }" data-product-id="{{ $product->id }}" v-cloak>

  @include('admin.snippets.save-bar')

  <h2 class="with-subtitle">
    ${ product.name }
  </h2>
  <div class="subtitle">
      ${ product.handle }
      <a class="small" target="_blank" :href="'/products/' + product.handle">view</a>
  </div>

  <div class="slide-in" :class="{ show: showSlideIn }">

    <div class="close" v-on:click="showSlideIn = false">x</div>

    <div id="product-history" class="content">

    Today<br>
    05:35pm Order was auto verified System <br>
    05:35pm Order was created Big Commerce

    </div>

  </div>

  <div class="modal" :class="{ show: modalView }">

    <div class="modal-view" :class="{ show: modalView == 'add-image' }">
      <div class="field">
        <label>Upload product images</label>
        <input type="file" id="image-upload" accept=".jpg,.jpeg,.png,.gif" multiple/>
        <div class="actions">
          <button class="primary" :disabled="uploading" v-on:click="uploadImage()">Upload</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'add-video' }">
      <div class="field">
        <label>Add a video</label>
        <input type="text" v-model="newVideo" />
        <div class="actions">
          <button class="primary" v-on:click="addVideo(newVideo)">Add</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'add-price' }">
      <div class="field">
        <label>Add a new price</label>
        <select v-model="newPriceGroup">
          <option value="">All Customers</option>
          <option v-for="group in groups" :value="group.id">${ group.name }</option>
        </select>
        <div class="actions">
          <button class="primary" v-on:click="addPrice">Add</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div class="modal-view image-preview" :class="{ show: modalView == 'image-preview' }" v-if="previewImage">
      <iframe v-if="previewImage.embed" width="100%" :src="youtubeUrl(previewImage.embed)" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
      <img v-else :src="previewImage" />
      <div class="close-preview" v-on:click="closeModal()">x</div>
    </div>

    <div class="modal-view" :class="{ show: modalView == 'edit-option' }">
      <h3>Product Option</h3>
      <div class="field">
        <label>Name</label>
        <input type="text" v-model="optionName" />
      </div>
      <div class="field">
        <label>Values</label>
        <input type="text" v-model="optionValues" />
        <div class="actions">
          <button class="primary" v-on:click="saveOption">Save</button>
          <button class="secondary" v-on:click="closeModal()">Cancel</button>
        </div>
      </div>
    </div>
    
  </div>

  <div class="columns layout">
      
    <div class="primary column">

      <div class="section">
        <div class="field">
          <label>Name</label>
          <input type="text" v-model="product.name" />
        </div>
      </div>

      <div class="section product-images">
        <h5>
          Images / Video
          <a class="small" v-on:click="showModal('add-image')">+ Images</a>
          <!-- <a class="small" v-on:click="showModal('add-video')">+ Video</a> -->
        </h5>
        <div class="images product-images" v-if="product.images">
          <draggable v-model="product.images" item-key="master" @end="imagesSorted">
            <template #item="{element}">
              <div class="image">
                <img v-if="element.embed" :src="youtubeThumbnail(element.embed)" v-on:click="previewImage = element; showModal('image-preview');"/>
                <img v-else :src="element"  v-on:click="previewImage = element; showModal('image-preview');"/>
                <span class="close" v-on:click="removeImage(element.master)">x</span>
              </div>
            </template>
          </draggable>
        </div>
      </div>

      <div class="section">
        <h5>Pricing</h5>

        @include('admin.snippets.pricing', [
          'rules' => 'product.rules',
          'price' => 'product.price',
          'prices' => 'product.prices',
          'cost' => 'product.cost'
        ])

      </div>

      <!-- <div class="variant-toggle"><input type="checkbox" v-model="hasVariants"> This product has multiple variants</div> -->

      

      <div class="variant-section" v-if="hasVariants">

        <div class="variants">

          <ul>
            <li>FDE / 7" / M-lok</li>
            <li>FDE / 7" / Keymod</li>
            <li>FDE / 10" / M-lok</li>
            <li>FDE / 10" / Keymod</li>
            <li>FDE / 12" / M-lok</li>
            <li>FDE / 12" / Keymod</li>
            <!-- <li>FDE / 15" / M-lok</li>
            <li>FDE / 15" / Keymod</li>
            <li>Black / 7" / M-lok</li>
            <li class="active">Black / 7" / Keymod</li>
            <li>Black / 10" / M-lok</li>
            <li>Black / 10" / Keymod</li>
            <li>Black / 12" / M-lok</li>
            <li>Black / 12" / Keymod</li>
            <li>Black / 15" / M-lok</li>
            <li>Black / 15" / Keymod</li>
             -->
          </ul>
          <a>Add Variant</a>
        </div>

        <div class="variant-options">

          <div class="selected-variant">
            Black / 7" / Keymod 
            <a>edit</a>
          </div>
          
          <div class="section">
            <h5>Inventory</h5>

            <div class="columns">
              <div class="column">
                <div class="field">
                  <label>SKU</label>
                  <input type="text" v-model="product.sku" />
                </div>
              </div>
              <div class="column">
                <div class="field">
                  <label>UPC</label>
                  <input type="text" v-model="product.upc" />
                </div>
              </div>
            </div>


            <table class="collapsed hidden" :class="{ show: showInventory }">
              <thead>
                <tr>
                  <th>Location</th>
                  <th>Codes</th>
                  <th class="number text-center">Qty</th>
                  <th class="number text-center">Adjust</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Warehouse</td>
                  <td>
                    <input type="text" v-model="product.location" class="light" />
                  </td>
                  <td class="number">
                    <input type="text" v-model="product.inventory.warehouse" class="text-center light" />
                  </td>
                  <td class="number">
                    <input type="text" v-model="product.adjust.warehouse" class="text-center light" />
                  </td>
                </tr>
                <tr>
                  <td>Showroom</td>
                  <td></td>
                  <td class="number">
                    <input type="text" v-model="product.inventory.showroom" class="text-center light" />
                  </td>
                  <td class="number">
                    <input type="text" v-model="product.adjust.showroom"  class="text-center light" />
                  </td>
                </tr>
                <tr>
                  <td><a :href="'/admin/inventory/hold/' + product.id">Held</a> (8)</td>
                  <td></td>
                  <td class="number">
                    <input type="text" v-model="product.inventory.hold" class="text-center light" />
                  </td>
                  <td class="number">
                    <input type="text" v-model="product.adjust.hold"  class="text-center light" />
                  </td>
                </tr>
                <tr>
                  <td><a :href="'/admin/inventory/incoming/' + product.id">Incoming</a></td>
                  <td></td>
                  <td class="number text-center">
                    100
                  </td>
                  <td class="number">
                    
                  </td>
                </tr>
              </tbody>
            </table>
          
          </div>

          <div class="section">
            <h5>Pricing</h5>

            <input type="checkbox" v-model="inheritPricing" /> Inherit product pricing

            <div class="variant-pricing" :class="{ hidden: inheritPricing }">
              
            </div>

          </div>

        </div>

      </div>

      <div class="section">
        <div class="tab-container">
          <div class="tabs">
            <span :class="{ active: tab == 'short' }" v-on:click="showTab('short')">Description</span>
            <span :class="{ active: tab == 'description' }" v-on:click="showTab('description')">Long Description</span>
            <span :class="{ active: tab == 'specs' }" v-on:click="showTab('specs')">Specs</span>
            <span :class="{ active: tab == 'other' }" v-on:click="showTab('other')">Other</span>
          </div>
          <div class="tab-contents">
            <div class="tab-content" :class="{ show: tab == 'short' }">
              <div id="short_desc" class="wysiwyg large"></div>
            </div>
            <div class="tab-content" :class="{ show: tab == 'description' }">
              <div id="description" class="wysiwyg large"></div>
            </div>
            <div class="tab-content" :class="{ show: tab == 'specs' }">
              <div id="specs" class="wysiwyg large"></div>
            </div>
            <div class="tab-content" :class="{ show: tab == 'other' }">
              <div id="other" class="wysiwyg large"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="section properties">
        <h5>Properties (${ product.properties.length })</h5>
        
        <table class="collapsed" v-if="showProperties">
          <thead>
            <tr>
              <th>Name</th>
              <th>Value</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(property,i) in product.properties">
              <td>
                <span v-if="property.id">${ property.property.name }</span>
                <span v-else>
                  <input type="text" v-model="property.property.name" class="light" @keyup="matchProperties(property)" />
                </span>
              </td>
              <td>
                <input type="text" v-model="property.value" class="light" />
              </td>
              <td class="text-center">
                  <span class="remove" v-on:click="removeProperty(i)">x</span>
                </td>
            </tr>
          </tbody>
        </table>

        <ul class="matches properties" v-if="propertyMatches.length > 0">
          <li v-for="property in propertyMatches" v-on:click="selectProperty(property)">
            <div class="name">${ property.name }</div>
          </li>
        </ul>

        <a v-on:click="addProperty">Add Property</a>
      </div>

      <div class="section">
        <h5>
          Product Handle
          <i class="gen-handle fa-solid fa-arrows-rotate" v-on:click="clearHandle"></i>
        </h5>

        <div class="field">
          <input type="text" v-model="product.handle" placeholder="[auto generate]"/>
        </div>
      </div>

    </div>

    <div class="secondary column">

      <div class="section">
        <h5>Product Status</h4>
        <div class="field">
          <select v-model="product.status">
            <option value="active">Active</option>
            <option value="draft">Draft</option>
          </select>
        </div>

        <!-- <div class="field text-right">
          <a v-on:click="showHistory()">View History</a>
        </div> -->
      </div>

      <div class="section">
        <h5>Product Information</h4>

        <div class="field">
          <label>Product Type</label>
          <div class="input-bar small no-button">
              <input type="text" v-model="productType" @keyup="matchTypes()"/>
              <span class="clear-input" v-on:click="productType = '';setType(undefined)">
                <span v-if="productType">x</span>
              </span>
          </div>
          
          <ul class="matches" v-if="productType && (!product.type || product.type.name != productType)">
            <li v-if="typeMatches.length == 0" v-on:click="setType({ name: productType })">
              <div class="name">Add type <b>${ productType }</b></div>
            </li>
            <li v-else v-for="type in typeMatches" v-on:click="setType(type)">
              <div class="name">${ type.name }</div>
            </li>
          </ul>
        </div>

        <!-- <div class="field">
          <label>Brand</label>
          <input type="text" v-model="product.brand" />
        </div> -->

        <div class="field">
          <label>Categories</label>
          <input type="text" v-model="categorySearch" @keyup="matchCategories()" placeholder="Search for categories" />
          <ul class="matches" v-if="categoryMatches.length > 0">
            <li v-for="category in categoryMatches" v-on:click="addCategory(category)">
              <div class="name">${ category.name }</div>
              <div class="breadcrumb">${ category.breadcrumb }</div>    
            </li>
          </ul>
          <ul class="product-categories">
            <li v-for="(category, i) in product.categories" class="columns nopad">
              <div class="column">
                <div class="name">${ category.name }</div>
                <div class="breadcrumb">${ category.breadcrumb }</div>
              </div>
              <div class="column remove">
                <span v-on:click="removeCategory(i)">x</span>
              </div>
              
            </li>
          </ul>
        </div>

      </div>
      
      <div class="section">
        <h5>Tags</h4>
        <div class="field">
          <input type="text" v-model="newTag" @keyup.enter.native="addTag" placeholder="Add a tag" />
          <ul class="product-tags">
            <li v-for="(tag, i) in product.tags" class="columns nopad">
              <div class="column">
                ${ tag }
              </div>
              <div class="column remove">
                <span v-on:click="removeTag(i)">x</span>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- <div class="section">
        <h5>Options</h4>
        <div v-if="product.options.length == 0">No options.</div>
        <div v-else>
          <ul class="product-options">
            <li v-for="(option, i) in product.options" class="columns nopad">
              <div class="column clickable" v-on:click="editOption(i)">
                <div class="name">${ option.name }</div>
                <div class="option-values">
                  <span v-for="value in option.values">${ value }</span>
                </div>
              </div>
              <div class="column remove">
                <span v-on:click="removeOption(i)">x</span>
              </div>
            </li>
          </ul>
        </div>
        <div class="field text-right">
          <a v-on:click="editOption()">Add Option</a>
        </div>
      </div> -->

      <!-- <div class="section">
        <h5>Related Products</h4>
        <div class="field">
          <input type="text" v-model="productSearch" @keyup="matchProducts()" placeholder="Search for products" />
          <ul class="matches" v-if="productMatches.length > 0">
            <li v-for="prod in productMatches" v-on:click="addRelated(prod)">
              <div class="name">${ prod.name }</div>
              <div class="breadcrumb">${ prod.sku }</div>    
            </li>
          </ul>
          <ul class="product-related">
            <li v-for="(related, i) in product.related" class="columns nopad">
              <div class="column">
                <div class="name"><a target="_blank" :href="'/admin/products/' + related.product.id">${ related.product.name }</a></div>
                <div class="sku">${ related.product.sku }</div>
              </div>
              <div class="column remove">
                <span v-on:click="removeProduct(i)">x</span>
              </div>
              
            </li>
          </ul>
        </div>
      </div>

      <div class="section">
        <h5>Suggested Products</h4>
        <div class="field">
          <input type="text" v-model="addonSearch" @keyup="matchAddons()" placeholder="Search for products" />
          <ul class="matches" v-if="addonMatches.length > 0">
            <li v-for="prod in addonMatches" v-on:click="addAddon(prod)">
              <div class="name">${ prod.name }</div>
              <div class="breadcrumb">${ prod.sku }</div>    
            </li>
          </ul>
          <ul class="product-related">
            <li v-for="(addon, i) in product.addons" class="columns nopad">
              <div class="column">
                <div class="name"><a target="_blank" :href="'/admin/products/' + addon.product.id">${ addon.product.name }</a></div>
                <div class="sku">${ addon.product.sku }</div>
              </div>
              <div class="column remove">
                <span v-on:click="removeAddon(i)">x</span>
              </div>
              
            </li>
          </ul>
        </div>
      </div> -->

      <div class="section" v-if="adjustments.length > 0">
        <h3>Inventory Adjustments</h3>
        <div class="timeline" style="padding-top:5px;margin-bottom:0;">
            <div v-for="(adjustment, i) in adjustments">
              <div class="timeline-item" style="margin-bottom:0">
                  <span class="timeline-time">${ formatDateTime(adjustment.created_at) }</span>
                  <span class="timeline-source" style="margin-left:5px">${ adjustment.user.name }</span>
                  <span class="toggle-changes">
                      <i class="fas fa-caret-right"></i>
                      <i class="fas fa-caret-down"></i>
                  </span>
                  <div class="timeline-changes">
                    <div class="timeline-change">
                      <span class="timeline-field">Warehouse</span>
                      <span class="timeline-value old">${ adjustment.prev_inventory.warehouse }</span>
                      <span class="timeline-value">${ adjustment.new_inventory.warehouse }</span>
                    </div>
                  </div>
              </div>
            </div>
        </div>
      </div>

      <div class="section" v-if="timeline.length > 0">
        <h3>Timeline</h3>
        <div class="timeline">
            <div v-for="(item, i) in timeline">
              <div class="timeline-date" v-if="i == 0 || item.sdate != timeline[i-1].sdate">${ item.sdate }</div>
              <div class="timeline-item">
                  <span class="timeline-time">${ item.time }</span>
                  ${ item.summary }
                  <span class="timeline-source">${ item.source }</span>
                  
                  <span v-if="item.changes.length > 0 || item.note">
                      <span class="toggle-changes">
                          <i class="fas fa-caret-right"></i>
                          <i class="fas fa-caret-down"></i>
                      </span>

                      <div class="timeline-changes">
                          <div class="timeline-note">${ item.note }</div>
                          
                          <div class="timeline-change" v-for="change in item.changes">
                              <span class="timeline-field">${ change.field }</span>
                              <span class="timeline-value old">${ change.old_value }</span>
                              <span class="timeline-value">${ change.new_value }</span>
                          </div>
                      </div>
                  </span>
              </div>
            </div>
        </div>
      </div>

      <!-- <div class="section">
        <h5>Additional</h4>
        <div v-if="product.orders_count > 0">
          <a :href="'/admin/orders?product_id=' + product.id">
            ${ formatNumber(product.orders_count) } 
            ${ product.orders_count == 1 ? 'order' : 'orders'}
          </a>
        </div>
        upsell products, location priority, related products
      </div> -->
<!-- 
      <div class="section">
        <h5>Additional</h4>
        upsell products, location priority, related products
      </div>

      <div class="section">
        <h5>Sales</h4>
        <div class="field">
          
        </div>
      </div> -->

    </div>

  </div>

  <div class="actions footer">
    <button v-on:click="copy">Copy Product</button>
    <button v-if="product.order_items_count == 0" v-on:click="deleteProduct" class="alert">Delete Product</button>
    <button v-else v-on:click="archiveProduct" class="alert">Archive Product</button>
  </div>

</div>

@stop
