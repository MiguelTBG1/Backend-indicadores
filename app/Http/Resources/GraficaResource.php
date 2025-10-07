<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GraficaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->_id ?? $this->id, 
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'series' => $this->series,
            'rangos' => $this->rangos,
            'chartOptions' => $this->chartOptions,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
