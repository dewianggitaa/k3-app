<?php

namespace App\Http\Controllers;

use App\Models\ChecklistParameter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class ChecklistParameterController extends Controller
{
    private $assetTypes = [
        ['value' => 'App\\Models\\Apar', 'label' => 'APAR'],
        ['value' => 'App\\Models\\Hydrant', 'label' => 'Hydrant'],
        ['value' => 'App\\Models\\P3k', 'label' => 'Kotak P3K'],
    ];

    public function index(Request $request)
    {
        $selectedType = $request->query('type', 'App\\Models\\Apar');

        $parameters = ChecklistParameter::where('asset_type', $selectedType)
            ->orderBy('order_index')
            ->get();

        return Inertia::render('ChecklistParameters/Index', [
            'parameters' => $parameters,
            'assetTypes' => $this->assetTypes,
            'currentType' => $selectedType,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_type'     => 'required|string',
            'label'          => 'required|string|max:255',
            'input_type'     => 'required|in:text,number,boolean,radio,select,textarea',
            'options'        => 'nullable|array',
            'standard_value' => 'required', 
            
            'order_index'    => 'integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        // Logic pembersihan data
        $data = $request->all();
        
        // Jika bukan pilihan ganda, kosongkan options
        if (!in_array($data['input_type'], ['radio', 'select'])) {
            $data['options'] = null;
        }

        ChecklistParameter::create($data);

        return to_route('checklist-parameters.index', [
            'type' => $request->asset_type
        ])->with('success', 'Parameter berhasil ditambahkan.');
    }

    public function update(Request $request, ChecklistParameter $checklistParameter)
    {
        $request->validate([
            'label'          => 'required|string',
            'input_type'     => 'required',
            'options'        => 'nullable|array',
            'standard_value' => 'required|string',
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
        $checklistParameter->delete();
        return redirect()->back()->with('success', 'Parameter dihapus.');
    }
}