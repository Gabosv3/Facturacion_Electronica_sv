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
        'cod_estable' => env('DTE_EMISOR_COD_ESTABLE', '0000'),
        'cod_punto_venta' => env('DTE_EMISOR_COD_PUNTO_VENTA', '0000'),
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
    | Email de Revisión Interna
    |--------------------------------------------------------------------------
    */
    'email_revision' => env('DTE_EMAIL_REVISION', 'revisor@empresa.com'),
    
    /*
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
    'mh_consulta_url' => env('MH_CONSULTA_URL', 'https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadte'),
    'mh_user' => env('MH_USER', '06142302881449'),
    'mh_password' => env('MH_PASSWORD', ''),
    'user_agent' => env('DTE_USER_AGENT', 'SistemaFacturacion/1.0'),

    /*
    |--------------------------------------------------------------------------
    | Política de Reintentos según MH
    |--------------------------------------------------------------------------
    */
    'timeout_envio' => env('DTE_TIMEOUT_ENVIO', 8), // 8 segundos según política MH
    'max_reintentos' => env('DTE_MAX_REINTENTOS', 2), // Máximo 2 reintentos

    /*
    |--------------------------------------------------------------------------
    | URLs de Contingencia y Lotes
    |--------------------------------------------------------------------------
    */
    'mh_contingencia_url' => env('MH_CONTINGENCIA_URL', 'https://apitest.dtes.mh.gob.sv/fesv/contingencia'),
    'mh_recepcion_lote_url' => env('MH_RECEPCION_LOTE_URL', 'https://apitest.dtes.mh.gob.sv/fesv/recepcionlote'),
    'mh_consulta_lote_url' => env('MH_CONSULTA_LOTE_URL', 'https://apitest.dtes.mh.gob.sv/fesv/consultarlote'),

    /*
    |--------------------------------------------------------------------------
    | URLs de Consulta DTE
    |--------------------------------------------------------------------------
    */
    'mh_consulta_dte_url' => env('MH_CONSULTA_DTE_URL', 'https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadte/'),
    'mh_consulta_dte_lote_url' => env('MH_CONSULTA_DTE_LOTE_URL', 'https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadtelote'),

    /*
    |--------------------------------------------------------------------------
    | URL de Invalidación
    |--------------------------------------------------------------------------
    */
    'mh_invalidacion_url' => env('MH_INVALIDACION_URL', 'https://apitest.dtes.mh.gob.sv/fesv/anulardte'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Lotes
    |--------------------------------------------------------------------------
    */
    'max_dte_por_lote' => 100, // Máximo 100 DTEs por lote según MH
    'max_lotes_pruebas' => 300, // Máximo 300 lotes en ambiente de pruebas
    'max_lotes_produccion' => 400, // Máximo 400 lotes en ambiente productivo
];