<?php

namespace App\Filament\Pages;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * docs/SAFIFX.md §20 — filterable transaction reporting with CSV export.
 */
class Reports extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Transactions';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return (bool) $user?->is_admin;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Transaction::query())
            ->columns([
                TextColumn::make('reference')->label('ID')->searchable(),
                TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
                TextColumn::make('from_currency')->label('From'),
                TextColumn::make('to_currency')->label('To'),
                TextColumn::make('amount_sent')->label('Sent')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('fee')->label('Fee')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Transaction $record) => $record->status->label())
                    ->color(fn (Transaction $record) => $record->status->color()),
                TextColumn::make('processedBy.name')->label('Operator')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                SelectFilter::make('status')->options(TransactionStatus::class),
                SelectFilter::make('from_currency')
                    ->label('Currency')
                    ->options(fn () => Transaction::query()->distinct()->pluck('from_currency', 'from_currency')),
                SelectFilter::make('processed_by_id')
                    ->label('Operator')
                    ->relationship('processedBy', 'name'),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn () => $this->exportCsv()),
            ]);
    }

    private function exportCsv(): StreamedResponse
    {
        // getTableQueryForExport() is untyped in Filament's HasRecords trait, but this
        // page's table is always scoped to Transaction (see table() above).
        /** @var Collection<int, Transaction> $rows */
        $rows = $this->getTableQueryForExport()->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            fputcsv($handle, ['Reference', 'Date', 'From', 'To', 'Sent', 'Fee', 'Recipient Amount', 'Status', 'Operator']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->reference,
                    $row->created_at?->toDateTimeString(),
                    $row->from_currency,
                    $row->to_currency,
                    $row->amount_sent,
                    $row->fee,
                    $row->recipient_amount,
                    $row->status->label(),
                    $row->processedBy?->name,
                ]);
            }

            fclose($handle);
        }, 'safifx-transactions-'.now()->format('Y-m-d').'.csv');
    }
}
