export type Transaction = {
    id: string
    gateway_payment_id: string|null
    order_id: string
    status: 'pending' | 'waiting_for_capture' | 'succeeded' | 'canceled'
    payment_method: string|null
    created_at: string|null
    cancellation_details: {
        party: string,
        reason: string,
    }|null,
    error_message: string|null
}
