@extends('layouts.help')

@section('help:content')
    <p class="warning">Il n'existe pas de mode "formation / training" dans l'application
        permettant de faire des "tests" qui ne seront pas enregistrés. Également, il n'est
        généralement pas possible d'annuler une action une fois qu'elle a été enregistrée.</p>
    <h1>Écran d'accueil</h1>
    <p>L'écran d'accueil affiche des boutons différents selon que la caisse est ouverte ou non.</p>
    <h2>Quand la caisse est fermée</h2>
    <p class="image">[img: accueil caisse fermée]</p>
    <p>Voici les actions possible :</p>
    <ul>
        <li>Ouvrir la caisse</li>
        <li>Rechercher une fiche</li>
    </ul>
    <h2>Quand la caisse est ouverte</h2>
    <p class="image">[img: accueil caisse ouverte]</p>
    <p>Voici les actions possible :</p>
    <ul>
        <li>Créer une nouvelle fiche ("check-in" d'un client)</li>
        <li>Rechercher une fiche</li>
        <li>Enregistrer une sortie/entrée d'argent</li>
        <li>Fermer la caisse</li>
    </ul>
    <h1>Ouvrir la caisse</h1>
    <p>Si la caisse a été fermée par l'aubergiste de la veille, vous devez l'ouvrir avant de
        pouvoir créer des nouvelles fiches.</p>
    <p>À l'écran d'accueil, appuyer sur "Ouvrir la caisse". Vous verrez l'écran suivant :</p>
    <p class="info">Si le bouton "Ouvrir la caisse" n'apparaît pas sur l'écran d'accueil(mais que
        vous voyez "Fermer la caisse"), la caisse est déjà ouverte.</p>
    <p class="image">[img: écran ouverture de caisse]</p>
    <ul>
        <li><strong>"Votre nom"</strong>: écrire le nom de l'aubergiste de la
            journée. Cette information est utiisée seulement pour avoir une idée de qui était
            l'aubergiste cette journée là; ce n'est pas
            grave si un autre employé cré une fiche dans la journée, si vous êtes plusieurs
            aubergistes dans
            la journée (vous pouvez écrire plusieurs noms dans ce champ) ou si la même caisse
            restera
            ouverte plusieurs jours (et donc plusieurs aubergistes).
        </li>
        <li><strong>"Argent dans la caisse"</strong>: inscrire le montant d'argent
            présentement dans la caisse. Normalement, l'aubergiste de la veille devrait y avoir
            laissé 100 $. <strong>Vous pouvez laisser le montant de 100 $</strong>, sans compter la
            caisse.
            <em>Optionnel:</em> si vous le souhaitez, vous pouvez compter l'argent de la caisse
            et y indiquer le montant réel. Bien compter au moins deux fois pour éviter les erreurs.
        </li>
    </ul>
    <p>Appuyer sur "Ouvrir la caisse". Vous serez redirigé à l'écran d'accueil, mais les boutons
        auront changés (car la caisse est maintenant ouverte).</p>


    <h1>Créer une fiche ("check-in" d'un client)</h1>
    <p class="warging">La caisse doit être ouverte pour pouvoir créer une nouvelle fiche.</p>
    <p class="info">On fait une seule fiche par "groupe" de client. Par exemple, faire une seule
        fiche dans les cas suivants: un couple dans la même chambre, deux personnes qui désirent
        prendre 2 chambres, un groupe scolaire qui prend 8 chambres). Ne pas faire une fiche par
        personne, faire une seule fiche. Nous inscrirons plus tard le nombre de personnes.</p>
    <p>Sur l'écran d'accueil, appuyer sur "Nouvelle fiche". L'écran suivant apparaîtra:</p>
    <p class="image">[img:écran nouvelle fiche]</p>
    <h4>1) Ajouter les produits</h4>
    <p class="info">Notez que les prix affichés inclus toutes les taxes. Certains sites de
        réservation (comme Booking.com) affichent au client les prix avant taxes, donc le client
        pourrait remarquer une différence de prix au moment de payer. Simplement lui
        indiquer que le prix afficher sur le site de réservation n'incluait pas les
        taxes (ces sites affichent un message avertissant que les taxes ne sont pas
        incluses).</p>
    <p>À droite, appuyer sur le ou les produits à ajouter. Les boutons bleus sont des produits,
        les boutons jaune-orange sont des catégories (appuyer dessus affiche d'autres
        produits). Le bouton vert est pour ajouter un produit spécial. Voir la section
        dans "Cas particuliers" pour ce bouton.</p>
    <p>Quand un produit est ajouté, il
        apparaît dans la partie gauche où l'on peut changer sa quantité (vous pouvez soit
        appuyer
        sur "+" et "-" pour changer sa quantité ou appuyer sur le chiffre pour
        faire
        apparaître un clavier numérique).</p>
    <p class="info">Pour supprimer un produit rajouté par erreur, faites le glisser vers la
        gauche et un bouton "Retirer" apparaîtra. Noter qu'une fois la fiche
        enregistrée, il n'est plus possible de supprimer un produit, il pourra
        seulement être remboursé. Voir la section des remboursements.</p>
    <p class="info">Il n'y a, ici, aucune différence entre "2 personnes en dortoir / 1
        nuit" et "1
        personne en dortoir / 2 nuits" (donc un total de 2 dortoirs dans les deux cas). Dans
        les deux cas, vous appuierez une fois sur "Dortoir" et vous lui mettrez une quantité
        de 2. Nous noterons plus tard les chambres et le nombre de personnes par chambre
        .</p>
    <p>Certains produits ont un prix différent selon que le client est membre HI ou
        pas. Dans ce cas, quand le produit est rajouté, une liste déroulante apparaît
        permettant de choisir si on souhaite le prix membre ou non-membre.</p>
    <p>Voici un exemple où deux dortoirs prix <em>non-membre</em>, un dortoir
        <em>membre</em> et une carte de membre ont été achetés.</p>
    <p class="image">[img:2 dortoirs non membre, 1 dortoir membre, carte de membre]</p>
    <h4>2) Saisir les informations du client</h4>
    <p>Si les informations du client n'ont pas encore été saisies, un bouton vert
        "Informations client" apparaît dans le bas de la fiche. Si les informations
        client ont déjà été saisies, appuyey sur "Modifier informations client" dans le
        haut de la fiche.</p>
    <p class="image">[img: boutons pour ouvrir le modal infos clients]</p>
    <p>Quand vous appuyez sur un de ces boutons, l'écran suivant apparaîtra.</p>
    <p class="image">[img: modal info clients]</p>
    <ul>
        <li><strong>Date d'arrivée et de départ</strong>: Appuyer dans ces champs pour
            modifier la date d'arrivée ou de départ du client. Il n'est pas possible de
            spécifier des dates non-continues (ex: le client vient ce lundi et ce jeudi).
            Dans ce cas, il faudra faire deux fiches.
        </li>
        <li>
            <strong>Liste des chambres et nombre de personnes</strong> Sélectionnez la
            chambre où sera le client et le nombre de personnes dans la chambre. Noter
            que ceci est pour information seulement, ça n'a aucun impact sur le prix. Si
            plus d'une chambre sera utilisée, appuyez sur "Ajouter une chambre".
        </li>
        <li><strong>Nom du client</strong> Ce champ est <strong>le seul obligatoire</strong>
            (bien qu'il est fortement recommandé de remplir les autres). Mettre le nom
            d'une seule personne. S'il s'agit d'un gros groupe vous pouvez indiquer un
            nom descriptif (ex: "Groupe d'impro louperivoise").
        </li>
        <li><strong>Courriel et téléphone</strong> Ces informations sont utilisées
            seulement si l'on a besoin de contacter le client pendant son séjour. Un
            seul des deux champs est nécessaire. Nous n'utilisons pas cette
            informations pour de la promotion.
        </li>
        <li><strong>Pays / province</strong> Si le client réside au Canada, sélectionnez
            sa province, sinon son pays. Les provinces et pays les plus utilisés sont
            affichés en premier, suivi des autres provinces et des autres pays.
        </li>
        <li><strong>Code postal</strong> Ce champ doit être rempli seulement si le
            client réside au Canada, sinon il peut rester vide.
        </li>
        <li><strong>Premier séjour dans cette auberge ?</strong> Pour statistiques.</li>
        <li><strong>Membre HI</strong> Si le client est membre HI, mettre ce champ à
            "Oui" et demandez à voir la carte. S'assurer qu'elle n'est pas expirée.
            Saisir le numéro de la carte dans le champ "Passeport ou # carte HI"
        </li>
        <li><strong>Dernière auberge visitée</strong> Pour statistiques. Remplir le champ
            si la personne a visité une autre auberge récemment (ex: dans
            le dernier mois), sinon laisser vide.
        </li>
        <li><strong>Numéro de passeport / # membre HI</strong> Si le client est membre
            HI, y indiquer son numéro de carte. Sinon, il est obligatoire pour les
            clients d'avoir une pièce d'identification avec photo et adresse (ou un
            passeport), <strong>mais</strong> nous ne demandons pas souvent cette
            information, laissé à votre discretion (donc si vous avez des doutes, vous
            pouvez demander une carte d'identité).
        </li>
    </ul>
    <p>Appuyer sur "Enregistrer" pour enregistrer et fermer cette fenêtre. Un résumé des
        informations du client apparaîtront dans le haut de la fiche. Vous pouvez
        modifier les informations à tout moment en appuyant
        sur le bouton "Modifier informations client".
    </p>
    <h4>3) Enregistrer le dépôt d'une réservation effectuée en ligne (ex: Hostelworld)</h4>
    <p class="info">Il n'y a aucun dépôt si la réservation a été effectuée par téléphone
        ou en personne. Également, il n'y a pas de dépôt pour la grande majorité des
        réservations Internet. Dans ces cas, passer à l'étape suivante.
    </p>
    <p>Certains sites de réservation (ex: Hostelworld, mais pas Booking.com) charge un
        dépôt au client lors de sa réservation. Ce montant apparaîtra dans la
        feuille de la réservation
        et il devra être soustrait du montant à payer.
    </p>
    <p class="warning">La fonctionnalité de crédit (dépôt) ne doit être utilisée que pour
        des dépôts. Ne jamais utiliser cette fonctionnalité pour faire des remboursements,
        pour corriger une erreur, pour enregistrer un paiement, etc.</p>
    <p>Dans le bas de l'écran, appuyer sur "Ajouter un crédit". La fenêtre suivante
        s'affichera.</p>
    <p class="image">[img: modal ajouter crédit]</p>
    <ul>
        <li><strong>Description</strong>: s'il s'agit du dépôt d'une réservation en
            ligne, inscrire le numéro de la réservation (qui apparaît dans la feuille
            de la réservation).
        </li>
        <li><strong>Montant</strong> le montant du dépôt (ne pas mettre le montant en
            négatif). Par exemple: 2,38
        </li>
    </ul>
    <p>Appuyer sur enregistrer, cette fenêtre se fermera et le dépôt sera ajouté à la
        fiche.</p>
    <p>Pour modifier un crédit, appuyer dessus. Pour le supprimer, le glisser vers la
        gauche et un bouton "supprimer" apparaîtra.</p>
    <h4>4) Faire payer le client et enregistrer son paiement</h4>
    <p>Après que le client a payé (soit en argent ou par carte de crédit ou débit), il faut
        enregistrer son paiement dans l'application. Appuyez sur le bouton "Enregistrer
        le paiement" dans le bas de l'écran. La fenêtre suivante apparaîtra.</p>
    <p class="image">[img: modal ajouter paiement]</p>
    <p class="info">Le bouton "Enregistrer le paiement" apparaît seulement si les
        informations du client ont été saisies, sinon c'est le bouton "Informations
        client" qui apparaît à la place. Dans ce cas, commencez par saisir les
        informations du client.</p>
    <ul>
        <li><strong>Mode de paiement</strong>: Sélectionnez le mode de paiement (carte
            (débit/crédit) ou argent
        </li>
        <li><strong>Montant</strong>: Le montant du paiement. Par défaut, le montant
            total est indiqué, mais vous pouvez modifier ce montant (pour séparer le
            montant en 2, par exemple). Voir la note ci-dessous.
        </li>
    </ul>
    <p>Appuyez sur "Enregistrer" pour enregistrer le paiement. La fenêtre se fermera et
        le paiement est ajouté à la fiche.</p>
    <p>Tant que la fiche n'est pas enregistrée, vous
        pouvez modifier un montant en appuyant dessus, et vous pouvez le supprimer en le
        glissant vers la gauche (un bouton "Supprimer" apparaîtra). Mais une fois la fiche
        enregistrée, il ne sera plus possible de modifier ou de supprimer un paiement.
    </p>
    <p class="info">Il est possible d'enregistrer plus d'un paiement pour une fiche.
        Exemples: chaque membre d'un groupe paie son lit; ou un couple souhaite
        chacun payer la moitié d'une chambre privée. Dans ce cas, dans la fenêtre
        permettant d'enregistrer le paiement, modifiez la valeur du "montant"
        pour y mettre le montant du premier paiement, enregistrez ce
        paiement et appuyez à nouveau sur le bouton
        "Enregistrer le paiement" pour enregistrer un autre paiement.</p>
    <h4>5) Enregistrer la fiche</h4>
    <p class="info">Il est possible d'enregistrer une fiche même si elle n'a pas encore
        été totalement payée. Le paiement manquant pourra être rajouté plus tard.</p>
    <p>Pour enregistrer la fiche, appuyez sur le bouton "Enregistrer" dans le bas de
        l'écran. Si la fiche a été payée en totalité, ce bouton est vert. S'il reste un
        montant à payer, il est gris, mais il est possible d'appuyer dessus (un
        avertissement vous demandant de confirmer s'affichera par contre).</p>
    <h1>Retrouver une fiche</h1>
    <p>Vous pouvez retrouver des fichées créées précédemment pour les voir ou les modifier.</p>
    <p>Depuis l'écran d'accueil, appuyez sur "Voir les fiches". L'écran qui s'affichera
        listera toutes les fiches créées, triées par date de départ. Cette liste vous permet,
        entre autres, de :
    </p>
    <ul>
        <li>Voir quels clients quittent aujourd'hui et lesquels restent une
            autre nuit.
        </li>
        <li>Voir quels clients n'ont pas encore payé en totalité leur fiche.</li>
        <li>En appuyant sur une fiche, voir le détail et pouvoir la modifier (pour
            rajouter un item ou faire un remboursement par exemple).
        </li>
    </ul>
    <p class="info">Il est possible d'accéder à la liste des fiches même si la caisse est
        fermée, mais il ne sera pas possible d'enregistrer un paiement ou un remboursement avant
        que la caisse ne soit ouverte.</p>
    <p>Pour voir le détail d'une fiche ou pour la modifier, appuyez dessus.</p>
    <p class="info">Il n'est malheureusement pas possible d'effectuer une recherche.</p>
    <h1>Fermer la caisse</h1>
    <p>À la fin de la journée (peut être après quelques jours en basse saison), l'aubergiste
        doit fermer la caisse. Sur l'écran d'accueil, appuyez sur "Fermer la caisse" et
        l'écran suivant apparaîtra.
    </p>
    <p class="info">Le bouton "Fermer la caisse" apparaît seulement si la caisse est
        ouverte.</p>
    <p class="image">[img:écran pour fermer la caisse]</p>
    <p>Sur la machine de carte de crédit (le TPV), fermer le lot, prendre le
        reçu et remplir les champs suivants dans l'application :</p>
    <ul>
        <li><strong>Numéro de lot</strong> Ce numéro apparaît sur le reçu de
            fermeture de lot du TPV.
        </li>
        <li><strong>Montant du TPV</strong> Inscrire le montant du <em>grand
                total</em> indiqué sur le reçu du TPV.
        </li>
    </ul>
    <p>
        Dans la caisse, compter le nombre de chaque pièce et de chaque billet
        et remplir les champs de l'écran. Ne <strong>pas</strong> soustraire le montant de
        100 $ qui
        devra rester dans la caisse (le fond de caisse), compter <strong>tous</strong> les
        billets et toutes les pièces.
    </p>
    <p>Recompter une deuxième et troisième fois si nécessaire. Faites un effort honnête
        pour limiter les erreurs !</p>
    <p>Appuyez sur "Fermer la caisse". Vous serez redirigé à l'écran d'accueil.</p>
    <hr>
    <h1>Cas particuliers</h1>
    <h2>Un client veut rester une autre nuit</h2>
    <p>Suivre les instructions de "Rajouter un produit à une fiche existante".</p>
    <h2>Rajouter un produit à une fiche existante</h2>
    <p class="info">La caisse doit être ouverte pour enregistrer le paiement.</p>
    <ul>
        <li>Depuis l'accueil, appuyez sur "Voir les fiches" et appuyez sur la fiche
            souhaitée.
        </li>
        <li>Dans l'écran de la fiche, appuyez, à droite, sur le produit à ajouter.</li>
        <li>Faites payer le client et enregistrez le paiement[lien].
        </li>
        <li>Enregistrez la fiche.</li>
    </ul>
    <p class="info">Si le client souhaite rester une autre nuit, n'oubliez pas de modifier la
        date de départ de la fiche. Dans l'écran de la fiche, appuyez sur le bouton "Modifier les
        informations" en haut de la fiche (sous le nom du client) et modifiez la date de
        départ.</p>
    <h2>Modifier le numéro de chambre/les chambres utilisées/le nombre de personnes</h2>
    <ul>
        <li>Depuis l'accueil, appuyez sur "Voir les fiches" et appuyez sur la fiche
            souhaitée.</li>
        <li>Appuyez sur "Modifier les informations" (en haut de la fiche, sous le nom du client).
        </li>
        <li>Modifiez le numéro de chambre ou le nombre de personnes.</li>
        <li>Si une chambre doit être rajoutée, appuyez sur "Ajouter une chambre".</li>
        <li>Si une chambre doit être retirée, glisser la ligne de la chambre vers la gauche et
            appuyer sur "Supprimer".</li>
    </ul>
    <h2>Rembourser un item</h2>
    <p class="info">La caisse doit être ouverte pour enregistrer le remboursement.</p>
    <ul>
        <li>Depuis l'accueil, appuyez sur "Voir les fiches" et appuyez sur la fiche
            souhaitée.</li>
        <li>Glissez l'item à rembourser vers la gauche. Un bouton "rembourser" apparaîtra.
            Appuyez dessus.</li>
        <li>Une fenêtre apparaîtra vous demandant la quantité à rembourser (donc, il est
            possible de rembourser, par exemple, une seule nuit si la personne avait payé pour
            deux nuits).</li>
        <li>Appuyez "Enregistrer" et la fenêtre se fermera. Le remboursement sera ajouté à la
            liste des items (avec une quantité négative).</li>
        <li><strong>Important :</strong> rajoutez une note explicative à la fiche en appuyant
            sur le bouton "Notes" en haut à droite de la fiche. Indiquez pourquoi un
            remboursement a été accordé au client.</li>
    </ul>
    <ul>
        <li>Si vous devez rembourser de l'argent au client, le montant à rembourser apparaîtra
            en jaune dans le bas de l'écran.</li>
        <li>Remboursez le client (dans la "vraie" vie !) avec la même méthode qu'il a initialement
            utilisé pour payer (en argent, sur la même carte de débit, de crédit, etc.).
        </li>
        <li>Appuyez sur "Enregistrer le remboursement". Assurez-vous de bien sélectionner le
            mode de remboursement.</li>
    </ul>
    <p class="warning">Toujours rembourser le client sur <strong>la même carte</strong> que celui-ci
        a utilisé s'il a payé par carte. Toujours rembourser en argent si celui-ci a payé en
        argent.
    </p>
    <h2>Enregistrer le dépôt d'une réservation faite en ligne</h2>
    <p>Voyez les instructions dans "créer une nouvelle fiche".[lien]</p>
    <h2>Un client souhaite payer en 2 paiements (ou plus). Par exemple, un couple souhaite
        chacun payer la moitité du prix de la chambre privée.</h2>
    <p>Voyez la note dans "Faire payer le client et enregistrer le paiement"[lien]</p>
    <h2>Un client reste gratuitement (ex: une employée d'une autre auberge)</h2>
    <ul>
        <li>Créez quand même une fiche. Saisisez toutes les informations du client, mais ne
            rajoutez aucun produit.
        </li>
        <li>Appuyez sur le bouton "Notes" en haut à droite pour y rajouter un commentaire
            expliquant la situation (par exemple: "Nuit gratuite car employée de l'auberge HI
            Québec").</li>
    </ul>
    <h2>Prix spéciaux (ex: prix de groupe, prix spécial pour un événement ou un client, etc.)
    </h2>
    <p class="info">S'il s'agit d'un groupe, voyez aussi "Une fiche avec plusieurs chambres"[lien]
        .</p>
    <ul>
        <li>Créez une fiche. S'il s'agit d'un groupe, ne faîtes qu'une seule
            fiche au nom du groupe.</li>
        <li>Dans la colonne de droite (où sont les boutons des produits), appuyez sur le bouton
            vert "Produit spécial".</li>
        <li>Dans la fenêtre qui apparaît, saisissez le nom du produit (ex: "Dortoir groupe
            hockey")
            et indiquez le prix <strong>unitaire</strong> (ex: pour un seul lit, une seule nuit,
            un seul item, ...). La quantité sera spécifiée après. Enregistrez pour fermer cette
                fenêtre. L'item sera rajouté à la liste des items.
            </li>
        <li>Modifiez la quantité (par exemple, pour 3 lits dortoirs à prix spécial, pour deux
            nuits, mettre la quantité "6").</li>

    </ul>
    <p class="warning">Le nom et le prix d'un item spécial ne peuvent plus être modifiés
        après que la fiche est enregistrée. Si vous avez fait une erreur de prix et que vous
        avez enregistré la fiche, voir la section "J'ai mis le mauvais prix d'un produit
        spécial".
    </p>
    <p class=info>Si le total est payé en différents paiements (par exemple, chaque membre du groupe
        paie séparemment son lit), voir la note dans "Faire payer le client et enregistrer le
        paiement" pour enregistrer les paiements individuellement[lien].</p>
    <p class="info">Cette fiche peut aussi contenir des items à prix réguliers. Il est
        fréquent qu'un groupe a un prix spécial pour le dortoir, mais qu'un membre du groupe
        souhaite une chambre privée. Tout simplement rajouter la chambre à cette fiche.
    </p>
    <h2>Argent retiré ou ajouté à la caisse (ex: pour faire un achat en argent)</h2>
    <p class="info">La caisse doit être ouverte pour pouvoir enregistrer une sortie ou
        un ajout d'argent.</p>
    <p>Vous devez enregistrer tout argent sonnant (billet, monnaie) qui est ajouté ou retiré
        de la caisse autrement que par un paiement/remboursement. Exemple : de l'argent est pris
        dans la caisse pour faire une
        épicerie; un 25 sous a été trouvé sous la caisse et il est rajouté; de la
        monnaie est rajoutée à la caisse (sans que des billets soient retirés).</p>
    <ul>
        <li>Sur l'écran d'accueil, appuyer sur "Entrée/sortie d'argent".</li>
        <li>Dans l'écran qui apparaît, appuyez sur "Nouveau mouvement".</li>
        <li>Dans la fenêtre qui s'ouvre, indiquer le type (sortie ou entrée d'argent selon
            que de l'argent a été retiré ou ajouté), le montant exacte et une description
            (ex: "Épicerie", "Ajout de monnaie").</li>
    </ul>
    <p>Vous pouvez modifier une entrée en appuyant dessus.</p>
    <p>Vous pouvez supprimer une entrée en la glissant vers la gauche et en appuyant sur
        "Supprimer".
    </p>
    <hr>
    <h2>Code d'autorisation et nouvelle tablette</h2>
    <h2>L'application demande un code d'autorisation</h2>
    <p class="image">[img: Écran qui demande un code d'autorisation]</p>
    <p>Cette situation ne devrait pas arrivée (normalement), à moins d'utiliser une nouvelle
        tablette ou si la tablette a été désactivée depuis l'outil d'administration.
    </p>
    <p>Si vous n'êtes pas dans une de ces situations, commencez par quitter l'application
        (appuyer sur le bouton "retour" de la tablette. Pas le bouton principal, celui
        avec la flèche). Ouvrez à nouveau la tablette.</p>
    <p>Si le problème persiste, suivez les instructions pour saisir un nouveau code
        d'autorisation[lien]</p>
    <h2>Saisir un nouveau code d'autorisation</h2>
    <p class="info">Vous pouvez saisir un code d'autorisation seulement si la tablette le
        demande.</p>
    <ul>
        <li>Connectez-vous, depuis n'importe quel ordinateur ou avec le navigateur web de la
            tablette, à
            l'outil de gestion à
            <a href="https://venteshirdl.ca" target="_blank">https://venteshirdl.ca</a> à l'aide des
            informations de connexion (voir cahier des codes "Guide
            d'utilisation des systèmes de réservation" ou ailleurs).</li>
        <li>Dans le menu du haut, cliquez sur "Appareils".</li>
        <li>À droite de l'appareil de l'accueil, cliquez sur "Obtenir code d'autorisation".</li>
        <li>Un code à quatre chiffre apparaîtra. Inscrire ce code dans le champ "Code" de l'écran
            d'authentification de la tablette.
        </li>
    </ul>
    <p class="info">Le code doit être saisi dans les minutes suivants son affichage, sinon
        un nouveau code devra être généré.</p>
    <p class="info">La tablette reprendra là où elle était. Si le code a été saisi sur une
        nouvelle tablette, elle reprendra également là où l'ancienne tablette était rendu. À
        noter qu'une seule tablette peut être utilisée à la fois.</p>
    <h2>Une nouvelle tablette Android est utilisée</h2>
    <ul>
        <li>Si l'application n'est pas déjà installée sur la nouvelle tablette, suivre les
            instructions suivantes :
            <ul>
                <li>Permettez à la tablette d'installer des applications de sources inconnues
                    (recherchez sur Google "android autoriser sources inconnues")</li>
                <li>Depuis le navigateur web de <strong>la tablette</strong>, visitez
                    <a href="https://venteshirdl.com/app.apk">https://venteshirdl.com/app
                        .apk</a>. Ceci va télécharger l'application sur la tablette.</li>
                <li>Une fois le téléchargement terminé, exécutez le fichier téléchargé (si le
                    navigateur n'offre pas automatiquement cette option, allez dans les
                    fichiers téléchargés de la tablette et appuyez sur l'application).</li>
                <li>L'application devrait maintenant être installée.</li>
                <li>Il est également recommandé de faire un lien vers cette documentation sur
                    le bureau de la tablette.</li>
            </ul>
        </li>
        <li>Si vous ouvrez l'application, elle devrait vous demander un code d'autorisation.
            Suivez les instructions pour en générer un nouveau[lien].</li>
    </ul>
    <h2>La tablette est volée ou perdue de façon permanente</h2>
    <p>Suivez les instructions pour générer un nouveau code[lien]. Ceci a pour effet de
        déconnecter les tablettes qui seraient encore connectées.</p>
    <h1>FAQ</h1>
    <h2>Le client souhaite payer plus tard</h2>
    <p>Il est possible d'enregistrer une fiche sans avoir saisi tous les paiements. L'écran
        listant les fiches[lien] montre également quelles fiches n'ont pas encore été payées en
        totalité. Par contre, évitez le plus possible cette situation.</p>
    <h2>Doit-on fermer la caisse tous les jours ?</h2>
    <p>Oui, sauf en basse saison. Vérifier avec le directeur de l'auberge.</p>
    <h2>Un ancient client revient, est-ce que je dois refaire une nouvelle fiche ?</h2>
    <p>Oui, car il n'est pas possible d'avoir une fiche pour des journées non contigües. Il
        n'est malheureusement pas possible "d'importer" les informations du client depuis une
        ancienne fiche, elles doivent être saisies de nouveau (comme si c'était un nouveau
        client).</p>
    <h2>Le client souhaite avoir un reçu</h2>
    <p>L'application ne permet malheureusement pas d'imprimer ou d'envoyer des reçus par
        courriel. Utilisez un carnet de reçu.</p>
    <ul>
        <li>Trouvez une nouvelle page du carnet et assurez-vous de mettre le carton séparateur
            entre ce reçu et le prochain (pour éviter d'écrire sur les prochains reçus).</li>
        <li>Il y a une étampe qui permet d'ajouter le nom de l'auberge et les numéros de taxes.
            Utilisez-là dans le haut du reçu.
        </li>
        <li>Sur la tablette, dans la fiche du client, se trouve à côté du montant
            total (en bas
            de la fiche) un bouton "Détails". Appuyez dessus pour faire afficher le détail des
            taxes.
        </li>
        <li>Détaillez sur le reçu les items achetés, leur quantité et leur prix avant taxes.</li>
        <li>Dans le bas du reçu, indiquez le total des taxes (tel qu'indiqué dans la tablette)
            et le grand total.</li>
    </ul>
    <h2>Corriger une erreur</h2>
    <h3>J'ai chargé le prix non-membre, mais le client est membre</h3>
    <p>Si vous n'avez pas encore enregistré la fiche, simplement appuyer sur la liste
        déroulante de l'item pour choisir "Membre".</p>
    <p>Si la fiche a déjà été enregistrée, il n'est pas possible de modifier un item
        existant. Vous devez rembourser l'item incorrect[lien] pour ensuite, rajouter le nouvel
        item[lien].
    </p>
    <p>Si le client doit être remboursé, effectuez le remboursement[lien]. Notez que si la
        fiche n'a pas encore été enregistrée depuis le paiement, il peut être possible de
        simplement le supprimer[lien].</p>
    <h3>J'ai oublié de mettre le dépôt</h3>
    <p>Ouvrir la fiche et suivre les instructions pour rajouter un dépôt[lien].</p>
    <h3>J'ai mis le mauvais montant de dépôt</h3>
    <p>Ouvrir la fiche et suivre les instructions pour modifier un dépôt[lien].</p>
    <h3>J'ai indiqué la mauvaise quantité pour un item</h3>
    <p>Si vous n'avez pas encore enregistré la fiche, simplement appuyer sur le "+" ou
        le "-", ou appuyer directement sur le chiffre de la quantité pour faire
        apparaître un clavier. Vous pouvez également glisser un item vers la gauche pour
        faire apparaître un bouton pour suppression.</p>
    <p>Si la fiche a déjà été enregistrée, il n'est pas possible de modifier la quantité
        d'un item déjà vendu. Vous devrez soit rembourser la quantité en trop (remboursement
        partiel) [lien] ou rajouter
        à nouveau le produit pour augmenter la quantité.
    </p>
    <h3>J'ai mis le mauvais produit</h3>
    <p>Si la fiche n'a <em>pas encore</em> été enregistrée, glisser l'item vers la gauche. Un
        bouton "Retirer" apparaîtra.</p>
    <p>Si la fiche a déjà été enregistrée, vous pourrez seulement rembourser l'item (et pas
        le supprimer)[lien]</p>
    <h3>J'ai mis le mauvais prix à un produit spécial</h3>
    <p>Si la fiche n'a pas encore été enregistrée, appuyez sur l'item du produit
        spéciale. Une fenêtre apparaîtra vous permettant de modifier le prix du produit
        spécial.</p>
    <p>Si la fiche a été enregistrée, vous devrez premièrement rembourser l'item[lien] et ensuite
        le rajouter avec les bonnes informations[lien].
    <h3>J'ai indiqué le mauvais mode de paiement (ex: "par carte" au lieu de "en argent")
        ou j'ai mis le mauvais montant.</h3>
    <p>Si la fiche n'a pas encore été enregistrée, appuyez sur la ligne du paiement. Une
        fenêtre apparaîtra vous permettant de modifier le mode de paiement et le montant. Vous
        pouvez également la glisser vers la gauche pour faire apparaître un bouton de suppression.
    </p>
    <p>Si la fiche a déjà été enregistrée, il n'est malheureusement pas possible de
        modifier les paiements déjà enregistrés. Dans ce cas, ajoutez une note à la fiche indiquant
        l'erreur[lien].</p>
@endsection
