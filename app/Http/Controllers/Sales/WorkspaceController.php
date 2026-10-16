<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductFamily;
use App\Services\Bom\BomGeneratorService;
use App\Services\Configuration\ConfigurationNumberGenerator;
use App\Services\Configurator\ConfiguratorService;
use App\Services\Pricing\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function __construct(
        private BomGeneratorService $bomService,
        private ConfiguratorService $configuratorService,
        private PricingService $pricingService,
        private ConfigurationNumberGenerator $configurationNumberGenerator,
    ) {}

    // ── Catalog ───────────────────────────────────────────────────────────────

    public function catalog(Request $request)
    {
        $query = Product::with(['family', 'category'])
            ->published()
            ->orderBy('name');

        if ($search = $request->search) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }
        if ($family = $request->family) {
            $query->whereHas('family', fn ($q) => $q->where('slug', $family));
        }
        if ($category = $request->category) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        $products = $query->paginate(12)->withQueryString();
        $families = ProductFamily::orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();

        return view('sales.catalog', compact('products', 'families', 'categories'));
    }

    public function productDetail(Product $product)
    {
        $product->load(['family', 'category', 'productAttributes.attribute.options']);

        return view('sales.product-detail', compact('product'));
    }

    // ── Configurations ────────────────────────────────────────────────────────

    public function configurations(Request $request)
    {
        $query = ConfigurationSession::with(['product', 'user', 'bomItems'])
            ->where('user_id', auth()->id())
            ->latest();

        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($search = $request->search) {
            $query->where(fn ($q) => $q->where('configuration_number', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('customer', 'like', "%{$search}%")
                ->orWhere('project', 'like', "%{$search}%"));
        }

        $configurations = $query->paginate(20)->withQueryString();

        return view('sales.configurations', compact('configurations'));
    }

    public function configurationDetail(ConfigurationSession $configuration)
    {
        $this->authorizeConfiguration($configuration);

        $configuration->load(['product.productAttributes.attribute.options', 'user', 'bomItems.component.category']);

        $selections = $configuration->getSelectionsArray();
        $result = null;

        if (! empty($selections)) {
            try {
                $result = $this->configuratorService->evaluate($configuration->product, $selections);
            } catch (\Throwable) {
            }
        }

        $bomItems = $configuration->bom_snapshot ?? [];

        // The stored snapshot is never regenerated implicitly; flag when the live product
        // definition / BOM rules would now produce different rows.
        $bomOutdated = $result !== null
            && $configuration->bom_snapshot !== null
            && $result->bom != $configuration->bom_snapshot;

        return view('sales.configuration-detail', compact('configuration', 'result', 'bomItems', 'selections', 'bomOutdated'));
    }

    public function duplicate(ConfigurationSession $configuration)
    {
        $this->authorizeConfiguration($configuration);

        $new = $configuration->replicate(['ulid', 'configuration_number', 'bom_revision', 'bom_generated_at', 'bom_snapshot', 'validation_errors']);
        $new->ulid = (string) Str::ulid();
        $new->configuration_number = $this->configurationNumberGenerator->next();
        $new->name = ($configuration->name ?? $configuration->configuration_number).' (Copy)';
        $new->status = 'draft';
        $new->bom_status = 'pending';
        $new->bom_revision = 0;
        $new->version = '1';
        $new->user_id = auth()->id();
        $new->save();

        foreach ($configuration->items as $item) {
            $new->items()->create(['attribute_id' => $item->attribute_id, 'value' => $item->value]);
        }

        return redirect()->route('sales.configuration.detail', $new)
            ->with('success', 'Configuration duplicated successfully.');
    }

    public function generateBom(ConfigurationSession $configuration)
    {
        $this->authorizeConfiguration($configuration);

        $configuration->load(['product', 'items']);
        $selections = $configuration->getSelectionsArray();

        // Persist the same rule-aware BOM (incl. add/remove component effects) as the configurator save path.
        $result = $this->configuratorService->evaluate($configuration->product, $selections);

        // An invalid sub-assembly structure (cycle / too deep) must not overwrite the stored BOM.
        if (! empty($result->validation['bom_errors'])) {
            return back()->with('error', implode(' ', $result->validation['bom_errors']));
        }

        $this->bomService->saveToSession($configuration, $result->bom, $result->validation['bom_warnings'] ?? []);

        return back()->with('success', 'BOM generated successfully. Revision '.$configuration->fresh()->bom_revision.'.');
    }

    public function submitForReview(Request $request, ConfigurationSession $configuration)
    {
        $this->authorizeConfiguration($configuration);

        $request->validate([
            'customer' => 'required|string|max:255',
            'project' => 'nullable|string|max:255',
        ]);

        $configuration->update([
            'status' => 'finalized',
            'customer' => $request->customer,
            'project' => $request->project,
        ]);

        return back()->with('success', 'Configuration submitted for review.');
    }

    public function updateStatus(Request $request, ConfigurationSession $configuration)
    {
        $this->authorizeConfiguration($configuration);

        $request->validate(['status' => 'required|in:draft,validated,finalized']);
        $configuration->update(['status' => $request->status]);

        return back()->with('success', 'Status updated to '.ucfirst($request->status).'.');
    }

    public function destroy(ConfigurationSession $configuration)
    {
        $this->authorizeConfiguration($configuration);
        $configuration->delete();

        return redirect()->route('sales.configurations')->with('success', 'Configuration deleted.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizeConfiguration(ConfigurationSession $configuration): void
    {
        $this->authorize('view', $configuration);
    }
}
