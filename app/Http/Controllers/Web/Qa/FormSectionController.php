<?php

namespace App\Http\Controllers\Web\Qa;

use App\Http\Controllers\Controller;
use App\Models\FormSection;
use App\Models\Form;
use Illuminate\Http\Request;

class FormSectionController extends Controller
{
    public function store(Request $request, Form $form)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $section = $form->sections()->create($data);

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    public function update(Request $request, FormSection $section)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $section->update($data);

        return response()->json([
            'success' => true,
            'section' => $section,
        ]);
    }

    public function destroy(FormSection $section)
    {
        // Check if section has questions
        if ($section->questions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete section with questions. Please delete questions first.',
            ], 422);
        }

        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Section deleted successfully.',
        ]);
    }
}
