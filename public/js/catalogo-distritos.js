/**
 * Catálogo CAT-12 - Distritos por Municipio
 * Ministerio de Hacienda - El Salvador
 *
 * Estructura: distritos organizados por departamento y municipio
 * Clave: 'departamento-municipio' (ejemplo: '01-13' = Ahuachapán Norte)
 */

const catalogoDistritos = {
    // ==========================================
    // AHUACHAPÁN (01)
    // ==========================================
    '01-13': [ // AHUACHAPAN NORTE
        { codigo: '01', nombre: 'Atiquizaya' },
        { codigo: '02', nombre: 'El Refugio' },
        { codigo: '03', nombre: 'San Lorenzo' }
    ],
    '01-14': [ // AHUACHAPAN CENTRO
        { codigo: '01', nombre: 'Ahuachapán' },
        { codigo: '02', nombre: 'Apaneca' },
        { codigo: '03', nombre: 'Concepción de Ataco' }
    ],
    '01-15': [ // AHUACHAPAN SUR
        { codigo: '01', nombre: 'Guaymango' },
        { codigo: '02', nombre: 'Jujutla' },
        { codigo: '03', nombre: 'San Francisco Menéndez' },
        { codigo: '04', nombre: 'Tacuba' }
    ],

    // ==========================================
    // SANTA ANA (02)
    // ==========================================
    '02-14': [ // SANTA ANA NORTE
        { codigo: '01', nombre: 'Masahuat' },
        { codigo: '02', nombre: 'Metapán' },
        { codigo: '03', nombre: 'Santa Rosa Guachipilín' },
        { codigo: '04', nombre: 'Texistepeque' }
    ],
    '02-15': [ // SANTA ANA CENTRO
        { codigo: '01', nombre: 'Santa Ana' }
    ],
    '02-16': [ // SANTA ANA ESTE
        { codigo: '01', nombre: 'Coatepeque' },
        { codigo: '02', nombre: 'El Congo' }
    ],
    '02-17': [ // SANTA ANA OESTE
        { codigo: '01', nombre: 'Candelaria de La Frontera' },
        { codigo: '02', nombre: 'Chalchuapa' },
        { codigo: '03', nombre: 'El Porvenir' },
        { codigo: '04', nombre: 'San Antonio Pajonal' },
        { codigo: '05', nombre: 'San Sebastián Salitrillo' },
        { codigo: '06', nombre: 'Santiago de La Frontera' }
    ],

    // ==========================================
    // SONSONATE (03)
    // ==========================================
    '03-18': [ // SONSONATE NORTE
        { codigo: '01', nombre: 'Juayúa' },
        { codigo: '02', nombre: 'Nahuizalco' },
        { codigo: '03', nombre: 'Salcoatitán' },
        { codigo: '04', nombre: 'Santa Catarina Masahuat' }
    ],
    '03-19': [ // SONSONATE CENTRO
        { codigo: '01', nombre: 'Sonsonate' },
        { codigo: '02', nombre: 'Sonzacate' },
        { codigo: '03', nombre: 'Nahulingo' },
        { codigo: '04', nombre: 'San Antonio del Monte' }
    ],
    '03-20': [ // SONSONATE ESTE
        { codigo: '01', nombre: 'Izalco' },
        { codigo: '02', nombre: 'Armenia' },
        { codigo: '03', nombre: 'Caluco' },
        { codigo: '04', nombre: 'Santa Isabel Ishuatán' }
    ],
    '03-34': [ // SONSONATE OESTE
        { codigo: '01', nombre: 'Acajutla' },
        { codigo: '02', nombre: 'San Julián' }
    ],

    // ==========================================
    // CHALATENANGO (04)
    // ==========================================
    '04-35': [ // CHALATENANGO NORTE
        { codigo: '01', nombre: 'Chalatenango' },
        { codigo: '02', nombre: 'Agua Caliente' },
        { codigo: '03', nombre: 'Arcatao' },
        { codigo: '04', nombre: 'Azacualpa' },
        { codigo: '05', nombre: 'Citalá' },
        { codigo: '06', nombre: 'Comalapa' },
        { codigo: '07', nombre: 'Concepción Quezaltepeque' },
        { codigo: '08', nombre: 'Dulce Nombre de María' },
        { codigo: '09', nombre: 'El Carrizal' },
        { codigo: '10', nombre: 'El Paraíso' },
        { codigo: '11', nombre: 'La Laguna' },
        { codigo: '12', nombre: 'La Palma' },
        { codigo: '13', nombre: 'La Reina' },
        { codigo: '14', nombre: 'Las Vueltas' },
        { codigo: '15', nombre: 'Nombre de Jesús' },
        { codigo: '16', nombre: 'Nueva Concepción' },
        { codigo: '17', nombre: 'Ojos de Agua' },
        { codigo: '18', nombre: 'Potonico' },
        { codigo: '19', nombre: 'San Antonio de La Cruz' },
        { codigo: '20', nombre: 'San Antonio Los Ranchos' },
        { codigo: '21', nombre: 'San Fernando' },
        { codigo: '22', nombre: 'San Francisco Lempa' },
        { codigo: '23', nombre: 'San Francisco Morazán' },
        { codigo: '24', nombre: 'San Ignacio' },
        { codigo: '25', nombre: 'San Isidro Labrador' },
        { codigo: '26', nombre: 'San Luis del Carmen' },
        { codigo: '27', nombre: 'San Miguel de Mercedes' },
        { codigo: '28', nombre: 'San Rafael' },
        { codigo: '29', nombre: 'Santa Rita' },
        { codigo: '30', nombre: 'Tejutla' }
    ],
    '04-36': [ // CHALATENANGO CENTRO
        { codigo: '01', nombre: 'Nueva Trinidad' },
        { codigo: '02', nombre: 'San José Cancasque' }
    ],

    // ==========================================
    // LA LIBERTAD (05)
    // ==========================================
    '05-23': [ // LA LIBERTAD NORTE
        { codigo: '01', nombre: 'Quezaltepeque' },
        { codigo: '02', nombre: 'San Matías' },
        { codigo: '03', nombre: 'San Pablo Tacachico' }
    ],
    '05-24': [ // LA LIBERTAD CENTRO
        { codigo: '01', nombre: 'San Juan Opico' },
        { codigo: '02', nombre: 'Ciudad Arce' }
    ],
    '05-25': [ // LA LIBERTAD OESTE
        { codigo: '01', nombre: 'Colón' },
        { codigo: '02', nombre: 'Jayaque' },
        { codigo: '03', nombre: 'Sacacoyo' },
        { codigo: '04', nombre: 'Tepecoyo' },
        { codigo: '05', nombre: 'Talnique' }
    ],
    '05-26': [ // LA LIBERTAD ESTE
        { codigo: '01', nombre: 'Antiguo Cuscatlán' },
        { codigo: '02', nombre: 'Huizúcar' },
        { codigo: '03', nombre: 'Nuevo Cuscatlán' },
        { codigo: '04', nombre: 'San José Villanueva' },
        { codigo: '05', nombre: 'Zaragoza' }
    ],
    '05-27': [ // LA LIBERTAD COSTA
        { codigo: '01', nombre: 'Chiltiupán' },
        { codigo: '02', nombre: 'Jicalapa' },
        { codigo: '03', nombre: 'La Libertad' },
        { codigo: '04', nombre: 'Tamanique' },
        { codigo: '05', nombre: 'Teotepeque' }
    ],
    '05-28': [ // LA LIBERTAD SUR
        { codigo: '01', nombre: 'Comasagua' },
        { codigo: '02', nombre: 'Santa Tecla' }
    ],

    // ==========================================
    // SAN SALVADOR (06)
    // ==========================================
    '06-29': [ // SAN SALVADOR NORTE
        { codigo: '01', nombre: 'Aguilares' },
        { codigo: '02', nombre: 'El Paisnal' },
        { codigo: '03', nombre: 'Guazapa' }
    ],
    '06-30': [ // SAN SALVADOR OESTE
        { codigo: '01', nombre: 'Apopa' },
        { codigo: '02', nombre: 'Nejapa' }
    ],
    '06-31': [ // SAN SALVADOR ESTE
        { codigo: '01', nombre: 'Ilopango' },
        { codigo: '02', nombre: 'San Martín' },
        { codigo: '03', nombre: 'Soyapango' },
        { codigo: '04', nombre: 'Tonacatepeque' }
    ],
    '06-32': [ // SAN SALVADOR CENTRO
        { codigo: '01', nombre: 'Ayutuxtepeque' },
        { codigo: '02', nombre: 'Cuscatancingo' },
        { codigo: '03', nombre: 'Ciudad Delgado' },
        { codigo: '04', nombre: 'Mejicanos' },
        { codigo: '05', nombre: 'San Salvador' }
    ],
    '06-33': [ // SAN SALVADOR SUR
        { codigo: '01', nombre: 'Panchimalco' },
        { codigo: '02', nombre: 'Rosario de Mora' },
        { codigo: '03', nombre: 'San Marcos' },
        { codigo: '04', nombre: 'Santo Tomás' },
        { codigo: '05', nombre: 'Santiago Texacuangos' }
    ],

    // ==========================================
    // CUSCATLÁN (07)
    // ==========================================
    '07-34': [ // CUSCATLAN NORTE
        { codigo: '01', nombre: 'Cojutepeque' },
        { codigo: '02', nombre: 'Candelaria' },
        { codigo: '03', nombre: 'El Carmen' },
        { codigo: '04', nombre: 'El Rosario' },
        { codigo: '05', nombre: 'Monte San Juan' },
        { codigo: '06', nombre: 'Oratorio de Concepción' },
        { codigo: '07', nombre: 'San Bartolomé Perulapía' },
        { codigo: '08', nombre: 'San Cristóbal' },
        { codigo: '09', nombre: 'San José Guayabal' },
        { codigo: '10', nombre: 'San Pedro Perulapán' },
        { codigo: '11', nombre: 'San Rafael Cedros' },
        { codigo: '12', nombre: 'San Ramón' },
        { codigo: '13', nombre: 'Santa Cruz Analquito' },
        { codigo: '14', nombre: 'Santa Cruz Michapa' },
        { codigo: '15', nombre: 'Suchitoto' },
        { codigo: '16', nombre: 'Tenancingo' }
    ],
    '07-35': [ // CUSCATLAN SUR
        { codigo: '01', nombre: 'San Vicente' }
    ],

    // ==========================================
    // LA PAZ (08)
    // ==========================================
    '08-36': [ // LA PAZ OESTE
        { codigo: '01', nombre: 'Cuyultitán' },
        { codigo: '02', nombre: 'Olocuilta' },
        { codigo: '03', nombre: 'San Antonio Masahuat' },
        { codigo: '04', nombre: 'San Emigdio' },
        { codigo: '05', nombre: 'San Francisco Chinameca' },
        { codigo: '06', nombre: 'San Juan Nonualco' },
        { codigo: '07', nombre: 'San Juan Talpa' },
        { codigo: '08', nombre: 'San Juan Tepezontes' },
        { codigo: '09', nombre: 'San Luis Talpa' },
        { codigo: '10', nombre: 'San Miguel Tepezontes' },
        { codigo: '11', nombre: 'San Pedro Masahuat' },
        { codigo: '12', nombre: 'San Rafael Obrajuelo' },
        { codigo: '13', nombre: 'Santa María Ostuma' },
        { codigo: '14', nombre: 'Santiago Nonualco' },
        { codigo: '15', nombre: 'Tapalhuaca' }
    ],
    '08-37': [ // LA PAZ CENTRO
        { codigo: '01', nombre: 'El Rosario' },
        { codigo: '02', nombre: 'Jerusalén' },
        { codigo: '03', nombre: 'Mercedes La Ceiba' },
        { codigo: '04', nombre: 'Paraíso de Osorio' },
        { codigo: '05', nombre: 'San Antonio Masahuat' },
        { codigo: '06', nombre: 'San Emigdio' },
        { codigo: '07', nombre: 'San Francisco Chinameca' },
        { codigo: '08', nombre: 'San Pedro Nonualco' },
        { codigo: '09', nombre: 'Santa María Ostuma' },
        { codigo: '10', nombre: 'Zacatecoluca' }
    ],
    '08-38': [ // LA PAZ ESTE
        { codigo: '01', nombre: 'San Luis La Herradura' },
        { codigo: '02', nombre: 'San Pedro Masahuat' }
    ],

    // ==========================================
    // CABAÑAS (09)
    // ==========================================
    '09-39': [ // CABAÑAS OESTE
        { codigo: '01', nombre: 'Cinquera' },
        { codigo: '02', nombre: 'Dolores' },
        { codigo: '03', nombre: 'Guacotecti' },
        { codigo: '04', nombre: 'Ilobasco' },
        { codigo: '05', nombre: 'Jutiapa' },
        { codigo: '06', nombre: 'San Isidro' },
        { codigo: '07', nombre: 'Tejutepeque' },
        { codigo: '08', nombre: 'Victoria' }
    ],
    '09-40': [ // CABAÑAS ESTE
        { codigo: '01', nombre: 'Sensuntepeque' }
    ],

    // ==========================================
    // SAN VICENTE (10)
    // ==========================================
    '10-14': [ // SAN VICENTE NORTE
        { codigo: '01', nombre: 'Apastepeque' },
        { codigo: '02', nombre: 'Guadalupe' },
        { codigo: '03', nombre: 'San Cayetano Istepeque' },
        { codigo: '04', nombre: 'San Esteban Catarina' },
        { codigo: '05', nombre: 'San Ildefonso' },
        { codigo: '06', nombre: 'San Lorenzo' },
        { codigo: '07', nombre: 'San Sebastián' },
        { codigo: '08', nombre: 'Santa Clara' },
        { codigo: '09', nombre: 'Santo Domingo' },
        { codigo: '10', nombre: 'Tecoluca' },
        { codigo: '11', nombre: 'Tepetitán' },
        { codigo: '12', nombre: 'Verapaz' }
    ],
    '10-15': [ // SAN VICENTE SUR
        { codigo: '01', nombre: 'San Vicente' }
    ],

    // ==========================================
    // USULUTÁN (11)
    // ==========================================
    '11-24': [ // USULUTAN NORTE
        { codigo: '01', nombre: 'Alegría' },
        { codigo: '02', nombre: 'Berlín' },
        { codigo: '03', nombre: 'California' },
        { codigo: '04', nombre: 'Concepción Batres' },
        { codigo: '05', nombre: 'El Triunfo' },
        { codigo: '06', nombre: 'Ereguayquín' },
        { codigo: '07', nombre: 'Estanzuelas' },
        { codigo: '08', nombre: 'Jiquilisco' },
        { codigo: '09', nombre: 'Jucuapa' },
        { codigo: '10', nombre: 'Jucuarán' },
        { codigo: '11', nombre: 'Mercedes Umaña' },
        { codigo: '12', nombre: 'Nueva Granada' },
        { codigo: '13', nombre: 'Ozatlán' },
        { codigo: '14', nombre: 'Puerto El Triunfo' },
        { codigo: '15', nombre: 'San Agustín' },
        { codigo: '16', nombre: 'San Buenaventura' },
        { codigo: '17', nombre: 'San Dionisio' },
        { codigo: '18', nombre: 'San Francisco Javier' },
        { codigo: '19', nombre: 'Santa Elena' },
        { codigo: '20', nombre: 'Santa María' },
        { codigo: '21', nombre: 'Santiago de María' },
        { codigo: '22', nombre: 'Tecapán' }
    ],
    '11-25': [ // USULUTAN ESTE
        { codigo: '01', nombre: 'Usulután' }
    ],
    '11-26': [ // USULUTAN OESTE
        { codigo: '01', nombre: 'San Casimiro' }
    ],

    // ==========================================
    // SAN MIGUEL (12)
    // ==========================================
    '12-21': [ // SAN MIGUEL NORTE
        { codigo: '01', nombre: 'Carolina' },
        { codigo: '02', nombre: 'Chapeltique' },
        { codigo: '03', nombre: 'Chinameca' },
        { codigo: '04', nombre: 'Chirilagua' },
        { codigo: '05', nombre: 'Ciudad Barrios' },
        { codigo: '06', nombre: 'Comacarán' },
        { codigo: '07', nombre: 'El Tránsito' },
        { codigo: '08', nombre: 'Lolotique' },
        { codigo: '09', nombre: 'Moncagua' },
        { codigo: '10', nombre: 'Nueva Guadalupe' },
        { codigo: '11', nombre: 'Nuevo Edén de San Juan' },
        { codigo: '12', nombre: 'Quelepa' },
        { codigo: '13', nombre: 'San Antonio del Mosco' },
        { codigo: '14', nombre: 'San Gerardo' },
        { codigo: '15', nombre: 'San Jorge' },
        { codigo: '16', nombre: 'San Luis de la Reina' },
        { codigo: '17', nombre: 'San Rafael Oriente' },
        { codigo: '18', nombre: 'Sesori' },
        { codigo: '19', nombre: 'Uluazapa' }
    ],
    '12-22': [ // SAN MIGUEL CENTRO
        { codigo: '01', nombre: 'San Miguel' }
    ],
    '12-23': [ // SAN MIGUEL OESTE
        { codigo: '01', nombre: 'San Francisco Gotera' }
    ],

    // ==========================================
    // MORAZÁN (13)
    // ==========================================
    '13-27': [ // MORAZAN NORTE
        { codigo: '01', nombre: 'Arambala' },
        { codigo: '02', nombre: 'Cacaopera' },
        { codigo: '03', nombre: 'Chilanga' },
        { codigo: '04', nombre: 'Corinto' },
        { codigo: '05', nombre: 'Delicias de Concepción' },
        { codigo: '06', nombre: 'El Divisadero' },
        { codigo: '07', nombre: 'El Rosario' },
        { codigo: '08', nombre: 'Gualococti' },
        { codigo: '09', nombre: 'Guatajiagua' },
        { codigo: '10', nombre: 'Joateca' },
        { codigo: '11', nombre: 'Jocoaitique' },
        { codigo: '12', nombre: 'Jocoro' },
        { codigo: '13', nombre: 'Lolotiquillo' },
        { codigo: '14', nombre: 'Meanguera' },
        { codigo: '15', nombre: 'Osicala' },
        { codigo: '16', nombre: 'Perquín' },
        { codigo: '17', nombre: 'San Carlos' },
        { codigo: '18', nombre: 'San Fernando' },
        { codigo: '19', nombre: 'San Isidro' },
        { codigo: '20', nombre: 'San Simón' },
        { codigo: '21', nombre: 'Sensembra' },
        { codigo: '22', nombre: 'Sociedad' },
        { codigo: '23', nombre: 'Torola' },
        { codigo: '24', nombre: 'Yamabal' },
        { codigo: '25', nombre: 'Yoloaiquín' }
    ],
    '13-28': [ // MORAZAN SUR
        { codigo: '01', nombre: 'San Francisco Gotera' },
        { codigo: '02', nombre: 'Ciudad Segundo Montes' }
    ],

    // ==========================================
    // LA UNIÓN (14)
    // ==========================================
    '14-19': [ // LA UNION NORTE
        { codigo: '01', nombre: 'Anamorós' },
        { codigo: '02', nombre: 'Bolívar' },
        { codigo: '03', nombre: 'Concepción de Oriente' },
        { codigo: '04', nombre: 'Conchagua' },
        { codigo: '05', nombre: 'El Carmen' },
        { codigo: '06', nombre: 'El Sauce' },
        { codigo: '07', nombre: 'Intipucá' },
        { codigo: '08', nombre: 'La Unión' },
        { codigo: '09', nombre: 'Lislique' },
        { codigo: '10', nombre: 'Meanguera del Golfo' },
        { codigo: '11', nombre: 'Nueva Esparta' },
        { codigo: '12', nombre: 'Pasaquina' },
        { codigo: '13', nombre: 'Polorós' },
        { codigo: '14', nombre: 'San Alejo' },
        { codigo: '15', nombre: 'San José' },
        { codigo: '16', nombre: 'Santa Rosa de Lima' },
        { codigo: '17', nombre: 'Yayantique' },
        { codigo: '18', nombre: 'Yucuaiquín' }
    ],
    '14-20': [ // LA UNION SUR
        { codigo: '01', nombre: 'San Miguel' }
    ],

    // ==========================================
    // EXTRANJEROS (00)
    // ==========================================
    '00-00': [ // Otro (Para extranjeros)
        { codigo: '01', nombre: 'No aplica' }
    ]
};
