# Skill: Testing & TDD con Pest PHP

> Framework: Pest 3 | Enfoque: TDD estricto (Red → Green)
> Basado en: `src/.claude/skills/pest-testing/SKILL.md`

---

## Principio Fundamental

**Escribe el test antes de escribir el código.** El test fallará (Red), luego escribes el código mínimo para que pase (Green), y finalmente refactoriza el código aplicando optimizaciones y mejores prácticas pero siempre manteniendo los tests verdes (Blue).

```
Red   → escribe el test (falla porque no existe el código)
Green → escribe el código mínimo que lo pasa
Blue → refactoriza el código manteniendo los tests verdes
```

El usuario quiere asegurarse de que el test refleja correctamente el criterio de aceptación antes de escribir código.

---

## Cómo Traducir Criterios de Aceptación a Tests

Los criterios de la issue de GitHub están en formato DADO/CUANDO/ENTONCES. Cada criterio = un test.

**Criterio de la issue:**
```
✅ Un usuario autorizado puede crear una entidad en su organización
   DADO:     un usuario autorizado autenticado con una organización activa
   CUANDO:   envía POST /api/v1/entities con datos válidos
   ENTONCES: se crea la entidad y devuelve 201 con los datos
   HTTP:     201
```

**Test resultante:**
```php
it('allows an authorized user to create an entity in their organization', function () {
    // DADO
    $user = User::factory()->create();
    $organization = Organization::factory()->for($user)->create();

    // CUANDO
    $response = $this->actingAs($user)->postJson('/api/v1/entities', [
        'name'            => 'Entity Name',
        'organization_id' => $organization->id,
    ]);

    // ENTONCES
    $response->assertCreated();
    $response->assertJsonStructure(['data' => ['id', 'name', 'organization_id']]);
    $this->assertDatabaseHas('entities', [
        'name'            => 'Entity Name',
        'organization_id' => $organization->id,
    ]);
});
```

---

## Tipos de Tests

### Feature Tests — Endpoints

Prueban la cadena completa: HTTP → Controller → Action → BD.

**Ubicación:** `tests/Feature/{Dominio}/{Entidad}Test.php`

**Cuándo:** para cada endpoint del `tasks.md`.

```php
<?php

declare(strict_types=1);

use App\Models\Entity;
use App\Models\User;

// Happy path
it('creates an entity successfully', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/entities', [
        'name' => 'Entity Name',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('entities', ['name' => 'Entity Name']);
});

// Error de validación
it('requires a name to create an entity', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/entities', []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['name']);
});

// Autorización
it('prevents unauthorized users from creating entities', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/entities', [
        'name' => 'Test',
    ]);

    $response->assertForbidden();
});

// No autenticado
it('requires authentication to create a checklist', function () {
    $this->postJson('/api/v1/checklists', ['name' => 'Test'])
        ->assertUnauthorized();
});
```

---

### Unit Tests — Actions

Prueban la Action de forma aislada, sin HTTP.

**Ubicación:** `tests/Unit/Actions/{Dominio}/{Verbo}{Entidad}ActionTest.php`

**Cuándo:** para lógica de negocio compleja o reglas de dominio.

```php
<?php

declare(strict_types=1);

use App\Actions\{Dominio}\Create{Entidad}Action;
use App\Models\{Entidad};
use App\Models\{EntidadPadre};

it('creates an entity with the correct data', function () {
    // Arrange
    $parent = {EntidadPadre}::factory()->create();

    // Act
    $entity = app(Create{Entidad}Action::class)->handle([
        'name'       => 'Entity Name',
        'parent_id'  => $parent->id,
    ]);

    // Assert
    expect($entity)->toBeInstanceOf({Entidad}::class);
    expect($entity->name)->toBe('Entity Name');
    expect($entity->parent_id)->toBe($parent->id);
});

it('throws an exception when parent does not exist', function () {
    expect(fn () => app(Create{Entidad}Action::class)->handle([
        'name'      => 'Test',
        'parent_id' => 'non-existent-id',
    ]))->toThrow(\Exception::class);
});
```

---

## Creación de Tests

Usa siempre Artisan para crear los archivos:

```bash
# Feature test
php artisan make:test --pest {Dominio}/{Entidad}Test

# Unit test
php artisan make:test --pest --unit Actions/{Dominio}/{Verbo}{Entidad}ActionTest
```

---

## Assertions de Referencia

### Respuestas HTTP

| Usa | En lugar de | Código |
|-----|-------------|--------|
| `assertCreated()` | `assertStatus(201)` | 201 |
| `assertOk()` | `assertStatus(200)` | 200 |
| `assertNoContent()` | `assertStatus(204)` | 204 |
| `assertUnprocessable()` | `assertStatus(422)` | 422 |
| `assertForbidden()` | `assertStatus(403)` | 403 |
| `assertUnauthorized()` | `assertStatus(401)` | 401 |
| `assertNotFound()` | `assertStatus(404)` | 404 |

### Base de Datos

```php
$this->assertDatabaseHas('entities', ['name' => 'Test']);
$this->assertDatabaseMissing('entities', ['name' => 'Borrado']);
$this->assertDatabaseCount('entities', 3);
$this->assertSoftDeleted('entities', ['id' => $entity->id]); // si usa SoftDeletes
```

### Estructura JSON

```php
$response->assertJsonStructure([
    'data' => ['id', 'name', 'created_at'],
]);

$response->assertJsonFragment(['name' => 'Control de temperaturas']);

$response->assertJson(fn (AssertableJson $json) =>
    $json->where('data.name', 'Control de temperaturas')
         ->has('data.id')
         ->etc()
);
```

### Expect (Pest nativo)

```php
expect($checklist)->toBeInstanceOf(Checklist::class);
expect($checklist->name)->toBe('Control de temperaturas');
expect($checklist->active)->toBeTrue();
expect($checklist->deleted_at)->toBeNull();
expect($collection)->toHaveCount(3);
expect($value)->toBeNull();
expect($fn)->toThrow(\Exception::class);
```

---

## Factories

Usa factories para crear datos de test. Comprueba siempre si hay estados (states) disponibles antes de crear datos manualmente.

```php
// Básico
$user = User::factory()->create();
$checklist = Checklist::factory()->create();

// Con atributos
$user = User::factory()->create(['email' => 'user@test.com']);

// Con estados (comprueba el factory antes de inventarte atributos)
$admin = User::factory()->admin()->create();
$manager = User::factory()->manager()->create();
$user  = User::factory()->regular()->create();

// Con relaciones
$checklist = Checklist::factory()
    ->for($organization)
    ->has(Checkpoint::factory()->count(3))
    ->create();

// Sin persistir (para tests unitarios)
$checklist = Checklist::factory()->make();
```

---

## Datasets — Validación con múltiples casos

Usa datasets cuando el mismo test se repite con distintos valores (ideal para reglas de validación).

```php
it('validates required fields', function (string $field) {
    $owner = User::factory()->owner()->create();
    $data  = ['name' => 'Test', 'organization_id' => 1];

    unset($data[$field]);

    $this->actingAs($user)
        ->postJson('/api/v1/entities', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with(['name', 'organization_id']);


it('rejects invalid email formats', function (string $email) {
    $this->postJson('/api/v1/auth/register', ['email' => $email])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
})->with([
    'not-an-email',
    'missing@',
    '@nodomain.com',
    '',
]);
```

---

## Mocking

```php
use function Pest\Laravel\mock; // importar siempre

it('sends a notification after creating an entity', function () {
    $notifier = mock(NotificationService::class)
        ->expect(send: fn () => true);

    app(Create{Entidad}Action::class)->handle([...]);
});
```

---

## Tests de Seguridad (obligatorios en cada endpoint)

Cada endpoint debe tener al menos estos tests de seguridad:

```php
// 1. Sin autenticación → 401
it('requires authentication', function () {
    $this->postJson('/api/v1/entities', [])->assertUnauthorized();
});

// 2. Rol sin permiso → 403
it('forbids users without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/entities', ['name' => 'Test'])
        ->assertForbidden();
});

// 3. Recurso de otra organización → 403 o 404
it('cannot access entities from another organization', function () {
    $user      = User::factory()->create();
    $otherOrg  = Organization::factory()->create();
    $entity    = Entity::factory()->for($otherOrg)->create();

    $this->actingAs($user)
        ->getJson("/api/v1/entities/{$entity->id}")
        ->assertForbidden();
});
```

---

## Architecture Tests

Añade en `tests/Feature/ArchitectureTest.php` para reforzar las convenciones del proyecto:

```php
arch('actions have only one public method')
    ->expect('App\Actions')
    ->toHaveMethod('handle')
    ->not->toHavePublicMethodsBesides(['handle']);

arch('controllers do not contain business logic')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller')
    ->toHaveSuffix('Controller');

arch('models extend Eloquent')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('no debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('strict types declared everywhere')
    ->expect('App')
    ->toUseStrictTypes();
```

---

## Ejecución de Tests

```bash
# Todos los tests
php artisan test --compact

# Filtrar por nombre
php artisan test --compact --filter="creates a checklist"

# Un archivo específico
php artisan test --compact tests/Feature/Checklist/ChecklistTest.php

# Con cobertura de tipos
php artisan test --type-coverage
```

**Regla:** filtra siempre con `--filter` durante el desarrollo para no ejecutar toda la suite en cada cambio.

---

## Orden de Tests por Subtarea (flujo TDD del DEV agent)

Para cada subtarea de la issue de GitHub:

1. **Identifica** el criterio de aceptación (DADO/CUANDO/ENTONCES).
2. **Clasifica** el test: ¿Feature (HTTP) o Unit (Action)?
3. **Escribe** el test. Debe fallar (Red).
4. **Espera aprobación** del usuario.
5. **Escribe** el código mínimo que lo pasa (Green).
6. **Verifica** con `php artisan test --compact --filter="nombre del test"`.

---

## ✅ Checklist antes de entregar un test

- [ ] El nombre del test describe el comportamiento en lenguaje natural
- [ ] Sigue el patrón Arrange / Act / Assert (DADO / CUANDO / ENTONCES)
- [ ] Usa assertions específicas (`assertCreated()`) no genéricas (`assertStatus(201)`)
- [ ] Cubre el happy path, errores de validación, autorización y no autenticado
- [ ] Usa factories con estados en lugar de crear datos manualmente
- [ ] Los datasets cubren variantes de validación repetitivas
- [ ] Importa `use function Pest\Laravel\mock;` si usa mocks
- [ ] El archivo de test está en la ubicación correcta: `tests/Feature/{Dominio}/` o `tests/Unit/Actions/{Dominio}/`
