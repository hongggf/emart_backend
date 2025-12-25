<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{

    public function index(Request $request)
    {
        // -------------------- TOTALS --------------------
        $totalOrders    = Order::count();
        $totalSales     = (int) Order::sum('total_amount');
        $totalCustomers = User::where('role', 'customer')->count();
        $totalProducts  = Product::count();

        // -------------------- WEEKLY SALES --------------------
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek   = Carbon::now()->endOfWeek();

        $weeklySalesQuery = Order::select(
            DB::raw('DAYOFWEEK(created_at) as day'),
            DB::raw('SUM(total_amount) as total')
        )
        ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
        ->groupBy('day')
        ->get();

        $salesSummary = array_fill(0, 7, 0.0);
        foreach ($weeklySalesQuery as $sale) {
            $index = $sale->day == 1 ? 6 : $sale->day - 2;
            $salesSummary[$index] = (float) $sale->total;
        }

        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        // -------------------- LOW STOCK ALERT --------------------
        $lowStockProducts = Product::orderBy('stock_quantity', 'asc')->take(3)->get();

        // -------------------- TOP 3 NEW USERS --------------------
        // -------------------- TOP 3 NEW USERS --------------------
        $topUsers = User::with('creator')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($user) {
                return [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'phone'      => $user->phone ?? '',
                    'role'       => $user->role,
                    'created_by' => $user->created_by ?? $user->id, // same as GetAllUserList
                    'avatar'     => $user->avatar ? asset('storage/' . $user->avatar) : '',
                    'creator'    => $user->creator
                        ? [
                            'id'    => $user->creator->id,
                            'name'  => $user->creator->name ?? '',
                            'email' => $user->creator->email ?? '',
                        ]
                        : [
                            'id'    => $user->id,
                            'name'  => $user->name ?? '',
                            'email' => $user->email ?? '',
                        ],
                    'created_at' => $user->created_at?->toDateTimeString(),
                    'updated_at' => $user->updated_at?->toDateTimeString(),
                ];
        });

        // -------------------- CURRENT USER DETAIL --------------------
        $currentUser = $request->user();
        $currentUserDetail = $currentUser ? [
            'id'         => $currentUser->id,
            'name'       => $currentUser->name ?? '',
            'email'      => $currentUser->email ?? '',
            'phone'      => $currentUser->phone ?? '',
            'role'       => $currentUser->role ?? '',
            'avatar'     => $currentUser->avatar ? asset('storage/' . $currentUser->avatar) : '',
            'created_at' => $currentUser->created_at?->toDateTimeString(),
            'updated_at' => $currentUser->updated_at?->toDateTimeString(),
        ] : null;

        return response()->json([
            'success' => true,
            'data' => [
                'totals'             => [
                    'total_orders'    => $totalOrders,
                    'total_sales'     => $totalSales,
                    'total_customers' => $totalCustomers,
                    'total_products'  => $totalProducts,
                ],
                'weekly_sales'       => [
                    'salesSummary' => $salesSummary,
                    'days'         => $days,
                ],
                'low_stock_products' => $lowStockProducts,
                'top_new_users'      => $topUsers,
                'current_user'       => $currentUserDetail,
            ]
        ]);
    }

}