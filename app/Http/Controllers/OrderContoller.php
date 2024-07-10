<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Carbon\Carbon;

class OrderContoller extends Controller
{

    public function index(Request $request)
    {
        $user_id = $request->input('user_id');

        $order = Order::query();

        $order->when($user_id, function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        });

        return response()->json([
            'status' => 'success',
            'data' => $order->get()
        ]);

    }

    public function create(Request $request)
    {
        $datenow = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $user = $request->input('user');
        $course = $request->input('course');

        $data = [
            'user_id' => $user['id'],
            'course_id' => $course['id']
        ];

        $countDataNow = Order::whereDate('created_at', $datenow)->orderBy('id')->count();

        if ($countDataNow > 0) {
            $order_id = str_replace("-", "", $datenow) . sprintf("%04s", $countDataNow + 1);
        } else {
            $order_id = str_replace("-", "", $datenow) . sprintf("%04s", 1);
        }

        $order = Order::create($data);

        $transactionDetails = [
            'order_id' => $order_id . $order->id,
            'gross_amount' => $course['price']
        ];

        $itemDetails = [
            [
                'id' => $course['id'],
                'price' => $course['price'],
                'quantity' => 1,
                'name' => $course['name'],
                'brand' => 'KursusKu',
                'category' => 'Kursus Online'
            ]
        ];

        $arrayName = explode(" ", $user['name']);
        $firstName = $arrayName[0];
        if (count($arrayName) > 2) {
            $lastname = implode(" ", array_splice($arrayName, 1));
        } else if (count($arrayName) == 2) {
            $lastname = $arrayName[1];
        } else {
            $lastname = "";
        }

        $customerDetails = [
            'fisrtname' => $firstName,
            'lastname' => $lastname,
            'email' => $user['email']
        ];

        $midtransParams = [
            'transaction_details' => $transactionDetails,
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails
        ];

        $midtransSnapUrl = $this->getMidtransSnapUrl($midtransParams);

        $order->snap_url = $midtransSnapUrl;

        $order->metadata = [
            'course_id' => $course['id'],
            'course_name' => $course['name'],
            'course_thumbnail' => $course['thumbnail'],
            'course_price' => $course['price'],
            'course_level' => $course['level'],
        ];

        $order->save();

        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
    }

    private function getMidtransSnapUrl($params)
    {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_PRODUCTION');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_3DS');

        $snapUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;

        return $snapUrl;
    }
}
