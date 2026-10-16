<?php

namespace App\Services\Bom;

use App\Models\ConfigurationSession;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Renders BOM documents (PDF/Excel) to raw byte strings. Centralizing the build
 * here lets both the synchronous controller download and the queued export job
 * share one implementation instead of duplicating the layout logic.
 */
class BomDocumentRenderer
{
    /**
     * Render the BOM PDF to a raw byte string.
     *
     * @param  array<int, array<string, mixed>>  $bomItems
     * @param  array<string, mixed>  $validation
     */
    public function renderPdf(ConfigurationSession $configuration, array $bomItems, array $validation, bool $showPricing): string
    {
        return Pdf::loadView('sales.exports.bom-pdf', compact('configuration', 'bomItems', 'validation', 'showPricing'))
            ->setPaper('a4', 'landscape')
            ->output();
    }

    /**
     * Render the BOM workbook to a raw byte string.
     *
     * @param  array<int, array<string, mixed>>  $bomItems
     */
    public function renderExcel(ConfigurationSession $configuration, array $bomItems, bool $showPricing): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('BOM - '.$configuration->configuration_number)
            ->setCreator('ConfigPro')
            ->setCompany('Heconomy');

        $this->buildBomSheet($spreadsheet, $configuration, $bomItems, $showPricing);
        $this->buildConfigSheet($spreadsheet, $configuration);
        $this->buildSummarySheet($spreadsheet, $configuration, $bomItems);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    // ── Sheet Builders ────────────────────────────────────────────────────────

    /**
     * @param  array<int, array<string, mixed>>  $bomItems
     */
    private function buildBomSheet(Spreadsheet $spreadsheet, ConfigurationSession $cfg, array $bomItems, bool $showPricing): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BOM');

        $headers = ['Item No.', 'Level', 'Part Number', 'Description', 'Type', 'Manufacturer', 'Mfr Part No.', 'Qty', 'Unit'];
        if ($showPricing) {
            $headers = array_merge($headers, ['Unit Price', 'Total Price', 'Currency']);
        }
        $headers[] = 'Remarks';

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];

        // Title row
        $sheet->mergeCells('A1:'.Coordinate::stringFromColumnIndex(count($headers)).'1');
        $sheet->setCellValue('A1', 'BILL OF MATERIALS — '.$cfg->configuration_number);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F2D5E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Info row
        $sheet->setCellValue('A2', 'Product: '.$cfg->product->name);
        $sheet->setCellValue('D2', 'Customer: '.($cfg->customer ?? '—'));
        $sheet->setCellValue('G2', 'Date: '.now()->format('d M Y'));
        $sheet->getStyle('A2:'.Coordinate::stringFromColumnIndex(count($headers)).'2')
            ->applyFromArray(['font' => ['italic' => true, 'size' => 9], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF0F7']]]);

        // Headers row 3
        foreach ($headers as $i => $header) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col.'3', $header);
        }
        $sheet->getStyle('A3:'.Coordinate::stringFromColumnIndex(count($headers)).'3')
            ->applyFromArray($headerStyle);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Data rows
        $row = 4;
        foreach ($bomItems as $item) {
            $col = 1;
            $level = (int) ($item['level'] ?? 0);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['item_no']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $level);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['part_number']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, str_repeat('  ', $level).$item['description']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['type']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['manufacturer']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['manufacturer_part_no']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['quantity']);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['unit']);
            if ($showPricing) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['unit_price']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['total_price']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['currency']);
            }
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++).$row, $item['remarks']);

            $bgColor = $row % 2 === 0 ? 'F7F9FC' : 'FFFFFF';
            $sheet->getStyle('A'.$row.':'.Coordinate::stringFromColumnIndex(count($headers)).$row)
                ->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                    'font' => ['bold' => (bool) ($item['is_assembly'] ?? false)],
                ]);
            $row++;
        }

        // Totals row
        if ($showPricing && count($bomItems) > 0) {
            $totalCol = 11; // Total Price column (after the Level column)
            $totalColLetter = Coordinate::stringFromColumnIndex($totalCol);
            $sheet->setCellValue('A'.$row, 'TOTAL');
            $sheet->setCellValue($totalColLetter.$row, array_sum(array_column($bomItems, 'total_price')));
            $sheet->getStyle('A'.$row.':'.Coordinate::stringFromColumnIndex(count($headers)).$row)
                ->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF0F7']]]);
        }

        // Column widths
        $widths = [8, 6, 20, 40, 18, 22, 20, 8, 8, 12, 12, 10, 20];
        foreach ($widths as $i => $width) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($width);
        }

        // Freeze panes & autofilter
        $sheet->freezePane('A4');
        $sheet->setAutoFilter('A3:'.Coordinate::stringFromColumnIndex(count($headers)).'3');
    }

    private function buildConfigSheet(Spreadsheet $spreadsheet, ConfigurationSession $cfg): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Configuration');

        $headerStyle = ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF0F7']]];

        $rows = [
            ['Configuration ID', $cfg->configuration_number],
            ['Configuration Name', $cfg->name ?? '—'],
            ['Product', $cfg->product->name],
            ['Product Code', $cfg->product->code ?? '—'],
            ['Customer', $cfg->customer ?? '—'],
            ['Project', $cfg->project ?? '—'],
            ['Sales Person', $cfg->user?->name ?? '—'],
            ['Status', strtoupper($cfg->status)],
            ['BOM Status', strtoupper($cfg->bom_status)],
            ['BOM Revision', $cfg->bom_revision],
            ['Created', $cfg->created_at->format('d M Y H:i')],
            ['Last Updated', $cfg->updated_at->format('d M Y H:i')],
        ];

        $sheet->setCellValue('A1', 'CONFIGURATION DETAILS');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12]]);

        foreach ($rows as $i => $row) {
            $r = $i + 3;
            $sheet->setCellValue('A'.$r, $row[0]);
            $sheet->setCellValue('B'.$r, $row[1]);
            $sheet->getStyle('A'.$r)->applyFromArray($headerStyle);
        }

        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(40);
    }

    /**
     * @param  array<int, array<string, mixed>>  $bomItems
     */
    private function buildSummarySheet(Spreadsheet $spreadsheet, ConfigurationSession $cfg, array $bomItems): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary');

        // Assembly rows only group their children, so counts and sums cover leaf rows.
        $leafItems = array_values(array_filter($bomItems, fn (array $item): bool => ! ($item['is_assembly'] ?? false)));
        $totalQty = array_sum(array_column($leafItems, 'quantity'));
        $totalValue = array_sum(array_column($leafItems, 'total_price'));

        $rows = [
            ['Total BOM Items', count($leafItems)],
            ['Total Quantity', $totalQty],
            ['Estimated Total Value', $totalValue],
            ['Currency', $cfg->product->currency ?? 'EUR'],
            ['Product', $cfg->product->name],
            ['Configuration', $cfg->configuration_number],
            ['BOM Revision', $cfg->bom_revision],
            ['Generated Date', now()->format('d M Y H:i')],
        ];

        $sheet->setCellValue('A1', 'BOM SUMMARY');
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 12]]);

        foreach ($rows as $i => $row) {
            $r = $i + 3;
            $sheet->setCellValue('A'.$r, $row[0]);
            $sheet->setCellValue('B'.$r, $row[1]);
            $sheet->getStyle('A'.$r)->applyFromArray(['font' => ['bold' => true]]);
        }

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(30);
    }
}
