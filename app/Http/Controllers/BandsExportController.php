<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\BandsExport;
use App\Interfaces\FestivalService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class BandsExportController
{
    public function __construct(
        private FestivalService $festivalService
    ) {}

    public function index(string $filename): BinaryFileResponse
    {
        return Excel::download(
            export: new BandsExport($this->festivalService->getBands()),
            fileName: $filename,
            writerType: \Maatwebsite\Excel\Excel::CSV
        );
    }
}
