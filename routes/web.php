<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BomController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CompatibilityController;
use App\Http\Controllers\Admin\ComponentController;
use App\Http\Controllers\Admin\ConfigurationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FamilyController;
use App\Http\Controllers\Admin\PartNumberController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\QuotationAdminController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RuleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Configurator\CatalogController;
use App\Http\Controllers\Configurator\QuotationController;
use App\Http\Controllers\Configurator\QuoteExportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Sales\BomExportController;
use App\Http\Controllers\Sales\StoredExportController;
use App\Http\Controllers\Sales\WorkspaceController;
use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/login', fn () => view('auth.login'))->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.post')->middleware(['guest', 'throttle:6,1']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/forgot-password', fn () => view('auth.passwords.forgot'))->name('password.request')->middleware('guest');
Route::post('/forgot-password', [PasswordController::class, 'sendLink'])->name('password.email')->middleware(['guest', 'throttle:6,1']);
Route::get('/reset-password/{token}', fn ($token) => view('auth.passwords.reset', ['token' => $token]))->name('password.reset')->middleware('guest');
Route::post('/reset-password', [PasswordController::class, 'reset'])->name('password.update')->middleware(['guest', 'throttle:6,1']);

// ── Profile ───────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// ── Main Application (auth required) ─────────────────────────────────────────
Route::get('/', fn () => redirect()->route('login'));

Route::middleware('auth')->group(function () {
    // ── Sales Workspace ───────────────────────────────────────────────────────
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/catalog', [WorkspaceController::class, 'catalog'])->name('catalog');
        Route::get('/catalog/{product:slug}', [WorkspaceController::class, 'productDetail'])->name('product.detail');
        Route::get('/configurations', [WorkspaceController::class, 'configurations'])->name('configurations');
        Route::get('/configurations/{configuration}', [WorkspaceController::class, 'configurationDetail'])->name('configuration.detail');
        Route::post('/configurations/{configuration}/duplicate', [WorkspaceController::class, 'duplicate'])->name('configuration.duplicate');
        Route::post('/configurations/{configuration}/generate-bom', [WorkspaceController::class, 'generateBom'])->name('configuration.generate-bom');
        Route::post('/configurations/{configuration}/submit', [WorkspaceController::class, 'submitForReview'])->name('configuration.submit');
        Route::delete('/configurations/{configuration}', [WorkspaceController::class, 'destroy'])->name('configuration.destroy');
        Route::patch('/configurations/{configuration}/status', [WorkspaceController::class, 'updateStatus'])->name('configuration.status');
        Route::get('/configurations/{configuration}/bom/pdf', [BomExportController::class, 'pdf'])->name('bom.pdf');
        Route::get('/configurations/{configuration}/bom/excel', [BomExportController::class, 'excel'])->name('bom.excel');
        Route::post('/configurations/{configuration}/bom/queue/{format}', [BomExportController::class, 'queue'])->name('bom.queue');
        Route::get('/bom/download', [StoredExportController::class, 'download'])->name('bom.download')->middleware('signed');
    });

    // ── Legacy redirects ──────────────────────────────────────────────────────
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', fn () => redirect()->route('sales.catalog'))->name('index');
        Route::get('/category/{category:slug}', [CatalogController::class, 'category'])->name('category');
        Route::get('/family/{family:slug}', [CatalogController::class, 'family'])->name('family');
        Route::get('/product/{product:slug}', [CatalogController::class, 'product'])->name('product');
    });

    Route::prefix('configurator')->name('configurator.')->group(function () {
        Route::get('/configure/{product:slug}', [CatalogController::class, 'configure'])->name('configure');
        Route::get('/configuration/{ulid}', [CatalogController::class, 'show'])->name('show');
        Route::get('/my-configurations', fn () => redirect()->route('sales.configurations'))->name('my-configurations');
        Route::post('/configuration/{ulid}/quotation', [QuotationController::class, 'store'])->name('quotation.store');
        Route::get('/quotation/{quotation:ulid}/pdf', [QuoteExportController::class, 'pdf'])->name('quotation.pdf');
    });
});

// ── Admin Panel ───────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin|product_manager'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Products
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

    // Categories / Families
    Route::delete('/categories/batch', [CategoryController::class, 'batchDestroy'])->name('categories.batch-destroy');
    Route::resource('categories', CategoryController::class);
    Route::delete('/families/batch', [FamilyController::class, 'batchDestroy'])->name('families.batch-destroy');
    Route::resource('families', FamilyController::class);

    // Attributes & Options
    Route::delete('/attributes/batch', [AttributeController::class, 'batchDestroy'])->name('attributes.batch-destroy');
    Route::resource('attributes', AttributeController::class);
    Route::delete('/attributes/{attribute}/options/{option}', [AttributeController::class, 'destroyOption'])->name('attributes.options.destroy');

    // Components
    Route::delete('/components/batch', [ComponentController::class, 'batchDestroy'])->name('components.batch-destroy');
    Route::resource('components', ComponentController::class);

    // Rules
    Route::get('/rules', [RuleController::class, 'index'])->name('rules.index');
    Route::get('/rules/create/{product}', [RuleController::class, 'create'])->name('rules.create');
    Route::post('/rules/store/{product}', [RuleController::class, 'store'])->name('rules.store');
    Route::get('/rules/{rule}/edit', [RuleController::class, 'edit'])->name('rules.edit');
    Route::put('/rules/{rule}', [RuleController::class, 'update'])->name('rules.update');
    Route::delete('/rules/{rule}', [RuleController::class, 'destroy'])->name('rules.destroy');

    // Compatibility
    Route::get('/compatibility', [CompatibilityController::class, 'index'])->name('compatibility.index');
    Route::get('/compatibility/create', [CompatibilityController::class, 'create'])->name('compatibility.create');
    Route::post('/compatibility', [CompatibilityController::class, 'store'])->name('compatibility.store');
    Route::get('/compatibility/{rule}/edit', [CompatibilityController::class, 'edit'])->name('compatibility.edit');
    Route::put('/compatibility/{rule}', [CompatibilityController::class, 'update'])->name('compatibility.update');
    Route::delete('/compatibility/{rule}', [CompatibilityController::class, 'destroy'])->name('compatibility.destroy');

    // BOM Rules
    Route::get('/bom', [BomController::class, 'index'])->name('bom.index');
    Route::get('/bom/create/{product}', [BomController::class, 'create'])->name('bom.create');
    Route::post('/bom/store/{product}', [BomController::class, 'store'])->name('bom.store');
    Route::get('/bom/{rule}/edit', [BomController::class, 'edit'])->name('bom.edit');
    Route::put('/bom/{rule}', [BomController::class, 'update'])->name('bom.update');
    Route::delete('/bom/{rule}', [BomController::class, 'destroy'])->name('bom.destroy');

    // Part Numbers
    Route::get('/part-numbers', [PartNumberController::class, 'index'])->name('part-numbers.index');
    Route::get('/part-numbers/create/{product}', [PartNumberController::class, 'create'])->name('part-numbers.create');
    Route::post('/part-numbers/store/{product}', [PartNumberController::class, 'store'])->name('part-numbers.store');
    Route::get('/part-numbers/{pattern}/edit', [PartNumberController::class, 'edit'])->name('part-numbers.edit');
    Route::put('/part-numbers/{pattern}', [PartNumberController::class, 'update'])->name('part-numbers.update');
    Route::delete('/part-numbers/{pattern}', [PartNumberController::class, 'destroy'])->name('part-numbers.destroy');

    // Pricing
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::get('/pricing/lists/create', [PricingController::class, 'createList'])->name('pricing.lists.create');
    Route::post('/pricing/lists', [PricingController::class, 'storeList'])->name('pricing.lists.store');
    Route::get('/pricing/lists/{priceList}/edit', [PricingController::class, 'editList'])->name('pricing.lists.edit');
    Route::put('/pricing/lists/{priceList}', [PricingController::class, 'updateList'])->name('pricing.lists.update');

    // Configurations
    Route::get('/configurations', [ConfigurationController::class, 'index'])->name('configurations.index');

    // Quotations
    Route::get('/quotations', [QuotationAdminController::class, 'index'])->name('quotations.index');
    Route::patch('/quotations/{quotation}/status', [QuotationAdminController::class, 'updateStatus'])->name('quotations.status');

    // Users
    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // Roles
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
