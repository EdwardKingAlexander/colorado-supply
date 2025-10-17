<?php

namespace App\Http\Controllers;

use App\Exports\OrderInvoiceExport;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class OrderExportController extends Controller
{
    /**
     * Download order invoice as PDF.
     */
    public function pdf(Order $order)
    {
        $pdf = Pdf::loadView('pdf.invoice', compact('order'));

        return $pdf->download("invoice-{$order->order_number}.pdf");
    }

    /**
     * Download order invoice as Excel.
     */
    public function excel(Order $order)
    {
        return Excel::download(
            new OrderInvoiceExport($order),
            "invoice-{$order->order_number}.xlsx"
        );
    }
}
