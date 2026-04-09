<x-filament-panels::page>
    <div class="space-y-6">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center gap-3 mb-4">
                <x-heroicon-o-banknotes class="w-6 h-6 text-primary-500" />
                <h2 class="text-lg font-semibold">Customer Payment Overview</h2>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                View all customers with Stripe billing. Click "View Invoices" on any customer to see their complete payment history and download invoice PDFs.
            </p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
