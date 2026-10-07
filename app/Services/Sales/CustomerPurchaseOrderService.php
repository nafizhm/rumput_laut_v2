<?php

namespace App\Services\Sales;

use App\Models\CustomerPurchaseOrder;
use App\Models\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CustomerPurchaseOrderService
{
    public function getAll(): Collection
    {
        return CustomerPurchaseOrder::orderByDesc('po_date')->orderByDesc('id')->get();
    }

    public function store(array $data): CustomerPurchaseOrder
    {
        $order = new CustomerPurchaseOrder;
        $this->save($order, $data);
        return $order;
    }

    public function update(CustomerPurchaseOrder $order, array $data): bool
    {
        return $this->save($order, $data);
    }

    public function delete(CustomerPurchaseOrder $order): bool
    {
        $path = $order->po_file_path;
        $deleted = $order->delete();
        if ($deleted && $path) {
            Storage::disk('local')->delete($path);
        }
        return $deleted;
    }

    private function save(CustomerPurchaseOrder $order, array $data): bool
    {
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
}
