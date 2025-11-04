<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\SubBranch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignProductToSubBranches implements ShouldQueue{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected Product $product;
    protected int $minStock;
    protected int $maxStock;
    public function __construct(Product $product, int $minStock = 0, int $maxStock = 0){
        $this->product = $product;
        $this->minStock = $minStock;
        $this->maxStock = $maxStock;
    }
    public function handle(): void{
        $subBranches = SubBranch::all();
        foreach ($subBranches as $subBranch) {
            $subBranch->subBranchProducts()->create([
                'product_id'        => $this->product->id,
                'packages_in_stock' => 0,
                'units_per_package' => 0,
                'min_stock'         => $this->minStock,
                'max_stock'         => $this->maxStock,
                'is_fractionable'   => $this->product->is_fractionable,
                'fraction_units'    => $this->product->is_fractionable ? $this->product->fraction_units : null,
                'is_active'         => $this->product->is_active,
                'created_by'        => $this->product->created_by,
            ]);
        }
    }
}
