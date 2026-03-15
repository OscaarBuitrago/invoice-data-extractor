# Skill: Spatie Laravel & PHP Guidelines

> Fuente oficial: https://spatie.be/guidelines/laravel-php
> Adaptado de: https://github.com/spatie/boost-spatie-guidelines

## Cuándo aplicar esta skill

Aplica estas reglas en **cualquier archivo PHP o Blade** que crees o edites:
controllers, models, actions, requests, resources, migrations, tests, config, routes.

**Orden de prioridad:**
1. Convenciones de Laravel (primero siempre)
2. PSR-12
3. Reglas de sección específica

---

## 1. PHP Standards

- Sigue PSR-1, PSR-2 y PSR-12.
- `declare(strict_types=1)` al inicio de cada archivo PHP.
- Usa notación corta para nullable: `?string` — nunca `string|null`.
- Especifica siempre el tipo de retorno, incluyendo `void`.

---

## 2. Estructura de Clases

- Usa **typed properties**, no docblocks para tipos.
- Usa **constructor property promotion** cuando todas las propiedades se puedan promover.
- Un trait por línea.

```php
// ✅ CORRECTO
class CreateEntityAction
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}
}

// ❌ INCORRECTO
class CreateEntityAction
{
    /** @var NotificationService */
    private $notifications;

    public function __construct(NotificationService $notifications)
    {
        $this->notifications = $notifications;
    }
}
```

---

## 3. Tipos y Docblocks

- **No añadas docblocks si ya hay type hints completos** — son redundantes.
- Importa siempre los classnames; nunca uses fully qualified names en docblocks.
- Usa docblocks de una línea cuando sea posible: `/** @var string */`
- Para colecciones, especifica el tipo de clave y valor:

```php
/** @return Collection<int, User> */
public function getUsers(): Collection
```

- Para arrays con forma fija (fixed keys), pon cada clave en su propia línea:

```php
/** @return array{
   first: Entity,
   second: RelatedEntity
} */
```

- Para parámetros array, especifica siempre los tipos:

```php
/**
 * @param array<int, RelatedEntity> $checkpoints
 */
public function handle(array $checkpoints): void
```

---

## 4. Control Flow (regla crítica)

### Primero los errores, el happy path al final

```php
// ✅ CORRECTO — early returns primero
public function handle(User $user): ?Entity
{
    if (! $user) {
        return null;
    }

    if (! $user->isActive()) {
        return null;
    }

    // happy path al final
    return $this->createEntity($user);
}

// ❌ INCORRECTO — else innecesario
public function handle(User $user): ?Entity
{
    if ($user && $user->isActive()) {
        return $this->createEntity($user);
    } else {
        return null;
    }
}
```

### Reglas de control flow

- **Evita `else`**: usa early returns en su lugar.
- **Siempre llaves** en control structures, aunque sea una sola línea.
- **Condiciones separadas**: prefiere múltiples `if` simples sobre `&&` compuestos.
- **Ternarios cortos**: en una línea si es muy breve.
- **Ternarios largos**: cada parte en su propia línea.

```php
// Ternario corto
$name = $isFoo ? 'foo' : 'bar';

// Ternario largo
$result = $object instanceof Model
    ? $object->name
    : 'valor por defecto';
```

---

## 5. Strings

- Usa **interpolación**, no concatenación:

```php
// ✅ CORRECTO
$message = "Processing entity id `{$entity->id}`...";

// ❌ INCORRECTO
$message = 'Processing entity id `' . $entity->id . '`...';
```

---

## 6. Convenciones Laravel

### Rutas

| Elemento | Convención | Ejemplo |
|----------|-----------|---------|
| URL | kebab-case | `/open-source`, `/entity-operations` |
| Nombre de ruta | camelCase | `->name('entityOperations')` |
| Parámetros | camelCase | `{checklistId}` |
| Notación | Tuple | `[EntitysController::class, 'store']` |

```php
Route::post('/entity-operations', [OperationsController::class, 'store'])
    ->name('entityOperations.store');
```

### Controllers

- Nombres en **singular**: `EntityController`, `OperationController`.
- Solo métodos CRUD: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
- Si necesitas una acción fuera de CRUD, **extrae un nuevo controller**.

### Configuration

- Archivos: **kebab-case** (`pdf-generator.php`).
- Claves: **snake_case** (`chrome_path`).
- Nunca uses `env()` fuera de archivos de config; usa siempre `config()`.
- Añade configs de servicios en `config/services.php`, no crees nuevos archivos.

### Artisan Commands

- Nombres: **kebab-case** (`delete-old-records`).
- Siempre da feedback: `$this->comment('All ok!')`.
- Muestra progreso en loops; resumen al final.
- Muestra el output **antes** de procesar (facilita debugging):

```php
$entities->each(function (Entity $entity) {
    $this->info("Processing entity id `{$entity->id}`...");
    $this->processEntity($entity);
});

$this->comment("Processed {$entities->count()} entities.");
```

### Migrations

- Solo método `up()` — no escribas `down()`.

---

## 7. Validación

- Usa **array notation** para reglas (más extensible):

```php
// ✅ CORRECTO
public function rules(): array
{
    return [
        'email' => ['required', 'email'],
        'name'  => ['required', 'string', 'max:255'],
    ];
}

// ❌ INCORRECTO
'email' => 'required|email',
```

- Custom rules en snake_case: `organisation_type`, `checklist_status`.

---

## 8. Blade Templates

> Las vistas del proyecto usan **React + Inertia**, no Blade. Blade se utiliza únicamente para **emails y layouts de autenticación** generados por Breeze.

- Indenta con **4 espacios**.
- **Sin espacio** entre la directiva y el paréntesis:

```blade
{{-- ✅ CORRECTO --}}
@if($condition)
    Something
@endif

{{-- ❌ INCORRECTO --}}
@if ($condition)
    Something
@endif
```

- Usa `__()` para traducciones, nunca `@lang`.

---

## 9. Enums

- Valores en **PascalCase**:

```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Archived = 'archived';
}
```

---

## 10. Comentarios

- Evita comentarios; escribe código expresivo.
- Cuando sean necesarios:

```php
// Una línea: espacio después de //

/*
 * Bloque multi-línea: empieza con un solo *
 */
```

- Si sientes que necesitas un comentario, refactoriza el código en un método con nombre descriptivo.

---

## 11. Espacios en Blanco

- Añade líneas en blanco **entre statements** distintos para legibilidad.
- Excepción: secuencias de operaciones simples equivalentes.
- No dejes líneas vacías extras dentro de `{}`.

---

## 12. Autorización

- Policies en **camelCase**: `Gate::define('editEntity', ...)`.
- Usa palabras CRUD, pero `view` en lugar de `show`.

---

## 13. Nomenclatura — Quick Reference

### Clases y archivos

| Artefacto | Convención | Ejemplo |
|-----------|-----------|---------|
| Clases | PascalCase | `EntityController` |
| Métodos y variables | camelCase | `getUserName`, `$firstName` |
| Routes (URL) | kebab-case | `/entity-operations` |
| Config files | kebab-case | `pdf-generator.php` |
| Config keys | snake_case | `chrome_path` |
| Artisan commands | kebab-case | `delete-old-records` |

### Nomenclatura de archivos específicos

| Tipo | Sufijo/Convención | Ejemplo |
|------|------------------|---------|
| Controllers | Singular + `Controller` | `EntityController` |
| Views | camelCase | `openSource.blade.php` |
| Jobs | Basado en acción | `CreateUser`, `SendEmailNotification` |
| Events | Tiempo verbal | `UserRegistering`, `UserRegistered` |
| Listeners | Acción + `Listener` | `SendInvitationMailListener` |
| Commands | Acción + `Command` | `PublishScheduledPostsCommand` |
| Mailables | Propósito + `Mail` | `AccountActivatedMail` |
| Resources | Singular + `Resource` | `EntityResource` |
| Enums | Descriptivo, sin prefijo | `OrderStatus`, `BookingType` |

---

## ✅ Entity antes de entregar código

- [ ] `declare(strict_types=1)` al inicio del archivo
- [ ] Tipos en todas las propiedades, parámetros y retornos (incluyendo `void`)
- [ ] Nullable con `?Type`, nunca `Type|null`
- [ ] Sin `else` innecesario — early returns primero
- [ ] Llaves en todos los control structures
- [ ] Interpolación de strings, no concatenación
- [ ] URLs en kebab-case, nombres de ruta en camelCase
- [ ] Reglas de validación en array notation
- [ ] No docblocks cuando los type hints ya son completos
- [ ] Solo `up()` en migrations
