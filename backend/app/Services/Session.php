<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Teacher;
use Illuminate\Support\Collection;

/**
 * Représente une séance de cours tenue dans le centre avec sa liste de présences.
 */
class Session
{
    public ?Teacher $teacher;
    public string $subjectName;
    public string $sessionDate;
    public ?string $groupName;
    public Collection $attendances;
    public ?float $defaultPricePerSession;

    public function __construct(
        ?Teacher $teacher = null,
        string $subjectName = '',
        string $sessionDate = '',
        ?string $groupName = null,
        $attendances = [],
        ?float $defaultPricePerSession = null
    ) {
        $this->teacher = $teacher;
        $this->subjectName = $subjectName;
        $this->sessionDate = $sessionDate;
        $this->groupName = $groupName;
        $this->attendances = $attendances instanceof Collection ? $attendances : collect($attendances);
        $this->defaultPricePerSession = $defaultPricePerSession;
    }

    /**
     * Crée une Session à partir d'une collection d'objets Attendance.
     */
    public static function fromAttendances($attendances, ?Teacher $teacher = null, ?float $defaultPrice = null): self
    {
        $collection = $attendances instanceof Collection ? $attendances : collect($attendances);
        $first = $collection->first();

        return new self(
            $teacher ?? ($first instanceof Attendance ? $first->teacher : null),
            $first instanceof Attendance ? $first->subject_name : '',
            $first instanceof Attendance ? $first->session_date : now()->toDateString(),
            $first instanceof Attendance ? $first->group_name : null,
            $collection,
            $defaultPrice
        );
    }

    /**
     * Retourne uniquement les présences effectives (élèves présents).
     */
    public function getPresentAttendances(): Collection
    {
        return $this->attendances->filter(function ($att) {
            if ($att instanceof Attendance) {
                return $att->status === 'present';
            }
            if (is_array($att)) {
                return ($att['status'] ?? 'present') === 'present';
            }
            if (is_object($att) && isset($att->status)) {
                return $att->status === 'present';
            }
            return true;
        });
    }

    /**
     * Nombre d'élèves effectivement présents.
     */
    public function getPresentCount(): int
    {
        return $this->getPresentAttendances()->count();
    }

    /**
     * Ajoute un enregistrement de présence.
     */
    public function addAttendance($attendance): self
    {
        $this->attendances->push($attendance);
        return $this;
    }

    /**
     * Définit le tarif de référence par défaut pour chaque élève de cette séance.
     */
    public function setDefaultPricePerSession(float $price): self
    {
        $this->defaultPricePerSession = $price;
        return $this;
    }

    /**
     * Associe l'enseignant de la séance.
     */
    public function setTeacher(Teacher $teacher): self
    {
        $this->teacher = $teacher;
        return $this;
    }
}
