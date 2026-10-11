<?php

namespace App\Services\Sales;

use App\Models\CustomerPurchaseOrder;
use App\Models\Factory;
use App\Models\QualityGrade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerPurchaseOrderService
{
    public function getAll(): Collection
    {
        return CustomerPurchaseOrder::with('items')->orderByDesc('po_date')->orderByDesc('id')->get();
    }

    public function store(array $data): CustomerPurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $order = new CustomerPurchaseOrder;
            $this->save($order, $data);
            return $order;
        });
    }

    public function update(CustomerPurchaseOrder $order, array $data): bool
    {
        return DB::transaction(function () use ($order, $data) {
            $order = CustomerPurchaseOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->salesTransactions()->exists()) {
                foreach (isset($data['items']) ? ['factory_id'] : ['factory_id', 'item_name', 'price_per_kg'] as $field) {
                    $changed = $field === 'item_name'
                        ? (string) $data[$field] !== (string) $order->$field
                        : (float) $data[$field] !== (float) $order->$field;
                    if ($changed) {
                        throw ValidationException::withMessages([$field => 'Konsumen, barang, dan harga PO yang sudah digunakan penjualan tidak dapat diubah.']);
                    }
                }
                if (! isset($data['items']) && (float) $data['quantity_kg'] < (float) $order->salesTransactions()->sum('total_weight_kg')) {
                    throw ValidationException::withMessages(['quantity_kg' => 'Jumlah PO tidak boleh lebih kecil dari jumlah yang sudah digunakan penjualan.']);
                }
            }
            return $this->save($order, $data);
        });
    }

    public function delete(CustomerPurchaseOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            $order = CustomerPurchaseOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->salesTransactions()->exists()) {
                throw ValidationException::withMessages(['po' => 'PO yang sudah digunakan penjualan tidak dapat dihapus.']);
            }
            $path = $order->po_file_path;
            $deleted = $order->delete();
            if ($deleted && $path) {
                Storage::disk('local')->delete($path);
            }
            return $deleted;
        });
    }

    private function save(CustomerPurchaseOrder $order, array $data): bool
    {
        $rows = $this->prepareItems($order, $data);
        if (isset($data['items'])) {
            $data['item_name'] = 'Rumput Laut';
            $data['quantity_kg'] = round(array_sum(array_column($rows, 'quantity_kg')), 2);
            $total = array_sum(array_map(fn ($row) => round($row['quantity_kg'] * $row['price_per_kg'], 2), $rows));
            $data['price_per_kg'] = round($total / $data['quantity_kg'], 2);
            if ($data['quantity_kg'] > 999999999.99) {
                throw ValidationException::withMessages(['items' => 'Total jumlah PO melewati batas pencatatan.']);
            }
        }
        unset($data['items']);
        $factory = Factory::findOrFail($data['factory_id']);
        $data['customer_name'] = $factory->name;
        $data['customer_phone'] = $factory->phone;
        $data['customer_address'] = $factory->address;
        $file = $data['po_file'] ?? null;
        $remove = $data['remove_po_file'] ?? false;
        unset($data['po_file'], $data['remove_po_file']);
        $oldPath = $order->po_file_path;
        $newPath = null;
        if ($file) {
            $newPath = $file->store('customer-po', 'local');
            if (! $newPath) {
                throw new \RuntimeException('File PO gagal disimpan.');
            }
            $data['po_file_path'] = $newPath;
            $data['po_file_name'] = mb_substr($file->getClientOriginalName(), 0, 255);
            $data['po_file_mime'] = $file->getMimeType();
        } elseif ($remove) {
            $data['po_file_path'] = $data['po_file_name'] = $data['po_file_mime'] = null;
        }

        try {
            $saved = $order->fill($data)->saveOrFail();
            $kept = [];
            foreach ($rows as $row) {
                $id = $row['id'] ?? null;
                unset($row['id']);
                $item = $id ? $order->items()->findOrFail($id) : $order->items()->make();
                $item->fill($row)->saveOrFail();
                $kept[] = $item->id;
            }
            $order->items()->whereNotIn('id', $kept)->delete();
            $order->unsetRelation('items');
        } catch (Throwable $e) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $e;
        }
        if ($oldPath && ($newPath || $remove)) {
            Storage::disk('local')->delete($oldPath);
        }
        return $saved;
    }

    private function prepareItems(CustomerPurchaseOrder $order, array $data): array
    {
        $existing = $order->exists ? $order->items()->lockForUpdate()->get()->keyBy('id') : collect();
        if (! isset($data['items']) && $existing->count() > 1) {
            throw ValidationException::withMessages(['items' => 'PO dengan beberapa grade harus diperbarui melalui rincian barang.']);
        }
        $rows = $data['items'] ?? [[
            'id' => $existing->first()?->id, 'item_name' => $data['item_name'],
            'quality_grade_id' => $existing->first()?->quality_grade_id,
            'quantity_kg' => $data['quantity_kg'], 'price_per_kg' => $data['price_per_kg'],
        ]];
        $normalized = [];
        $kept = [];
        foreach ($rows as $index => $row) {
            $id = $row['id'] ?? null;
            $item = $id ? $existing->get($id) : null;
            if ($id && ! $item) {
                throw ValidationException::withMessages(["items.$index.id" => 'Baris barang bukan milik PO ini.']);
            }
            $grade = ! empty($row['quality_grade_id']) ? QualityGrade::findOrFail($row['quality_grade_id']) : null;
            $name = isset($data['items']) ? ($item && ! $item->quality_grade_id ? $item->item_name : 'Rumput Laut') : $row['item_name'];
            if ($item && $item->salesTransactions()->exists()) {
                if ((int) $item->quality_grade_id !== (int) ($row['quality_grade_id'] ?? 0)
                    || (float) $item->price_per_kg !== (float) $row['price_per_kg'] || $item->item_name !== $name) {
                    throw ValidationException::withMessages(["items.$index" => 'Grade, produk, dan harga baris yang sudah dijual tidak dapat diubah.']);
                }
                if ((float) $row['quantity_kg'] < (float) $item->salesTransactions()->sum('total_weight_kg')) {
                    throw ValidationException::withMessages(["items.$index.quantity_kg" => 'Jumlah tidak boleh lebih kecil dari jumlah yang sudah dijual untuk grade ini.']);
                }
            }
            $normalized[] = ['id' => $id, 'item_name' => $name, 'quality_grade_id' => $row['quality_grade_id'] ?? null,
                'grade_name' => $item?->quality_grade_id == ($row['quality_grade_id'] ?? null)
                    ? ($item?->grade_name ?? $grade?->name) : $grade?->name,
                'quantity_kg' => (float) $row['quantity_kg'], 'price_per_kg' => (float) $row['price_per_kg']];
            if ($id) $kept[] = $id;
        }
        foreach ($existing as $item) {
            if (! in_array($item->id, $kept) && $item->salesTransactions()->exists()) {
                throw ValidationException::withMessages(['items' => 'Baris grade yang sudah dijual tidak dapat dihapus.']);
            }
        }
        return $normalized;
    }
}
