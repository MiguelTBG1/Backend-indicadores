<?php

namespace App\Http\Controllers\configIndicador;

use App\Http\Controllers\configIndicador\OperacionInterface;
use Exception;

class SumarOperacion implements OperacionInterface {
    private $collection;

    public function __construct($collection) {
        $this->collection = $collection;
    }

    public function ejecutar(array $configuracion, array $filter): float|int {
        if (!isset($configuracion['campo'])) {
            throw new Exception('Campo no especificado para la operación de suma');
        }

        $pipeline = !empty($filter) ? [['$match' => $filter]] : [];

        $pipeline[] = [
            '$group' => [
                '_id' => null,
                'total' => ['$sum' => '$' . $configuracion['campo']]
            ]
        ];

        $cursor = $this->collection->aggregate($pipeline);
        $resultados = iterator_to_array($cursor);

        return $resultados[0]['total'] ?? 0;
    }
}
