<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documento extends Model
{
    use HasFactory;

    protected $fillable = [
        'expediente_id',
        'usuario_id',
        'titulo',
        'fojas',
        'archivo',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Alias para que ConsolidadorDocumentosService (pensado originalmente para
    // Seguimiento->archivo_adjunto) funcione igual con este modelo sin cambios.
    public function getArchivoAdjuntoAttribute(): ?string
    {
        return $this->archivo;
    }

    public function getIconoArchivoAttribute(): string
    {
        return match (strtolower(pathinfo($this->archivo ?? '', PATHINFO_EXTENSION))) {
            'pdf'                => 'fa-file-pdf',
            'doc', 'docx'        => 'fa-file-word',
            'jpg', 'jpeg', 'png' => 'fa-file-image',
            default              => 'fa-file',
        };
    }
}
