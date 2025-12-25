<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    
    public function topSellingProducts()
    {
        $data = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as total')
            )
            ->groupBy('products.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $data->pluck('name'),
                'values' => $data->pluck('total')->map(fn ($v) => (int) $v),
            ]
        ]);
    }

    public function leastSellingProducts()
    {
        $data = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as total')
            )
            ->groupBy('products.name')
            ->orderBy('total')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $data->pluck('name'),
                'values' => $data->pluck('total')->map(fn ($v) => (int) $v),
            ]
        ]);
    }

    public function productRevenue()
    {
        $data = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity * order_items.price) as revenue')
            )
            ->groupBy('products.name')
            ->orderByDesc('revenue')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $data->pluck('name'),
                'values' => $data->pluck('revenue')->map(fn ($v) => (float) $v),
            ]
        ]);
    }

    public function stockLevels()
    {
        $products = Product::orderBy('stock_quantity', 'asc')->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $products->pluck('name'),
                'values' => $products->pluck('stock_quantity')->map(fn ($v) => (int) $v),
            ]
        ]);
    }

    public function productDistribution()
    {
        $data = Product::select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $data->pluck('category_id'),
                'values' => $data->pluck('total'),
            ]
        ]);
    }

    public function productSales(Request $request)
    {
        $period = $request->query('period', 'week');

        $labels = [];
        $values = [];

        if ($period === 'day') {
            // Last 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $labels[] = $date->format('d M');

                $values[] = (float) Order::whereDate('created_at', $date)
                    ->sum('total_amount');
            }
        }

        if ($period === 'week') {
            // Current week (Mon–Sun)
            $start = Carbon::now()->startOfWeek();

            for ($i = 0; $i < 7; $i++) {
                $date = $start->copy()->addDays($i);
                $labels[] = $date->format('D');

                $values[] = (float) Order::whereDate('created_at', $date)
                    ->sum('total_amount');
            }
        }

        if ($period === 'month') {
            // Last 6 months
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $labels[] = $month->format('M Y');

                $values[] = (float) Order::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('total_amount');
            }
        }

        return response()->json([
            'success' => true,
            'period'  => $period,
            'data' => [
                'labels' => $labels,
                'values' => $values,
            ]
        ]);
    }
}