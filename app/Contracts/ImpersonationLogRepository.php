<?php

namespace App\Contracts;

use App\Models\ImpersonationLog;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface ImpersonationLogRepository.
 *
 * @package namespace App\Repositories;
 */
interface ImpersonationLogRepository extends RepositoryInterface
{
    public function create(array $data): ImpersonationLog;

}
