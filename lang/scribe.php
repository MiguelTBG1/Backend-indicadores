<?php

return [
    "labels" => [
        "search" => "Buscar",
        "base_url" => "URL Base",
    ],

    "auth" => [
        "none" => "Esta API no requiere autenticación.",
        "instruction" => [
            "query" => <<<TEXT
                Para autenticar las solicitudes, incluye un parámetro de consulta **`:parameterName`** en la solicitud.
                TEXT,
            "body" => <<<TEXT
                Para autenticar las solicitudes, incluye un parámetro **`:parameterName`** en el cuerpo de la solicitud.
                TEXT,
            "query_or_body" => <<<TEXT
                Para autenticar las solicitudes, incluye un parámetro **`:parameterName`** en la cadena de consulta o en el cuerpo de la solicitud.
                TEXT,
            "bearer" => <<<TEXT
                Para autenticar las solicitudes, incluye un encabezado **`Authorization`** con el valor **`"Bearer :placeholder"`**.
                TEXT,
            "basic" => <<<TEXT
                Para autenticar las solicitudes, incluye un encabezado **`Authorization`** en el formato **`"Basic {credentials}"`**. 
                El valor de `{credentials}` debe ser tu usuario/id y tu contraseña, unidos por dos puntos (:), 
                y luego codificados en base64.
                TEXT,
            "header" => <<<TEXT
                Para autenticar las solicitudes, incluye un encabezado **`:parameterName`** con el valor **`":placeholder"`**.
                TEXT,
        ],
        "details" => <<<TEXT
            Todos los endpoints autenticados están marcados con una insignia `requiere autenticación` en la documentación a continuación.
            TEXT,
    ],

    "headings" => [
        "introduction" => "Introducción",
        "auth" => "Autenticación de solicitudes",
    ],

    "endpoint" => [
        "request" => "Solicitud",
        "headers" => "Encabezados",
        "url_parameters" => "Parámetros de URL",
        "body_parameters" => "Parámetros de cuerpo",
        "query_parameters" => "Parámetros de consulta",
        "response" => "Respuesta",
        "response_fields" => "Campos de respuesta",
        "example_request" => "Ejemplo de solicitud",
        "example_response" => "Ejemplo de respuesta",
        "responses" => [
            "binary" => "Datos binarios",
            "empty" => "Respuesta vacía",
        ],
    ],

    "try_it_out" => [
        "open" => "Probar ⚡",
        "cancel" => "Cancelar 🛑",
        "send" => "Enviar solicitud 💥",
        "loading" => "⏱ Enviando...",
        "received_response" => "Respuesta recibida",
        "request_failed" => "La solicitud falló con error",
        "error_help" => <<<TEXT
            Consejo: Verifica que estés correctamente conectado a la red.
            Si eres el responsable de esta API, asegúrate de que tu API esté en funcionamiento y que hayas habilitado CORS.
            Puedes revisar la consola de herramientas de desarrollo para información de depuración.
            TEXT,
    ],

    "links" => [
        "postman" => "Ver colección de Postman",
        "openapi" => "Ver especificación OpenAPI",
    ],
];
