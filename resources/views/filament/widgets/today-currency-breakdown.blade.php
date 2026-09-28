<x-filament-widgets::widget>
    <x-filament::section
        heading="Today's Money, by Currency"
        description="Each corridor settles in its own currency — these aren't blended into one number."
    >
        @php($rows = $this->getRows())

        @if (empty($rows))
            <p class="text-sm text-gray-500 dark:text-gray-400">No transactions yet today.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pe-4">Currency</th>
                            <th class="py-2 pe-4 text-right">Received</th>
                            <th class="py-2 pe-4 text-right">Paid Out</th>
                            <th class="py-2 pe-4 text-right">Fees</th>
                            <th class="py-2 text-right">FX Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                <td class="py-2.5 pe-4 font-bold text-gray-950 dark:text-white">{{ $row['currency'] }}</td>
                                <td class="py-2.5 pe-4 text-right font-medium tabular-nums text-gray-950 dark:text-white">{{ number_format($row['received'], 2) }}</td>
                                <td class="py-2.5 pe-4 text-right font-medium tabular-nums text-gray-950 dark:text-white">{{ number_format($row['paid_out'], 2) }}</td>
                                <td class="py-2.5 pe-4 text-right font-medium tabular-nums text-gray-950 dark:text-white">{{ number_format($row['fees'], 2) }}</td>
                                <td @class([
                                    'py-2.5 text-right font-medium tabular-nums',
                                    'text-danger-600 dark:text-danger-400' => $row['fx_revenue'] < 0,
                                    'text-gray-950 dark:text-white' => $row['fx_revenue'] >= 0,
                                ])>{{ number_format($row['fx_revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
