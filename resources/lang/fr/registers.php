<?php

return [
    'list' => [
        'title' => 'Caisses',
        'empty' => 'Aucune caisse n\'a encore été créée sur vos appareils.'
    ],
    'view' => [
        'title' => 'Caisse #:number',
        'meta' => [
            'general' => 'Informations générales',
            'opening' => 'Information sur l\'ouverture',
            'closing' => 'Information sur la fermeture',
        ],
        'transactions' => [
            'title' => 'Liste des transactions',
            'empty' => 'Aucune transaction',
        ],
        'cashMovements' => [
            'title' => 'Autres mouvements d\'argent',
            'empty' => 'Aucune mouvement d\'argent',
        ],
    ],
    'fields' => [
        'state' => 'État',
        'number' => 'Numéro de caisse',
        'numberShort' => 'No.',
        'openedAt' => 'Ouverte le',
        'closedAt' => 'Fermée le',
        'employee' => 'Employé(e)',
        'openingCash' => 'Argent déclarée à l\'ouverture',
        'closingCash' => 'Argent déclarée à la fermeture',
        'POSTRef' => 'Numéro de lot',
        'POSTAmount' => 'Montant du lot',
        'paymentsTotal' => 'Paiements',
        'refundsTotal' => 'Remboursements',
        'cashMovementsTotal' => 'Autres mouvements d\'argent',
        'netTotal' => 'Total net',
        'declaredTotal' => 'Total déclaré',
        'registerError' => 'Erreur de caisse',
        'expectedClosingCash' => 'Argent attendu à la fermeture',
        'cashError' => 'Erreur sur l\'argent de la caisse',
    ],
    'states' => [
        'opened' => 'Ouverte',
        'closed' => 'Fermée',
    ],
];
