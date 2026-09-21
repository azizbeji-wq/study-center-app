<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DaycareChild;
use Illuminate\Http\Request;

class DaycareController extends Controller
{
    public function index()
    {
        $children = DaycareChild::all();
        
        $formatted = $children->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'age' => $c->age,
                'parentName' => $c->parent_name,
                'phone' => $c->phone,
                'schedule' => $c->schedule,
                'status' => $c->status,
            ];
        });

        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer',
            'parentName' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'schedule' => 'required|string|max:255',
            'status' => 'nullable|string|in:present,absent',
        ]);

        // Map parentName to parent_name
        if (isset($data['parentName'])) {
            $data['parent_name'] = $data['parentName'];
            unset($data['parentName']);
        }

        $child = DaycareChild::create($data);

        return response()->json([
            'id' => $child->id,
            'name' => $child->name,
            'age' => $child->age,
            'parentName' => $child->parent_name,
            'phone' => $child->phone,
            'schedule' => $child->schedule,
            'status' => $child->status,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $child = DaycareChild::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'age' => 'sometimes|required|integer',
            'parentName' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:255',
            'schedule' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|string|in:present,absent',
        ]);

        if (isset($data['parentName'])) {
            $data['parent_name'] = $data['parentName'];
            unset($data['parentName']);
        }

        $child->update($data);

        return response()->json([
            'id' => $child->id,
            'name' => $child->name,
            'age' => $child->age,
            'parentName' => $child->parent_name,
            'phone' => $child->phone,
            'schedule' => $child->schedule,
            'status' => $child->status,
        ]);
    }

    public function destroy($id)
    {
        $child = DaycareChild::findOrFail($id);
        $child->delete();

        return response()->json(['message' => 'Enfant de garde supprimé avec succès']);
    }
}
