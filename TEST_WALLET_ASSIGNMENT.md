# TEST DE ASIGNACIÓN DE WALLETS

## Todos los usuarios actuales YA tienen wallet asignada:

```
Usuario ID 1 (Propietario): 0x24CFa8f7157A69AB1a2Cc2182f19ef0f5C6B53F3
Usuario ID 2 (Cliente):     0xD3dc883b21035A1AB965736493771B1E2153F074
Usuario ID 3 (Rosario):     0xFDF5859C660e277FD0AA2b246904f081f42b8460
Usuario ID 4 (Test Debug):  0xF1e47ba25902e475f490A9876B53c30b2F7E4214
```

## Pruebas a realizar cuando reinicies el servidor:

### 1. Probar endpoint de registro (/app/create/user):

```bash
curl -X POST "http://192.168.180.149:8000/api/app/create/user" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Registro",
    "email": "testregistro@test.com",
    "password": "123456",
    "usernick": "testregistro",
    "num_id": "77777777",
    "telefono": "77777777",
    "direccion": "Calle Test",
    "tipo_usuario": "cliente"
  }'
```

**Resultado esperado:** Debe asignar wallet automáticamente (wallet #4: 0xac0808Dc1B52FD0bCA294e79c6EBD88eE32334d7)

---

### 2. Probar endpoint admin (/app/users/store):

```bash
curl -X POST "http://192.168.180.149:8000/api/app/users/store" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Admin",
    "email": "testadmin@test.com",
    "password": "123456",
    "usernick": "testadmin",
    "num_id": "88888888",
    "telefono": "88888888",
    "direccion": "Calle Admin",
    "tipo_usuario": "propietario"
  }'
```

**Resultado esperado:** Debe asignar wallet automáticamente (siguiente disponible)

---

### 3. Verificar wallets asignadas:

```bash
# Desde tinker
php artisan tinker --execute="echo json_encode(DB::table('users')->select('id', 'name', 'wallet_address')->get());"
```

---

### 4. Probar seeder (después de borrar BD):

```bash
php artisan migrate:fresh
php artisan db:seed
```

**Resultado esperado:**
- Usuario "Propietario" debe tener wallet
- Usuario "Cliente" debe tener wallet

---

## Verificación rápida de private keys:

```bash
# Usuario 1
curl -X GET "http://192.168.180.149:8000/api/app/users/1/private-key"

# Usuario 2
curl -X GET "http://192.168.180.149:8000/api/app/users/2/private-key"
```

Debería retornar las private keys encriptadas.
