# Login
Usado para obtener el token de inicio de sesion.

**URL** : `/api/login/`

**Method** : `POST`

**Auth requerido** : NO

**Body de solicitud**

```json
{
    "email": "[correo electronico valido]",
    "password": "[contrasenia en texto valido]"
}
```

**Ejemplo del body**

```json
{
    "username": "iloveauth@example.com",
    "password": "abcd1234"
}
```

## Respuesta exitosa

**Code** : `200 OK`

**Content example**

```json
{
    "message": "Login exitoso",
    "user": {
        "nombre": "Rodrigo",
        "roles": [
            "administrador",
            "plantillas",
            "capturista",
            "validador",
            "carrusel"
        ],
        "id": "6848f129411b2f868804e5ec"
    },
    "token": "6849b35d7edf7cca610c0df4|vlLKWAtE9CHFUmMTsoyw8LeaS66iRNVs1ziBBEXfe1a8e71b"
}
```

## Error Response

**Condicion** : Si la combinacion de 'username' y 'password' esta mal.

**Code** : `401 UNAUTHORIZED`

**Contenido** :

```json
{
    "message": "Credenciales inválidas"
}
```