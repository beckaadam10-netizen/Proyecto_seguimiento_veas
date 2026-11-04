<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

// Sube los respaldos a una carpeta de Google Drive.
//
// El permiso lo da un script de Google Apps Script (resources/drive/respaldos-drive.gs)
// publicado desde la cuenta dueña de la carpeta: con la clave correcta devuelve un token
// de acceso temporal (1 hora) de esa cuenta. Así se usa una carpeta que ya existe, sin
// crear un proyecto en Google Cloud ni una conexión OAuth que en Gmail vence a los 7 días.
class GoogleDriveRespaldos
{
    // Múltiplo de 256 KB, como exige la subida por partes de Drive.
    private const TAMANIO_PARTE = 8 * 1024 * 1024;

    private const API    = 'https://www.googleapis.com/drive/v3';
    private const SUBIDA = 'https://www.googleapis.com/upload/drive/v3/files';

    public static function configurado(): bool
    {
        return filled(config('services.google_drive.script_url')) && filled(config('services.google_drive.clave'));
    }

    // Token temporal y carpeta de destino, pedidos al script.
    public function acceso(): array
    {
        $respuesta = Http::timeout(60)
            ->asJson()
            ->post(config('services.google_drive.script_url'), ['clave' => config('services.google_drive.clave')]);

        $datos = $respuesta->json();

        if (! $respuesta->successful() || ! is_array($datos)) {
            throw new RuntimeException('El script de Google no respondió (HTTP ' . $respuesta->status() . '). Revisá que esté publicado como aplicación web con acceso "Cualquier usuario".');
        }
        if (! empty($datos['error'])) {
            throw new RuntimeException('El script de Google respondió: ' . $datos['error'] . '.');
        }
        if (empty($datos['token']) || empty($datos['carpeta'])) {
            throw new RuntimeException('El script de Google no devolvió el permiso de acceso.');
        }

        return $datos;
    }

    // Sube el archivo por partes (sirve para respaldos grandes) y devuelve su id y link.
    public function subir(string $ruta, string $nombre): array
    {
        $acceso = $this->acceso();
        $total  = filesize($ruta);

        $inicio = Http::withToken($acceso['token'])
            ->timeout(60)
            ->withHeaders([
                'X-Upload-Content-Type'   => 'application/zip',
                'X-Upload-Content-Length' => (string) $total,
            ])
            ->post(self::SUBIDA . '?uploadType=resumable&supportsAllDrives=true&fields=id,webViewLink', [
                'name'    => $nombre,
                'parents' => [$acceso['carpeta']],
            ]);

        $destino = $inicio->header('Location');
        if (! $inicio->successful() || ! $destino) {
            throw new RuntimeException('Drive no aceptó la subida: ' . $this->mensaje($inicio));
        }

        $archivo = fopen($ruta, 'rb');
        $enviado = 0;

        try {
            while (true) {
                $parte = fread($archivo, self::TAMANIO_PARTE) ?: '';
                $fin   = $enviado + strlen($parte) - 1;

                $respuesta = Http::withToken($acceso['token'])
                    ->timeout(600)
                    ->withHeaders(['Content-Range' => $total === 0 ? 'bytes */0' : "bytes {$enviado}-{$fin}/{$total}"])
                    ->withBody($parte, 'application/zip')
                    ->put($destino);

                $enviado += strlen($parte);

                // 308 = Drive recibió la parte y espera la siguiente.
                if ($respuesta->status() === 308) {
                    continue;
                }
                if ($respuesta->successful()) {
                    return [
                        'id'   => $respuesta->json('id'),
                        'link' => $respuesta->json('webViewLink') ?: 'https://drive.google.com/file/d/' . $respuesta->json('id') . '/view',
                    ];
                }

                throw new RuntimeException('Drive cortó la subida: ' . $this->mensaje($respuesta));
            }
        } finally {
            fclose($archivo);
        }
    }

    // Manda a la papelera de Drive (no borra del todo: se puede recuperar 30 días) los
    // respaldos de esa carpeta con más de $dias días. Solo toca archivos con el nombre que
    // genera el sistema, nunca otros que haya en la carpeta.
    public function limpiar(int $dias): int
    {
        $acceso = $this->acceso();
        $corte  = now()->subDays($dias);

        $lista = Http::withToken($acceso['token'])->timeout(60)->get(self::API . '/files', [
            'q'                         => "'{$acceso['carpeta']}' in parents and trashed = false and name contains 'respaldo-'",
            'fields'                    => 'files(id,name,createdTime)',
            'pageSize'                  => 1000,
            'supportsAllDrives'         => 'true',
            'includeItemsFromAllDrives' => 'true',
        ]);

        if (! $lista->successful()) {
            throw new RuntimeException('No se pudo listar la carpeta de Drive: ' . $this->mensaje($lista));
        }

        $enviados = 0;
        foreach ($lista->json('files', []) as $f) {
            if (! preg_match('/^respaldo-\d{4}-\d{2}-\d{2}_\d{6}\.zip$/', $f['name'])) {
                continue;
            }
            if (Carbon::parse($f['createdTime'])->gte($corte)) {
                continue;
            }

            Http::withToken($acceso['token'])->timeout(60)
                ->patch(self::API . '/files/' . $f['id'] . '?supportsAllDrives=true', ['trashed' => true])
                ->throw();
            $enviados++;
        }

        return $enviados;
    }

    // Registro de qué respaldo se subió (o por qué falló), para mostrarlo en la página.
    public static function registro(): array
    {
        $ruta = self::rutaRegistro();

        return is_file($ruta) ? (json_decode(file_get_contents($ruta), true) ?: []) : [];
    }

    public static function registrar(string $nombre, array $datos): void
    {
        $registro = self::registro();
        $registro[$nombre] = $datos + ['fecha' => now()->toDateTimeString()];

        // Solo los respaldos que siguen existiendo en el servidor.
        $registro = array_filter($registro, fn ($_, $n) => is_file(dirname(self::rutaRegistro()) . '/' . $n), ARRAY_FILTER_USE_BOTH);

        file_put_contents(self::rutaRegistro(), json_encode($registro, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private static function rutaRegistro(): string
    {
        return storage_path('respaldos/.google-drive.json');
    }

    private function mensaje($respuesta): string
    {
        $mensaje = $respuesta->json('error.message') ?? ('HTTP ' . $respuesta->status());

        if (str_contains($mensaje, 'insufficient authentication scopes')) {
            $mensaje .= ' — El script tiene permiso de solo lectura: agregá el permiso de Drive en appsscript.json, '
                . 'ejecutá "autorizar" en el editor y publicá una versión nueva (ver la página Respaldos).';
        }

        return $mensaje;
    }
}
