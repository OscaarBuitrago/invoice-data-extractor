# Flujo de Desarrollo: DEV Agent

Este documento define cómo trabaja el agente de desarrollo y cómo conecta con el flujo de planificación mediante la especificación de tareas.

---

## Visión General

```
GitHub Issue #N
└── creada por TPM (criterios de aceptación + especificación técnica)
        │
        ▼
┌───────────────────┐
│  DEV Agent        │  "DEV: #N"
│  Senior Developer │  Rama: feat/EP-XXX-T-XXX-N-nombre
└────────┬──────────┘
         │  0. Muestra TODOS los escenarios de test → espera aprobación
         │  Por cada subtarea:
         │  1. Escribe test Pest (Red) → espera aprobación
         │  2. Escribe implementación (Green)
         │  3. Ejecuta tests → verifica que pasan
         │  4. Marca subtarea en la issue de GitHub
         │  5. Al terminar la tarea → commit + PR (Closes #N)
         │
         ▼
   ✅ PR CREADA — issue se cierra al mergear
```

---

## Agentes Disponibles

| Agente | Activación | Responsabilidad |
|--------|-----------|-----------------|
| [Senior Developer](senior-developer.md) | `DEV: #N` | Implementa la issue con TDD, Action Pattern, crea rama `feat/EP-{EPIC}-T-{TASK}-{N}-nombre` y PR |

---

## Skills Disponibles

Las skills son guías/plantillas que el agente usa internamente al generar código.

| Skill | Descripción | Archivo |
|-------|-------------|---------|
| Spatie Guidelines | **Base** — convenciones PHP/Laravel aplicadas en todo el código | [`skills/spatie-guidelines.md`](skills/spatie-guidelines.md) |
| Testing & TDD | **Base** — tests Pest 3, flujo TDD, traducción de criterios de aceptación | [`skills/testing-tdd.md`](skills/testing-tdd.md) |
| Action class | Cómo crear una Action con `handle()` público y métodos privados | [`skills/action.md`](skills/action.md) |
| Endpoint REST | Controller + Form Request + API Resource + Route | [`skills/endpoint.md`](skills/endpoint.md) |
| Migration + Model | Migration y modelo Eloquent con relaciones | [`skills/migration-model.md`](skills/migration-model.md) |
| Frontend | React, TypeScript, Tailwind, Inertia, testing con Vitest | [`skills/frontend.md`](skills/frontend.md) |

---

## Flujo de Trabajo del DEV Agent

El agente trabaja **subtarea a subtarea** con aprobación manual en cada paso:

```
Al recibir la tarea (ANTES de cualquier código):

  0. Genera la lista completa de escenarios de test de todas las subtareas
  ⏸ → Espera aprobación del usuario

Para cada Subtarea (ST-XXX.X.Y):

  1. Anuncia la subtarea
  2. Escribe el TEST (Pest) basado en criterios de aceptación de la issue
  ⏸ → Espera aprobación del usuario
  3. Escribe la IMPLEMENTACIÓN mínima que pasa el test
  4. Ejecuta los tests y verifica que pasan
  ⏸ → Espera confirmación para continuar con la siguiente
```

---

## Cómo Invocar al DEV Agent

```
DEV: [ruta al tasks.md] [ID de tarea]
```

**Ejemplos:**
```
DEV: #42
DEV: #57
```

---

## Prerrequisitos

Antes de activar el DEV agent, asegúrate de que:

1. La issue de GitHub existe y tiene toda la especificación técnica del TPM.
2. Las issues de las que depende esta tarea (campo `Depende de: #N`) están cerradas.
