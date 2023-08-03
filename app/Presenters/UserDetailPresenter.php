<?php

namespace App\Presenters;

use App\Transformers\UserDetailTransformer;
use Prettus\Repository\Presenter\FractalPresenter;

/**
 * Class UserDetailPresenter.
 *
 * @package namespace App\Presenters;
 */
class UserDetailPresenter extends FractalPresenter
{
    /**
     * Transformer
     *
     * @return \League\Fractal\TransformerAbstract
     */
    public function getTransformer()
    {
        return new UserDetailTransformer();
    }
}
