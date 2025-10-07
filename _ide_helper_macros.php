<?php

namespace Illuminate\Contracts\Routing {

    use Illuminate\Http\JsonResponse;

    /**
     * @method JsonResponse success(string $message, $data = null, string $resource = 'data', int $status = 200)
     * @method JsonResponse fail(string $message, $data = null, string $resource = 'data', int $status = 400)
     * @method JsonResponse error(string $message, $data = null, string $resource = 'data', int $status = 500)
     * @method JsonResponse created(string $message, $data = null, string $resource = 'data')
     * @method JsonResponse updated(string $message, $data = null, string $resource = 'data')
     * @method JsonResponse deleted(string $message)
     * @method JsonResponse validationError($errors, string $message = 'Error de validación', string $resource = 'data')
     */
    interface ResponseFactory {}
}

namespace {

    use Illuminate\Contracts\Routing\ResponseFactory;

    /**
     * Devuelve una instancia extendida de ResponseFactory con métodos macro personalizados.
     *
     * @return ResponseFactory
     */
    function response() {}
}
