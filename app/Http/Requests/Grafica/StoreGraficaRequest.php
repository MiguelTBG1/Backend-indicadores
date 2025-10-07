<?php

namespace App\Http\Requests\Grafica;

use Illuminate\Foundation\Http\FormRequest;

class StoreGraficaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'chartOptions' => 'nullable|array',
            'rangos' => 'required|array|min:1',
            'rangos.*.inicio' => 'required|string',
            'rangos.*.fin' => 'required|string',
            'rangos.*.label' => 'required|string',
            'series' => 'required|array|min:1',
            'series.*.name' => 'required|string',
            'series.*.configuracion' => 'required|array',
        ];
    }
}
