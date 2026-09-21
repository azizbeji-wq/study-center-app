<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleItem;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $items = ScheduleItem::all();
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'day' => 'required|string|max:255',
            'time' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'teacher' => 'required|string|max:255',
            'level' => 'required|string|max:255',
            'room' => 'required|string|max:255',
        ]);

        $item = ScheduleItem::create($data);

        return response()->json($item, 201);
    }

    public function update(Request $request, $id)
    {
        $item = ScheduleItem::findOrFail($id);

        $data = $request->validate([
            'day' => 'sometimes|required|string|max:255',
            'time' => 'sometimes|required|string|max:255',
            'subject' => 'sometimes|required|string|max:255',
            'teacher' => 'sometimes|required|string|max:255',
            'level' => 'sometimes|required|string|max:255',
            'room' => 'sometimes|required|string|max:255',
        ]);

        $item->update($data);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = ScheduleItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Cours supprimé avec succès']);
    }
}
