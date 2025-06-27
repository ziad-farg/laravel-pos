<?php

namespace App\Http\Controllers;

use App\Models\Till;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\OpenTillRequest;
use App\Http\Requests\CloseTillRequest;
use Illuminate\Support\Facades\Session;


class TillController extends Controller
{

    /**
     * TillController constructor.
     *
     * Applies the 'auth' middleware to ensure that only authenticated users can access the methods in this controller.
     */
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

    /**
     * Show the form for ending the till.
     *
     * This method checks if there is a currently open till for the authenticated user.
     * If an open till exists, it returns the view to end the till; otherwise, it returns an error message.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function endTill()
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

    /**
     * Open a new till for the authenticated user.
     *
     * This method checks if the user already has an open till. If not, it creates a new till
     * with the provided cash and visa amounts, and stores the till ID in the session.
     *
     * @param OpenTillRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function openTill(OpenTillRequest $request)
    {
        $user = Auth::user();

        $existingOpenTill = Till::where('user_id', $user->id)
            ->whereNull('closed_at')
            ->first();

        if ($existingOpenTill) {
            return response()->json(['message' => 'You already have an open till. Please close it first.'], 400);
        }

        try {
            DB::beginTransaction();

            $till = Till::create([
                'user_id' => $user->id,
                'opened_at' => now(),
                'cash_handed_over' => $request->cash_handed_over,
                'visa_handed_over' => $request->visa_handed_over,
                'shortage' => 0,
                'surplus' => 0,
            ]);

            Session::put('current_till_id', $till->id);

            DB::commit();

            return response()->json([
                'message' => 'Till opened successfully',
                'till_id' => $till->id,
                'till_data' => $till->load('user')
            ], 201);
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error opening till: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close the currently open till for the authenticated user.
     *
     * This method checks if there is an open till for the user, updates its closed_at timestamp,
     * and clears the session variable for the current till ID.
     *
     * @param CloseTillRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function closeTill(CloseTillRequest $request)
    {
        $user = Auth::user();

        $till = Till::where('user_id', $user->id)
            ->whereNull('closed_at')
            ->first();

        if (!$till) {
            return response()->json([
                'message' => 'No till is currently open for you or till not found by you.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $till->update([
                'closed_at' => now(),
                'cash_handed_over' => $request->cash_handed_over,
                'visa_handed_over' => $request->visa_handed_over,
                'shortage' => $request->input('shortage', 0),
                'surplus' => $request->input('surplus', 0),
            ]);

            Session::forget('current_till_id');

            DB::commit();

            $payments = $till->payments()->get();

            return response()->json([
                'message' => 'Till closed successfully',
                'till' => $till->load('user'),
                'payments' => $payments
            ]);
        } catch (\Exception | \Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error closing till: ' . $e->getMessage()], 500);
        }
    }
}
