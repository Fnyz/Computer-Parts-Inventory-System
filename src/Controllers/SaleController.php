<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csv;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\SaleRepository;
use App\Services\ActivityLogger;
use App\Services\CheckoutService;
use App\Services\StockException;
use Dompdf\Dompdf;
use Dompdf\Options;

final class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);
        $sales = new SaleRepository();
        if ($request->string('export') === 'csv') {
            return Csv::download(
                'sales-' . date('Y-m-d') . '.csv',
                ['Receipt', 'Date', 'Cashier', 'Customer', 'Payment', 'Items', 'Subtotal', 'Discount', 'Total', 'Status'],
                array_map(static fn (array $s) => [
                    $s['sale_number'], $s['created_at'], $s['cashier_name'], $s['customer_name'], SaleRepository::METHODS[$s['payment_method']] ?? $s['payment_method'],
                    $s['item_count'], $s['subtotal'], $s['discount'], $s['total'], $s['status'],
                ], $sales->all($filters)),
            );
        }

        return $this->view('sales/index', [
            'pageTitle' => 'Sales',
            'filters' => $filters,
            'result' => $sales->paginate($filters, $request->int('page', 1)),
            'summary' => $sales->summary($filters),
            'cashiers' => $sales->cashiers(),
        ]);
    }

    public function show(Request $request): Response
    {
        $sales = new SaleRepository();
        $sale = $this->findOr404($sales->find((int) $request->param('id')));

        return $this->view('sales/show', ['pageTitle' => $sale['sale_number'], 'sale' => $sale, 'items' => $sales->items((int) $sale['id'])]);
    }

    /** Printable 80 mm receipt page. */
    public function receipt(Request $request): Response
    {
        [$sale, $items] = $this->load($request);

        return $this->view('sales/receipt', ['pageTitle' => $sale['sale_number'], 'sale' => $sale, 'items' => $items, 'print' => true], null);
    }

    public function receiptPdf(Request $request): Response
    {
        [$sale, $items] = $this->load($request);
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        // vendor/ is read-only in the Docker image, so Dompdf works in the temp folder
        $options->set('tempDir', sys_get_temp_dir());
        $options->set('fontCache', sys_get_temp_dir());
        $pdf = new Dompdf($options);
        $pdf->loadHtml(View::render('sales/receipt', ['sale' => $sale, 'items' => $items, 'print' => false, 'pdf' => true], null));
        // 80 mm wide thermal-paper style page; height grows with the number of lines
        $pdf->setPaper([0, 0, 226.77, 360 + 28 * count($items)]);
        $pdf->render();

        return new Response((string) $pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $sale['sale_number'] . '.pdf"',
        ]);
    }

    public function void(Request $request): Response
    {
        $sale = $this->findOr404((new SaleRepository())->find((int) $request->param('id')));
        $id = (int) $sale['id'];
        $reason = $request->string('reason');
        if ($reason === '') {
            return $this->backWithErrors($request, '/sales/' . $id, ['reason' => 'Enter why this sale is being voided.']);
        }
        try {
            (new CheckoutService())->void($id, $reason);
        } catch (StockException $e) {
            return $this->backWithErrors($request, '/sales/' . $id, [], $e->getMessage());
        }
        ActivityLogger::log('voided sale', 'sale', $id, ['number' => $sale['sale_number'], 'reason' => $reason]);

        return $this->redirect('/sales/' . $id, "{$sale['sale_number']} voided. Its stock was put back.");
    }

    private function load(Request $request): array
    {
        $sales = new SaleRepository();
        $sale = $this->findOr404($sales->find((int) $request->param('id')));

        return [$sale, $sales->items((int) $sale['id'])];
    }

    private function filters(Request $request): array
    {
        $date = static fn (string $v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';

        return [
            'from' => $date($request->string('from')),
            'to' => $date($request->string('to')),
            'user' => $request->int('user'),
            'status' => in_array($request->string('status'), ['completed', 'voided'], true) ? $request->string('status') : '',
            'method' => array_key_exists($request->string('method'), SaleRepository::METHODS) ? $request->string('method') : '',
            'q' => $request->string('q'),
        ];
    }
}
