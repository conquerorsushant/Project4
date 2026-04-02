<?php
// Quick script to sync Stripe subscription for user ID 2
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::find(2);
echo "User: {$user->name} | Stripe: {$user->stripe_id}\n";

$stripe = new \Stripe\StripeClient(config('cashier.secret'));
$subs = $stripe->subscriptions->all(['customer' => $user->stripe_id, 'status' => 'all', 'limit' => 10]);

echo "Found " . count($subs->data) . " subscription(s) on Stripe\n";

foreach ($subs->data as $stripeSub) {
    if ($stripeSub->status === 'incomplete_expired') continue;

    $localSub = \Laravel\Cashier\Subscription::where('stripe_id', $stripeSub->id)->first();
    if (!$localSub) {
        $localSub = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => $stripeSub->id,
            'stripe_status' => $stripeSub->status,
            'stripe_price' => $stripeSub->items->data[0]->price->id ?? null,
            'quantity' => $stripeSub->items->data[0]->quantity ?? 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        foreach ($stripeSub->items->data as $item) {
            $localSub->items()->create([
                'stripe_id' => $item->id,
                'stripe_product' => $item->price->product,
                'stripe_price' => $item->price->id,
                'quantity' => $item->quantity ?? 1,
            ]);
        }
        echo "SYNCED: {$stripeSub->id} ({$stripeSub->status})\n";
    } else {
        echo "ALREADY EXISTS: {$stripeSub->id}\n";
    }
}

echo "\nSubscribed now? " . ($user->fresh()->subscribed('default') ? 'YES' : 'NO') . "\n";
echo "DB subscriptions: " . \DB::table('subscriptions')->where('user_id', $user->id)->count() . "\n";
echo "DB subscription_items: " . \DB::table('subscription_items')->count() . "\n";
