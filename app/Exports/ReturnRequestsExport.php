<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Enums\ingaz\OrderEnum;

class ReturnRequestsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'رقم الطلب',
            'اسم العميل',
            'رقم الهاتف',
            'اسم العاملة',
            'الحالة',
            'تاريخ الطلب',
            'الموظف المسؤول'
        ];
    }

    public function map($order): array
    {
        $status = '';
        if ($order->deleted_at) {
            $status = 'محذوفة';
        } elseif ($order->status == OrderEnum::Refused->value) {
            $status = 'مرفوضة';
        } elseif ($order->status == OrderEnum::Return->value) {
            $status = 'ملغية';
        } else {
            $status = getEnumValue('orders_statuses', $order->status);
        }

        return [
            $order->order_code,
            $order->user ? $order->user->name : '-',
            $order->user ? ($order->user->phone_code . $order->user->phone) : '-',
            $order->maid ? $order->maid->name : '-',
            $status,
            $order->created_at->format('Y-m-d H:i'),
            $order->details && $order->details->admin ? $order->details->admin->name : '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ]
            ]
        ];
    }
}
