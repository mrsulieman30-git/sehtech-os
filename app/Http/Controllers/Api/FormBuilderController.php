<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormBuilderController extends Controller
{
    public function index()
    {
        $forms = Form::withCount('submissions')->orderBy('created_at', 'desc')->get();
        return response()->json(['forms' => $forms]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'required|array',
            'fields.*.type' => 'required|string',
            'fields.*.label' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $form = Form::create([
                'title' => $request->title,
                'description' => $request->description,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            foreach ($request->fields as $index => $fieldData) {
                FormField::create([
                    'form_id' => $form->id,
                    'type' => $fieldData['type'],
                    'label' => $fieldData['label'],
                    // Generate a safe machine-name if none provided
                    'name' => $fieldData['name'] ?? Str::slug($fieldData['label'], '_'),
                    'placeholder' => $fieldData['placeholder'] ?? null,
                    'is_required' => $fieldData['is_required'] ?? false,
                    'options' => $fieldData['options'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Form created successfully', 'form' => $form]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to save form', 'error' => $e->getMessage()], 500);
        }
    }
}
