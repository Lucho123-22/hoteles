<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateSubBranchProductsStock
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Product $product;
    protected int $minStock;
    protected int $maxStock;

    public function __construct(Product $product, int $minStock, int $maxStock)
    {
        $this->product = $product;
        $this->minStock = $minStock;
        $this->maxStock = $maxStock;
    }

    public function handle(): void
    {
        foreach ($this->product->subBranchProducts as $subBranchProduct) {
            $subBranchProduct->update([
                'min_stock' => $this->minStock,
                'max_stock' => $this->maxStock,
            ]);
        }
    }
}
