<?php

namespace App\Services\Transaction;

use App\Models\Expense;
use App\Traits\HandlesTransaction;
use Illuminate\Database\Eloquent\Collection;

class ExpenseService
{
    use HandlesTransaction;

    /**
     * Get all expenses.
     */
    public function getAllExpenses(): Collection
    {
        return Expense::with('category')
            ->orderBy('expense_date', 'desc')
            ->get();
    }

    /**
     * Create a new expense.
     */
    public function createExpense(array $data): Expense
    {
        return $this->transaction(function () use ($data) {
            return Expense::create($data);
        });
    }

    /**
     * Update an existing expense.
     */
    public function updateExpense(int $id, array $data): Expense
    {
        return $this->transaction(function () use ($id, $data) {
            $expense = Expense::findOrFail($id);
            $expense->update($data);
            return $expense;
        });
    }

    /**
     * Delete an expense.
     */
    public function deleteExpense(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $expense = Expense::findOrFail($id);
            return $expense->delete();
        });
    }
}
