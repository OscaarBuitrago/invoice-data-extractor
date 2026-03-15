# Skill: Crear Migration + Model

## Reglas Generales

1. **Un modelo por entidad de dominio.**
2. **ULIDs como PK:** todas las tablas usan ULID en lugar de auto-increment integer. Usar `$table->ulid('id')->primary()` en la migración y el trait `HasUlids` en el modelo.
3. **Tipos de columna explícitos:** nunca usar `string` cuando debe ser `unsignedBigInteger`, `decimal`, etc.
4. **Siempre `$fillable`:** nunca usar `$guarded = []`.
5. **Casts explícitos:** fechas, booleans y decimales siempre tipados en `$casts`.
6. **Soft deletes** solo si la épica lo requiere explícitamente.
7. **Foreign keys** siempre con `constrained()` y la acción `onDelete`.

## Ubicaciones

```
database/migrations/{timestamp}_create_{tabla}_table.php
app/Models/{Entidad}.php
database/factories/{Entidad}Factory.php   ← crear siempre junto con el modelo
```

Los modelos siguen estando en `app/Models/` (Laravel estándar, carpeta plana).

---

## Plantilla de Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{tabla}', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Foreign keys — usar foreignUlid para columnas que referencian tablas con ULID PK
            $table->foreignUlid('{entidad_padre}_id')
                  ->constrained()
                  ->onDelete('cascade'); // o 'restrict' según regla de negocio

            // Campos propios
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);

            // Enum con valores válidos del dominio
            $table->enum('estado', ['borrador', 'activo', 'archivado'])->default('borrador');

            // Soft deletes (solo si la épica lo requiere)
            // $table->softDeletes();

            $table->timestamps();
        });
    }

};
```

---

## Plantilla de Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\SoftDeletes; // si aplica

class {Entidad} extends Model
{
    use HasFactory;
    use HasUlids;
    // use SoftDeletes; // si aplica

    /**
     * @var list<string>
     */
    protected $fillable = [
        '{entidad_padre}_id',
        'nombre',
        'descripcion',
        'activo',
        'orden',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo'     => 'boolean',
            'orden'      => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function {entidadPadre}(): BelongsTo
    {
        return $this->belongsTo({EntidadPadre}::class);
    }

    public function {entidadesHijas}(): HasMany
    {
        return $this->hasMany({EntidadHija}::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /**
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
```

---


## Tablas Pivot (Relaciones M:N)

Cuando dos entidades tienen una relación muchos-a-muchos, se crea una tabla pivot. La tabla NO tiene modelo propio ni factory. Se nombra en singular, en orden alfabético: `{entidad_a}_{entidad_b}`.

```php
Schema::create('{entidad_a}_{entidad_b}', function (Blueprint $table) {
    $table->foreignUlid('{entidad_a}_id')->constrained()->onDelete('cascade');
    $table->foreignUlid('{entidad_b}_id')->constrained()->onDelete('cascade');
    $table->unsignedInteger('order')->default(0); // si tiene orden
    // Sin id, sin timestamps (salvo que la épica lo requiera)
});
```

En el modelo se define la relación con `belongsToMany`:

```php
public function {entidadesB}(): BelongsToMany
{
    return $this->belongsToMany({EntidadB}::class)
                ->withPivot('order')
                ->orderByPivot('order');
}
```

---

## Factory

**Todo modelo debe tener su factory.** Se crea en `database/factories/{Entidad}Factory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\{EntidadPadre};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\{Entidad}>
 */
class {Entidad}Factory extends Factory
{
    public function definition(): array
    {
        return [
            '{entidad_padre}_id' => {EntidadPadre}::factory(),
            'nombre'             => $this->faker->words(3, true),
            'descripcion'        => $this->faker->sentence(),
            'activo'             => true,
        ];
    }

    // Estados reutilizables en tests
    public function inactive(): static
    {
        return $this->state(['activo' => false]);
    }
}
```

---

## Tipos de Columna de Referencia

| Dato | Tipo de columna |
|------|-----------------|
| ULID PK | `$table->ulid('id')->primary()` |
| Foreign key (a tabla con ULID) | `$table->foreignUlid('user_id')->constrained()->onDelete('cascade')` |
| Texto corto (≤255) | `$table->string('nombre')` |
| Texto largo | `$table->text('descripcion')` |
| Número entero | `$table->unsignedInteger('orden')` |
| Decimal | `$table->decimal('valor', 8, 2)` |
| Booleano | `$table->boolean('activo')->default(true)` |
| Opciones fijas | `$table->enum('estado', ['a', 'b', 'c'])` |
| Fecha y hora | `$table->timestamp('ejecutado_en')->nullable()` |
| JSON | `$table->json('metadata')->nullable()` |

---

## ✅ Checklist antes de crear Migration + Model

- [ ] El nombre de la tabla es plural snake_case (`checklist_checkpoints`)
- [ ] La PK usa `$table->ulid('id')->primary()` (nunca `$table->id()`)
- [ ] Las foreign keys a otras tablas usan `foreignUlid()` (nunca `foreignId()`)
- [ ] El modelo tiene el trait `HasUlids`
- [ ] Todas las foreign keys tienen `constrained()` y `onDelete` definido
- [ ] El modelo tiene `$fillable` con todos los campos modificables
- [ ] Los `casts` están definidos para booleans, integers y fechas
- [ ] Las relaciones están definidas con tipos de retorno correctos
- [ ] No hay lógica de negocio en el modelo (eso va en Actions)
- [ ] La migración NO incluye método `down()` (ver spatie-guidelines)
- [ ] Se ha creado el Factory con al menos el estado por defecto
- [ ] Las tablas pivot no tienen modelo ni factory propio
