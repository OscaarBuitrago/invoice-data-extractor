# Skill: Crear Action Class

## Propósito

Una Action es la unidad mínima de lógica de negocio. Cada Action hace **una sola cosa** y la hace bien.

## Reglas de Oro

1. **Un único método público:** `handle()` — es el único punto de entrada.
2. **Todos los demás métodos son privados.** Sin excepciones.
3. **Nombre:** `{Verbo}{Entidad}Action` — siempre en inglés, siempre en PascalCase.
4. **Sin lógica en Controllers:** los controllers delegan 100% a Actions.
5. **Composición:** una Action puede inyectar y usar otras Actions en el constructor.
6. **Transacciones:** toda modificación a la base de datos va dentro de `DB::transaction()`.
7. **Tipado estricto:** siempre `declare(strict_types=1)`, parámetros y retornos tipados.

## Convención de Nombres

| Operación | Nombre |
|-----------|--------|
| Crear     | `CreateEntityAction` |
| Actualizar | `UpdateEntityAction` |
| Eliminar  | `DeleteEntityAction` |
| Calcular  | `CalculateValueAction` |
| Enviar    | `SendNotificationAction` |
| Procesar  | `ProcessBatchAction` |

## Ubicación

```
app/Actions/{Dominio}/{Verbo}{Entidad}Action.php
```

**Ejemplos:**
```
app/Actions/Domain1/CreateEntityAction.php
app/Actions/Domain2/UpdateEntityAction.php
app/Actions/Domain3/ProcessBatchAction.php
```

## Plantilla Base

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Dominio};

use App\Models\{Entidad};
use Illuminate\Support\Facades\DB;

class {Verbo}{Entidad}Action
{
    public function __construct(
        // Inyecta otras Actions si necesitas componer lógica
        // private readonly OtraAction $otraAction,
    ) {}

    /**
     * @throws \Exception
     */
    public function handle({ParámetrosTipados}): {TipoRetorno}
    {
        return DB::transaction(function () use ({parámetros}) {
            $this->validate({parámetros});

            // lógica principal

            return $resultado;
        });
    }

    private function validate({parámetros}): void
    {
        // Validaciones de reglas de negocio (no de formato — eso va en Form Request)
        // Lanza excepciones de dominio si algo falla
    }
}
```

## Plantilla con Datos Tipados (Data Objects)

Cuando la Action recibe múltiples parámetros, agrúpalos en un Data Object:

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Dominio};

use App\Data\{Dominio}\{Entidad}Data;
use App\Models\{Entidad};
use Illuminate\Support\Facades\DB;

class Create{Entidad}Action
{
    public function handle({Entidad}Data $data): {Entidad}
    {
        return DB::transaction(function () use ($data) {
            $this->ensureUnique($data);

            return {Entidad}::create([
                'campo_1' => $data->campo1,
                'campo_2' => $data->campo2,
            ]);
        });
    }

    private function ensureUnique({Entidad}Data $data): void
    {
        // Comprueba reglas de unicidad u otras restricciones de negocio
    }
}
```

## Cómo se invoca desde un Controller

```php
// En el Controller — cero lógica, solo delegación
public function store(Create{Entidad}Request $request, Create{Entidad}Action $action): JsonResponse
{
    $resultado = $action->handle({Entidad}Data::from($request->validated()));

    return {Entidad}Resource::make($resultado)
        ->response()
        ->setStatusCode(201);
}
```

## Cómo se invoca desde otro contexto (Jobs, Listeners...)

```php
// Usando resolve() para consistencia
$resultado = resolve(Create{Entidad}Action::class)->handle($data);
```

## ✅ Checklist antes de crear una Action

- [ ] El nombre sigue el patrón `{Verbo}{Entidad}Action`
- [ ] Está en `app/Actions/{Dominio}/`
- [ ] Tiene `declare(strict_types=1)`
- [ ] Solo tiene un método público: `handle()`
- [ ] Todos los métodos auxiliares son `private`
- [ ] Las modificaciones a BD están en `DB::transaction()`
- [ ] Los parámetros y el retorno están tipados
- [ ] No contiene lógica de presentación (eso va en Resources)
- [ ] No valida formato de campos (eso va en Form Requests)
