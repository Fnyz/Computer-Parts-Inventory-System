<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csv;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\InsightRepository;
use App\Repositories\ProductRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\SaleRepository;
use Dompdf\Dompdf;
use Dompdf\Options;

final class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        [$from, $to] = $this->range($request);
        $sales = new SaleRepository();
        $insights = new InsightRepository();
        $data = [
            'from' => $from,
            'to' => $to,
            'totals' => $insights->salesTotals($from, $to),
            'daily' => $sales->byDay($from, $to),
            'received' => (new PurchaseOrderRepository())->receivedByDay($from, $to),
            'top' => $sales->topProducts($from, $to, 10),
            'cashiers' => $sales->byCashier($from, $to),
            'methods' => $insights->paymentMethods($from, $to),
            'categories' => $insights->categories(),
            'stock' => $insights->stockTotals(),
            'low' => (new ProductRepository())->lowStock(15),
        ];

        return match ($request->string('export')) {
            'daily' => Csv::download("sales-by-day-{$from}-to-{$to}.csv", ['Date', 'Receipts', 'Revenue', 'Gross profit'], array_map(
                static fn (string $day, array $d) => [$day, $d['receipts'], db_money($d['revenue']), db_money($d['profit'])],
                array_keys($data['daily']),
                $data['daily'],
            )),
            'top' => Csv::download("top-products-{$from}-to-{$to}.csv", ['SKU', 'Product', 'Units sold', 'Revenue', 'Gross profit'], array_map(
                static fn (array $p) => [$p['sku'], $p['product_name'], $p['qty'], $p['revenue'], $p['profit']],
                $data['top'],
            )),
            'valuation' => Csv::download('stock-valuation-' . date('Y-m-d') . '.csv', ['Category', 'SKUs', 'Units', 'Value at cost', 'Value at retail', 'SKUs above reorder level %'], array_map(
                static fn (array $c) => [$c['name'], $c['skus'], $c['units'], db_money($c['cost_value']), db_money($c['retail_value']), $c['health']],
                $data['categories'],
            )),
            'pdf' => $this->pdf($data),
            default => $this->view('reports/index', $data + ['pageTitle' => 'Reports']),
        };
    }

    /** @return array{0: string, 1: string} from, to (defaults: this month so far) */
    private function range(Request $request): array
    {
        $valid = static fn (string $v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false;
        $from = $valid($request->string('from')) ? $request->string('from') : date('Y-m-01');
        $to = $valid($request->string('to')) ? $request->string('to') : date('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function pdf(array $data): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('tempDir', sys_get_temp_dir());
        $options->set('fontCache', sys_get_temp_dir());
        $pdf = new Dompdf($options);
        $pdf->loadHtml(View::render('reports/pdf', $data, null));
        $pdf->setPaper('A4');
        $pdf->render();

        return new Response((string) $pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="sales-report-' . $data['from'] . '-to-' . $data['to'] . '.pdf"',
        ]);
    }
}
