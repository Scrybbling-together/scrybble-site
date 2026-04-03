<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use League\Csv\Writer;
use SplTempFileObject;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class ExportableChartWidget extends ChartWidget
{
    protected string $view = 'filament.widgets.exportable-chart-widget';

    abstract protected function getExportData(): array;

    public function exportCsv(): StreamedResponse
    {
        $exportData = $this->getExportData();

        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne($exportData['headers']);
        $csv->insertAll($exportData['rows']);

        $filename = str($this->getHeading() ?? 'chart-export')->slug() . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(
            fn () => $csv->output(),
            $filename,
            ['Content-Type' => 'text/csv'],
        );
    }
}
