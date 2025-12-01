# Star Wars — Consulta de personajes (SWAPI)

<div align="center">
  
  [![PHP](https://img.shields.io/badge/php-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)

</div>

Página en PHP que:
- Carga TODOS los personajes con una sola petición (`/people?limit=...`).
- Los muestra en un desplegable.
- Al enviar, consulta el detalle por `uid` y renderiza todas las propiedades.
- Usa Bootstrap para el diseño e incluye una imagen de la saga.
- Cachea la lista 24h para no saturar la API.

## Requisitos
- PHP 8.x con cURL habilitado

## Ejecutar
```bash
php -S localhost:8000
```
Abre http://localhost:8000

## Endpoints usados
- Lista: `https://www.swapi.tech/api/people?limit=1000`
- Detalle: `https://www.swapi.tech/api/people/{uid}`

## Notas
- La salida se escapa con `htmlspecialchars`.
- Si cambia el límite permitido por la API, ajusta el parámetro `limit`.

