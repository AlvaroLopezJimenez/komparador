<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Scraping\PeticionApiHTMLController;

/**
 * PLANTILLA BASE PARA CONTROLADORES DE TIENDAS
 * 
 * INSTRUCCIONES PARA UNA IA:
 * 
 * 1. Copia este archivo y renómbralo como [NombreTienda]Controller.php
 * 2. Cambia el nombre de la clase a [NombreTienda]Controller
 * 3. Implementa el método obtenerPrecio() con la lógica específica de la tienda
 * 
 * ESTRUCTURA DEL MÉTODO obtenerPrecio():
 * - Recibe $url y $variante como parámetros
 * - Usa $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null) para obtener el HTML
 * - Extrae el precio del HTML usando selectores CSS, regex, o DOM
 * - Devuelve un array con 'success' => true/false y 'precio' => valor o 'error' => mensaje
 * 
 * EJEMPLO DE IMPLEMENTACIÓN:
 * 
 * public function obtenerPrecio($url, $variante = null, $tienda = null)
 * {
 *     // Obtener HTML de la página
 *     $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);
 *     
 *     if (!$resultado['success']) {
 *         return response()->json([
 *             'success' => false,
 *             'error' => $resultado['error']
 *         ]);
 *     }
 *     
 *     $html = $resultado['html'];
 *     
 *     // Extraer precio usando selectores CSS específicos de la tienda
 *     // Ejemplo: buscar elemento con clase 'price' o 'precio'
 *     preg_match('/class="price">([0-9,]+)/', $html, $matches);
 *     
 *     if (empty($matches[1])) {
 *         return response()->json([
 *             'success' => false,
 *             'error' => 'No se pudo encontrar el precio en la página'
 *         ]);
 *     }
 *     
 *     $precio = str_replace(',', '.', $matches[1]);
 *     
 *     return response()->json([
 *         'success' => true,
 *         'precio' => (float) $precio
 *     ]);
 * }
 * 
 * NOTAS IMPORTANTES:
 * - Cada tienda puede tener diferentes estructuras HTML
 * - Algunas tiendas pueden usar JavaScript para cargar precios
 * - Considera usar diferentes selectores según la variante del producto
 * - Maneja errores de forma robusta
 * - Normaliza el formato del precio (cambiar comas por puntos)
 * - Devuelve siempre un JSON con estructura consistente
 */
abstract class PlantillaTiendaController extends Controller
{
    protected $apiHTML;

    public function __construct()
    {
        $this->apiHTML = new PeticionApiHTMLController();
    }

    /**
     * Método abstracto que debe implementar cada tienda
     */
    abstract public function obtenerPrecio($url, $variante = null, $tienda = null);
}


