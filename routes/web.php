<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Configurator\CatalogController;
use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/login', fn() => view('auth.login'))->name('login')->middleware('guest');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
Route::get('/forgot-password', fn() => view('auth.passwords.forgot'))->name('password.request')->middleware('guest');
Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordController::class, 'sendLink'])->name('password.email')->middleware('guest');
Route::get('/reset-password/{token}', fn($token) => view('auth.passwords.reset', ['token' => $token]))->name('password.reset')->middleware('guest');
Route::post('/reset-password', [App\Http\Controllers\Auth\PasswordController::class, 'reset'])->name('password.update')->middleware('guest');

// ── Profile ───────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
});

// ── Main Application (auth required) ─────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));

Route::middleware('auth')->group(function () {
    // ── Sales Workspace ───────────────────────────────────────────────────────
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/catalog', [App\Http\Controllers\Sales\WorkspaceController::class, 'catalog'])->name('catalog');
        Route::get('/catalog/{product:slug}', [App\Http\Controllers\Sales\WorkspaceController::class, 'productDetail'])->name('product.detail');
        Route::get('/configurations', [App\Http\Controllers\Sales\WorkspaceController::class, 'configurations'])->name('configurations');
        Route::get('/configurations/{configuration}', [App\Http\Controllers\Sales\WorkspaceController::class, 'configurationDetail'])->name('configuration.detail');
        Route::post('/configurations/{configuration}/duplicate', [App\Http\Controllers\Sales\WorkspaceController::class, 'duplicate'])->name('configuration.duplicate');
        Route::post('/configurations/{configuration}/generate-bom', [App\Http\Controllers\Sales\WorkspaceController::class, 'generateBom'])->name('configuration.generate-bom');
        Route::post('/configurations/{configuration}/submit', [App\Http\Controllers\Sales\WorkspaceController::class, 'submitForReview'])->name('configuration.submit');
        Route::delete('/configurations/{configuration}', [App\Http\Controllers\Sales\WorkspaceController::class, 'destroy'])->name('configuration.destroy');
        Route::patch('/configurations/{configuration}/status', [App\Http\Controllers\Sales\WorkspaceController::class, 'updateStatus'])->name('configuration.status');
        Route::get('/configurations/{configuration}/bom/pdf', [App\Http\Controllers\Sales\BomExportController::class, 'pdf'])->name('bom.pdf');
        Route::get('/configurations/{configuration}/bom/excel', [App\Http\Controllers\Sales\BomExportController::class, 'excel'])->name('bom.excel');
    });

    // ── Legacy redirects ──────────────────────────────────────────────────────
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', fn() => redirect()->route('sales.catalog'))->name('index');
        Route::get('/category/{category:slug}', [CatalogController::class, 'category'])->name('category');
        Route::get('/family/{family:slug}', [CatalogController::class, 'family'])->name('family');
        Route::get('/product/{product:slug}', [CatalogController::class, 'product'])->name('product');
    });

    Route::prefix('configurator')->name('configurator.')->group(function () {
        Route::get('/configure/{product:slug}', [CatalogController::class, 'configure'])->name('configure');
        Route::get('/configuration/{ulid}', [CatalogController::class, 'show'])->name('show');
        Route::get('/my-configurations', fn() => redirect()->route('sales.configurations'))->name('my-configurations');
        Route::post('/configuration/{ulid}/quotation', [App\Http\Controllers\Configurator\QuotationController::class, 'store'])->name('quotation.store');
    });
});

// ── Admin Panel ───────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Products
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

    // Categories / Families
    Route::delete('/categories/batch', [App\Http\Controllers\Admin\CategoryController::class, 'batchDestroy'])->name('categories.batch-destroy');
    Route::resource('categories', App\Http\Controllers\Admin\CategoryController::class);
    Route::delete('/families/batch', [App\Http\Controllers\Admin\FamilyController::class, 'batchDestroy'])->name('families.batch-destroy');
    Route::resource('families', App\Http\Controllers\Admin\FamilyController::class);

    // Attributes & Options
    Route::delete('/attributes/batch', [App\Http\Controllers\Admin\AttributeController::class, 'batchDestroy'])->name('attributes.batch-destroy');
    Route::resource('attributes', App\Http\Controllers\Admin\AttributeController::class);
    Route::delete('/attributes/{attribute}/options/{option}', [App\Http\Controllers\Admin\AttributeController::class, 'destroyOption'])->name('attributes.options.destroy');

    // Components
    Route::delete('/components/batch', [App\Http\Controllers\Admin\ComponentController::class, 'batchDestroy'])->name('components.batch-destroy');
    Route::resource('components', App\Http\Controllers\Admin\ComponentController::class);

    // Rules
    Route::get('/rules', [App\Http\Controllers\Admin\RuleController::class, 'index'])->name('rules.index');
    Route::get('/rules/create/{product}', [App\Http\Controllers\Admin\RuleController::class, 'create'])->name('rules.create');
    Route::post('/rules/store/{product}', [App\Http\Controllers\Admin\RuleController::class, 'store'])->name('rules.store');
    Route::get('/rules/{rule}/edit', [App\Http\Controllers\Admin\RuleController::class, 'edit'])->name('rules.edit');
    Route::put('/rules/{rule}', [App\Http\Controllers\Admin\RuleController::class, 'update'])->name('rules.update');
    Route::delete('/rules/{rule}', [App\Http\Controllers\Admin\RuleController::class, 'destroy'])->name('rules.destroy');

    // Compatibility
    Route::get('/compatibility', [App\Http\Controllers\Admin\CompatibilityController::class, 'index'])->name('compatibility.index');
    Route::get('/compatibility/create', [App\Http\Controllers\Admin\CompatibilityController::class, 'create'])->name('compatibility.create');
    Route::post('/compatibility', [App\Http\Controllers\Admin\CompatibilityController::class, 'store'])->name('compatibility.store');
    Route::delete('/compatibility/{rule}', [App\Http\Controllers\Admin\CompatibilityController::class, 'destroy'])->name('compatibility.destroy');

    // BOM Rules
    Route::get('/bom', [App\Http\Controllers\Admin\BomController::class, 'index'])->name('bom.index');
    Route::get('/bom/create/{product}', [App\Http\Controllers\Admin\BomController::class, 'create'])->name('bom.create');
    Route::post('/bom/store/{product}', [App\Http\Controllers\Admin\BomController::class, 'store'])->name('bom.store');
    Route::get('/bom/{rule}/edit', [App\Http\Controllers\Admin\BomController::class, 'edit'])->name('bom.edit');
    Route::delete('/bom/{rule}', [App\Http\Controllers\Admin\BomController::class, 'destroy'])->name('bom.destroy');

    // Part Numbers
    Route::get('/part-numbers', [App\Http\Controllers\Admin\PartNumberController::class, 'index'])->name('part-numbers.index');
    Route::get('/part-numbers/create/{product}', [App\Http\Controllers\Admin\PartNumberController::class, 'create'])->name('part-numbers.create');
    Route::post('/part-numbers/store/{product}', [App\Http\Controllers\Admin\PartNumberController::class, 'store'])->name('part-numbers.store');
    Route::get('/part-numbers/{pattern}/edit', [App\Http\Controllers\Admin\PartNumberController::class, 'edit'])->name('part-numbers.edit');
    Route::put('/part-numbers/{pattern}', [App\Http\Controllers\Admin\PartNumberController::class, 'update'])->name('part-numbers.update');
    Route::delete('/part-numbers/{pattern}', [App\Http\Controllers\Admin\PartNumberController::class, 'destroy'])->name('part-numbers.destroy');

    // Pricing
    Route::get('/pricing', [App\Http\Controllers\Admin\PricingController::class, 'index'])->name('pricing.index');
    Route::get('/pricing/lists/create', [App\Http\Controllers\Admin\PricingController::class, 'createList'])->name('pricing.lists.create');
    Route::post('/pricing/lists', [App\Http\Controllers\Admin\PricingController::class, 'storeList'])->name('pricing.lists.store');
    Route::get('/pricing/lists/{priceList}/edit', [App\Http\Controllers\Admin\PricingController::class, 'editList'])->name('pricing.lists.edit');
    Route::put('/pricing/lists/{priceList}', [App\Http\Controllers\Admin\PricingController::class, 'updateList'])->name('pricing.lists.update');

    // Configurations
    Route::get('/configurations', function () {
        $configurations = \App\Models\ConfigurationSession::with(['product', 'user'])->latest()->paginate(20);
        return view('admin.configurations.index', compact('configurations'));
    })->name('configurations.index');

    // Quotations
    Route::get('/quotations', function () {
        $quotations = \App\Models\QuotationRequest::with(['configuration.product'])->latest()->paginate(20);
        return view('admin.quotations.index', compact('quotations'));
    })->name('quotations.index');
    Route::patch('/quotations/{quotation}/status', [App\Http\Controllers\Admin\QuotationAdminController::class, 'updateStatus'])->name('quotations.status');

    // Users
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
    Route::patch('/users/{user}/toggle-active', [App\Http\Controllers\Admin\UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('/users/{user}/reset-password', [App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('users.reset-password');

    // Roles
    Route::get('/roles', [App\Http\Controllers\Admin\RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [App\Http\Controllers\Admin\RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [App\Http\Controllers\Admin\RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit', [App\Http\Controllers\Admin\RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [App\Http\Controllers\Admin\RoleController::class, 'update'])->name('roles.update');

    // Audit Logs
    Route::get('/audit-logs', function () {
        $logs = \App\Models\AuditLog::with('user')->latest()->paginate(50);
        return view('admin.audit-logs.index', compact('logs'));
    })->name('audit-logs.index');

    // Settings
    Route::get('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
});
