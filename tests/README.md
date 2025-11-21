# Horus - Tests de Integración con Playwright

## 🎭 Playwright Tests

Esta carpeta contiene tests end-to-end para verificar la integración de Tailwind CSS con Elementor.

## 📋 Tests Disponibles

### 1. `tailwind-integration.spec.js`

Tests que verifican:
- ✅ Carga de Tailwind CSS en el frontend
- ✅ Detección de comentarios debug de Horus
- ✅ Aplicación de clases Tailwind a elementos
- ✅ Tamaño y optimización del CSS generado
- ✅ Extracción de todas las clases Tailwind de la página

## 🚀 Ejecutar Tests

### Todos los tests
```bash
npm test
```

### Ver en el navegador (modo headed)
```bash
npm run test:headed
```

### Debug interactivo
```bash
npm run test:debug
```

### Ver reporte HTML
```bash
npm run test:report
```

## 📊 Resultados Recientes

### ✅ Lo que funciona:
- CSS generado se carga correctamente (8.5KB optimizado)
- Sistema detecta que está usando CSS optimizado
- No hay CDN cargándose (correcto para producción)
- Archivo comprimido: 2.53KB

### ⚠️ Lo que falta:
- No hay elementos con clases `bg-*` en la página actual
- Necesitas agregar clases de Tailwind en Elementor

## 🔧 Cómo agregar clases para testing:

1. Abre Elementor en cualquier página
2. Selecciona un widget
3. Ve a **Advanced > CSS Classes**
4. Agrega: `bg-blue-500 text-white p-4 rounded-lg`
5. Guarda la página
6. Corre `npm run build`
7. Ejecuta los tests de nuevo

## 📁 Archivos Generados

- `detected-classes.txt` - Lista de todas las clases encontradas en la página
- `test-results/` - Screenshots y videos de tests fallidos
- `playwright-report/` - Reporte HTML de tests

## 🐛 Troubleshooting

### Test falla: "No Tailwind classes found"
**Solución:** Agrega clases en Elementor y regenera CSS

### Test falla: "CSS not loaded"
**Solución:** Verifica que el plugin esté activado

### Test falla: "Styles not applied"
**Solución:** Regenera el CSS después de agregar clases

## 📚 Documentación

- [Playwright Docs](https://playwright.dev)
- [Horus README](../README.md)
- [Optimización Guide](../OPTIMIZACION.md)
