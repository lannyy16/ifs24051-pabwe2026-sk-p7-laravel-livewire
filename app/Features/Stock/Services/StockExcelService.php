<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Models\Stock;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use UnexpectedValueException;

class StockExcelService
{
    public const HEADERS = [
        'name',
        'sku',
        'quantity',
        'unit',
        'price',
        'min_stock',
        'status',
        'description',
    ];

    /** @param Collection<int, Stock> $stocks */
    public function export(Collection $stocks): string
    {
        $rows = $stocks->map(fn (Stock $s) => [
            $s->name,
            $s->sku,
            $s->quantity,
            $s->unit,
            $s->price,
            $s->min_stock,
            $s->status->value,
            $s->description,
        ])->all();

        return $this->build($rows);
    }

    /** Template kosong untuk import (berisi satu baris contoh). */
    public function template(): string
    {
        return $this->build([
            [
                'Contoh Barang',
                'SKU-001',
                10,
                'pcs',
                15000,
                5,
                'available',
                'Deskripsi opsional',
            ],
        ]);
    }

    /**
     * Baca file Excel menjadi baris-baris siap simpan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function import(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        $headerRow = array_shift($data) ?? [];

        $header = array_map(
            fn ($h) => strtolower(trim((string) $h)),
            $headerRow
        );

        // Sheet kosong menghasilkan array kosong.
        if (count(array_filter($header, fn ($h) => $h !== '')) === 0) {
            return [];
        }

        // Mendukung nama kolom qty sebagai alias quantity.
        $header = array_map(
            fn ($h) => $h === 'qty' ? 'quantity' : $h,
            $header
        );

        // Kolom name wajib tersedia.
        if (! in_array('name', $header, true)) {
            throw new UnexpectedValueException(
                'Kolom wajib name tidak ditemukan.'
            );
        }

        $rows = [];

        foreach ($data as $line) {
            $row = [];

            foreach ($header as $i => $key) {
                if ($key !== '') {
                    $row[$key] = $line[$i] ?? null;
                }
            }

            // Lewati baris yang tidak memiliki nama barang.
            if (trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }

            $rows[] = [
                'name' => (string) $row['name'],
                'sku' => $row['sku'] ?? null,
                'description' => $row['description'] ?? null,
                'quantity' => (int) ($row['quantity'] ?? 0),
                'unit' => $row['unit'] ?? null,
                'price' => $row['price'] ?? null,
                'min_stock' => (int) ($row['min_stock'] ?? 5),
            ];
        }

        return $rows;
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function build(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Stok');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'stk');

        if ($tmp === false) {
            throw new \RuntimeException(
                'Gagal membuat file sementara Excel.'
            );
        }

        try {
            (new Xlsx($spreadsheet))->save($tmp);

            $content = file_get_contents($tmp);

            if ($content === false) {
                throw new \RuntimeException(
                    'Gagal membaca file sementara Excel.'
                );
            }

            return $content;
        } finally {
            if (is_file($tmp)) {
                unlink($tmp);
            }
        }
    }
}
