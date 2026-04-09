<div class="p-4">
    @if($invoices->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No invoices found for this customer.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300">Date</th>
                        <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300">Description</th>
                        <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300">Amount</th>
                        <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300">Status</th>
                        <th class="text-left py-2 px-3 font-semibold text-gray-700 dark:text-gray-300">PDF</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 px-3 text-gray-600 dark:text-gray-400">{{ $invoice['date'] }}</td>
                            <td class="py-2 px-3 text-gray-600 dark:text-gray-400">{{ $invoice['description'] }}</td>
                            <td class="py-2 px-3 font-medium text-gray-900 dark:text-gray-100">{{ $invoice['total'] }}</td>
                            <td class="py-2 px-3">
                                @if($invoice['status'] === 'paid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Paid
                                    </span>
                                @elseif($invoice['status'] === 'open')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                        Open
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ ucfirst($invoice['status']) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2 px-3">
                                @if($invoice['invoice_pdf'])
                                    <a href="{{ $invoice['invoice_pdf'] }}" target="_blank" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 text-xs font-medium">
                                        Download
                                    </a>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
