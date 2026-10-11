<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'amount' => 'required|integer|min:1|max:999999999999',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:65535',
            'chart_of_account_id' => ['required', 'integer', 'different:cash_account_id', Rule::exists('chart_of_accounts', 'id')->where('is_active', true)],
            'cash_account_id' => ['required', 'integer', Rule::exists('chart_of_accounts', 'id')->where('is_active', true)->where('category', 'Harta')->where('account_group', 'Kas dan Bank')->where('normal_balance', 'Debit')],
        ];
    }
}
