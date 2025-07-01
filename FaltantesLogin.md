- [x] Crear seeders para datos iniciales (roles básicos, funciones sistema, usuario admin)

# Modelos

- [x] Modelo Rol
- [x] Modelo Recursos
- [x] Modelo Operaciones
- [ ] Funciones Sistema
- [ ] Extender modelo User para incluir campos de permisos

# Controladores CRUD

- [ ] RolController completo
- [ ] FuncionSistemaController completo
- [ ] Recursos
- [ ] Operaciones
- [] Extender UserController con métodos de gestión de permisos

# Rutas API

- [ ] Rutas para CRUD de roles
- [ ] Rutas para CRUD de recursos
- [ ] Rutas para CRUD de operaciones
- [ ] Rutas para CRUD de funciones sistema
- [ ] Rutas extendidas para usuarios (asignar/quitar permisos)
- [ ] Rutas protegidas con Sanctum

5. Middleware de Autorización

Middleware personalizado para verificar permisos
Middleware para verificar funciones específicas
Registrar middlewares en Kernel