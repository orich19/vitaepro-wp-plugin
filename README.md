# vitaepro-wp-plugin
Plugin WordPress para generar currículum Vitae dinámicos con tablas personalizadas.

## Dependencias

Para habilitar la exportación a PDF es necesario instalar las dependencias de Composer dentro de la carpeta del plugin:

```
cd wp-content/plugins/vitaepro
composer require dompdf/dompdf
```

Tras la instalación recuerda volver a activar el plugin o ejecutar un `flush_rewrite_rules()` para asegurar la carga de las nuevas rutas públicas.
