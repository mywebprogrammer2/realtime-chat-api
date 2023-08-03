<?php

namespace App\Repositories;

use App\Models\User;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\Contracts\UsersRepository;
use App\Validators\UsersValidator;

/**
 * Class UsersRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class UsersRepositoryEloquent extends BaseRepository implements UsersRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return User::class;
    }

    /**
    * Specify Validator class name
    *
    * @return mixed
    */
    public function validator()
    {

        return UsersValidator::class;
    }


    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }



    public function getUsers($withRelations){

    }


    public function getUserById($withRelations){

    }


    public function createUser(){

    }


    public function updateUser($id){

    }


    public function deleteUser($id){

    }


}
