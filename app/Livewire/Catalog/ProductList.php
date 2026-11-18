<?php

namespace App\Livewire\Catalog;

use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProductList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $category_id = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $categories = Category::orderBy('name')->get();

        $products = Product::where('is_active', true)
            ->with(['category', 'units', 'prices.unit', 'stockLevels'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('sku', 'ilike', '%'.$this->search.'%')
                        ->orWhere('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->category_id, function ($query) {
                $query->where('category_id', $this->category_id);
            })
            ->paginate(12);

        return view('livewire.catalog.product-list', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
