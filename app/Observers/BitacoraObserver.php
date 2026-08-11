<?php

namespace App\Observers;

use App\Models\Bitacora;
use Illuminate\Database\Eloquent\Model;

// Observer genérico: se registra para varios modelos de negocio (ver AppServiceProvider::boot())
// y arma una fila de bitácora en cada alta, baja o cambio, sin que cada controlador tenga que
// acordarse de loguear nada a mano.
class BitacoraObserver
{
    // Nunca se guardan en la bitácora, ni en "antes" ni en "después": son secretos o
    // metadata de auditoría propia de Eloquent que no aporta nada al historial.
    private const CAMPOS_OCULTOS = ['password', 'remember_token', 'created_at', 'updated_at'];

    public function created(Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->registrar(
            $model,
            'creado',
            "Creó {$this->etiquetaModelo($model)} {$this->identificador($model)}",
            null,
            $this->limpiar($model->getAttributes())
        );
    }

    public function updated(Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $nuevos = $this->limpiar($model->getChanges());
        if (! $nuevos) {
            return;
        }

        $anteriores = $this->limpiar(array_intersect_key($model->getOriginal(), $nuevos));
        $campos     = implode(', ', array_keys($nuevos));

        $this->registrar(
            $model,
            'actualizado',
            "Actualizó {$this->etiquetaModelo($model)} {$this->identificador($model)} ({$campos})",
            $anteriores,
            $nuevos
        );
    }

    public function deleted(Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->registrar(
            $model,
            'eliminado',
            "Eliminó {$this->etiquetaModelo($model)} {$this->identificador($model)}",
            $this->limpiar($model->getAttributes()),
            null
        );
    }

    private function registrar(Model $model, string $accion, string $descripcion, ?array $anteriores, ?array $nuevos): void
    {
        Bitacora::create([
            'usuario_id'       => auth()->id(),
            'accion'           => $accion,
            'modelo'           => class_basename($model),
            'modelo_id'        => $model->getKey(),
            'descripcion'      => $descripcion,
            'datos_anteriores' => $anteriores ?: null,
            'datos_nuevos'     => $nuevos ?: null,
            'ip'               => request()?->ip(),
        ]);
    }

    private function limpiar(array $datos): array
    {
        return array_diff_key($datos, array_flip(self::CAMPOS_OCULTOS));
    }

    private function etiquetaModelo(Model $model): string
    {
        return match (class_basename($model)) {
            'Cliente'     => 'el cliente',
            'Expediente'  => 'el expediente',
            'Tramite'     => 'el trámite',
            'Seguimiento' => 'el seguimiento',
            'Gasto'       => 'el gasto',
            'Cobro'       => 'el cobro',
            'Documento'   => 'el documento',
            'Audiencia'   => 'la audiencia',
            'User'        => 'el usuario',
            'Rol'         => 'el rol',
            default       => 'el registro',
        };
    }

    private function identificador(Model $model): string
    {
        return match (class_basename($model)) {
            'Cliente'     => $model->nombre_completo ?? "#{$model->id}",
            'Expediente'  => $model->numero ?? "#{$model->id}",
            'Tramite'     => $model->codigo ?? "#{$model->id}",
            'Seguimiento' => $model->titulo ?? "#{$model->id}",
            'Gasto'       => $model->concepto ?? "#{$model->id}",
            'Cobro'       => number_format((float) $model->monto, 2) . ' Bs',
            'Documento'   => $model->titulo ?? "#{$model->id}",
            'Audiencia'   => $model->titulo ?? "#{$model->id}",
            'User'        => $model->name ?? "#{$model->id}",
            'Rol'         => $model->nombre ?? "#{$model->id}",
            default       => "#{$model->id}",
        };
    }
}
