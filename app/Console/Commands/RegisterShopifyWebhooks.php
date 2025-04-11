<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RegisterShopifyWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:register-webhooks
                            {--shop= : The Shopify shop domain}
                            {--token= : The Shopify access token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register required webhooks with Shopify';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shop = $this->option('shop') ?? config('services.shopify.shop_domain');
        $accessToken = $this->option('token');

        if (!$shop) {
            $this->error('Shop domain is required. Provide it using --shop option or set SHOPIFY_SHOP_DOMAIN in .env');
            return 1;
        }

        if (!$accessToken) {
            $this->error('Access token is required. Provide it using --token option');
            return 1;
        }

        // The webhook URL where Shopify will send notifications
        $webhookUrl = route('webhooks.shopify');

        // Topics to subscribe to
        $topics = [
            'customers/update',
            'customers/delete',
            'app/uninstalled',
            'shop/update',
        ];

        $this->info('Registering webhooks for ' . $shop);
        $this->info('Webhook URL: ' . $webhookUrl);

        $client = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ]);

        $success = 0;
        $failed = 0;

        foreach ($topics as $topic) {
            $response = $client->post("https://{$shop}/admin/api/2023-10/webhooks.json", [
                'webhook' => [
                    'topic' => $topic,
                    'address' => $webhookUrl,
                    'format' => 'json'
                ]
            ]);

            if ($response->successful()) {
                $this->info("✓ Registered webhook for {$topic}");
                $success++;
            } else {
                $this->error("✗ Failed to register webhook for {$topic}: " . $response->body());
                $failed++;
            }
        }

        $this->info("Registration complete: {$success} succeeded, {$failed} failed");

        return $failed > 0 ? 1 : 0;
    }
}
