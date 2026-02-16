<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductPartner;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductPartnerController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'partner_type' => ['required', 'string'],
            'partner_id' => ['required', 'integer'],
            'alias_sku' => ['nullable', 'string', 'max:255'],
            'alias_name' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($validated['alias_sku']) && empty($validated['alias_name'])) {
            return back()->withErrors(['alias_name' => 'Either Alias SKU or Alias Name must be provided.']);
        }

        $product->partners()->create($validated);

        return back()->with('success', 'Partner alias added successfully.');
    }

    public function destroy(ProductPartner $partner)
    {
        $partner->delete();

        return back()->with('success', 'Partner alias removed successfully.');
    }

    public function template(Request $request)
    {
        if ($request->boolean('with_data')) {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ProductAliasExport, 'product_aliases_data.xlsx');
        }
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\Template\ProductAliasTemplateExport, 'product_aliases_template.xlsx');
    }

    public function import(Request $request) 
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx,xls',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\ProductAliasImport, $request->file('file'));
            return back()->with('success', 'Product Aliases imported successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}
