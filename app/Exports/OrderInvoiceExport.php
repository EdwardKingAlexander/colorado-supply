<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class OrderInvoiceExport implements FromView
{
    public function __construct(
        private Order $order
    ) {
    }

    public function view(): View
    {
        return view('excel.invoice', [
            'order' => $this->order,
        ]);
    }
}
