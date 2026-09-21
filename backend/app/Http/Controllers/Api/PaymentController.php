<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::all();

        $formatted = $payments->map(function ($p) {
            return [
                'id' => $p->id,
                'studentName' => $p->student_name,
                'month' => $p->month,
                'amount' => $p->amount,
                'date' => $p->date,
                'status' => $p->status,
            ];
        });

        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'studentName' => 'required|string|max:255',
            'month' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'date' => 'nullable|string|max:255',
            'status' => 'required|string|in:paid,pending,overdue',
        ]);

        if (isset($data['studentName'])) {
            $data['student_name'] = $data['studentName'];
            unset($data['studentName']);
        }

        $payment = Payment::create($data);

        return response()->json([
            'id' => $payment->id,
            'studentName' => $payment->student_name,
            'month' => $payment->month,
            'amount' => $payment->amount,
            'date' => $payment->date,
            'status' => $payment->status,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $data = $request->validate([
            'studentName' => 'sometimes|required|string|max:255',
            'month' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|numeric',
            'date' => 'nullable|string|max:255',
            'status' => 'sometimes|required|string|in:paid,pending,overdue',
        ]);

        if (isset($data['studentName'])) {
            $data['student_name'] = $data['studentName'];
            unset($data['studentName']);
        }

        $payment->update($data);

        return response()->json([
            'id' => $payment->id,
            'studentName' => $payment->student_name,
            'month' => $payment->month,
            'amount' => $payment->amount,
            'date' => $payment->date,
            'status' => $payment->status,
        ]);
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json(['message' => 'Paiement supprimé avec succès']);
    }
}
