<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\DaycareChild;
use App\Models\Payment;
use App\Models\ScheduleItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Users
        User::create([
            'name' => 'Directeur',
            'email' => 'director@studycenter.com',
            'password' => Hash::make('director123'),
            'role' => 'director',
        ]);

        User::create([
            'name' => 'Secrétaire',
            'email' => 'secretary@studycenter.com',
            'password' => Hash::make('secretary123'),
            'role' => 'secretary',
        ]);

        // 2. Teachers
        $teachers = [
            ['name' => 'Prof. Karim Belhadj', 'subject' => 'Mathématiques', 'phone' => '20 111 222', 'email' => 'karim@email.com', 'weekly_lessons' => 8],
            ['name' => 'Prof. Leila Amor', 'subject' => 'Français', 'phone' => '20 222 333', 'email' => 'leila@email.com', 'weekly_lessons' => 6],
            ['name' => 'Prof. Hichem Zaied', 'subject' => 'Physique', 'phone' => '20 333 444', 'email' => 'hichem@email.com', 'weekly_lessons' => 5],
            ['name' => 'Prof. Sonia Jebali', 'subject' => 'Anglais', 'phone' => '20 444 555', 'email' => 'sonia@email.com', 'weekly_lessons' => 5],
        ];

        $teacherModels = [];
        foreach ($teachers as $t) {
            $teacherModels[] = Teacher::create($t);
        }

        // 3. Students
        $students = [
            ['name' => 'Ahmed Ben Ali', 'level' => '7ème année', 'phone' => '20 123 456', 'status' => 'active'],
            ['name' => 'Fatima Mansour', 'level' => '8ème année', 'phone' => '20 234 567', 'status' => 'active'],
            ['name' => 'Mohamed Trabelsi', 'level' => '9ème année', 'phone' => '20 345 678', 'status' => 'active'],
            ['name' => 'Amira Gharbi', 'level' => '1ère année', 'phone' => '20 456 789', 'status' => 'active'],
            ['name' => 'Youssef Saidi', 'level' => 'Bac Sciences', 'phone' => '20 567 890', 'status' => 'inactive'],
        ];

        $studentModels = [];
        foreach ($students as $s) {
            $studentModels[] = Student::create($s);
        }

        // 4. Enrollments
        // Ahmed Ben Ali (index 0)
        StudentSubjectEnrollment::create(['student_id' => $studentModels[0]->id, 'subject_name' => 'Mathématiques', 'teacher_id' => $teacherModels[0]->id]);
        StudentSubjectEnrollment::create(['student_id' => $studentModels[0]->id, 'subject_name' => 'Français', 'teacher_id' => $teacherModels[1]->id]);

        // Fatima Mansour (index 1)
        StudentSubjectEnrollment::create(['student_id' => $studentModels[1]->id, 'subject_name' => 'Mathématiques', 'teacher_id' => $teacherModels[0]->id]);
        StudentSubjectEnrollment::create(['student_id' => $studentModels[1]->id, 'subject_name' => 'Physique', 'teacher_id' => $teacherModels[2]->id]);
        StudentSubjectEnrollment::create(['student_id' => $studentModels[1]->id, 'subject_name' => 'Anglais', 'teacher_id' => $teacherModels[3]->id]);

        // Mohamed Trabelsi (index 2)
        StudentSubjectEnrollment::create(['student_id' => $studentModels[2]->id, 'subject_name' => 'Physique', 'teacher_id' => $teacherModels[2]->id]);
        StudentSubjectEnrollment::create(['student_id' => $studentModels[2]->id, 'subject_name' => 'Mathématiques', 'teacher_id' => $teacherModels[0]->id]);

        // Amira Gharbi (index 3)
        StudentSubjectEnrollment::create(['student_id' => $studentModels[3]->id, 'subject_name' => 'Français', 'teacher_id' => $teacherModels[1]->id]);
        StudentSubjectEnrollment::create(['student_id' => $studentModels[3]->id, 'subject_name' => 'Anglais', 'teacher_id' => $teacherModels[3]->id]);

        // Youssef Saidi (index 4)
        StudentSubjectEnrollment::create(['student_id' => $studentModels[4]->id, 'subject_name' => 'Physique', 'teacher_id' => $teacherModels[2]->id]);

        // 5. Daycare children
        $daycare = [
            ['name' => 'Aya Mansouri', 'age' => 7, 'parent_name' => 'Mme Mansouri', 'phone' => '20 111 222', 'schedule' => 'Lun-Ven 8h-17h', 'status' => 'present'],
            ['name' => 'Yassine Gharbi', 'age' => 6, 'parent_name' => 'M. Gharbi', 'phone' => '20 222 333', 'schedule' => 'Lun-Mer 9h-15h', 'status' => 'present'],
            ['name' => 'Lina Trabelsi', 'age' => 9, 'parent_name' => 'Mme Trabelsi', 'phone' => '20 333 444', 'schedule' => 'Lun-Ven 8h-17h', 'status' => 'present'],
            ['name' => 'Adam Saidi', 'age' => 8, 'parent_name' => 'M. Saidi', 'phone' => '20 444 555', 'schedule' => 'Mar-Sam 10h-16h', 'status' => 'absent'],
            ['name' => 'Salma Ben Ali', 'age' => 10, 'parent_name' => 'Mme Ben Ali', 'phone' => '20 555 666', 'schedule' => 'Lun-Ven 8h-17h', 'status' => 'present'],
            ['name' => 'Omar Jebali', 'age' => 11, 'parent_name' => 'M. Jebali', 'phone' => '20 666 777', 'schedule' => 'Lun-Jeu 9h-15h', 'status' => 'present'],
        ];

        foreach ($daycare as $dc) {
            DaycareChild::create($dc);
        }

        // 6. Payments
        $payments = [
            ['student_name' => 'Ahmed Ben Ali', 'month' => 'Octobre 2023', 'amount' => 150.00, 'date' => '2023-10-05', 'status' => 'paid'],
            ['student_name' => 'Fatima Mansour', 'month' => 'Octobre 2023', 'amount' => 220.00, 'date' => '2023-10-07', 'status' => 'paid'],
            ['student_name' => 'Mohamed Trabelsi', 'month' => 'Octobre 2023', 'amount' => 180.00, 'date' => null, 'status' => 'pending'],
            ['student_name' => 'Amira Gharbi', 'month' => 'Octobre 2023', 'amount' => 150.00, 'date' => '2023-10-10', 'status' => 'paid'],
            ['student_name' => 'Youssef Saidi', 'month' => 'Octobre 2023', 'amount' => 120.00, 'date' => null, 'status' => 'overdue'],
            ['student_name' => 'Aya Mansouri', 'month' => 'Octobre 2023', 'amount' => 300.00, 'date' => '2023-10-02', 'status' => 'paid'],
            ['student_name' => 'Yassine Gharbi', 'month' => 'Octobre 2023', 'amount' => 250.00, 'date' => null, 'status' => 'pending'],
        ];

        foreach ($payments as $p) {
            Payment::create($p);
        }

        // 7. Schedule Items
        $schedules = [
            ['day' => 'Lundi', 'time' => '08:00 - 10:00', 'subject' => 'Mathématiques', 'teacher' => 'Prof. Karim', 'level' => '7ème', 'room' => 'Salle 101'],
            ['day' => 'Lundi', 'time' => '10:15 - 12:15', 'subject' => 'Français', 'teacher' => 'Prof. Leila', 'level' => '8ème', 'room' => 'Salle 102'],
            ['day' => 'Lundi', 'time' => '14:00 - 16:00', 'subject' => 'Physique', 'teacher' => 'Prof. Hichem', 'level' => '9ème', 'room' => 'Salle 103'],
            ['day' => 'Mardi', 'time' => '08:00 - 10:00', 'subject' => 'Anglais', 'teacher' => 'Prof. Sonia', 'level' => 'Bac', 'room' => 'Salle 104'],
            ['day' => 'Mardi', 'time' => '10:15 - 12:15', 'subject' => 'Mathématiques', 'teacher' => 'Prof. Karim', 'level' => '9ème', 'room' => 'Salle 101'],
            ['day' => 'Mercredi', 'time' => '08:00 - 10:00', 'subject' => 'Français', 'teacher' => 'Prof. Leila', 'level' => '7ème', 'room' => 'Salle 102'],
            ['day' => 'Jeudi', 'time' => '14:00 - 16:00', 'subject' => 'Physique', 'teacher' => 'Prof. Hichem', 'level' => 'Bac', 'room' => 'Salle 103'],
            ['day' => 'Vendredi', 'time' => '08:00 - 10:00', 'subject' => 'Anglais', 'teacher' => 'Prof. Sonia', 'level' => '8ème', 'room' => 'Salle 104'],
        ];

        foreach ($schedules as $s) {
            ScheduleItem::create($s);
        }
    }
}
