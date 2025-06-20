# Tipos de Usuarios del Sistema

Este documento describe los distintos tipos de usuarios registrados en el sistema, cómo inician sesión y qué permisos tienen en base a su rol asignado.

---

## 1. Super Usuario

* **Correo:** `admin@test.com`
* **Contraseña (por defecto):** `123456`
* **Rol:** `super_usuario`
* **Descripción:** Tiene acceso completo a todos los recursos y acciones del sistema.
* **Permisos:**

  * Recursos: Todos (`*`)
  * Acciones: Todas (`*`)

---

## 2. Coordinador Académico

* **Correo:** `coordinador@test.com`
* **Contraseña (por defecto):** `123456`
* **Rol:** `Coordinador académico`
* **Descripción:** Gestiona indicadores y documentos académicos.
* **Permisos:**

  * Recursos: `Indicadores`, `Documentos`
  * Acciones: Todas (`crear`, `leer`, `actualizar`, `eliminar`)

---

## 3. Editor de Plantillas

* **Correo:** `editorPlantillas@hotmail.com`
* **Contraseña (por defecto):** `123456`
* **Rol:** `Editor de plantillas`
* **Descripción:** Responsable de gestionar las plantillas de documentos.
* **Permisos:**

  * Recursos: `Plantillas`
  * Acciones: Todas (`crear`, `leer`, `actualizar`, `eliminar`)

---

## 4. Lector General

* **Correo:** `lector@test.com`
* **Contraseña (por defecto):** `123456`
* **Rol:** `Lector general`
* **Descripción:** Usuario con permisos de solo lectura en todo el sistema.
* **Permisos:**

  * Recursos: Todos (`*`)
  * Acciones: `leer`

---

## 5. Analista de Indicadores

* **Correo:** `analista@test.com`
* **Contraseña (por defecto):** `123456`
* **Rol:** `Analista de indicadores`
* **Descripción:** Accede a indicadores para análisis sin posibilidad de modificarlos.
* **Permisos:**

  * Recursos: `Indicadores`
  * Acciones: `leer`

---

## 6. Creador de Documentos

* **Correo:** `creador@test.com`
* **Contraseña (por defecto):** `123456`
* **Rol:** `Creador de documentos`
* **Descripción:** Puede crear y modificar sus propios documentos, pero no tiene acceso a eliminar o modificar documentos de otros.
* **Permisos:**

  * Recursos: `Documentos`
  * Acciones: `crear`, `actualizar`

---

## Inicio de Sesión

Todos los usuarios inician sesión mediante el sistema de autenticación del backend. Se autentican usando:

* **Email:** proporcionado en la creación del usuario
* **Contraseña:** almacenada de forma segura mediante `Hash::make()`

Las rutas de login deberían estar protegidas por mecanismos como Laravel Sanctum para garantizar autenticación segura.

---

## Consideraciones Adicionales

* Los roles se asignan mediante el campo `roles` en el modelo `User`, que acepta uno o más identificadores de roles (`_id`).
* Los permisos se definen como combinaciones de `acciones` y `recursos`.
* El uso de `*` en acciones o recursos implica acceso total.

---

Para una descripción técnica del modelo de roles y permisos, revisar los seeders: `UserCollectionSeeder` y `RolesSeeder`.
