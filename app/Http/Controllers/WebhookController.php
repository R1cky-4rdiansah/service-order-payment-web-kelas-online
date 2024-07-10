<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function midtransHandler(Request $request)
    {
        $data = $request->all();

        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        $serverKey = env('MIDTRANS_SERVER_KEY');

        $signatureKey = hash('SHA512', $orderId . $statusCode . $grossAmount . $serverKey);

        $transactionStatus = $data['transaction_status'];
        $paymentType = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];

        if ($data['signature_key'] !== $signatureKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Signature key tidak valid'
            ], 404);
        }

        $realIdCourse = substr($orderId, 12);

        $order = Order::find($realIdCourse);

        if ($order->status == 'success') {
            return response()->json([
                'status' => 'error',
                'message' => 'Order telah sukses'
            ], 409);
        }

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'accept') {
                // set transaction status on your database to 'success'
                $order->status = 'success';
            }
        } else if ($transactionStatus == 'settlement') {
            // set transaction status on your database to 'success'
            $order->status = 'success';
        } else if (
            $transactionStatus == 'cancel' ||
            $transactionStatus == 'deny' ||
            $transactionStatus == 'expire'
        ) {
            // set transaction status on your database to 'failure'
            $order->status = 'failure';
        } else if ($transactionStatus == 'pending') {
            // set transaction status on your database to 'pending' / waiting payment
            $order->status = 'pending';
        }

        $order->save();

        $historyData = [
            'status' => $transactionStatus,
            'payment_type' => $paymentType,
            'order_id' => $realIdCourse,
            'raw_response' => json_encode($data)
        ];

        PaymentLog::create($historyData);

        if ($order->status == 'success') {
            $coursePremi = createCoursePremium([
                "user_id" => $order->user_id,
                "course_id" => $order->course_id
            ]);

            if ($coursePremi["status"] === "error") {
                return response()->json([
                    "status" => $coursePremi["status"],
                    "message" => $coursePremi["message"]
                ], $coursePremi["http_code"]);
            }
        }

        return response()->json("Ok");
    }
}
