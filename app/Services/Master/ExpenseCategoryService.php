<?php

namespace App\Services\Master;

use App\Models\ExpenseCategory;
use App\Traits\HandlesTransaction;
use Illuminate\Database\Eloquent\Collection;

class ExpenseCategoryService
{
    use HandlesTransaction;

    /**
     * Get all expense categories.
     */
    public function getAllCategories(): Collection
    {
        return ExpenseCategory::orderBy('name', 'asc')->get();
    }

    /**
     * Get active categories for select options, optionally filtered by type.
     */
    public function getCategoryOptions(?string $type = null): array
    {
        $query = ExpenseCategory::where('is_active', true);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('name', 'asc')
            ->get(['id', 'name'])
            ->map(fn($item) => [
                'label' => $item->name,
                'value' => $item->id,
            ])
            ->toArray();
    }

    /**
     * Create a new category.
     */
    public function createCategory(array $data): ExpenseCategory
    {
        return $this->transaction(function () use ($data) {
            return ExpenseCategory::create($data);
        });
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(int $id, array $data): ExpenseCategory
    {
        return $this->transaction(function () use ($id, $data) {
            $category = ExpenseCategory::findOrFail($id);
            $category->update($data);
            return $category;
        });
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $category = ExpenseCategory::findOrFail($id);
            return $category->delete();
        });
    }
}
