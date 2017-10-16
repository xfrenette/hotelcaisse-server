<?php

return [
    'list' => [
        'title' => 'Caisses',
        'empty' => 'Aucune caisse n\'a encore été créée sur vos appareils.',
        'naDefinition' => 'Sera disponible seulement quand la caisse sera fermée',
        'naMessage' => ':na : information non-disponible tant que la caisse est ouverte.',
        'columns' => [
            'opening' => [
                'title' => 'Ouverture',
                'number' => 'No',
                'date' => 'Date/heure',
                'employee' => 'Employé(e)',
                'openingCash' => 'Fond<br>déclaré',
            ],
            'transactions' => [
                'title' => 'Transactions',
                'payments' => 'Paiements',
                'refunds' => 'Rembours.',
                'total' => 'Total',
            ],
            'cash' => [
                'title' => 'Argent net dans la caisse',
                'transactionsTotal' => 'Source:<br>transactions',
                'cashMovements' => 'Source:<br>autre',
                'floatError' => 'Erreur de<br>fond ouv.',
                'expectedTtotal' => 'Total<br>attendu',
                'declaredTotal' => 'Total<br>déclaré',
            ],
            'POST' => [
                'title' => 'Lot du TPV',
                'ref' => 'No. lot',
                'expectedTotal' => 'Total<br>attendu',
                'declaredTotal' => 'Total<br>déclaré',
            ],
            'closing' => [
                'title' => 'Fermeture',
                'date' => 'Date/heure',
            ],
        ],
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
        'transactionsTotal' => 'Total transactions',
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
