<?php

namespace App\Observers;

use App\Models\Cliente;
use App\Models\User;

// Cada vez que se registra un cliente nuevo, se le crea de una su cuenta de acceso con
// el rol "Cliente" (antes se creaba recién en su primer login, ver LoginRequest).
class ClienteObserver
{
    public function created(Cliente $cliente): void
    {
        User::paraCliente($cliente);
    }
}
