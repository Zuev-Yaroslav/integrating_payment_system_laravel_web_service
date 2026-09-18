<?php

namespace App\Http\Requests\Payment;

use App\Services\Payments\PaymentGatewayFactory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CallbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return PaymentGatewayFactory::make()->validateWebhook($this);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event' => ['required', 'string'],
            'object' => ['required', 'array'],
            'object.id' => ['required', 'string'],
            'object.metadata.transaction_id' => ['required', 'string'],
            'object.amount.value' => ['required', 'numeric'],
            'object.amount.currency' => ['required', 'string', 'size:3'],
        ];
    }
}
