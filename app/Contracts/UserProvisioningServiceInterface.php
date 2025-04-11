<?php
// Create a new interface
namespace App\Contracts;

interface UserProvisioningServiceInterface
{
    public function findOrCreateUser(array $claims): \App\Models\User;
}
