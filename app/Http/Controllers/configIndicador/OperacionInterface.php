<?php

namespace App\Http\Controllers\configIndicador;

interface OperacionInterface {
    public function ejecutar(array $configuracion, array $filter);
}
