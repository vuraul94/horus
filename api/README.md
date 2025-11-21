# Horus Tailwind API

API REST para compilar Tailwind CSS de forma remota.

## Instalación

```bash
cd api
npm install
```

## Uso

### Iniciar servidor

```bash
npm start
```

El servidor se iniciará en `http://localhost:3200`

### Desarrollo (con auto-reload)

```bash
npm run dev
```

## Endpoints

### GET /health

Health check del servicio.

**Response:**
```json
{
  "status": "ok",
  "service": "horus-tailwind-api",
  "version": "1.0.0"
}
```

### POST /compile

Compila clases de Tailwind CSS.

**Request Body:**
```json
{
  "classes": ["flex", "bg-red-500", "text-white", "p-4"],
  "config": {
    "colors": {
      "brand": "#ff0000"
    }
  }
}
```

**Response:**
```json
{
  "success": true,
  "css": ".flex{display:flex}.bg-red-500{...}",
  "stats": {
    "classesReceived": 4,
    "outputSize": 1234
  }
}
```

## Variables de entorno

- `PORT`: Puerto del servidor (default: 3200)

## Despliegue

Esta API puede desplegarse en cualquier servicio de hosting Node.js:

- Railway
- Render
- Heroku
- DigitalOcean App Platform
- VPS con PM2

### Ejemplo con PM2

```bash
npm install -g pm2
pm2 start server.js --name horus-api
pm2 save
```
