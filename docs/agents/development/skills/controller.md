# Skill: Controllers

Los controllers son **adaptadores HTTP extremadamente delgados**. Gestionan exclusivamente las preocupaciones HTTP y contienen **cero lógica de dominio**.

## Tipos de Controller

El proyecto tiene dos familias de controllers, separadas por propósito y autenticación:

| Tipo | Ubicación | Autenticación | Respuesta |
|------|-----------|---------------|-----------|
| **API** | `app/Http/Controllers/Api/{Dominio}/` | Sanctum (Bearer token) | API Resource (JSON) |
| **Web** | `app/Http/Controllers/Web/{Dominio}/` | Breeze (sesión) | Inertia::render() |

**Regla crítica:** Los controllers API y Web comparten las **mismas Action classes**. Nunca se duplica la lógica de negocio creando Actions distintas por tipo de controller.

```
Web Controller ──┐
                 ├──▶ Action::handle() ──▶ Model
API Controller ──┘
```

**Un controller solo debe:**
1. Recibir la request con sus dependencias inyectadas
2. Delegar la validación al Form Request
3. Llamar a la Action correspondiente
4. Devolver la respuesta HTTP

**Un controller nunca debe:**
- Contener lógica de negocio
- Hacer queries a la base de datos directamente
- Realizar cálculos
- Gestionar transacciones

---

## Nomenclatura de Form Requests

Cada endpoint tiene **su propia Form Request**. El nombre sigue exactamente el mismo patrón que su Action correspondiente, sustituyendo el sufijo:

| Action | Form Request |
|--------|-------------|
| `RegisterUserAction` | `RegisterUserRequest` |
| `LoginUserAction` | `LoginUserRequest` |
| `UpdateProfileAction` | `UpdateProfileRequest` |
| `CreateEntityAction` | `CreateEntityRequest` |
| `DeleteEntityAction` | `DeleteEntityRequest` |

**Patrón:** `{Verbo}{Entidad}Request` — en `app/Http/Requests/{Dominio}/`

```php
// ✅ CORRECTO — una Request por endpoint, nombre alineado con la Action
RegisterUserRequest   →  RegisterUserAction
LoginUserRequest      →  LoginUserAction
UpdateProfileRequest  →  UpdateProfileAction

// ❌ INCORRECTO — nombre genérico o compartido entre endpoints
RegisterRequest       // sin entidad
AuthRequest           // agrupa múltiples endpoints
UserRequest           // sin verbo
```

**Nunca compartas una Form Request entre dos endpoints distintos.** Si dos endpoints validan campos similares, crea dos Requests independientes.

---

## Nomenclatura de Controllers

Los controllers se nombran en **singular** y se ubican bajo `Api/` o `Web/` según su propósito:

```php
// ✅ CORRECTO — Api controllers
App\Http\Controllers\Api\Domain1\EntityController
App\Http\Controllers\Api\Domain2\ItemController

// ✅ CORRECTO — Web controllers (Inertia)
App\Http\Controllers\Web\Domain1\EntityController
App\Http\Controllers\Web\Domain2\ItemController

// ❌ INCORRECTO — sin separación Api/Web
App\Http\Controllers\Domain1\EntityController
```

Para recursos anidados, se combina padre + hijo (ambos en singular):

```php
// Ruta: /parents/{parent}/children
App\Http\Controllers\Api\Domain1\ParentChildController

// Ruta: /entities/{entity}/related
App\Http\Controllers\Api\Domain2\EntityRelatedController
```

**Patrón:** `{Padre}{Hijo}Controller`

---

## Métodos RESTful

Los controllers de API usan únicamente estos cinco métodos estándar:

| Método | HTTP | Descripción |
|--------|------|-------------|
| `index` | GET | Lista paginada del recurso |
| `show` | GET | Detalle de un recurso |
| `store` | POST | Crear un recurso |
| `update` | PUT/PATCH | Actualizar un recurso |
| `destroy` | DELETE | Eliminar un recurso |

**Los métodos `create` y `edit` no existen en APIs** — son exclusivos de aplicaciones web con formularios HTML.

### Acciones no RESTful → Controller invocable

Si un endpoint no encaja en los cinco métodos estándar, extráelo a su propio controller invocable:

```php
// ✅ CORRECTO — controller invocable para acción no RESTful (API)
// app/Http/Controllers/Api/User/AcceptInvitationController.php
namespace App\Http\Controllers\Api\User;

class AcceptInvitationController extends Controller
{
    public function __invoke(
        string $token,
        AcceptInvitationAction $action
    ): \Illuminate\Http\Response {
        $action->handle($token);

        return response()->noContent();
    }
}

// routes/api.php
Route::post('invitations/{token}/accept', AcceptInvitationController::class);
```

---

## Estructura de un Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\{Dominio}; // o Web\{Dominio} para controllers Inertia

use App\Actions\{Dominio}\Create{Entidad}Action;
use App\Actions\{Dominio}\Update{Entidad}Action;
use App\Actions\{Dominio}\Delete{Entidad}Action;
use App\Http\Controllers\Controller;
use App\Http\Requests\{Dominio}\Create{Entidad}Request;  // una Request por endpoint
use App\Http\Requests\{Dominio}\Update{Entidad}Request;  // una Request por endpoint
use App\Http\Resources\{Dominio}\{Entidad}Resource;
use App\Models\{Entidad};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class {Entidad}Controller extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return {Entidad}Resource::collection({Entidad}::paginate());
    }

    public function show({Entidad} ${entidad}): {Entidad}Resource
    {
        return {Entidad}Resource::make(${entidad});
    }

    public function store(
        Create{Entidad}Request $request,
        Create{Entidad}Action $action
    ): JsonResponse {
        ${entidad} = $action->handle($request->validated());

        return {Entidad}Resource::make(${entidad})
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        Update{Entidad}Request $request,
        {Entidad} ${entidad},
        Update{Entidad}Action $action
    ): {Entidad}Resource {
        ${entidad} = $action->handle(${entidad}, $request->validated());

        return {Entidad}Resource::make(${entidad});
    }

    public function destroy(
        {Entidad} ${entidad},
        Delete{Entidad}Action $action
    ): \Illuminate\Http\Response {
        $action->handle(${entidad});

        return response()->noContent(); // HTTP 204
    }
}
```

---

## Autorización

Usa `$this->authorize()` dentro del método o Policy en la ruta:

```php
// Opción A — en el método del controller
public function store(
    CreateEntityRequest $request,  // {Verbo}{Entidad}Request
    CreateEntityAction $action     // {Verbo}{Entidad}Action — mismo verbo y entidad
): JsonResponse {
    $this->authorize('create', Entity::class);

    $entity = $action->handle($request->validated());

    return EntityResource::make($entity)->response()->setStatusCode(201);
}

// Opción B — en la ruta (preferida para mantener el controller limpio)
Route::post('entities', [EntityController::class, 'store'])
    ->can('create', Entity::class);
```

---

## Route Model Binding

Usa siempre route model binding para inyectar modelos automáticamente:

```php
// routes/api.php
Route::get('entities/{entity}', [EntityController::class, 'show']);

// Controller — recibe el modelo directamente, sin buscar en BD
public function show(Entity $entity): EntityResource
{
    return EntityResource::make($entity->load('relatedEntities'));
}
```

Para claves personalizadas (UUID):
```php
Route::get('entities/{entity:uuid}', [EntitiesController::class, 'show']);
```

---

## Tipos de Respuesta

**Regla absoluta: todos los controllers devuelven siempre un API Resource.** Nunca `response()->json()`, nunca arrays, nunca modelos crudos. La única excepción es `destroy`, que devuelve `response()->noContent()` (HTTP 204) porque no hay cuerpo que representar.

```php
// Recurso único — 200
return {Entidad}Resource::make($entidad);

// Recurso creado — 201
return {Entidad}Resource::make($entidad)
    ->response()
    ->setStatusCode(201);

// Colección paginada — 200
return {Entidad}Resource::collection($entidades->paginate());

// Sin contenido (delete) — 204 — única excepción al uso de Resource
return response()->noContent();
```

**Tipos de retorno PHP en la firma del método:**

```php
// Recurso único — 200
public function show(Entidad $entidad): EntidadResource { ... }

// Recurso creado — 201 (->response()->setStatusCode() devuelve JsonResponse)
public function store(...): JsonResponse { ... }

// Colección
public function index(): AnonymousResourceCollection { ... }

// Delete
public function destroy(...): \Illuminate\Http\Response { ... }
```

---

## Errores Comunes

```php
// ❌ INCORRECTO — lógica de negocio en el controller
public function store(Request $request): JsonResponse
{
    $entity = Entity::create($request->validated());
    $entity->relatedItems()->createMany($request->items);
    $entity->update(['status' => 'active']);

    return response()->json($entity, 201);
}

// ✅ CORRECTO — el controller delega todo a la Action
public function store(
    CreateEntityRequest $request,
    CreateEntityAction $action
): JsonResponse {
    $entity = $action->handle($request->validated());

    return EntityResource::make($entity)->response()->setStatusCode(201);
}
```

```php
// ❌ INCORRECTO — query directa en el controller
public function index(): JsonResponse
{
    $entities = Entity::where('status', 'active')->latest()->paginate();

    return response()->json($entities);
}

// ✅ CORRECTO — colección con Resource
public function index(): AnonymousResourceCollection
{
    return EntityResource::collection(
        Entity::where('status', 'active')->latest()->paginate()
    );
}
```

---

## ✅ Checklist antes de escribir un controller

- [ ] Nombre en singular (`EntityController`, no `EntitiesController`)
- [ ] Solo métodos RESTful (`index`, `show`, `store`, `update`, `destroy`)
- [ ] Acciones no RESTful extraídas a controllers invocables
- [ ] Cada endpoint tiene su propia Form Request con el patrón `{Verbo}{Entidad}Request`
- [ ] El nombre de la Request coincide con el de su Action (mismo verbo y entidad)
- [ ] Cero lógica de negocio — todo delegado a la Action
- [ ] Cero queries directas — usa route model binding o API Resource
- [ ] Autorización via `authorize()` o middleware `can:`
- [ ] Toda respuesta pasa por un API Resource — sin `response()->json()`, sin arrays, sin modelos crudos
- [ ] `destroy` devuelve `response()->noContent()` (única excepción al uso de Resource)
- [ ] Códigos HTTP correctos (201 al crear, 204 al eliminar)
