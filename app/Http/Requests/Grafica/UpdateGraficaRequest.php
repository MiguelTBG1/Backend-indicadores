<?php

namespace App\Http\Requests\Grafica;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGraficaRequest extends FormRequest
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
            'titulo' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'chartOptions' => 'nullable|array',
            'rangos' => 'nullable|array',
            'rangos.*.inicio' => 'required_with:rangos|string',
            'rangos.*.fin' => 'required_with:rangos|string',
            'rangos.*.label' => 'required_with:rangos|string',
            'series' => 'nullable|array',
            'series.*.name' => 'required_with:series|string',
            'series.*.configuracion' => 'required_with:series|array',
        ];
    }
}
