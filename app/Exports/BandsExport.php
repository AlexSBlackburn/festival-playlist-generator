<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

final readonly class BandsExport implements FromCollection, WithHeadings
{
    public function __construct(
        private Collection $bands
    ) {}

    public function collection(): Collection
    {
        return collect([
            $this->bands->sort()->map(fn (string $band): array => [$band]),
        ]);
    }

    public function headings(): array
    {
        return ['Band', 'Rating /5'];
    }
}
