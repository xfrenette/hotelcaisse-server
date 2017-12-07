@extends('layouts.help')

@section('help:content')
    <h1>Ouvrir la caisse</h1>
    <p class="warning">Il n'existe pas de mode "formation / training" et il n'est pas possible
        d'annuler une ouverture de caisse une fois qu'elle est faite.</p>
    <p>Si la caisse a été fermée par l'aubergiste de la veille, vous devez l'ouvrir avant de
        pouvoir créer des nouvelles fiches.</p>
    <p>Voici ce que vous verrez à l'écran d'accueil si la caisse a été fermée. Si vous ne voyez
        pas le bouton "Ouvrir la caisse" et, à la place, vous voyez "Nouvelle fiche", la caisse
        est déjà ouverte.</p>
    <p class="image">[img:accueil caisse fermée]</p>
    <p>Appuyez sur "Ouvrir la caisse". Vous verrez l'écran suivant.</p>
    <p class="image">[img:écran ouverture de caisse]</p>
    <ul>
        <li>Dans le champ <strong>"Votre nom"</strong>, écrire le nom de l'aubergiste de la
            journée. Cette
            information
            est seulement pour avoir une idée de qui était l'aubergiste cette journée là; ce n'est pas
            grave si un autre employé cré une fiche dans la journée, si vous êtes plusieurs aubergistes dans
            la journée (vous pouvez écrire plusieurs noms dans ce champ) ou si la même caisse sera
            ouverte plusieurs jours (et donc plusieurs aubergistes).</li>
        <li>Le champ <strong>"Argent dans la caisse"</strong> permet d'indiquer combien d'argent
            (liquide) se trouve dans la caisse lors de l'ouverture. L'aubergiste de la veille est
            supposé y avoir laissé
            exactement 100&nbsp;$, c'est pourquoi ce champ a déjà la valeur "100&nbsp;$". <strong>Vous
                pouvez le
            laisser ainsi</strong>, mais, si vous souhaitez (pas obligatoire), vous pouvez également recompter
            l'argent de la caisse et y mettre le montant réel (bien compter 2 ou 3 fois pour éviter
            les erreurs).</li>
    </ul>
    <p>Appuyer sur "Ouvrir la caisse". Vous serez redirigé à l'écran d'accueil, mais les boutons
        auront changés (car la caisse est maintenant ouverte).</p>
    <h1>Créer une fiche</h1>
    <p class="warging">La caisse doit être ouverte pour pouvoir créer une nouvelle fiche.</p>
    <p class="info">On fait une seule fiche par "groupe" de client (ex: un couple dans la même
        chambre, deux personnes qui désirent prendre 2 chambres, un groupe scolaire qui prend 8
        chambres). Ne pas faire une fiche par personne, faire une seule fiche.</p>
    <p class="info">Vous pouvez, à tout moment, annuler la création de la fiche en appuyant sur
        "Annuler" ou en appuyant sur le bouton de retour de la tablette.</p>
    <p>À l'écran d'accueil, appuyer sur "Nouvelle fiche". Vous verrez l'écran suivant.</p>
    <p class="image">[img:écran nouvelle fiche]</p>
        <h4>Ajouter les produits</h4>
            <p class="info">Notez que les prix affichés inclus toutes les taxes. Certains sites de
                réservation (comme Booking.com) affichent au client les prix avant taxes, donc le client
                pourrait souligner la différence de prix. Dans ce cas, seulement lui dire que le prix
                qu'il a vu est avant taxes et qu'il est indiqué dans sa réservation Booking.com que les
                taxes applicables seront rajoutées.</p>
            <p>À droite, appuyer sur le ou les produits à ajouter. Les boutons bleus sont des produits,
                les boutons jaune-orange sont des catégories (appuyer dessus montre d'autres produits).
                Le bouton vert est pour ajouter un produit spécial. Voir la section dans "Cas
                particuliers" pour ce bouton.</p>
            <p>Quand un produit est ajouté, il
                apparaît dans la partie gauche où on peut changer sa quantité (vous pouvez soit appuyer
                sur "+" et "-" pour changer la quantité ou simplement appuyer sur le chiffre pour faire
                apparaître un clavier numérique).</p>
            <p class="info">Pour supprimer un produit rajouté par erreur, faites le glisser vers la
                gauche et un bouton "Retirer" apparaîtra. Noter que si vous êtes en train de
                <em>modifier</em> une fiche qui a été créée et enregistrée plus tôt, il ne sera pas
                possible de supprimer les produits déjà créés. Vous pourrez seulement les rembourser (voir
                la section dans les "Cas particuliers").</p>
            <p class="info">Il n'y a aucune différence entre "2 personnes en dortoir / 1 nuit" et "1
                personne en dortoir / 2 nuits" (donc un total de 2 dortoirs dans les deux cas). Dans
                les deux cas, vous appuierez une fois sur "Dortoir" et vous lui mettrez une quantité
                de 2. C'est dans la prochaine section, "Informations client", que la différence sera
                notée.</p>
            <p>Certains produits ont un prix différent selon que le client est membre HI ou
                pas. Dans ce cas, une liste déroulante apparaît permettant de choisir si on souhaite le
                prix membre ou non-membre. Choisissez le bon montant.</p>
            <p>Voici un exemple où deux dortoirs prix <em>non-membre</em>, un dortoir
                <em>membre</em> et une carte de membre ont été acheté.</p>
            <p class="image">[img:2 dortoirs non membre, 1 dortoir membre, carte de membre]</p>
        <h4>Informations du client</h4>
            <p class="info">Vous pouvez à tout moment modifier à nouveau les informations du
                client en appuyant sur le bouton "Modifier les informations client" dans le haut
                de la fiche.</p>
            <p>Si les informations du client n'ont pas encore été saisies, un bouton
                "Informations client" apparaît dans le bas de la fiche. Sinon, vous pouvez aussi
                appuyer sur "Modifier informations client" dans le haut de la fiche pour les
                modifier.</p>
            <p class="image">[img: boutons pour ouvrir le modal infos clients]</p>
            <p>Quand vous appuyez sur un de ces boutons, l'écran suivant apparaîtra.</p>
            <p class="image">[img: modal info clients]</p>
            <ul>
                <li><strong>Date d'arrivée et de départ</strong> Appuyer dans ces champs pour
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
                    nom descriptif (ex: "Groupe impro").
                </li>
                <li><strong>Courriel et téléphone</strong> Cette information est utilisée
                    seulement si l'on a besoin de contacter le client pendant son séjour. Un
                    seul des deux champs est nécessaire. Nous n'utilisons pas cette
                    informations pour de la promotion.</li>
                <li><strong>Pays / province</strong> Si le client réside au Canada, sélectionnez
                    sa province, sinon son pays.
                </li>
                <li><strong>Code postal</strong> Ce champ doit être rempli seulement si le
                    client réside au Canada, sinon il peut rester vide.</li>
                <li><strong>Premier séjour dans cette auberge ?</strong> Pour statistiques.</li>
                <li><strong>Membre HI</strong> Si le client est membre HI, mettre ce champ à
                    "Oui" et demandez à voir la carte. S'assurer qu'elle n'est pas expirée.
                    Saisir le numéro de la carte dans le champ "Passeport ou # carte HI"</li>
                <li><strong>Dernière auberge visitée</strong> Pour statistiques. Seulement
                    remplir le champ si la personne a visité une autre auberge récemment (ex: dans
                    le dernier mois).</li>
                <li><strong>Numéro de passeport / # membre HI</strong> Si le client est membre
                    HI, y indiquer le numéro de carte. Sinon, il est obligatoire pour les
                    clients d'avoir une pièce d'identification avec photo et adresse (ou un
                    passeport).
                    Par contre, il n'est pas obligatoire pour l'aubergiste de demander cette
                    information, c'est laissé à sa discrétion.</li>
            </ul>
            <p>Appuyer sur "Enregistrer" pour enregistrer et fermer cette fenêtre. Vous reviendrez à
                la liste des produits et certaines des informations saisies appraîtront dans le haut
                de la fiche. Vous pouvez modifier les informations à tout moment en appuyant
                sur "Modifier informations client" dans le haut.
            </p>
        <h4>Enregistrer un dépôt (crédit) d'une réservation effectuée en ligne (ex: Hostelworld)
        </h4>
            <p>Certains sites de réservation (ex: Hostelworld, mais pas Booking.com) charge un
                dépôt au client lors de sa réservation. Le montant du dépôt doit alors être
                soustrait du montant à payer. Pour ce cas, l'application permet d'enregistrer un
                "crédit".
            </p>
            <p class="warning">La fonctionnalité de crédit (dépôt) ne doit être utilisée que pour
                des dépôts. Ne jamais utiliser cette fonctionnalité pour faire des remboursements,
                pour corriger une erreur, pour enregistrer le paiement, etc.</p>
            <p>Dans le bas de l'écran, appuyer sur "Ajouter un crédit". La fenêtre suivante
                s'affichera.</p>
            <p class="image">[img: modal ajouter crédit]</p>
            <ul>
                <li><strong>Description</strong> s'il s'agit d'un dépôt d'une réservation en
                    ligne, inscrire le numéro de la réservation (qui apparaît dans la feuille
                    de la réservation).</li>
                <li><strong>Montant</strong> le montant du dépôt (ne pas mettre le montant en
                    négatif; ex: 2.38).</li>
            </ul>
            <p>Appuyer sur enregistrer</p>
            <p>Pour modifier un crédit, appuyer dessus. Pour le supprimer, le glisser vers la
                gauche et un bouton "supprimer" apparaîtra.</p>
        <h4>Faire payer le client et enregistrer le paiement</h4>
            <p>Quand le client a payé (soit en argent ou par carte de crédit ou débit), il faut
                enregistrer son paiement dans l'application. Appuyez sur le bouton "Enregistrer
                le paiement" dans le bas de l'écran. La fenêtre suivante apparaîtra.</p>
            <p class="info">Le bouton "Enregistrer le paiement" apparaît seulement si les
                informations du client ont été saisies, sinon c'est le bouton "Informations
                client" qui apparaît à la place</p>
            <p class="image">[img: modal ajouter paiement]</p>
            <ul>
                <li><strong>Mode de paiement</strong> Sélectionnez le mode de paiement (carte
                    (débit/crédit) ou argent</li>
                <li><strong>Montant</strong> Le montant du paiement. Par défaut, le montant
                    total est indiqué, mais vous pouvez modifier ce montant (pour séparer le
                    montant en 2, par exemple). Voir ci-dessous.</li>
            </ul>
            <p class="info">Il est possible d'enregistrer plus d'un paiement pour une fiche. Par
                exemple, chaque membre d'un groupe paie son lit, ou un couple souhaite chacun
                payer la moitié d'une chambre privée. Dans ce cas, dans la fenêtre du paiement,
                modifiez le montant, enregistrez ce paiement et appuyez à nouveau le bouton
                "Enregistrer le paiement" pour enregistrer un autre paiement.</p>
        <h4>Enregistrer la fiche</h4>
            <p class="info">Il est possible d'enregistrer la fiche même si elle n'a pas encore
                été totalement payée. Le paiement manquant pourra être rajouté plus tard.</p>
            <p>Pour enregistrer la fiche, appuyez sur le bouton "Enregistrer" dans le bas de
                l'écran. Si la fiche a été payée en totalité, ce bouton est vert. S'il reste un
                montant à payer, il est gris, mais il est encore actif.</p>
            <p class="image">[img: bouton enregistrer fiche vert et gris]</p>
    <h1>Retrouver une fiche</h1>
        <p>L'application permet de voir la liste des fiches déjà créées. Cet écran permet de:</p>
        <ul>
            <li>Voir quels clients quittent aujourd'hui et lesquels restent une
                autre nuit.</li>
            <li>Voir quels clients n'ont pas encore payé en totalité leur fiche.</li>
            <li>Modifier une fiche (pour rajouter un item ou faire un remboursement par exemple).
            </li>
        </ul>
        <p class="info">Il est possible d'accéder à la liste des fiches même si la caisse est
            fermée, mais il ne sera pas possible d'enregistrer un paiement ou un remboursement avant
            que la caisse ne soit ouverte.</p>
        <p>Depuis l'écran d'accueil, appuyez sur "Voir les fiches". L'écran suivant apparaît.</p>
        <p class="image">[img: écran des fiches avec des clients qui partent aujourd'hui, demain
            et des montants à percevoir]</p>
        <p>Cet écran montre des fiches de clients qui partent aujourd'hui et d'autres qui
            partent le lendemain.</p>
        <p>Pour voir ou modifier une fiche, appuyer dessus.</p>
        <p class="info">Il n'est pas possible d'effectuer une recherche de fiche.</p>
    <h1>Fermer la caisse</h1>
        <p>À la fin de la journée (peut être après quelques jours en basse saison), l'aubergiste
            doit fermer la caisse. Sur l'écran d'accueil, appuyez sur "Fermer la caisse" et
            l'écran suivant apparaîtra.
        </p>
        <p class="image">[img:écran pour fermer la caisse]</p>
        <p class="info">Le bouton "Fermer la caisse" apparaît seulement si la caisse est
            ouverte.</p>
        <p>Sur la machine de carte de crédit (le TPV), faire fermer le lot et prendre le
            reçu et remplir les champs suivants dans l'application :</p>
        <ul>
            <li><strong>Numéro de lot</strong> Ce numéro apparaît sur le reçu de
                fermeture de lot du TPV.</li>
            <li><strong>Montant du TPV</strong> Inscrire le montant du <em>grand
                total</em> indiqué sur le reçu du TPV.</li>
        </ul>
        <p>
            Dans la caisse, compter le nombre de chaque pièce et de chaque billet et remplir
            les champs de l'écran. Ne <strong>pas</strong> soustraire le montant de 100 $ qui
            devra rester dans la caisse (le fond de caisse), compter <strong>tous</strong> les
            billets et toutes les pièces.
        </p>
        <p>Recompter une deuxième et troisième fois si nécessaire. Faites un effort honnête
            pour limiter les erreurs !</p>
        <p>Appuyez sur "Fermer la caisse". Vous serez redirigé à l'écran d'accueil.</p>
    <h1>Cas particuliers</h1>
        <h2>Un client veut rester une autre nuit</h2>
        <p>Suivre les instructions du point suivant.</p>
        <h2>Rajouter un produit à une fiche existante</h2>
        <p class="info">La caisse doit être ouverte pour enregistrer le paiement.</p>
        <ul>
            <li>Depuis l'accueil, appuyer sur "Voir les fiches" et appuyer sur la fiche
                souhaitée.</li>
            <li>Dans l'édition de la fiche, appuyer sur le produit à ajouter.</li>
            <li>Faire payer le client et enregistrer le paiement comme lors de la création
                d'une nouvelle fiche.</li>
            <li>Enregistrer la fiche.</li>
        </ul>
        <p class="info">Si le client souhaite rester une autre nuit, ne pas oublier d'appuyer
            sur "Modifier les informations" sous le nom du client pour modifier la date de
            départ.</p>
        <h2>Un client change de chambre</h2>
        <p>La seule chose à faire est de modifier le numéro de chambre.</p>
        <p>Ouvrir la fiche (depuis l'accueil, appuyer
            sur "Voir les fiches" et appuyer sur la fiche voulue).</p>
        <p>En haut à gauche, sous le nom du client, appuyer sur "Modifier les informations" et,
            dans la fenêtre qui s'ouvre, modifier le numéro de chambre.</p>
        <p>Enregistrer la fiche.</p>
        <h2>Rembourser un item</h2>
        <p class="info">La caisse doit être ouverte pour enregistrer le remboursement.</p>
        <p>Ouvrir la fiche où l'item a initialement été vendu (depuis l'accueil, appuyer
            sur "Voir les fiches" et appuyer sur la fiche voulue).</p>
        <p>Glisser vers la gauche l'item à rembourser. Un bouton "rembourser" apparaîtra.
                Appuyer dessus</p>
            <p class="image">[img: fiche - bouton rembourser]</p>
        <p>Une fenêtre apparaîtra vous demandant la quantité à rembourser (donc, il est
            possible de rembourser, par exemple, une seule nuit si la personne avait payé pour
            deux nuits).</p>
        <p>Une fois cette fenêtre fermée, le remboursement sera rajouté dans la liste des
            items (avec une quantité négative).</p>
        <p><strong>Important :</strong> rajouter une note explicative à la fiche en appuyant
            sur le bouton "Notes" en haut à droite de la fiche. Indiquer pourquoi un
            remboursement a été accordé au client.</p>
        <p>S'il y a un montant à rembourser, celui-ci apparaîtra en jaune dans le bas de
            la fiche. Effectuer le remboursement au client  (dans la vraie vie) et après
            appuyer sur le bouton "enregistrer le remboursement". Sélectionnez le bon
            mode utilisé pour le remboursement (par carte ou en argent) et le montant</p>
        <p class="warning">Toujours rembourser le client sur <strong>la même carte</strong> que celui-ci a
            utilisé s'il a payé par carte. Toujours rembourser en argent si celui-ci a payé en
            argent.
        </p>
        <p>Enregistrer la fiche.</p>
        <h2>Enregistrer le dépôt d'une réservation faite en ligne</h2>
        <p class="info">Le dépôt est enregistré dans l'application seulement quand le client se
            présente à l'auberge. Il n'est pas enregistré, par exemple, quand on reçoit la
            réservation par internet.</p>
        <p class="info">Il est possible de rajouter un dépôt à une fiche existante. Il y aura
            probablement un remboursement à effectuer si on avait initialement chargé le plein
            prix au client.</p>
        <p>Voir les instructions dans "créer une nouvelle fiche".</p>
        <h2>Un client souhaite payer en 2 paiements (ou plus). Par exemple, un couple souhaite
            chacun payer la moitité du prix de la chambre privée.</h2>
        <p>Voir la note dans "Faire payer le client et enregistrer le paiement"</p>
        <h2>Un client reste gratuitement</h2>
        <p>Créer une fiche quand même. Saisir toutes les informations du client, mais ne rajouter
            aucun produit.
        </p>
        <p>Appuyer sur le bouton "Notes" en haut à droite pour y rajouter un commentaire
            expliquant la situation (par exemple: "Nuit gratuite car directeur de l'auberge HI
            Québec").</p>
        <p>Enregistrer la fiche</p>
        <h2>Prix spéciaux (ex: prix de groupe, prix spécial pour un événement ou un client, etc.)
        </h2>
        <p class="info">S'il s'agit d'un groupe, voir aussi "Une fiche avec plusieurs chambres".</p>
        <p>Faire une fiche quand même. S'il s'agit d'un groupe, vous pouvez faire une seule
            fiche au nom du groupe.</p>
        <p>Dans la colonne de droite (où sont les boutons des produits), appuyez sur le bouton
            vert "Produit spécial".</p>
        <p>Dans la fenêtre qui apparaît, saisir le nom du produit (ex: "Dortoir groupe hockey")
            et y mettre le prix pour <strong>un seul lit</strong> (ou un seul item s'il s'agit
            d'un autre item). La quantité sera spécifié après. Enregistrez cette petite fenêtre.</p>
        <p>L'item a été rajouté. Si vous souhaitez modifier son nom ou son prix, appuyez dessus.</p>
        <p class="warning">Le nom et le prix d'un item spécial ne peuvent plus être modifiés
            après que la fiche est enregistrée. Si vous avez fait une erreur de prix et que vous
            avez enregistré la fiche, voir la section "J'ai mis le mauvais prix d'un produit
            spécial".
        </p>
        <p>Modifiez la quantité selon le nombre utilisé (par exemple, modifier la quantité selon
            le nombre de lit en dortoir à prix spécial).
        </p>
        <p class="info">Cette fiche peut aussi contenir des items à prix réguliers. Il est
            fréquent qu'un groupe a un prix spécial pour le dortoir, mais qu'un membre du groupe
            souhaite une chambre privée. Tout simplement rajouter la chambre à cette fiche.
        </p>
        <p>Si le total est payé en différents paiements (par exemple, chaque membre du groupe
            paie séparemment son lit), voir la note dans "Faire payer le client et enregistrer le
            paiement" pour enregistrer les paiements individuellement.</p>
        <h2>Une fiche avec plusieurs chambres</h2>
        <h2>Corriger une erreur</h2>
            <h3>J'ai chargé le prix non-membre, mais le client est membre</h3>
            <h3>J'ai oublié de mettre le dépôt</h3>
            <h3>J'ai indiqué la mauvaise quantité</h3>
            <h3>J'ai mis le mauvais produit</h3>
            <h3>J'ai mis le mauvais prix d'un produit spécial</h3>
            <h3>J'ai indiqué le mauvais mode de paiement (ex: "par carte" au lieu de "en argent")</h3>
        <h2>Argent retiré ou ajouté à la caisse (ex: pour faire un achat en argent)</h2>
        <h2>L'application demande un code d'autorisation</h2>
        <h2>Une nouvelle tablette Android est utilisée</h2>
        <h2>La tablette est volée ou perdue de façon permanente</h2>
        <h2>Le client souhaite payer plus tard</h2>
    <h1>FAQ</h1>
        <h2>Doit-on fermer la caisse tous les jours ?</h2>
        <h2>Comment différencier une fiche pour 2 nuits d'une fiche pour 2 chambres ?</h2>
        <h2>Un ancient client revient, est-ce que je dois refaire une nouvelle fiche ?</h2>
        <h2>Le client souhaite avoir un reçu</h2>
@endsection
