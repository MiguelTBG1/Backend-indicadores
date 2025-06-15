<?php

namespace App\Http\Controllers\configIndicador;


class ContarOperacion implements OperacionInterface {
    private $collection;

    public function __construct($collection) {
        $this->collection = $collection;
    }

    public function ejecutar(array $configuracion, array $filter): int {
        return $this->collection->countDocuments($filter);
    }
}
