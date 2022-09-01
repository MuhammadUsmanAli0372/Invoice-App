<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function get_all_invoice()
    {
        $invoices = Invoice::with('customer')->orderBy('id', 'DESC')->get();
        return response()->json([
            'invoices' => $invoices
        ], 200);
    }

    public function search_invoice(Request $request)
    {
        $search = $request->get('s');
        if ($search != null) {
            $invoices = Invoice::with('customer')
                            ->where('id', 'LIKE', "%$search%")
                            ->orderBy('id', 'DESC')->get();
            return response()->json([
                'invoices' => $invoices
            ], 200);
        } else {
            return $this->get_all_invoice();
        }
    }

    public function create_invoice()
    {
        $counter = Counter::query()->where('key', 'invoice')->first();
        $random = Counter::query()->where('key', 'invoice')->first();

        $invoice = Invoice::query()->where('id', 'DESC')->first();
        if ($invoice) {
            $invoice = $invoice->id+1;
            $counters = $counter->value + $invoice;
        } else {
            $counters = $counter->value;
        }

        $formData = [
            'number' => $counter->prefix.$counters,
            'customer_id' => null,
            'customer' => null,
            'date' => date('Y-m-d'),
            'due_date' => null,
            'reference' => null,
            'discount' => 0,
            'terms_and_conditions' => 'Default Term and Condition',
            'items'  => [
                [
                    'product_id' => null,
                    'product' => null,
                    'unit_price' => 0,
                    'quantity' => 1
                ]
            ]
        ];

        return response()->json($formData); 
    }

    public function add_invoice(Request $request)
    {
        $invoiceItem = $request->get('invoice_item');

        $invoiceData['sub_total'] = $request->get('subtotal');
        $invoiceData['total'] = $request->get('total');
        $invoiceData['customer_id'] = $request->get('customer_id');
        $invoiceData['number'] = $request->get('number');
        $invoiceData['date'] = $request->get('date');
        $invoiceData['due_date'] = $request->get('due_date');
        $invoiceData['discount'] = $request->get('discount');
        $invoiceData['reference'] = $request->get('reference');
        $invoiceData['terms_and_conditions'] = $request->get('terms_and_conditions');

        $invoice = Invoice::create($invoiceData);

        foreach(json_decode($invoiceItem) as $item)
        {
            $itemdata['product_id'] =  $item->id;
            $itemdata['invoice_id'] =  $invoice->id;
            $itemdata['quantity'] =  $item->quantity;
            $itemdata['unit_price'] =  $item->unit_price;

            InvoiceItem::create($itemdata);
        }
    }

    public function show_invoice($id)
    {
        $invoice = Invoice::with(['customer', 'invoice_items.product'])->find($id);

        return response()->json([
            'invoice' => $invoice
        ], 200);
    }

    public function edit_invoice($id)
    {
        $invoice = Invoice::with(['customer', 'invoice_items.product'])->find($id);

        return response()->json([
            'invoice' => $invoice
        ], 200);
    }

    public function delete_invoice_items($id)
    {
        $invoiceItem = InvoiceItem::findOrFail($id);
        $invoiceItem->delete(); 
    } 

    public function update_invoice(Request $request, $id)
    {
        // dd($request->all(), $id);
        $invoice = Invoice::where('id', $id)->first();

        $invoice->sub_total = $request->subtotal;
        $invoice->total = $request->total;
        $invoice->customer_id = $request->customer_id;
        $invoice->number = $request->number;
        $invoice->date = $request->date;
        $invoice->due_date = $request->due_date;
        $invoice->reference = $request->reference;
        $invoice->terms_and_conditions = $request->terms_and_conditions;
        $invoice->discount = $request->discount ?? 0;

        // $invoice->update($request->all());
        $invoice->save();

        $invoiceItem = $request->get('invoice_items');

        $invoice->invoice_items()->delete();

        foreach(json_decode($invoiceItem) as $item)
        {
            $itemdata['product_id'] =  $item->product_id;
            $itemdata['invoice_id'] =  $invoice->id;
            $itemdata['quantity'] =  $item->quantity;
            $itemdata['unit_price'] =  $item->unit_price;

            InvoiceItem::create($itemdata);
        }
    }

    public function delete_invoice($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->invoice_items()->delete();
        $invoice->delete();
    }
}
