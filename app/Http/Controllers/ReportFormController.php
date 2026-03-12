<?php

namespace App\Http\Controllers;

use App\Models\ReportDocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportFormController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('manage-report-forms'), 403, 'Anda tidak memiliki izin mengakses format laporan.');

        $tab = $request->query('tab', 'p3k');

        $versions = ReportDocumentVersion::where('asset_type', $tab)
            ->orderByRaw("FIELD(status, 'active', 'draft', 'inactive')")
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($v) {
                $v->needs_renewal = $v->needsRenewal();
                return $v;
            });

        $needsRenewalAny = ReportDocumentVersion::hasRenewalNeeded();
        $typesNeedingRenewal = ReportDocumentVersion::typesNeedingRenewal();

        return Inertia::render('MasterData/ReportForm/Index', [
            'versions' => $versions,
            'activeTab' => $tab,
            'needsRenewalAny' => $needsRenewalAny,
            'typesNeedingRenewal' => $typesNeedingRenewal,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('manage-report-forms'), 403, 'Anda tidak memiliki izin.');

        $request->validate([
            'asset_type' => 'required|in:p3k,apar,hydrant',
            'document_code' => 'required|string|max:100',
            'attachment_number' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'revision_number' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        ReportDocumentVersion::create([
            'asset_type' => $request->asset_type,
            'document_code' => $request->document_code,
            'attachment_number' => $request->attachment_number,
            'title' => $request->title,
            'revision_number' => $request->revision_number,
            'status' => 'draft',
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Versi dokumen baru berhasil dibuat sebagai draft.');
    }

    public function update(Request $request, ReportDocumentVersion $reportForm)
    {
        abort_unless(Auth::user()->can('manage-report-forms'), 403, 'Anda tidak memiliki izin.');

        if ($reportForm->status !== 'draft') {
            return redirect()->back()->with('error', 'Hanya versi draft yang dapat diedit.');
        }

        $request->validate([
            'document_code' => 'required|string|max:100',
            'attachment_number' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'revision_number' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $reportForm->update($request->only([
            'document_code', 'attachment_number', 'title', 'revision_number', 'notes'
        ]));

        return redirect()->back()->with('success', 'Versi dokumen berhasil diperbarui.');
    }

    public function activate(ReportDocumentVersion $reportForm)
    {
        abort_unless(Auth::user()->can('manage-report-forms'), 403, 'Anda tidak memiliki izin.');

        if ($reportForm->status !== 'draft') {
            return redirect()->back()->with('error', 'Hanya versi draft yang dapat diaktifkan.');
        }

        $reportForm->activate();

        $assetTypeLabel = strtoupper($reportForm->asset_type);
        activity()
            ->performedOn($reportForm)
            ->causedBy(Auth::user())
            ->event('updated')
            ->withProperties([
                'attributes' => [
                    'asset_type' => $reportForm->asset_type,
                    'document_code' => $reportForm->document_code,
                    'revision_number' => $reportForm->revision_number,
                    'status' => 'active',
                ]
            ])
            ->log("Mengaktifkan versi dokumen baru untuk {$assetTypeLabel} (Revisi {$reportForm->revision_number})");

        return redirect()->back()->with('success', 'Versi dokumen berhasil diaktifkan. Versi sebelumnya telah dinonaktifkan.');
    }

    public function destroy(ReportDocumentVersion $reportForm)
    {
        abort_unless(Auth::user()->can('manage-report-forms'), 403, 'Anda tidak memiliki izin.');

        if ($reportForm->status !== 'draft') {
            return redirect()->back()->with('error', 'Hanya versi draft yang dapat dihapus.');
        }

        $reportForm->delete();

        return redirect()->back()->with('success', 'Versi draft berhasil dihapus.');
    }

    public function preview(ReportDocumentVersion $reportForm)
    {
        abort_unless(Auth::user()->can('manage-report-forms'), 403, 'Anda tidak memiliki izin.');

        $pdftab = $reportForm->asset_type;
        $pdfView = 'pdf.' . $pdftab;
        $orientation = $pdftab === 'apar' ? 'portrait' : 'landscape';

        $checklistCols = \App\Models\ChecklistParameter::where(function($q) use ($pdftab) {
            $modelClass = $pdftab === 'apar' ? \App\Models\Apar::class : ($pdftab === 'hydrant' ? \App\Models\Hydrant::class : $pdftab);
            $q->where('asset_type', $pdftab)->orWhere('asset_type', $modelClass);
        })->where('input_type', '!=', 'date')->orderBy('order_index')->get();

        $pdf = Pdf::loadView($pdfView, [
            'data' => collect(),
            'tab' => strtoupper($pdftab),
            'startDate' => now()->startOfMonth()->toDateString(),
            'endDate' => now()->endOfMonth()->toDateString(),
            'selectedAsset' => ' ',
            'printedBy' => auth()->user()->name ?? 'Preview',
            'printedByDepartment' => auth()->user()->department->name ?? 'K3',
            'supervisor' => '',
            'documentVersion' => $reportForm,
            'p3kInventory' => collect(),
            'checklistCols' => $checklistCols,
            'isPreview' => true,
            'roomName' => ' ',
            'yearRange' => now()->year,
        ])->setPaper('a4', $orientation);

        return $pdf->stream('Preview_' . strtoupper($reportForm->asset_type) . '_v' . $reportForm->revision_number . '.pdf');
    }
}
