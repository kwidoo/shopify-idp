<?php

namespace App\Repositories;

use App\Contracts\ImpersonationLogRepository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Models\ImpersonationLog;
use App\Validators\ImpersonationLogValidator;

/**
 * Class ImpersonationLogRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ImpersonationLogRepositoryEloquent extends BaseRepository implements ImpersonationLogRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ImpersonationLog::class;
    }



    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function create(array $data): ImpersonationLog
    {
        return ImpersonationLog::create($data);
    }

}
