<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequest;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {

    }

    public function callback(Request $request)
    {
        $data = $request->all();

        $this->paymentService->callback($data);
    }
}
