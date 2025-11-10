# 🚀 DEPLOYMENT DE SMART CONTRACT

## 📋 Cuándo usar esto

**SOLO si cierras Ganache y pierdes el contrato desplegado.**

Mientras Ganache esté abierto, el contrato sigue funcionando en:
```
0xBc1721749875D48c17693fc9B99FEB88264Eb521
```

---

## 🛠️ Instrucciones

### 1. Instalar dependencias (solo la primera vez)
```bash
cd rentalsApi/blockchain-deployment
npm install
```

### 2. Verificar Ganache está corriendo
```
✅ Abrir Ganache GUI
✅ Debe estar en: http://192.168.100.9:7545
✅ Network ID: 5777
```

### 3. Desplegar a Ganache
```bash
cd rentalsApi/blockchain-deployment
npm run deploy
```

### 4. Copiar nuevo contract address del output
```
> contract address:    0xNUEVO_ADDRESS_AQUI    ← COPIAR
```

### 5. Actualizar .env
```bash
# Edita: rentalsApi/.env
BLOCKCHAIN_CONTRACT_ADDRESS=0xNUEVO_ADDRESS_AQUI
```

### 6. Limpiar cache y probar
```bash
cd rentalsApi
php artisan config:clear
php tests/blockchain_test.php
```
