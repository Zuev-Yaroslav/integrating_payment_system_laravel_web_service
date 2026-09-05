import { Transaction } from '@/models/Transaction';

export type Order = {
    id: string
    user_id: number
    transactions: Transaction[]
    amount: number
    currency: string
    status: 'pending'|'completed'|'failed'
    description: string
    created_at: string
}
