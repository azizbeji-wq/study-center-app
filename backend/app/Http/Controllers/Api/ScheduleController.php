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
            'group_name' => 'nullable|string|max:100',
            'force' => 'nullable|boolean',
        ]);

        $force = (bool) ($data['force'] ?? false);
        unset($data['force']);

        // Salle deja occupee au meme moment : refus (422), meme avec "force".
        // Groupe deja occupe : avertissement (409), modifiable avec "force".
        // Un enseignant peut donner deux seances en meme temps (dans deux salles differentes).
        if ($conflict = $this->findConflict($data)) {
            if ($conflict['type'] === 'room') {
                return response()->json(['message' => $conflict['message']], 422);
            }
            if (! $force) {
                return response()->json(['message' => $conflict['message']], 409);
            }
        }

        $item = new ScheduleItem();
        $item->forceFill($data)->save();

        \App\Models\ActivityLog::record(
            $request->user(),
            'schedule',
            'created',
            "Cours planifié : {$item->subject} par {$item->teacher} ({$item->day} de {$item->time}, Salle {$item->room})",
            ['schedule_id' => $item->id, 'subject' => $item->subject, 'day' => $item->day],
            $request->ip()
        );

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = ScheduleItem::findOrFail($id);
        return response()->json($item);
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
            'group_name' => 'nullable|string|max:100',
            'force' => 'nullable|boolean',
        ]);

        $force = (bool) ($data['force'] ?? false);
        unset($data['force']);

        $candidate = array_merge(
            $item->only(['day', 'time', 'subject', 'teacher', 'level', 'room', 'group_name']),
            $data
        );

        if ($conflict = $this->findConflict($candidate, (int) $item->id)) {
            if ($conflict['type'] === 'room') {
                return response()->json(['message' => $conflict['message']], 422);
            }
            if (! $force) {
                return response()->json(['message' => $conflict['message']], 409);
            }
        }

        $item->forceFill($data)->save();

        \App\Models\ActivityLog::record(
            $request->user(),
            'schedule',
            'updated',
            "Cours modifié : {$item->subject} ({$item->day} {$item->time})",
            ['schedule_id' => $item->id],
            $request->ip()
        );

        return response()->json($item);
    }

    public function destroy(Request $request, $id)
    {
        $item = ScheduleItem::findOrFail($id);
        $subject = $item->subject;
        $day = $item->day;
        $item->delete();

        \App\Models\ActivityLog::record(
            $request->user(),
            'schedule',
            'deleted',
            "Suppression du cours {$subject} ({$day})",
            ['schedule_id' => $id],
            $request->ip()
        );

        return response()->json(['message' => 'Cours supprimé avec succès']);
    }

    // ------------------------------------------------------------------

    /**
     * Cherche un cours qui chevauche l'horaire demande le meme jour :
     *  - meme salle  : conflit bloquant (type "room")
     *  - meme groupe : avertissement (type "group")
     * Le meme enseignant peut avoir deux seances en meme temps.
     *
     * @return array{type: string, message: string}|null
     */
    private function findConflict(array $candidate, ?int $ignoreId = null): ?array
    {
        [$start, $end] = $this->parseRange($candidate['time'] ?? null);
        if ($start === null || empty($candidate['day'])) {
            return null; // horaire illisible : on ne bloque pas
        }

        $others = ScheduleItem::where('day', $candidate['day'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        $same = function ($a, $b): bool {
            $a = mb_strtolower(trim((string) $a));
            $b = mb_strtolower(trim((string) $b));
            return $a !== '' && $a === $b;
        };

        $groupConflict = null;

        foreach ($others as $other) {
            [$s, $e] = $this->parseRange($other->time);
            if ($s === null || $s >= $end || $e <= $start) {
                continue; // pas de chevauchement
            }

            if ($same($candidate['room'] ?? null, $other->room)) {
                return [
                    'type' => 'room',
                    'message' => "Conflit : la salle {$other->room} est déjà occupée le {$other->day} de {$other->time} ({$other->subject}, {$other->teacher}). Choisissez une autre salle.",
                ];
            }

            if ($groupConflict === null && $same($candidate['group_name'] ?? null, $other->group_name ?? null)) {
                $groupConflict = [
                    'type' => 'group',
                    'message' => "Attention : le groupe {$other->group_name} a déjà un cours le {$other->day} de {$other->time} ({$other->subject}, {$other->teacher}).",
                ];
            }
        }

        return $groupConflict;
    }

    /**
     * Lit un horaire du type "08:00 - 10:00" ou "8h00 - 10h00".
     * Retourne [debut, fin] en minutes, ou [null, null] si illisible.
     */
    private function parseRange(?string $time): array
    {
        if ($time && preg_match('/(\d{1,2})\s*[:hH]\s*(\d{2})\s*(?:-|–|—|à)\s*(\d{1,2})\s*[:hH]\s*(\d{2})/u', $time, $m)) {
            $start = ((int) $m[1]) * 60 + (int) $m[2];
            $end   = ((int) $m[3]) * 60 + (int) $m[4];
            if ($end > $start) {
                return [$start, $end];
            }
        }

        return [null, null];
    }
}