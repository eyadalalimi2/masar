<?php

namespace App\Support;

class StatusLabel
{
    public static function order(?string $status): string
    {
        return self::label($status, [
            'pending' => 'قيد الانتظار',
            'approved' => 'معتمد',
            'assigned' => 'مُسند',
            'accepted' => 'معتمد',
            'preparing' => 'قيد التجهيز',
            'ready' => 'جاهز',
            'out_for_delivery' => 'خرج للتوصيل',
            'on_way' => 'خرج للتوصيل',
            'delivered' => 'تم التسليم',
            'cancelled' => 'ملغي',
        ]);
    }

    public static function distributorStage(?string $status): string
    {
        return self::label($status, [
            'assigned' => 'مُسند',
            'accepted' => 'تم القبول',
            'picked_up' => 'تم الاستلام',
            'out_for_delivery' => 'خرج للتوصيل',
            'delivered' => 'تم التسليم',
            'cancelled' => 'ملغي',
        ]);
    }

    public static function workshopPurchaseOrder(?string $status): string
    {
        return self::label($status, [
            'pending' => 'قيد الانتظار',
            'approved' => 'معتمد',
            'in_transit' => 'قيد النقل',
            'received' => 'تم الاستلام',
            'cancelled' => 'ملغي',
        ]);
    }

    public static function workshopServiceOrder(?string $status): string
    {
        return self::label($status, [
            'requested' => 'مطلوب',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
        ]);
    }

    public static function workshopAppointment(?string $status): string
    {
        return self::label($status, [
            'scheduled' => 'مجدول',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
        ]);
    }

    public static function sellerType(?string $type): string
    {
        return self::label($type, [
            'supplier' => 'مورد',
            'branch' => 'فرع',
            'distributor' => 'مندوب',
            'customer' => 'عميل تجاري',
            'workshop' => 'ورشة',
            'pos' => 'محل تجاري',
        ]);
    }

    public static function paymentStatus(?string $status): string
    {
        return self::label($status, [
            'paid' => 'مدفوع',
            'partial' => 'جزئي',
            'partially_paid' => 'جزئي',
            'unpaid' => 'غير مدفوع',
            'pending' => 'قيد الانتظار',
            'failed' => 'فشل الدفع',
            'cancelled' => 'ملغي',
        ]);
    }

    public static function paymentType(?string $type): string
    {
        return self::label($type, [
            'cash' => 'نقدي',
            'credit' => 'آجل',
            'card' => 'بطاقة',
            'transfer' => 'تحويل',
            'wallet' => 'محفظة',
        ]);
    }

    private static function label(?string $value, array $map): string
    {
        $key = strtolower(trim((string) $value));

        return $map[$key] ?? 'غير محدد';
    }
}
