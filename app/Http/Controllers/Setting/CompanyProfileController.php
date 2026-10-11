<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\DocumentNumberRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyProfileController extends Controller
{
    public function index()
    {
        return view('pages.settings.profile.index', [
            'profile' => CompanyProfile::current(),
            'rules' => DocumentNumberRule::orderBy('id')->get()->keyBy('document_type'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255', 'address' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:60', 'tagline' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:60', 'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:100', 'bank_account_name' => 'nullable|string|max:255',
            'signatory_name' => 'nullable|string|max:255',
            'rules' => 'required|array:delivery_note,invoice',
            'rules.delivery_note' => 'required|array', 'rules.invoice' => 'required|array',
            'rules.*.format' => 'required|string|max:80', 'rules.*.padding' => 'required|integer|min:1|max:10',
            'rules.*.start_number' => 'required|integer|min:1|max:999999999',
            'rules.*.reset_period' => 'required|in:never,year,month',
        ]);
        foreach ($data['rules'] as $type => $rule) {
            $literal = str_replace(['{SEQ}', '{YYYY}', '{MM}'], '', $rule['format']);
            if (substr_count($rule['format'], '{SEQ}') !== 1 || ! preg_match('/^[A-Za-z0-9\/._ -]*$/D', $literal)
                || ($rule['reset_period'] !== 'never' && ! str_contains($rule['format'], '{YYYY}'))
                || ($rule['reset_period'] === 'month' && ! str_contains($rule['format'], '{MM}'))) {
                throw ValidationException::withMessages(["rules.$type.format" => 'Format harus memuat satu {SEQ}. Reset tahunan memerlukan {YYYY}; reset bulanan memerlukan {YYYY} dan {MM}. Gunakan huruf, angka, spasi, /, -, _, atau titik.']);
            }
        }
        DB::transaction(function () use ($data) {
            $rules = $data['rules'];
            unset($data['rules']);
            CompanyProfile::whereKey(1)->lockForUpdate()->firstOrFail()->update($data);
            foreach (DocumentNumberRule::orderBy('id')->lockForUpdate()->get() as $rule) {
                $rule->update($rules[$rule->document_type]);
            }
        });
        return redirect()->route('company-profile.index')->with('success', 'Profile perusahaan dan aturan penomoran berhasil disimpan.');
    }
}
