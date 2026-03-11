<?php

namespace App\Http\Controllers;

use App\Models\ChecklistParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class ChecklistParameterController extends Controller
{
    private $assetTypes = [
        ['value' => 'App\\Models\\Apar',    'label' => 'APAR'],
        ['value' => 'App\\Models\\Hydrant', 'label' => 'Hydrant'],
        ['value' => 'App\\Models\\P3k',     'label' => 'Kotak P3K'],
    ];

    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('manage-checklist-parameters'), 403, 'Anda tidak memiliki izin mengakses parameter checklist.');

        $selectedType = $request->query('type', 'App\\Models\\Apar');

        $parameters = ChecklistParameter::where('asset_type', $selectedType)
            ->orderBy('order_index')
            ->get();

        return Inertia::render('MasterData/ChecklistParameters/Index', [
            'parameters'  => $parameters,
            'assetTypes'  => $this->assetTypes,
            'currentType' => $selectedType,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('manage-checklist-parameters'), 403, 'Anda tidak memiliki izin.');

        $validator = Validator::make($request->all(), [
            'asset_type'     => 'required|string',
            'label'          => 'required|string|max:255',
            'input_type'     => 'required|in:text,number,boolean,radio,select,textarea,date',
            'options'        => 'nullable|array',
            'standard_value' => 'nullable',
            'order_index'    => 'integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $data = $request->all();

        if (!in_array($data['input_type'], ['radio', 'select'])) {
            $data['options'] = null;
        }

        if (in_array($data['input_type'], ['date', 'textarea'])) {
            $data['standard_value'] = null;
        }

        ChecklistParameter::create($data);

        return to_route('checklist-parameters.index', [
            'type' => $request->asset_type,
        ])->with('success', 'Parameter berhasil ditambahkan.');
    }

    public function update(Request $request, ChecklistParameter $checklistParameter)
    {
        abort_unless(Auth::user()->can('manage-checklist-parameters'), 403, 'Anda tidak memiliki izin.');

        $request->validate([
            'label'          => 'required|string',
            'input_type'     => 'required',
            'options'        => 'nullable|array',
            'standard_value' => 'nullable|string',
            'order_index'    => 'integer',
        ]);

        $data = $request->all();

        if (!in_array($data['input_type'], ['radio', 'select'])) {
            $data['options'] = null;
        }

        $checklistParameter->update($data);

        return redirect()->back()->with('success', 'Parameter berhasil diperbarui.');
    }

    public function destroy(ChecklistParameter $checklistParameter)
    {
        abort_unless(Auth::user()->can('manage-checklist-parameters'), 403, 'Anda tidak memiliki izin.');

        $checklistParameter->delete();
        return redirect()->back()->with('success', 'Parameter dihapus.');
    }
}