<?php

namespace App\Livewire\Admin;

use App\Enums\ProductStatus;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Services\AuditService;
use Illuminate\Support\Str;
use Livewire\Component;

class ProductBuilder extends Component
{
    public ?Product $product = null;

    public bool $isNew = false;

    // Product fields
    public string $name = '';

    public string $code = '';

    public string $sku = '';

    public string $manufacturer = '';

    public string $short_description = '';

    public string $long_description = '';

    public string $applications = '';

    public string $features = '';

    public string $base_price = '0';

    public string $currency = 'EUR';

    public string $status = 'draft';

    public string $version = '1.0';

    public int $product_family_id = 0;

    // Attribute assignment
    public array $assignedAttributes = [];

    public string $attributeSearch = '';

    public function mount(?Product $product = null): void
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'product_manager']), 403);

        if ($product && $product->exists) {
            $this->product = $product;
            $this->product->load(['productAttributes.attribute', 'family']);
            $this->fillFromProduct();
        } else {
            $this->isNew = true;
        }
    }

    private function fillFromProduct(): void
    {
        $this->name = $this->product->name;
        $this->code = $this->product->code;
        $this->sku = $this->product->sku ?? '';
        $this->manufacturer = $this->product->manufacturer ?? '';
        $this->short_description = $this->product->short_description ?? '';
        $this->long_description = $this->product->long_description ?? '';
        $this->applications = $this->product->applications ?? '';
        $this->features = $this->product->features ?? '';
        $this->base_price = (string) $this->product->base_price;
        $this->currency = $this->product->currency;
        $this->status = $this->product->status;
        $this->version = $this->product->version;
        $this->product_family_id = $this->product->product_family_id;

        $this->assignedAttributes = $this->product->productAttributes->map(fn ($pa) => [
            'attribute_id' => $pa->attribute_id,
            'name' => $pa->attribute->name,
            'code' => $pa->attribute->code,
            'type' => $pa->attribute->type,
            'is_required' => $pa->is_required,
            'default_value' => $pa->default_value ?? '',
            'is_visible' => $pa->is_visible,
            'help_text' => $pa->help_text ?? '',
            'sort_order' => $pa->sort_order,
            'step_name' => $pa->step_name ?? '',
        ])->toArray();
    }

    public function addAttribute(int $attributeId): void
    {
        if (collect($this->assignedAttributes)->pluck('attribute_id')->contains($attributeId)) {
            return;
        }

        $attribute = Attribute::find($attributeId);
        if (! $attribute) {
            return;
        }

        $this->assignedAttributes[] = [
            'attribute_id' => $attributeId,
            'name' => $attribute->name,
            'code' => $attribute->code,
            'type' => $attribute->type,
            'is_required' => false,
            'default_value' => '',
            'is_visible' => true,
            'help_text' => '',
            'sort_order' => count($this->assignedAttributes),
            'step_name' => '',
        ];
    }

    public function removeAttribute(int $index): void
    {
        array_splice($this->assignedAttributes, $index, 1);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'product_family_id' => 'required|exists:product_families,id',
            'base_price' => 'required|numeric|min:0',
        ]);

        $data = [
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'slug' => Str::slug($this->name),
            'sku' => $this->sku ?: null,
            'manufacturer' => $this->manufacturer ?: null,
            'short_description' => $this->short_description ?: null,
            'long_description' => $this->long_description ?: null,
            'applications' => $this->applications ?: null,
            'features' => $this->features ?: null,
            'base_price' => $this->base_price,
            'currency' => $this->currency,
            'status' => $this->status,
            'version' => $this->version,
            'product_family_id' => $this->product_family_id,
        ];

        $oldValues = $this->product?->toArray() ?? [];

        if ($this->isNew) {
            $data['ulid'] = Str::ulid();
            $this->product = Product::create($data);
        } else {
            $this->product->update($data);
        }

        // Sync attributes
        $syncData = [];
        foreach ($this->assignedAttributes as $pa) {
            $syncData[$pa['attribute_id']] = [
                'is_required' => $pa['is_required'],
                'default_value' => $pa['default_value'] ?: null,
                'is_visible' => $pa['is_visible'],
                'help_text' => $pa['help_text'] ?: null,
                'sort_order' => $pa['sort_order'],
                'step_name' => $pa['step_name'] ?: null,
            ];
        }
        $this->product->attributes()->sync($syncData);

        app(AuditService::class)->log(
            $this->isNew ? 'created' : 'updated',
            $this->product,
            $oldValues,
            $this->product->fresh()->toArray()
        );

        $this->isNew = false;
        session()->flash('success', 'Product saved successfully.');
    }

    public function publish(): void
    {
        if (! $this->product) {
            return;
        }

        $this->product->update(['status' => ProductStatus::Published->value]);
        $this->status = ProductStatus::Published->value;

        app(AuditService::class)->log('published', $this->product);
        session()->flash('success', 'Product published successfully.');
    }

    public function render()
    {
        $availableAttributes = Attribute::where('is_active', true)
            ->when($this->attributeSearch, fn ($q) => $q->where('name', 'like', "%{$this->attributeSearch}%"))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.product-builder', [
            'families' => ProductFamily::with('category')->where('is_active', true)->orderBy('name')->get(),
            'availableAttributes' => $availableAttributes,
            'statuses' => ProductStatus::cases(),
        ]);
    }
}
