<?php

declare(strict_types=1);

namespace App\Modules\Domains\Catalog\Repositories;

use App\Modules\Domains\Catalog\Models\Product;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    public function findProductOrFail(int $id): Product;

    public function allActive(): Collection;
}
