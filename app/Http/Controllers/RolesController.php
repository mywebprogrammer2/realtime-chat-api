<?php

namespace App\Http\Controllers;

use App\Facades\ReusableFacades;
use Illuminate\Http\Request;

use App\Http\Requests;
use Prettus\Validator\Contracts\ValidatorInterface;
use Prettus\Validator\Exceptions\ValidatorException;
use App\Http\Requests\RoleCreateRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Repositories\Contracts\RoleRepository;
use App\Validators\RoleValidator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/**
 * Class RolesController.
 *
 * @package namespace App\Http\Controllers;
 */
class RolesController extends Controller
{
    /**
     * @var RoleRepository
     */
    protected $repository;

    /**
     * @var RoleValidator
     */
    protected $validator;

    /**
     * RolesController constructor.
     *
     * @param RoleRepository $repository
     * @param RoleValidator $validator
     */
    public function __construct(RoleRepository $repository, RoleValidator $validator)
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
        $roles = $this->repository->with('permissions')->findWhere([
            ['name', '!=' , 'Super Admin']
        ])->all();


        if (request()->wantsJson()) {

            return ReusableFacades::createResponse(true,$roles);

        }

        return view('roles.index', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  RoleCreateRequest $request
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function store(RoleCreateRequest $request)
    {
        DB::beginTransaction();
        try {

            $this->validator->with($request->all())->passesOrFail(ValidatorInterface::RULE_CREATE);

            $role = $this->repository->create(['name' => $request->name]);

            $permissions = array_keys(array_filter($request->except(['name'])));

            $role->syncPermissions($permissions);

            $response = [
                'message' => 'Role created.',
                'data'    => $role->toArray(),
            ];

            DB::commit();

            if ($request->wantsJson()) {

                return ReusableFacades::createResponse(true,$role->toArray(),'Role created.');
            }
            return redirect()->back()->with('message', $response['message']);
        } catch (ValidatorException $e) {
            DB::rollback();
            if ($request->wantsJson()) {

                return ReusableFacades::createResponse(false,[],'',$e->getMessageBag(),400);

            }

            return redirect()->back()->withErrors($e->getMessageBag())->withInput();
        }
        catch (\Exception $e){
            DB::rollback();
            return ReusableFacades::createResponse(false,[],'',$e->getMessage(),400);
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
        $role = $this->repository->with('permissions')->find($id);

        if (request()->wantsJson()) {

            return ReusableFacades::createResponse(true,$role,'Role showed.');

        }

        return view('roles.show', compact('role'));
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
        $role = $this->repository->find($id);

        return view('roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  RoleUpdateRequest $request
     * @param  string            $id
     *
     * @return Response
     *
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function update(RoleUpdateRequest $request, $id)
    {
        try {

            $this->validator->with($request->all())->setId($id)->passesOrFail(ValidatorInterface::RULE_UPDATE);

            $role = $this->repository->update(['name' => $request->name], $id);

            $permissions = array_keys(array_filter($request->except(['name'])));

            $role->syncPermissions($permissions);

            $response = [
                'message' => 'Role updated.',
                'data'    => $role->toArray(),
            ];

            DB::commit();

            if ($request->wantsJson()) {

                return ReusableFacades::createResponse(true,$role->toArray(),'Role updated.');

            }

            return redirect()->back()->with('message', $response['message']);
        } catch (ValidatorException $e) {
            DB::rollback();


            if ($request->wantsJson()) {

                return ReusableFacades::createResponse(false,[],'',$e->getMessageBag(),400);

            }

            return redirect()->back()->withErrors($e->getMessageBag())->withInput();
        }
        catch (\Exception $e) {
            DB::rollback();
            return ReusableFacades::createResponse(false,[],'',$e->getMessage(),400);
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

            return ReusableFacades::createResponse(true,[],'Role deleted.');

        }

        return redirect()->back()->with('message', 'Role deleted.');
    }

    protected function ignorePermissions(){
        return [
            // Specify permissions
        ];
    }


    public function allPermissions(){
        $permissions = Permission::where('guard_name','sanctum')
        ->whereNotIn('name',$this->ignorePermissions())->get()->groupBy(function ($item, $key) {
            return explode("-",$item->name)[0];
        })->toArray();

        return ReusableFacades::createResponse(true,$permissions,'Permissions List.');

    }
}

