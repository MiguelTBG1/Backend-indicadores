<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use App\Models\PersonalAccessToken;
use App\Models\Plantillas;
use App\Models\Documentos;
use App\Policies\PlantillaPolicy;
use App\Policies\DocumentoPolicy;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        Gate::policy(Plantillas::class, PlantillaPolicy::class);

        /**
         * Macros para manejar respuestas estandarizadas
         * 
         * Estas estan basadas en las recomendaciones de JSEND
         * 
         * Puedes encontrar mas informacion en:
         * https://github.com/omniti-labs/jsend
         */

        /**
         *  Respuesta estandarizada para respuestas exitosas
         * 
         * Usar para cualquier caso de exito
        */
        Response::macro('success', function (string $message, $data = null, int $status = HttpResponse::HTTP_OK) {
            return Response::json([
                'status'  => 'success',
                'message' => $message,
                'data'    => $data,
            ], $status);
        });

        /**
         * Respuesta estandarizada para respuestas fallidas
         * 
         * Usar cuando se tenga que cancelar una operacion
         */
        Response::macro('fail', function (string $message, $data = null, int $status = HttpResponse::HTTP_BAD_REQUEST) {
            return Response::json([
                'status'  => 'fail',
                'message' => $message,
                'data'    => $data,
            ], $status);
        });

        /**
         * Repuesta estandarizada para errores del servidor
         * 
         * Usar para errores inesperados (try-catch, excepciones, etc)
         */
        Response::macro('error', function (string $message, $data = null, int $status = HttpResponse::HTTP_INTERNAL_SERVER_ERROR) {
            return Response::json([
                'status'  => 'error',
                'message' => $message,
                'data'    => $data,
            ], $status);
        });

        /**
         * Atajos para respuestas comunes.
         * 
         * Usar para respuestas 
         */
        Response::macro('created', fn ($message, $data = null) => response()->success($message, $data, HttpResponse::HTTP_CREATED));
        Response::macro('updated', fn ($message, $data = null) => response()->success($message, $data, HttpResponse::HTTP_OK));
        Response::macro('deleted', fn ($message) => response()->success($message, null, HttpResponse::HTTP_OK));

        /**
         * Respuesta estandarizada para errores de validacion
         * 
         * Usar cuando la validacion falle
         */
        Response::macro('validationError', function ($errors, string $message = 'Error de validación') {
            return response()->fail($message, $errors, HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        });
    }
}
