<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\UserDetailRepository;
use App\Models\UserDetail;
use App\Validators\UserDetailValidator;

/**
 * Class UserDetailRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class UserDetailRepositoryEloquent extends BaseRepository implements UserDetailRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return UserDetail::class;
    }

    /**
    * Specify Validator class name
    *
    * @return mixed
    */
    public function validator()
    {

        return UserDetailValidator::class;
    }


    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    
}
