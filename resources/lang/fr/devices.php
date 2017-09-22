<?php

return [
    'list' => [
        'title' => 'Appareils',
        'empty' => 'Aucun appareil n\'est encore enregistré.',
        'cols' => [
            'name' => 'Nom',
        ],
        'actions' => [
            'revoke' => 'Dé-autoriser',
            'get_passcode' => 'Obtenir code d\'autorisation',
        ],
    ],
    'add' => [
        'title' => 'Enregistrer un nouvel appareil',
        'fields' => [
            'name' => 'Nom de l\'appareil',
            'initialRegisterNumber' => 'Numéro de caisse initial',
        ],
    ],
    'code' => [
        'title' => 'Code d\'autorisation de l\'appareil',
        'instructions' => 'Dans l\'écran de votre appareil, saisissez le code suivant. '.
            'Ce code est valide pour quelques minutes seulement.',
    ],
    'actions' => [
        'add_device' => 'Enregistrer un nouvel appareil',
    ],
];
