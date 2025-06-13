# Sistema de gestor de indicadores (API)

## Autentificacion
Para iniciar sesion
- [Login](api-endpoints/login.md) : POST `/api/login`

Para cerrar sesion
- [Logout](api-edpoints/logout.md) : POST `/api/logout`

Siempre que se mande un token invalido se obtendra la siguiente respuesta.

**Codigo:** `401` Unauthorized
```json
{
    "success": false,
    "message": "Token de autenticación inválido o expirado"
}
```

## Endpoints Abiertos
Estos son los endpoints que no requieren autentificacion

## Endpoints Cerrados
Para estas rutas es necesario que mandes un token valido en los headers

### Indicadores
### Documentos
### Plantillas
### Ejes
### Usuarios