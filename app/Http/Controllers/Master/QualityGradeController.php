<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\QualityGrade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QualityGradeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $grades = QualityGrade::query()
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->orderBy('sort_order')->orderBy('code')->get();
        return view('pages.master.quality-grade.index', compact('grades'));
    }

    public function create()
    {
        return view('pages.master.quality-grade.form', ['grade' => new QualityGrade(['is_active' => true, 'sort_order' => 0])]);
    }

    public function edit(QualityGrade $kualitas_barang)
    {
        return view('pages.master.quality-grade.form', ['grade' => $kualitas_barang]);
    }

    public function store(Request $request)
    {
        QualityGrade::create($this->validated($request));
        return redirect()->route('kualitas-barang.index')->with('success', 'Kualitas barang berhasil ditambahkan.');
    }

    public function update(Request $request, QualityGrade $kualitas_barang)
    {
        $kualitas_barang->update($this->validated($request, $kualitas_barang));
        return redirect()->route('kualitas-barang.index')->with('success', 'Kualitas barang berhasil diperbarui.');
    }

    private function validated(Request $request, ?QualityGrade $grade = null): array
    {
        if (is_string($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }
        return $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', Rule::unique('quality_grades', 'code')->ignore($grade?->id)],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
            'criteria' => 'nullable|string|max:5000',
            'sort_order' => 'required|integer|min:0|max:9999',
            'is_active' => 'required|boolean',
        ]);
    }
}
