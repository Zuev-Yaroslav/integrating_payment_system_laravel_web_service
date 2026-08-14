<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreRequest;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $link = $this->orderService->store($data);

        return response()->json([
            'link' => $link,
        ]);
    }
}
