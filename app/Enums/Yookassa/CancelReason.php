<?php

namespace App\Enums\Yookassa;

enum CancelReason: string
{
    case THREE_D_SECURE_FAILED = "3d_secure_failed";
    case CALL_ISSUER = "call_issuer";
    case CANCELED_BY_MERCHANT = "canceled_by_merchant";
    case CARD_EXPIRED = "card_expired";
    case COUNTRY_FORBIDDEN = "country_forbidden";
    case DEAL_EXPIRED = "deal_expired";
    case EXPIRED_ON_CAPTURE = "expired_on_capture";
    case EXPIRED_ON_CONFIRMATION = "expired_on_confirmation";
    case FRAUD_SUSPECTED = "fraud_suspected";
    case GENERAL_DECLINE = "general_decline";
    case IDENTIFICATION_REQUIRED = "identification_required";
    case INSUFFICIENT_FUNDS = "insufficient_funds";
    case INTERNAL_TIMEOUT = "internal_timeout";
    case INVALID_CARD_NUMBER = "invalid_card_number";
    case INVALID_CSC = "invalid_csc";
    case ISSUER_UNAVAILABLE = "issuer_unavailable";
    case LOAN_APPLICATION_EXPIRED = "loan_application_expired";
    case LOAN_DECLINED = "loan_declined";
    case LOAN_DECLINED_BY_PAYER = "loan_declined_by_payer";
    case PAYMENT_METHOD_LIMIT_EXCEEDED = "payment_method_limit_exceeded";
    case PAYMENT_METHOD_RESTRICTED = "payment_method_restricted";
    case PERMISSION_REVOKED = "permission_revoked";
    case UNSUPPORTED_MOBILE_OPERATOR = "unsupported_mobile_operator";
}
