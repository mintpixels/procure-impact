<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\BuyerController as AdminBuyerController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;

use App\Http\Controllers\Store\PageController as StorePageController;
use App\Http\Controllers\Store\DataController as StoreDataController;
use App\Http\Controllers\Store\SearchController as StoreSearchController;
use App\Http\Controllers\Store\ProductController as StoreProductController;
use App\Http\Controllers\Store\CategoryController as StoreCategoryController;
use App\Http\Controllers\Store\CartController as StoreCartController;
use App\Http\Controllers\Store\CheckoutController as StoreCheckoutController;
use App\Http\Controllers\Store\AccountController as StoreAccountController;
use App\Http\Controllers\Store\BrandController as StoreBrandController;

Route::get('admin/login', [AdminAuthController::class, 'loginView'])->name('login');
Route::post('admin/login', [AdminAuthController::class, 'attemptLogin']);
Route::get('admin/logout', [AdminAuthController::class, 'logout']);


Route::group(['prefix' => 'account'], function() {
    Route::get('login', [StoreAccountController::class, 'loginView'])->name('store.login');
    Route::post('login', [StoreAccountController::class, 'login']);
    Route::get('logout', [StoreAccountController::class, 'logout'])->name('store.logout');
    Route::get('forgot', [StoreAccountController::class, 'forgotView']);
    Route::post('forgot', [StoreAccountController::class, 'sendReset']);
    Route::get('reset/{code}', [StoreAccountController::class, 'resetView']);
    Route::post('reset', [StoreAccountController::class, 'reset']);
    Route::get('register', [StoreAccountController::class, 'registerView']);
    Route::post('register', [StoreAccountController::class, 'register']);
});

Route::group(['middleware' => 'auth.customer'], function () 
{
    Route::get('/', [StorePageController::class, 'homeView']);
    Route::get('data/home', [StorePageController::class, 'home']);
    Route::get('data/common', [StorePageController::class, 'common']);
    

    // Route::get('/', [StorePageController::class, 'home']);
    Route::get('products/{product:handle}', [StoreProductController::class, 'view']);
    Route::get('categories/{path}/{path2?}/{path3?}/{path4?}', [StoreCategoryController::class, 'view']);
    Route::get('search', [StoreSearchController::class, 'home']);
    Route::get('pages/dealer-transfer-network', [StorePageController::class, 'viewDtn']);
    Route::get('pages/{page:handle}', [StorePageController::class, 'viewPage']);
    Route::get('unsubscribe', [StorePageController::class, 'unsubscribe']);
    Route::post('products/{product}/reviews', [StoreProductController::class, 'addReview']);
    Route::get('brands/{handle}', [StoreBrandController::class, 'view']);

    // Route::get('data/common', [StoreDataController::class, 'common']);
    // Route::get('data/featured', [StoreDataController::class, 'featured']);
    Route::get('data/products/{product}', [StoreProductController::class, 'product']);
    Route::get('data/products/{product}/reviews', [StoreProductController::class, 'reviews']);
    Route::get('data/search/suggestions', [StoreSearchController::class, 'suggestions']);
    Route::get('data/search', [StoreSearchController::class, 'search']);
    Route::get('data/brands/{handle}', [StoreBrandController::class, 'brand']);

    Route::group(['prefix' => 'cart'], function () {
        Route::get('json', [StoreCartController::class, 'get']);
        Route::post('items', [StoreCartController::class, 'addItem']);
        Route::post('update', [StoreCartController::class, 'updateItem']);
        Route::post('checkout', [StoreCartController::class, 'checkout']);
        Route::post('remove', [StoreCartController::class, 'removeItem']);
        Route::post('dealer', [StoreCartController::class, 'setDealer']);
        Route::get('recover/{id}', [StoreCartController::class, 'recoverCart']);
    });
    

    Route::get('checkout', [StoreCheckoutController::class, 'view']);
    Route::get('checkout/{checkout:guid}', [StoreCheckoutController::class, 'viewById']);
    Route::get('data/checkout', [StoreCheckoutController::class, 'checkout']);
    Route::get('data/checkout/{checkout:guid}', [StoreCheckoutController::class, 'checkout']);
    Route::post('data/checkout/{checkout:guid}/shipments/methods', [StoreCheckoutController::class, 'getAllMethods']);
    Route::post('data/checkout/{checkout:guid}/shipments/{index}/methods', [StoreCheckoutController::class, 'getMethods']);
    Route::post('data/checkout/{checkout:guid}/shipments/{index}/method', [StoreCheckoutController::class, 'saveMethod']);
    Route::post('checkout', [StoreCheckoutController::class, 'updateCheckout']);
    Route::post('checkout/{checkout:guid}/signout', [StoreCheckoutController::class, 'signout']);
    Route::post('checkout/{checkout:guid}/signin', [StoreCheckoutController::class, 'signin']);
    Route::post('checkout/{checkout:guid}/customer', [StoreCheckoutController::class, 'saveCustomer']);
    Route::post('checkout/{checkout:guid}/pickup', [StoreCheckoutController::class, 'savePickup']);
    Route::post('checkout/{checkout:guid}/usebilling', [StoreCheckoutController::class, 'saveUseBilling']);
    Route::post('checkout/{checkout:guid}/insurance', [StoreCheckoutController::class, 'saveInsurance']);
    Route::post('checkout/{checkout:guid}/billing', [StoreCheckoutController::class, 'saveBilling']);
    Route::post('checkout/{checkout:guid}/tax', [StoreCheckoutController::class, 'saveTax']);
    Route::post('checkout/{checkout:guid}/complete', [StoreCheckoutController::class, 'completeCheckout']);
    Route::post('checkout/{checkout:guid}/removedealer', [StoreCheckoutController::class, 'removeDealer']);
    Route::post('checkout/{checkout:guid}/intent',  [StoreCheckoutController::class, 'paymentIntent']);
    Route::get('thankyou/{checkout:guid}', [StoreCheckoutController::class, 'thankYou']);
});


Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () 
{
    Route::get('/', [AdminOrderController::class, 'ordersView']);

    Route::get('drafts', [AdminOrderController::class, 'draftsView']);
    Route::get('drafts/create', [AdminOrderController::class, 'newDraftView']);
    Route::get('drafts/{draft}', [AdminOrderController::class, 'draftView']);
    Route::post('drafts', [AdminOrderController::class, 'saveDraft']);
    Route::post('drafts/{id}', [AdminOrderController::class, 'saveDraft']);
    Route::post('drafts/{draft}/complete', [AdminOrderController::class, 'completeDraft']);
    Route::post('drafts/{id}/delete', [AdminOrderController::class, 'deleteDraft']);
    Route::get('data/drafts', [AdminOrderController::class, 'drafts']);
    Route::get('data/drafts/customergroups', [AdminOrderController::class, 'customerGroups']);
    Route::get('data/drafts/{draft}', [AdminOrderController::class, 'draft']);


    Route::get('orders', [AdminOrderController::class, 'ordersView']);
    Route::post('orders/tax', [AdminOrderController::class, 'getTax']);
    Route::get('orders/ffl', [AdminOrderController::class, 'fflRequired']);
    Route::post('orders/shipments', [AdminOrderController::class, 'shipments']);
    Route::get('orders/{order}', [AdminOrderController::class, 'orderView']);
    Route::post('orders/{order}', [AdminOrderController::class, 'saveOrder']);
    Route::post('orders/{order}/verify', [AdminOrderController::class, 'verifyOrder']);
    Route::post('orders/{order}/unverify', [AdminOrderController::class, 'unverifyOrder']);
    Route::post('orders/{order}/status', [AdminOrderController::class, 'updateOrderStatus']);
    Route::post('orders/{order}/problem', [AdminOrderController::class, 'saveProblem']);
    Route::post('orders/{order}/sendpo', [AdminOrderController::class, 'sendPO']);
    Route::post('orders/{order}/payments/{id}/capture', [AdminOrderController::class, 'capturePayment']);
    Route::get('data/orders', [AdminOrderController::class, 'orders']);
    Route::get('data/orders/products', [AdminOrderController::class, 'productLookup']);
    Route::get('data/orders/{id}', [AdminOrderController::class, 'order']);

    Route::get('transactions', [AdminTransactionController::class, 'transactionsView']);
    Route::get('transactions/{order}', [AdminTransactionController::class, 'orderView']);
    Route::post('transactions/{order}/pay', [AdminTransactionController::class, 'makePayment']);
    Route::get('data/transactions', [AdminTransactionController::class, 'transactions']);
    Route::get('data/transactions/{id}', [AdminTransactionController::class, 'order']);

    Route::get('brands', [AdminBrandController::class, 'brands']);
    Route::get('brands/{id}', [AdminBrandController::class, 'showBrand']);
    Route::post('brands/{id}', [AdminBrandController::class, 'saveBrand']);
    Route::post('brands/{id}/delete', [AdminBrandController::class, 'deleteBrand']);

    Route::get('settings', [AdminSettingsController::class, 'showSettings']);
    Route::post('settings', [AdminSettingsController::class, 'saveSettings']);

    Route::get('buyers', [AdminBuyerController::class, 'buyers']);
    Route::get('buyers/{id}', [AdminBuyerController::class, 'showBuyer']);
    Route::post('buyers/{id}', [AdminBuyerController::class, 'saveBuyer']);
    Route::post('buyers/{id}/delete', [AdminBuyerController::class, 'deleteBuyer']);

    Route::get('users', [AdminUserController::class, 'users']);
    Route::get('users/{id}', [AdminUserController::class, 'showUser']);
    Route::post('users/{id}', [AdminUserController::class, 'saveUser']);
    Route::post('users/{id}/delete', [AdminUserController::class, 'deleteUser']);

    Route::get('products', [AdminProductController::class, 'productsView']);
    Route::get('products/create', [AdminProductController::class, 'createProductView']);
    Route::post('products', [AdminProductController::class, 'updateProducts']);
    Route::get('products/{product}', [AdminProductController::class, 'productView']);
    Route::post('products/{product}/images', [AdminProductController::class, 'addImages']);
    Route::post('products/{product}/video', [AdminProductController::class, 'addVideo']);
    Route::post('products/{product}/copy', [AdminProductController::class, 'copyProduct']);
    Route::post('products/{product}/delete', [AdminProductController::class, 'delete']);
    Route::post('products/{product}/archive', [AdminProductController::class, 'archive']);
    Route::post('products/{product}', [AdminProductController::class, 'save']);
    Route::get('data/products', [AdminProductController::class, 'products']);
    Route::get('data/products/categories', [AdminProductController::class, 'categories']);
    Route::get('data/products/lookup', [AdminProductController::class, 'lookup']);
    Route::get('data/products/properties', [AdminProductController::class, 'properties']);
    Route::get('data/products/{product}', [AdminProductController::class, 'product']);

    Route::get('categories', [AdminCategoryController::class, 'categoriesView']);
    Route::get('categories/{product}', [AdminCategoryController::class, 'categoryView']);
    Route::post('categories', [AdminCategoryController::class, 'save']);
    Route::post('categories/update', [AdminCategoryController::class, 'updateCategories']);
    Route::get('data/categories', [AdminCategoryController::class, 'categories']);
    Route::get('data/categories/{id}', [AdminCategoryController::class, 'category']);

    Route::get('properties', [AdminPropertyController::class, 'propertiesView']);
    Route::post('properties', [AdminPropertyController::class, 'save']);
    Route::post('properties/{property}/values', [AdminPropertyController::class, 'saveValues']);
    Route::get('data/properties', [AdminPropertyController::class, 'properties']);
    
    Route::get('customers',  [AdminCustomerController::class, 'list']);
    Route::get('customers/create', [AdminCustomerController::class, 'createCustomerView']);
    Route::get('customer/exists', [AdminCustomerController::class, 'emailExists']);
    Route::get('customers/{customer}', [AdminCustomerController::class, 'customerView']);
    Route::post('customers', [AdminCustomerController::class, 'save']);
    Route::post('customers/{customer}', [AdminCustomerController::class, 'save']);
    Route::get('data/customers', [AdminCustomerController::class, 'customers']);
    Route::get('data/customers/lookup', [AdminCustomerController::class, 'lookup']);
    Route::get('data/customers/{customer}', [AdminCustomerController::class, 'customer']);
    Route::get('data/customers/{customer}/metrics', [AdminCustomerController::class, 'metrics']);
    Route::get('data/customers/{customer}/addresses', [AdminCustomerController::class, 'addresses']);

});