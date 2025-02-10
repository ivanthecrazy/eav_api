<?php

namespace App\Http\Controllers\Api;

use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Enums\AttributeType;

class AttributeController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a list of all attributes.
     * 
     * @group Attributes
     * @queryParam per_page int The number of attributes to return per page.
     * @authenticated
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Attribute 1",
     *       "type": "string"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost:8000/api/attributes?page=1",
     *     "last": "http://localhost:8000/api/attributes?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "first_page_url": "http://localhost:8000/api/attributes?page=1",
     *   "from": 1,
     *   "last_page": 1,
     *   "last_page_url": "http://localhost:8000/api/attributes?page=1",
     *   "next_page_url": null,
     *   "path": "http://localhost:8000/api/attributes",
     *   "per_page": 10,
     *   "prev_page_url": null,
     *   "to": 10,
     *   "total": 15
     * }
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Attribute::class);
        $perPage = $request->query('per_page', 10);
        return response()->json(Attribute::paginate($perPage));
    }

    /**
     * Create a new attribute.
     * 
     * @group Attributes
     * @bodyParam name string required The name of the attribute.
     * @bodyParam type number required Type of the attribute (1 - text, 2 - date, 3 - number, 4 - select)
     * @authenticated
     * @response 200 {
     *   "message": "Attribute created successfully",
     *   "attribute": {
     *     "id": 1,
     *     "name": "Attribute 1",
     *     "type": "string"
     *   }
     * }
     */
    public function store(Request $request)
    {
        $this->authorize('create', Attribute::class);

        $validated = $request->validate([
            'name' => 'required|string|unique:attributes,name',
            'type' => 'required|integer|in:'.implode(',', AttributeType::values())
        ]);

        $attribute = Attribute::create($validated);

        return response()->json([
            'message' => 'Attribute created successfully',
            'attribute' => $attribute,
        ], 201);
    }

    /**
     * Display a single attribute.
     * 
     * @group Attributes
     * @urlParam id int required The ID of the attribute.
     * @authenticated
     * @response 200 {
     *   "id": 1,
     *   "name": "Attribute 1",
     *   "type": "string"
     * }
     */
    public function show($id)
    {
        $attribute = Attribute::findOrFail($id);
        $this->authorize('view', $attribute);

        return response()->json($attribute);
    }

    /**
     * Update an attribute.
     * 
     * @group Attributes
     * @urlParam id int required The ID of the attribute.
     * @bodyParam name string required The name of the attribute.
     * @bodyParam type number required Type of the attribute (1 - text, 2 - date, 3 - number, 4 - select)
     * @authenticated
     * @response 200 {
     *   "message": "Attribute updated successfully",
     *   "attribute": {
     *     "id": 1,
     *     "name": "Attribute 1",
     *     "type": "string"
     *   }
     * }
     */
    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);
        $this->authorize('update', $attribute);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:attributes,name,' . $id,
            'type' => 'required|integer|in:'.implode(',', AttributeType::values())
        ]);

        $attribute->update($validated);

        return response()->json([
            'message' => 'Attribute updated successfully',
            'attribute' => $attribute,
        ]);
    }

    /**
     * Delete an attribute.
     * 
     * @group Attributes
     * @authenticated
     * @urlParam id int required The ID of the attribute.
     * @response 200 {
     *   "message": "Attribute deleted successfully"
     * }
     */
    public function destroy($id)
    {
        $attribute = Attribute::findOrFail($id);
        $this->authorize('delete', $attribute);

        $attribute->delete();

        return response()->json(['message' => 'Attribute deleted successfully']);
    }
}