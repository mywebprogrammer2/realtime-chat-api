<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\UserDetail;

/**
 * Class UserDetailTransformer.
 *
 * @package namespace App\Transformers;
 */
class UserDetailTransformer extends TransformerAbstract
{
    /**
     * Transform the UserDetail entity.
     *
     * @param \App\Models\UserDetail $model
     *
     * @return array
     */
    public function transform(UserDetail $model)
    {
        return [
            'id'         => (int) $model->id,

            /* place your other model properties here */

            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at
        ];
    }
}
