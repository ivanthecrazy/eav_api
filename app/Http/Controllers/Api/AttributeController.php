<?php

namespace App\Http\Controllers\Api;

use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AttributeController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a list of all attributes.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Attribute::class);
        $perPage = $request->query('per_page', 10);
        return response()->json(Attribute::paginate($perPage));
    }

    /**
     * Store a new attribute.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Attribute::class);

        $validated = $request->validate([
            'name' => 'required|string|unique:attributes,name',
            'type' => 'required|string|in:text,number,date,select',
        ]);

        $attribute = Attribute::create($validated);

        return response()->json([
            'message' => 'Attribute created successfully',
            'attribute' => $attribute,
        ], 201);
    }

    /**
     * Display a single attribute.
     */
    public function show($id)
    {
        $attribute = Attribute::findOrFail($id);
        $this->authorize('view', $attribute);

        return response()->json($attribute);
    }

    /**
     * Update an attribute.
     */
    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);
        $this->authorize('update', $attribute);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:attributes,name,' . $id,
            'type' => 'sometimes|string|in:text,number,date,select',
        ]);

        $attribute->update($validated);

        return response()->json([
            'message' => 'Attribute updated successfully',
            'attribute' => $attribute,
        ]);
    }

    /**
     * Delete an attribute.
     */
    public function destroy($id)
    {
        $attribute = Attribute::findOrFail($id);
        $this->authorize('delete', $attribute);

        $attribute->delete();

        return response()->json(['message' => 'Attribute deleted successfully']);
    }
}