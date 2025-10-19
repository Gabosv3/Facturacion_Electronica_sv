<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Sistema DTE El Salvador
    |--------------------------------------------------------------------------
    */

    'nit' => env('DTE_NIT', '06142302881449'),
    'password_pri' => env('DTE_PASSWORD_PRI', ''),
    'ambiente' => env('DTE_AMBIENTE', '00'), // 00 = Pruebas, 01 = Producción
    'establecimiento' => env('DTE_ESTABLECIMIENTO', 'S001'),
    'punto_venta' => env('DTE_PUNTO_VENTA', 'P001'),

    /*
    |--------------------------------------------------------------------------
    | Datos del Emisor
    |--------------------------------------------------------------------------
    */
    'emisor' => [
        'nit' => env('DTE_EMISOR_NIT', '06142302881449'),
        'nrc' => env('DTE_EMISOR_NRC', '3391583'),
        'nombre' => env('DTE_EMISOR_NOMBRE', 'Taller González'),
        'nombre_comercial' => env('DTE_EMISOR_NOMBRE_COMERCIAL', 'Taller González'),
        'cod_actividad' => env('DTE_EMISOR_COD_ACTIVIDAD', '33120'),
        'desc_actividad' => env('DTE_EMISOR_DESC_ACTIVIDAD', 'VENTA AL POR MAYOR DE PRODUCTOS ALIMENTICIOS'),
        'cod_estable' => env('DTE_EMISOR_COD_ESTABLE', null),
        'cod_punto_venta' => env('DTE_EMISOR_COD_PUNTO_VENTA', null),
        'direccion' => [
            'departamento' => env('DTE_EMISOR_DEPARTAMENTO', '06'),
            'municipio' => env('DTE_EMISOR_MUNICIPIO', '23'),
            'distrito' => env('DTE_EMISOR_DISTRITO', '03'),
            'complemento' => env('DTE_EMISOR_COMPLEMENTO', 'Colonia Centro'),
        ],
        'telefono' => env('DTE_EMISOR_TELEFONO', '64404895'),
        'correo' => env('DTE_EMISOR_CORREO', 'jonagozalez23@gmail.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API del Ministerio de Hacienda
    |--------------------------------------------------------------------------
    */
    'api_url' => env('DTE_API_URL', 'http://127.0.0.1:8113'),
    
    /*
    |--------------------------------------------------------------------------
    | API Pública del Ministerio de Hacienda
    |--------------------------------------------------------------------------
    */
    'mh_auth_url' => env('MH_AUTH_URL', 'https://apitest.dtes.mh.gob.sv/seguridad/auth'),
    'mh_recepcion_url' => env('MH_RECEPCION_URL', 'https://apitest.dtes.mh.gob.sv/fesv/recepciondte'),
    'mh_user' => env('MH_USER', '06142302881449'),
    'mh_password' => env('MH_PASSWORD', ''),
    'user_agent' => env('DTE_USER_AGENT', 'SistemaFacturacion/1.0'),
];