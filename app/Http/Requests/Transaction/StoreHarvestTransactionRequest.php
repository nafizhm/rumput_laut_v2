<?php
namespace App\Http\Requests\Transaction;
use Illuminate\Foundation\Http\FormRequest;
class StoreHarvestTransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $areaId = $this->input('lines.0.warehouse_area_id');
        $this->merge(['inventory_id' => is_scalar($areaId)
            ? \App\Models\WarehouseArea::where('id', $areaId)->value('inventory_id')
            : null]);
    }

    public function authorize(): bool
    {
        return $this->user()->can('manajemen-panen.create');
    }
    public function rules(): array
    {
        return [
            'submission_key' => 'required|uuid', 'confirm' => 'required|accepted',
            'inventory_id' => 'required|integer|exists:inventories,id', 'lines' => 'required|array|min:1|max:50',
            'lines.*.quality_grade_id' => 'nullable|integer|exists:quality_grades,id',
            'lines.*.warehouse_area_id' => 'required|integer|exists:warehouse_areas,id',
            'lines.*.actual_weight_kg' => 'required|numeric|decimal:0,2|min:0.01|max:999999999.99',
            'lines.*.sack_count' => 'required|integer|min:1|max:1000000',
            'farmer_id' => 'required|integer|exists:farmers,id',
            'harvest_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'location' => 'required|string|max:255',
            'total_weight' => 'required|numeric|decimal:0,2|min:0.01|max:999999999.99',
            'sack_count' => 'required|integer|min:1|max:1000000',
            'extra_per_sack' => 'required|numeric|decimal:0,2|min:0|max:1000', 'notes' => 'nullable|string|max:5000',
        ];
    }
}
