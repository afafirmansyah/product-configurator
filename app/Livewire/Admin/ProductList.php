<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductFamily;
use Livewire\Component;
use Livewire\WithPagination;

class ProductList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $categoryFilter = '';
    public string $familyFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->with(['family.category'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter, fn($q) => $q->whereHas('family', fn($q) => $q->where('product_category_id', $this->categoryFilter)))
            ->when($this->familyFilter, fn($q) => $q->where('product_family_id', $this->familyFilter))
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.product-list', [
            'products' => $products,
            'categories' => ProductCategory::where('is_active', true)->orderBy('name')->get(),
            'families' => ProductFamily::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
