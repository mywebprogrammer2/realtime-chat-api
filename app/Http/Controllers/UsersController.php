<?php

namespace App\Http\Controllers;

use App\Facades\ReusableFacades;
use Illuminate\Http\Request;

use App\Http\Requests;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\Exceptions\ValidatorException;
use App\Http\Requests\UsersCreateRequest;
use App\Http\Requests\UsersUpdateRequest;
use App\Models\User;
use App\Repositories\Contracts\UsersRepository;
use App\Validators\UsersValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Class UsersController.
 *
 * @package namespace App\Http\Controllers;
 */
class UsersController extends Controller
{
    /**
     * @var UsersRepository
     */
    protected $repository;

    /**
     * @var UsersValidator
     */
    protected $validator;

    /**
     * UsersController constructor.
     *
     * @param UsersRepository $repository
     * @param UsersValidator $validator
     */
    public function __construct(UsersRepository $repository, UsersValidator $validator)
    {
        $this->repository = $repository;
        $this->validator  = $validator;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->repository->pushCriteria(app('Prettus\Repository\Criteria\RequestCriteria'));
        $users = $this->repository
        ->with('user_detail')
        ->findWhere([
            ['roles','HAS',function($q){
                $q->where( 'name','!=', 'Super Admin');
            }]
        ])
        ->all();

        if (request()->wantsJson()) {

            return ReusableFacades::createResponse(true,$users);

        }

        return view('users.index', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  UsersCreateRequest $request
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function store(UsersCreateRequest $request)
    {
        try {
            DB::beginTransaction();

            $this->validator->with($request->all())->passesOrFail(ValidatorInterface::RULE_CREATE);

            $inputs = $request->all();
            if($request->has('password')){
                $inputs['password'] = Hash::make($request->password);
            }

            $user = $this->repository->create($inputs);

            $role =  Role::find($request->role);

            $user->assignRole($role);

            $response = [
                'message' => 'Users created.',
                'data'    => $user->toArray(),
            ];

            DB::commit();

            if ($request->wantsJson()) {

                return ReusableFacades::createResponse(true,$user->toArray(),'Users created.');

            }

            return redirect()->back()->with('message', $response['message']);
        } catch (ValidatorException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {

                return ReusableFacades::createResponse(false,[],'',$e->getMessageBag(),400);

            }

            return redirect()->back()->withErrors($e->getMessageBag())->withInput();
        }
        catch (\Exception $e){

            DB::rollBack();

            return ReusableFacades::createResponse(false,[],$e->getMessage(),[],400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = $this->repository->with('roles')->find($id);

        if (request()->wantsJson()) {

            return ReusableFacades::createResponse(true,$user,'Users found.');

        }

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = $this->repository->find($id);

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UsersUpdateRequest $request
     * @param  string            $id
     *
     * @return Response
     *
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function update(UsersUpdateRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $this->validator->with($request->all())->setId($id)->passesOrFail(ValidatorInterface::RULE_UPDATE);


            $inputs = $request->all();
            if($request->has('password')){
                $inputs['password'] = Hash::make($request->password);
            }

            $user = $this->repository->update( $inputs, $id);

            $role =  Role::find($request->role);

            $user->syncRoles([$role->name]);

            $response = [
                'message' => 'Users updated.',
                'data'    => $user->toArray(),
            ];

            DB::commit();


            if ($request->wantsJson()) {

                return ReusableFacades::createResponse(true, $user->toArray(),'Users updated.');

            }

            return redirect()->back()->with('message', $response['message']);
        } catch (ValidatorException $e) {

            DB::rollBack();

            if ($request->wantsJson()) {

                return ReusableFacades::createResponse(false,[],'',$e->getMessageBag(),400);

            }

            return redirect()->back()->withErrors($e->getMessageBag())->withInput();
        }
        catch (\Exception $e){

            DB::rollBack();

            return ReusableFacades::createResponse(false,[],$e->getMessage(),[],400);
        }
    }

    public function activate($id){
        try{

            if($id < 1){
                return ReusableFacades::createResponse(false,[],'User Record not Found',[],400);
            }

            $user = User::with('user_detail')->find($id);
            $user->update(['status' => 1 ]);
            $user = $user->refresh();

            return ReusableFacades::createResponse(true, $user->toArray(),'User Activated.');

        }
        catch (\Exception $e){

            DB::rollBack();

            return ReusableFacades::createResponse(false,[],$e->getMessage(),[],400);
        }
    }
    public function deactivate($id){
        try{

            if($id < 1){
                return ReusableFacades::createResponse(false,[],'User Record not Found',[],400);
            }

            $user = User::with('user_detail')->find($id);
            $user->update(['status' => 0 ]);
            $user = $user->refresh();

            return ReusableFacades::createResponse(true, $user->toArray(),'User Deactivated.');

        }
        catch (\Exception $e){

            DB::rollBack();

            return ReusableFacades::createResponse(false,[],$e->getMessage(),[],400);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deleted = $this->repository->delete($id);

        if (request()->wantsJson()) {

            return ReusableFacades::createResponse($deleted,[],'User deleted.');

        }

        return redirect()->back()->with('message', 'Users deleted.');
    }
}
