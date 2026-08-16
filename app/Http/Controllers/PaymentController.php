<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {

    }

    public function callback(Request $request)
    {
//        $source = file_get_contents('php://input');
        $requestBody = $request->all();
        $payment = Payment::find($requestBody['object']['metadata']['transaction_id']);
        $payment->status = 'paid';
        $payment->save();
    }
}
