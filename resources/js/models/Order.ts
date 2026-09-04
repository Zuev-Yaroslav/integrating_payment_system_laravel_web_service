import { Transaction } from '@/models/Transaction';

export type Order = {
    id: string
    transaction: Transaction
    gateway_payment_id: string
    status: string
    payment_method: string
    created_at: string

}
