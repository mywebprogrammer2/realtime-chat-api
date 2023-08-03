<?php

namespace App\Repositories\Contracts;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface UsersRepository.
 *
 * @package namespace App\Repositories\Contracts;
 */
interface UsersRepository extends RepositoryInterface
{

    public function getUsers($withRelations);


    public function getUserById($withRelations);


    public function createUser();


    public function updateUser($id);


    public function deleteUser($id);

}
