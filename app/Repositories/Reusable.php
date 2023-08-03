<?php

namespace App\Repositories;

class Reusable {

    /**
     * Create a JSON response following the Response Object pattern.
     *
     * @param bool $success      Indicates the success or failure of the request.
     * @param array $data        (Optional) The main payload or response data.
     * @param string $message (Optional) A general message or description.
     * @param array $errors      (Optional) Error details, if any.
     * @param int $statusCode (Optional) Indicates the Status Code of the request.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response following the Response Object pattern.
     */
    function createResponse($success, $data = [],$message = '',  $errors = [] ,$statusCode = 200) {
        $response = [
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'errors' => $errors,
        ];

        return response()->json($response,$statusCode);
    }

}
