<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\SalesRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SalesGraph extends Controller
{
    //
    public function index()
    {

        if(!auth()->check()) {
            return response()->json([
                'error' => 'Invalid Session'
            ],500);
        }

        $user = auth()->user();

        $interval = 25;

        $startDate = Carbon::today()->addDays(1);            
        $endDate = Carbon::today()->subDays($interval);
        $dates = Carbon::parse($endDate)->toPeriod($startDate);
        $format = 'd';
        

        $recentSales = SalesRecord::where('user_id', $user->id)
        ->select(
            DB::raw('COALESCE(SUM(amount), 0) as total_amount'),
            DB::raw("EXTRACT(DAY FROM transact_date) as day")
            )
        ->whereBetween('transact_date', [$endDate->format('Y-m-d'), $startDate->format('Y-m-d')])
        ->groupBy(
            DB::raw("EXTRACT(DAY FROM transact_date)")
        )
        ->pluck('total_amount','day')
        ->take($interval)
        ->toArray();

        $result = [];
        foreach ($dates as $date) {
            $formattedDate = $date->format($format);
            $sales = isset($recentSales[intval($formattedDate)]) ? $recentSales[intval($formattedDate)] : 0;
            $result[] = [
                'date_time' => $date->format('M d,Y'),
                'sales' => $sales
            ];
        }

        $sales_keys = array_map(function ($item) {
            return Carbon::parse($item["date_time"])->format('M d');
        }, $result);    

        $sales_value = array_map(function ($item) {
            return number_format($item['sales'],2);
        }, $result); 

        return response()->json([
            'date_time' =>  $sales_keys,
            'sales'     =>  $sales_value,
            'type'      =>  'line'
        ]);

    }
}
