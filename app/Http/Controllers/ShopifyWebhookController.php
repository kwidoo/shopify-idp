<?php

namespace App\Http\Controllers;

use App\Contracts\UserRepository;
use App\Services\ShopifyWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    protected $webhookService;
    protected $userRepository;

    public function __construct(
        ShopifyWebhookService $webhookService,
        UserRepository $userRepository
    ) {
        $this->webhookService = $webhookService;
        $this->userRepository = $userRepository;
    }

    /**
     * Handle various webhook topics from Shopify
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        $topic = $request->header('X-Shopify-Topic');
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $payload = $request->getContent();

        Log::info("Received Shopify webhook", [
            'topic' => $topic,
            'shop' => $shopDomain
        ]);

        try {
            $this->webhookService->processWebhook($topic, $payload, $shopDomain);
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'topic' => $topic,
                'shop' => $shopDomain
            ]);

            return response()->json(['success' => false], 500);
        }
    }
}
