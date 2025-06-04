<?php

namespace App\Http\Controllers;

use App\Models\Till;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class TillController extends Controller
{


    public function startTill()
    {
        $userId = Auth::id();

        $existingOpenTill = Till::where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();

        if ($existingOpenTill) {
            return response()->json(['message' => 'You already have an open till. Please close it first.'], 400);
        }

        return view('till.start');
    }

    public function endtTill()
    {
        $userId = Auth::id();
        $tillId = Session::get('current_till_id');

        if (!$tillId) {
            return response()->json(['message' => 'No till is currently open for you'], 400);
        }

        $till = Till::where('id', $tillId)
            ->where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();

        if (!$till) {
            return response()->json(['message' => 'Till not found or not open by you'], 404);
        }

        return view('till.end', compact('till'));
    }

    public function openTill(Request $request)
    {
        $userId = Auth::id();

        $existingOpenTill = Till::where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();

        if ($existingOpenTill) {
            return response()->json(['message' => 'You already have an open till. Please close it first.'], 400);
        }

        $till = Till::create([
            'user_id' => $userId,
            'opened_at' => now(),
            'cash' => $request->input('cash', 0),
            'visa' => $request->input('visa', 0),
        ]);

        Session::put('current_till_id', $till->id);

        return response()->json(['message' => 'Till opened successfully', 'till_id' => $till->id]);
    }


    public function closeTill(Request $request)
    {
        $userId = Auth::id();
        $tillId = Session::get('current_till_id');
        // $userId = 1;
        // $tillId = 1;

        if (!$tillId) {
            return response()->json(['message' => 'No till is currently open for you'], 400);
        }

        $till = Till::where('id', $tillId)
            ->where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();

        if (!$till) {
            return response()->json(['message' => 'Till not found or not open by you'], 404);
        }

        $till->update([
            'closed_at' => now(),
            'cash' => $request->input('cash', 0),
            'visa' => $request->input('visa', 0),
            'shortage' => $request->input('shortage', 0),
            'surplus' => $request->input('surplus', 0),
        ]);

        Session::forget('current_till_id');

        $payments = $till->payments()->get();

        return response()->json([
            'message' => 'Till closed successfully',
            'till' => $till,
            'payments' => $payments
        ]);
    }
}
