<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DaycareChild;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DaycareController extends Controller
{
    public function index()
    {
        return response()->json(DaycareChild::all()->map(fn ($c) => $this->format($c)));
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
            'monthlyFee' => 'nullable|numeric|min:0',
            'startDate' => 'nullable|date',
        ]);

        $child = new DaycareChild();
        $child->forceFill($this->toAttributes($data))->save();

        \App\Models\ActivityLog::record(
            $request->user(),
            'daycare',
            'created',
            "Inscription en garderie : {$child->name} ({$child->age} ans)",
            ['child_id' => $child->id, 'parent' => $child->parent_name],
            $request->ip()
        );

        return response()->json($this->format($child), 201);
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
            'monthlyFee' => 'nullable|numeric|min:0',
            'startDate' => 'nullable|date',
        ]);

        $child->forceFill($this->toAttributes($data))->save();

        \App\Models\ActivityLog::record(
            $request->user(),
            'daycare',
            'updated',
            "Mise à jour enfant de garderie : {$child->name}",
            ['child_id' => $child->id],
            $request->ip()
        );

        return response()->json($this->format($child));
    }

    public function destroy(Request $request, $id)
    {
        $child = DaycareChild::findOrFail($id);
        $name = $child->name;
        $child->delete();

        \App\Models\ActivityLog::record(
            $request->user(),
            'daycare',
            'deleted',
            "Suppression de l'enfant en garderie : {$name}",
            ['child_id' => $id],
            $request->ip()
        );

        return response()->json(['message' => 'Enfant de garde supprimé avec succès']);
    }

    /** Convertit les noms de champs du front (camelCase) vers ceux de la base. */
    private function toAttributes(array $data): array
    {
        $map = [
            'parentName' => 'parent_name',
            'monthlyFee' => 'monthly_fee',
            'startDate'  => 'start_date',
        ];

        $attributes = [];
        foreach ($data as $key => $value) {
            $attributes[$map[$key] ?? $key] = $value;
        }

        // monthly_fee n'accepte pas NULL
        if (array_key_exists('monthly_fee', $attributes) && $attributes['monthly_fee'] === null) {
            $attributes['monthly_fee'] = 0;
        }

        return $attributes;
    }

    private function format(DaycareChild $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'age' => $c->age,
            'parentName' => $c->parent_name,
            'phone' => $c->phone,
            'schedule' => $c->schedule,
            'status' => $c->status,
            'monthlyFee' => (float) $c->monthly_fee,
            'startDate' => $c->start_date ? Carbon::parse($c->start_date)->toDateString() : null,
        ];
    }
}