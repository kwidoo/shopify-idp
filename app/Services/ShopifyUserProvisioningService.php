<?php
// Implement the service
namespace App\Services;

use App\Contracts\UserProvisioningServiceInterface;
use App\Contracts\UserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ShopifyUserProvisioningService implements UserProvisioningServiceInterface
{
    public function __construct(protected UserRepository $userRepository) {}

    public function findOrCreateUser(array $claims): User
    {
        // First try to find by Shopify ID if available
        if (isset($claims['sub'])) {
            $userByShopifyId = $this->userRepository->findByField('shopify_id', $claims['sub'])->first();
            if ($userByShopifyId) {
                return $this->updateUserFromClaims($userByShopifyId, $claims);
            }
        }

        // Then try by email
        $user = $this->userRepository->findByField('email', $claims['email'])->first();

        if ($user) {
            return $this->updateUserFromClaims($user, $claims);
        }

        // Create new user with Shopify data
        return $this->userRepository->create([
            'name' => $claims['name'] ?? 'Shopify User',
            'email' => $claims['email'],
            'password' => Hash::make(Str::random(32)),
            'shopify_id' => $claims['sub'] ?? null,
            'shopify_metadata' => $this->extractShopifyMetadata($claims),
            'last_login_at' => now(),
        ]);
    }

    /**
     * Update user with latest data from Shopify claims
     */
    protected function updateUserFromClaims(User $user, array $claims): User
    {
        $user->name = $claims['name'] ?? $user->name;

        // Only update shopify_id if it's not already set
        if (empty($user->shopify_id) && isset($claims['sub'])) {
            $user->shopify_id = $claims['sub'];
        }

        // Update Shopify metadata
        $user->shopify_metadata = $this->extractShopifyMetadata($claims);

        // Update last login time
        $user->last_login_at = now();

        $user->save();
        return $user;
    }

    /**
     * Extract relevant Shopify metadata from claims
     */
    protected function extractShopifyMetadata(array $claims): array
    {
        $metadata = [];

        // Common OIDC fields that might be useful
        $relevantFields = [
            'sub',
            'given_name',
            'family_name',
            'locale',
            'picture',
            'updated_at',
            'email_verified',
            'zoneinfo',
            'shop_id',
            'shop_name',
            'staff_id',
            'staff_access'
        ];

        foreach ($relevantFields as $field) {
            if (isset($claims[$field])) {
                $metadata[$field] = $claims[$field];
            }
        }

        return $metadata;
    }
}
