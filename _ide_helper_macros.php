<?php

namespace Illuminate\Support\Facades {

    /**
     * @method static \Illuminate\Http\JsonResponse success(string $message, $data = null, string $resource = 'data', int $status = 200)
     * @method static \Illuminate\Http\JsonResponse fail(string $message, $data = null, string $resource = 'data', int $status = 400)
     * @method static \Illuminate\Http\JsonResponse error(string $message, $data = null, string $resource = 'data', int $status = 500)
     * @method static \Illuminate\Http\JsonResponse created(string $message, $data = null, string $resource = 'data')
     * @method static \Illuminate\Http\JsonResponse updated(string $message, $data = null, string $resource = 'data')
     * @method static \Illuminate\Http\JsonResponse deleted(string $message)
     * @method static \Illuminate\Http\JsonResponse validationError($errors, string $message = 'Error de validación', string $resource = 'data')
     */
    class Response {}
}
