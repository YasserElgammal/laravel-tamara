<?php

namespace YasserElgammal\Tamara\Enums;

enum WebhookEventType: string
{
    case APPROVED='order_approved'; case DECLINED='order_declined'; case AUTHORISED='order_authorised'; case CANCELED='order_canceled'; case CAPTURED='order_captured'; case REFUNDED='order_refunded'; case EXPIRED='order_expired';
}
