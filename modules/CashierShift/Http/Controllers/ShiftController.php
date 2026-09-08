<?php

namespace Modules\CashierShift\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Modules\CashierShift\Models\Shift;
use Modules\Order\Models\Order;
use Modules\Payment\Models\Payment;

class ShiftController extends Controller
{
    public function open()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // If user already has an open shift, redirect to POS
        if ($user->hasOpenShift()) {
            return redirect()->route('dashboard');
        }
        
        return Inertia::render('shifts/Open', [
            'lastShift' => $user->shifts()
                ->latest()
                ->whereNotNull('closed_at')
                ->first(),
        ]);
    }

    /**
     * Store a newly opened shift.
     */
    public function store(Request $request)
    {
        $request->validate([
            'opening_cash' => 'required|numeric|min:0|max:100000',
        ]);
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Check if user already has an open shift
        if ($user->hasOpenShift()) {
            return redirect()->route('dashboard');
        }
        
        $shift = Shift::create([
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_cash' => $request->opening_cash,
        ]);
        
        return redirect()->route('dashboard')
            ->with('success', 'Shift opened successfully.');
    }

    /**
     * Show the close shift form.
     */
    public function close()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $shift = $user->openShift;
        
        if (!$shift) {
            return redirect()->route('shifts.open')->with('error', 'No open shift found');
        }

        // Get all sales from this shift
        $sales = Order::where('shift_id', $shift->id)
            ->with('payments')
            ->orderBy('completed_at')
            ->get();

        // Calculate the total cash payments for this shift
        $cash_payments = Payment::whereIn('sale_id', $sales->pluck('id'))
            ->where('method', 'cash')
            ->sum('amount');

        
        // Calculate expected cash (opening cash + cash sales)
        $expected_cash = $shift->opening_cash + $cash_payments;
        
        return Inertia::render('shifts/Close', [
            'shift' => $shift,
            'sales' => $sales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'total_amount' => $sale->total_amount,
                    'cash_amount' => $sale->payments
                        ->where('method', 'cash')
                        ->sum('amount'),
                    'completed_at' => $sale->completed_at,
                ];
            }),
            'expectedCash' => $expected_cash,
            'cash_payments' => $cash_payments,
        ]);
    }

    /**
     * Close the current shift.
     */
    public function update(Request $request)
    {
        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $shift = $user->openShift;
        
        if (!$shift) {
            return redirect()->route('shifts.open')
                ->with('error', 'No open shift found.');
        }
        
        try {
            DB::beginTransaction();
            
            // Calculate variance
            $sales = Order::where('shift_id', $shift->id)->get();
            $cashPayments = Payment::whereIn('sale_id', $sales->pluck('id'))
                ->where('method', 'cash')
                ->sum('amount');
            $expectedCash = $shift->opening_cash + $cashPayments;
            $variance = $request->closing_cash - $expectedCash;
            
            $shift->update([
                'closed_at' => now(),
                'closing_cash' => $request->closing_cash,
                'notes' => $request->notes . ($variance != 0 ? " | Variance: " . number_format($variance, 2) : ""),
            ]);
            
            // Record closing cash movement
            // CashMovement::create([
            //     'shift_id' => $shift->id,
            //     'user_id' => $user->id,
            //     'type' => 'closing',
            //     'amount' => $request->closing_cash,
            //     'note' => 'Closing cash for shift' . ($variance != 0 ? " (Variance: {$variance})" : ""),
            // ]);
            
            DB::commit();
            
            $message = 'Shift closed successfully.';
            if ($variance != 0) {
                $message .= ' Variance: ' . number_format($variance, 2);
            }
            
            return redirect()->route('dashboard')
                ->with('success', $message);
                
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to close shift. Please try again.');
        }
    }

    /**
     * Show shift history.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $shifts = $user->shifts()
            ->withCount('sales')
            ->latest()
            ->paginate(20);
        
        // Add sales total to each shift
        foreach ($shifts as $shift) {
            $shift->total_sales = Order::where('shift_id', $shift->id)->sum('total_amount');
        }
        
        return Inertia::render('shifts/Index', [
            'shifts' => $shifts,
        ]);
    }
}
