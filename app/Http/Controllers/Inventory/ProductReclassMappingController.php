<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductReclassMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use App\Exports\Template\ProductReclassMappingTemplateExport;
use App\Imports\ProductReclassMappingImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductReclassMappingController extends Controller
{
    public function index(): Response
    {
        $mappings = ProductReclassMapping::query()
            ->with(['sourceProduct:id,sku,name', 'targetProduct:id,sku,name', 'createdBy:id,name'])
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('source_product_id')
            ->get();

        $mappingProducts = $mappings
            ->flatMap(function ($m) {
                $out = [];

                if ($m->sourceProduct) {
                    $sku = $m->sourceProduct->sku ?? '-';
                    $out[] = [
                        'id' => $m->sourceProduct->id,
                        'label' => "[{$sku}] {$m->sourceProduct->name}",
                        'sku' => $m->sourceProduct->sku,
                        'name' => $m->sourceProduct->name,
                    ];
                }

                if ($m->targetProduct) {
                    $sku = $m->targetProduct->sku ?? '-';
                    $out[] = [
                        'id' => $m->targetProduct->id,
                        'label' => "[{$sku}] {$m->targetProduct->name}",
                        'sku' => $m->targetProduct->sku,
                        'name' => $m->targetProduct->name,
                    ];
                }

                return $out;
            })
            ->unique('id')
            ->values();

        return Inertia::render('Inventory/Reclassifications/Mappings', [
            'mappings' => $mappings,
            'productLookupUrl' => route('inventory.products.lookup'),
            'mappingProducts' => $mappingProducts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_product_id' => 'required|exists:products,id',
            'target_product_id' => 'required|exists:products,id|different:source_product_id',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $sourceId = (int) $validated['source_product_id'];
        $targetId = (int) $validated['target_product_id'];

        $exists = ProductReclassMapping::query()
            ->whereNull('deleted_at')
            ->where('source_product_id', $sourceId)
            ->where('target_product_id', $targetId)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Mapping source-target ini sudah ada.');
        }

        DB::transaction(function () use ($validated, $sourceId, $targetId) {
            $hasAny = ProductReclassMapping::query()
                ->whereNull('deleted_at')
                ->where('source_product_id', $sourceId)
                ->exists();

            $isDefault = (bool) ($validated['is_default'] ?? false);
            if (!$hasAny) {
                $isDefault = true;
            }

            if ($isDefault) {
                ProductReclassMapping::query()
                    ->whereNull('deleted_at')
                    ->where('source_product_id', $sourceId)
                    ->update(['is_default' => false]);
            }

            ProductReclassMapping::create([
                'source_product_id' => $sourceId,
                'target_product_id' => $targetId,
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'is_default' => $isDefault,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $activeCount = ProductReclassMapping::query()
                ->whereNull('deleted_at')
                ->where('source_product_id', $sourceId)
                ->where('is_active', true)
                ->count();

            if ($activeCount > 1) {
                $defaultCount = ProductReclassMapping::query()
                    ->whereNull('deleted_at')
                    ->where('source_product_id', $sourceId)
                    ->where('is_active', true)
                    ->where('is_default', true)
                    ->count();

                if ($defaultCount === 0) {
                    $fallbackId = ProductReclassMapping::query()
                        ->whereNull('deleted_at')
                        ->where('source_product_id', $sourceId)
                        ->where('is_active', true)
                        ->orderBy('id')
                        ->value('id');

                    if ($fallbackId) {
                        ProductReclassMapping::query()
                            ->where('id', $fallbackId)
                            ->update(['is_default' => true]);
                    }
                }
            }
        });

        return back()->with('success', 'Mapping berhasil ditambahkan.');
    }

    public function update(Request $request, ProductReclassMapping $reclassMapping)
    {
        $validated = $request->validate([
            'source_product_id' => 'required|exists:products,id',
            'target_product_id' => 'required|exists:products,id|different:source_product_id',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $sourceId = (int) $validated['source_product_id'];
        $targetId = (int) $validated['target_product_id'];

        $exists = ProductReclassMapping::query()
            ->whereNull('deleted_at')
            ->where('source_product_id', $sourceId)
            ->where('target_product_id', $targetId)
            ->where('id', '!=', $reclassMapping->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Mapping source-target ini sudah ada.');
        }

        try {
            DB::transaction(function () use ($validated, $reclassMapping, $sourceId, $targetId) {
                $isDefault = (bool) ($validated['is_default'] ?? false);
                $isActive = (bool) ($validated['is_active'] ?? true);

                $activeCountAfter = ProductReclassMapping::query()
                    ->whereNull('deleted_at')
                    ->where('source_product_id', $sourceId)
                    ->where('is_active', true)
                    ->when(!$isActive, fn ($q) => $q->where('id', '!=', $reclassMapping->id))
                    ->count();

                $willBeDefault = $isDefault && $isActive;

                if ($activeCountAfter > 1 && !$willBeDefault) {
                    $otherDefaultExists = ProductReclassMapping::query()
                        ->whereNull('deleted_at')
                        ->where('source_product_id', $sourceId)
                        ->where('is_active', true)
                        ->where('is_default', true)
                        ->where('id', '!=', $reclassMapping->id)
                        ->exists();

                    if (!$otherDefaultExists && $reclassMapping->is_default) {
                        throw new \RuntimeException('Source ini punya lebih dari 1 target. Wajib ada 1 Default Target.');
                    }
                }

                if ($isDefault) {
                    ProductReclassMapping::query()
                        ->whereNull('deleted_at')
                        ->where('source_product_id', $sourceId)
                        ->where('id', '!=', $reclassMapping->id)
                        ->update(['is_default' => false]);
                }

                $reclassMapping->update([
                    'source_product_id' => $sourceId,
                    'target_product_id' => $targetId,
                    'is_active' => $isActive,
                    'is_default' => $isDefault,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Mapping berhasil diupdate.');
    }

    public function destroy(ProductReclassMapping $reclassMapping)
    {
        DB::transaction(function () use ($reclassMapping) {
            $sourceId = (int) $reclassMapping->source_product_id;
            $wasDefault = (bool) $reclassMapping->is_default;

            $reclassMapping->delete();

            $activeCount = ProductReclassMapping::query()
                ->whereNull('deleted_at')
                ->where('source_product_id', $sourceId)
                ->where('is_active', true)
                ->count();

            if ($activeCount > 1 && $wasDefault) {
                $fallbackId = ProductReclassMapping::query()
                    ->whereNull('deleted_at')
                    ->where('source_product_id', $sourceId)
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->value('id');

                if ($fallbackId) {
                    ProductReclassMapping::query()
                        ->where('id', $fallbackId)
                        ->update(['is_default' => true]);
                }
            }
        });

        return back()->with('success', 'Mapping berhasil dihapus.');
    }

    public function template(Request $request)
    {
        if ($request->boolean('with_data')) {
            return Excel::download(new \App\Exports\ProductReclassMappingDataExport, 'reclass_mappings_data_' . now()->format('Y-m-d') . '.xlsx');
        }
        return Excel::download(new ProductReclassMappingTemplateExport, 'reclass_mappings_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            $import = new ProductReclassMappingImport($request->boolean('overwrite'));
            Excel::import($import, $request->file('file'));

            $imported = $import->getImportedCount();
            $updated = $import->getUpdatedCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();

            if ($imported === 0 && $updated === 0 && $skipped === 0 && empty($errors)) {
                return back()->with('error', 'File kosong atau tidak ada data yang bisa diproses.');
            }

            $message = "Import selesai. ";
            if ($imported > 0) {
                $message .= "{$imported} mapping baru ditambahkan. ";
            }
            if ($updated > 0) {
                $message .= "{$updated} mapping diperbarui. ";
            }
            if ($skipped > 0) {
                $message .= "{$skipped} baris dilewati/gagal. ";
            }

            if (!empty($errors)) {
                return back()->with('success', $message)->with('error', implode(' | ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? '... dan lainnya.' : ''));
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses file import: ' . $e->getMessage());
        }
    }
}

