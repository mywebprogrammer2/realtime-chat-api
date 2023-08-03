<?php

namespace App\Http\Middleware;

use App\Facades\ReusableFacades;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CheckPermission
{
    /**
     * The configuration for permissions.
     *
     * @var array
     */
    protected $configure = [
        'index' => ['view'],
        'create' => ['create'],
        'store' => ['create'],
        'edit' => ['edit'],
        'update' => ['edit'],
        'destroy' => ['delete'],
        'show' => ['view'],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string[]  $permissions
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next, ...$permissions)
    {
        $routeName = $request->route()->getName();
        $requestMethod = $request->getMethod();

        foreach ($permissions as $permission) {
            if ($this->shouldCheckPermission($permission, $routeName, $requestMethod)) {
                if($request->wantsJson()){
                    return ReusableFacades::createResponse(false,[],'Unauthorized',[],403);
                }
                abort(403, 'Unauthorized');
            }
        }

        return $next($request);
    }

    /**
     * Determine if the permission should be checked for the given route.
     *
     * @param  string  $permission
     * @param  string  $routeName
     * @param  string  $requestMethod
     * @return bool
     */
    private function shouldCheckPermission($permission, $routeName, $requestMethod)
    {
        $key = explode('.', $routeName);

        if (count($key)) {
            $concatValues = array_key_exists(end($key), $this->configure) ? $this->configure[end($key)] : [];
            if(count($concatValues)){

                $denyAccess = true;
                foreach ($concatValues as $key => $value) {
                    if(Gate::allows($permission . '-' . $value)){
                        $denyAccess =  false;
                    }
                }
                return $denyAccess;
            }
        }

        return Gate::denies($permission);
    }
}
