# Skill: Crear Endpoint REST

## Estructura de un Endpoint

Un endpoint REST completo tiene **4 piezas**. Cada una tiene una responsabilidad única.

```
HTTP Request
    │
    ▼
Form Request     ← valida y autoriza la request
    │
    ▼
Controller       ← recibe, delega a la Action, devuelve respuesta
    │
    ▼
Action           ← ejecuta la lógica de negocio
    │
    ▼
API Resource     ← formatea la respuesta JSON
```

## Convención de Rutas

Todas las rutas de la API siguen: `/api/v1/{recurso}`

```php
// routes/api.php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('entities', EntityController::class);
    // o rutas individuales:
    Route::post('entities', [EntityController::class, 'store']);
    Route::get('entities/{entity}', [EntityController::class, 'show']);
});
```

## Ubicaciones de Archivos

Este skill cubre endpoints **API REST**. Para controllers Web (Inertia), ver skill `controller.md`.

```
app/Http/Controllers/Api/{Dominio}/{Entidad}Controller.php
app/Http/Requests/{Dominio}/{Verbo}{Entidad}Request.php
app/Http/Resources/{Dominio}/{Entidad}Resource.php
routes/api.php
```

---

## 1. Form Request

Valida formato e inputs. **No valida reglas de negocio** (eso es la Action).

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\{Dominio};

use Illuminate\Foundation\Http\FormRequest;

class {Verbo}{Entidad}Request extends FormRequest
{
    public function authorize(): bool
    {
        // Autorización basada en Policy o Gate
        return $this->user()->can('{accion}', {Entidad}::class);
        // o para recursos existentes:
        // return $this->user()->can('{accion}', $this->route('{entidad}'));
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campo_1' => ['required', 'string', 'max:255'],
            'campo_2' => ['nullable', 'string'],
            // añade reglas según la especificación del tasks.md
        ];
    }
}
```

---

## 2. Controller

Cero lógica de negocio. Solo recibe, delega y responde.

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\{Dominio};

use App\Actions\{Dominio}\Create{Entidad}Action;
use App\Http\Controllers\Controller;
use App\Http\Requests\{Dominio}\Create{Entidad}Request;
use App\Http\Resources\{Dominio}\{Entidad}Resource;
use Illuminate\Http\JsonResponse;

class {Entidad}Controller extends Controller
{
    public function store(Create{Entidad}Request $request, Create{Entidad}Action $action): JsonResponse
    {
        $entidad = $action->handle($request->validated());

        return {Entidad}Resource::make($entidad)
            ->response()
            ->setStatusCode(201);
    }

    public function show({Entidad} $entidad): {Entidad}Resource
    {
        return {Entidad}Resource::make($entidad);
    }

    public function update(Update{Entidad}Request $request, {Entidad} $entidad, Update{Entidad}Action $action): {Entidad}Resource
    {
        $entidad = $action->handle($entidad, $request->validated());

        return {Entidad}Resource::make($entidad);
    }

    public function destroy({Entidad} $entidad, Delete{Entidad}Action $action): \Illuminate\Http\Response
    {
        $action->handle($entidad);

        return response()->noContent();
    }
}
```

---

## 3. API Resource

Formatea el JSON de respuesta. Nunca expone campos que no deben ser públicos.

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\{Dominio};

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {Entidad}Resource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'campo_1'    => $this->campo_1,
            'campo_2'    => $this->campo_2,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

---

## Códigos HTTP de Referencia

| Situación | Código |
|-----------|--------|
| Recurso creado | `201 Created` |
| Operación exitosa (lectura/update) | `200 OK` |
| Eliminación exitosa | `204 No Content` |
| Validación fallida | `422 Unprocessable Entity` |
| No autenticado | `401 Unauthorized` |
| Sin permisos | `403 Forbidden` |
| Recurso no encontrado | `404 Not Found` |

---

## ✅ Checklist antes de crear un endpoint

- [ ] La ruta sigue la convención `/api/v1/{recurso}`
- [ ] La ruta está bajo middleware `auth:sanctum` (salvo que sea pública)
- [ ] El Form Request valida todos los campos del tasks.md
- [ ] El Form Request autoriza usando Policy o Gate
- [ ] El Controller no tiene lógica de negocio (solo delega)
- [ ] La Action es la que hace el trabajo real
- [ ] El API Resource no expone campos sensibles
- [ ] Los códigos HTTP son los correctos según la operación
