export type Transaction = {
    id: string
    gateway_payment_id: string
    status: string
    payment_method: string
    created_at: string
    cancellation_details: {
        party: string,
        reason: string,
    },
    error_message: string
}
