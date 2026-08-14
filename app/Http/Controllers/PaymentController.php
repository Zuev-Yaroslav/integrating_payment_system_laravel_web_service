<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequest;
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
        $source = file_get_contents('php://input');
        $requestBody = json_decode($source, true);
        Log::info('body', $requestBody);
    }
}
