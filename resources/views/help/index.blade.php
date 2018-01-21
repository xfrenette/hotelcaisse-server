@extends('layouts.help')

@push('scripts')
    <script>
        $(function() {
        	var $toc = $('[data-toc=toc]'),
        	    $root = $('[data-toc=root-container]');

			var $elements = $root.find('[data-toc=element]');
			var currentLevel = 0;
			var $currentContainer = $toc;

			$elements.each(function(i, e) {
				var $element = $(e);
				var name = $element.prop('tagName').toLowerCase();

				if (name.indexOf('h') !== 0) {
					console.warn('data-toc=element attribute can only be used on title (h1, h2, ' +
                        '...) elements');
					return;
                }

                var level = parseInt(name.substring(1));
				var $anchor = $element.find('a[name]');
				var $entry = $('<li><a href="#' + $anchor.attr('name') + '">' + $element.text() +
                    '</a></li>');

				if (currentLevel === level) {
					$currentContainer.append($entry);
                } else if (currentLevel < level) {
					var $ul = $('<ul/>');
					$ul.append($entry);
					$currentContainer.append($ul);
					$currentContainer = $ul;
                } else {
					var diff = currentLevel - level;
					for(var i = 0; i < diff; i++) {
						$currentContainer = $currentContainer.parent();
                    }
					$currentContainer.append($entry);
                }
                currentLevel = level;
            });
        });
    </script>
@endpush

@section('help:content')
    <p class="alert alert-warning"><strong>Important: </strong>Il n'existe pas un mode
        «&nbsp;formation/training&nbsp;» dans l'application permettant de faire des «&nbsp;tests&nbsp;» qui ne seront pas
        enregistrés. Également, il n'est généralement pas possible d'annuler une action une fois
        qu'elle a été enregistrée. <strong>Donc ne faites pas des
            «&nbsp;tests&nbsp;» croyant qu'ils pourront être annulés ensuite.</strong></p>
    <div class="row">
        <div class="col-md-6 col-sm-7">
            <div class="panel panel-default">
                <div class="panel-heading">Table des matières</div>
                <div class="panel-body">
                    <div data-toc="toc" class="toc">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div data-toc="root-container">
        <h1 data-toc="element"><a name="home-screen"></a>Écran d'accueil</h1>
        <p>L'écran d'accueil affiche des boutons différents selon que la caisse est ouverte ou non.</p>
        <h2>Quand la caisse est fermée</h2>
        <p class="image"><img src="/img/help/home_closed.jpg"></p>
        <p>Voici les actions possibles :</p>
        <ul>
            <li><a href="#open-register">Ouvrir la caisse</a></li>
            <li><a href="#view-orders">Voir les fiches</a></li>
        </ul>
        <h2>Quand la caisse est ouverte</h2>
        <p class="image"><img src="/img/help/home_opened.jpg"></p>
        <p>Voici les actions possibles :</p>
        <ul>
            <li><a href="#new-order">Nouvelle inscription («&nbsp;check-in&nbsp;» d'un
                client)</a>
            </li>
            <li><a href="#view-orders">Voir les fiches</a></li>
            <li><a href="#cash-movement">Enregistrer une sortie/entrée d'argent</a></li>
            <li><a href="#close-register">Fermer la caisse</a></li>
        </ul>
        <hr>
        <h1 data-toc="element"><a name="open-register"></a>Ouvrir la caisse</h1>
        <p>Si la caisse a été fermée par l'aubergiste de la veille, vous devez l'ouvrir avant de pouvoir
            créer des nouvelles fiches.</p>
        <p>Depuis l'écran d'accueil, appuyez sur «&nbsp;Ouvrir la caisse&nbsp;». Vous verrez l'écran
            suivant
            :</p>
        <p class="alert alert-info">Si le bouton «&nbsp;Ouvrir la caisse&nbsp;» n'apparaît pas sur l'écran
            d'accueil (mais que
            vous voyez «&nbsp;Fermer la caisse&nbsp;»), la caisse est déjà ouverte.</p>
        <p class="image"><img src="/img/help/open_register.jpg"></p>
        <ul>
            <li><strong>Nom de l'employé(e)</strong>: écrivez le nom de l'aubergiste de la journée.
                Cette
                information est utilisée seulement pour avoir une idée de qui était l'aubergiste cette
                journée-là; ce n'est pas grave si une autre employée cré une fiche dans la journée, si
                vous êtes plusieurs aubergistes dans la journée (vous pouvez écrire plusieurs noms dans
                ce champ) ou si la même caisse restera ouverte plusieurs jours (et donc plusieurs
                aubergistes).
            </li>
            <li><strong>Argent dans la caisse</strong>: inscrivez le montant d'argent
                actuellement
                dans la caisse. Normalement, l'aubergiste de la veille devrait y avoir laissé 100 $.
                <strong>Vous pouvez laisser le montant de 100 $</strong>, sans compter la caisse. <em>Optionnel:</em>
                si vous le souhaitez, vous pouvez compter l'argent de la caisse et y indiquer le montant
                réel. Bien compter au moins deux fois pour éviter les erreurs.
            </li>
        </ul>
        <p>Appuyez sur «&nbsp;Ouvrir la caisse&nbsp;». Vous serez redirigé à l'écran d'accueil, mais les
            boutons
            auront changé (car la caisse est maintenant ouverte).</p>
        <hr>
        <h1 data-toc="element"><a name="new-order"></a>Nouvelle inscription/fiche («&nbsp;check-in&nbsp;» d'un client)</h1>
        <p class="warging">La caisse doit être ouverte pour pouvoir créer une nouvelle fiche.</p>
        <p class="alert alert-info">On fait une seule fiche par «&nbsp;groupe&nbsp;» de clients. Par
            exemple, faites
            une seule
            fiche dans les cas suivants: un couple dans la même chambre, deux personnes qui désirent
            prendre 2 chambres, un groupe scolaire qui prend 8 chambres. Ne faites pas une fiche par
            personne, faites une seule fiche. Nous inscrirons plus tard le nombre de personnes.</p>
        <p>Sur l'écran d'accueil, appuyer sur «&nbsp;Nouvelle inscription&nbsp;». L'écran suivant
            apparaîtra:</p>
        <p class="image"><img src="/img/help/new_order.jpg"></p>
        <h3><a name="add-items"></a>1) Ajouter les produits</h3>
        <p class="alert alert-info">Notez que les prix affichés inclus toutes les taxes. Certains sites de
            réservation
            (comme Booking.com) affichent au client les prix avant taxes, donc le client pourrait
            remarquer une différence de prix au moment de payer. Simplement lui indiquer que le prix
            affiché sur le site de réservation n'incluait pas les taxes (ces sites affichent un message
            avertissant que les taxes ne sont pas incluses).</p>
        <p>À droite, appuyez sur le ou les produits à ajouter. Les boutons bleus sont des produits, les
            boutons jaune-orange sont des catégories (appuyer dessus affiche d'autres produits). Le
            bouton vert est pour <a href="#special-item">ajouter un produit spécial</a>.</p>
        <p>Quand un produit est ajouté, il apparaît dans la partie gauche où l'on peut changer sa
            quantité (le chiffre à gauche du produit). Vous pouvez soit appuyer sur «&nbsp;+&nbsp;» et «&nbsp;-» pour
            changer sa quantité ou appuyer sur
            le chiffre pour faire apparaître un clavier numérique).</p>
        <p class="alert alert-info"><strong>Supprimer un produit:</strong> Pour supprimer un produit
            rajouté par erreur, faites le  glisser (glissez son nom) vers la gauche et
            un bouton «&nbsp;Retirer&nbsp;» apparaîtra. Notez qu'une fois la fiche enregistrée, il n'est
            plus possible de supprimer un produit, il pourra seulement
            <a href="#refund-item">être remboursé</a>.</p>
        <p class="alert alert-info">Il n'y a, ici, aucune différence entre «&nbsp;2 personnes en dortoir/1 nuit&nbsp;»
            et «&nbsp;1
            personne en dortoir/2 nuits&nbsp;» (donc un total de 2 dortoirs dans les deux cas). Dans les
            deux cas, vous appuierez une fois sur «&nbsp;Dortoir&nbsp;» et vous lui mettrez une quantité de
            2. Nous verrons dans la prochaine section comment enregistrer les chambres et le nombre de
            personnes par chambre.</p>
        <p>Certains produits ont un prix différent selon que le client est membre HI ou pas. Dans ce
            cas, quand le produit est rajouté, une liste déroulante apparaît permettant de choisir si on
            souhaite le prix membre ou non membre.</p>
        <p>Voici un exemple où un dortoir prix <em>non membre</em>, deux dortoirs prix
            <em>membre</em> et une carte de membre ont été achetés.</p>
        <p class="image"><img src="/img/help/sample_order.jpg"></p>
        <h3><a name="client-information"></a>2) Saisir les informations du client</h3>
        <p>Si les informations du client n'ont pas encore été saisies, un bouton vert
            «&nbsp;Saisir informations client&nbsp;» apparaît dans le bas de l'écran. Si les informations
            du client ont déjà été saisies,
            appuyez sur «&nbsp;Modifier les informations&nbsp;» dans le haut de la fiche, sous les informations
            du client.</p>
        <p class="image"><img src="/img/help/customer_buttons.jpg"></p>
        <p>Quand vous appuyez sur un de ces boutons, l'écran suivant apparaîtra.</p>
        <p class="image"><img src="/img/help/customer_modal.jpg"></p>
        <ul>
            <li><strong>Date d'arrivée et de départ</strong>: Appuyer dans ces champs pour modifier la
                date d'arrivée ou de départ du client. Il n'est pas possible de spécifier des dates non
                continues (ex.: le client vient ce lundi et ce jeudi, mais pas mardi et mercredi). Dans
                ce cas, il faudra faire deux
                fiches.
            </li>
            <li><strong>Liste des chambres et nombre de personnes:</strong> Sélectionnez la chambre où
                sera le client et le nombre de personnes dans la chambre. Noter que ceci est pour
                information seulement, ça n'a aucun impact sur le prix. Si plus d'une chambre sera
                utilisée, appuyez sur «&nbsp;Ajouter une chambre&nbsp;».
            </li>
            <li><strong>Nom complet:</strong> Nom complet du client. Ce champ est <strong>le seul
                    obligatoire</strong> (bien
                qu'il soit fortement recommandé de remplir les autres). Mettre le nom d'une seule
                personne. S'il s'agit d'un groupe, vous pouvez indiquer un nom descriptif (ex.:
                «&nbsp;Groupe d'impro Rimouski&nbsp;»).
            </li>
            <li><strong>Code postal:</strong> Ce champ doit être rempli seulement si le client réside au
                Canada, sinon il peut rester vide.
            </li>
            <li><strong>Province/pays:</strong> Si le client réside au Canada, sélectionnez sa province,
                sinon son pays. Les provinces et pays les plus utilisés sont affichés en premier, suivis
                des autres provinces et des autres pays.
            </li>
            <li><strong>Courriel et téléphone:</strong> Ces informations sont utilisées seulement si l'on
                a besoin de contacter le client pendant son séjour. Un seul des deux champs est
                nécessaire. Nous n'utilisons pas cette information pour de la promotion.
            </li>
            <li><strong>Membre HI ?:</strong> Si le client est membre HI, mettre ce champ à «&nbsp;
                Oui&nbsp;» et
                demandez à voir la carte. S'assurer qu'elle n'est pas expirée. Saisir le numéro de la
                carte dans le champ «&nbsp;Passeport ou # carte HI&nbsp;»
            </li>
            <li><strong>No. passeport ou membre HI:</strong> Si le client est membre HI, y indiquer
                son numéro de carte. Sinon, il est obligatoire pour les clients d'avoir une pièce
                d'identification avec photo et adresse (ou un passeport), <strong>mais</strong> nous ne
                demandons pas souvent cette information.&nbsp;C'est laissé à votre discrétion (donc si
                vous avez des doutes, vous pouvez demander une carte d'identité, sinon vous pouvez
                laisser faire).
            </li>
            <li><strong>Premier séjour dans cette auberge ?:</strong> Pour statistiques.</li>
            <li><strong>Dernière auberge visitée:</strong> Pour statistiques. Remplir le champ si la
                personne a visité une autre auberge récemment (ex.: dans le dernier mois), sinon laisser
                vide.
            </li>
        </ul>
        <p>Appuyer sur «&nbsp;Enregistrer&nbsp;» pour enregistrer et fermer cette fenêtre. Un résumé des
            informations du client apparaîtra dans le haut de la fiche. Vous pouvez modifier les
            informations à tout moment en appuyant sur le bouton «&nbsp;Modifier les informations&nbsp;» sous
            celles-ci.
        </p>
        <h3><a name="credit"></a>3) Enregistrer le dépôt (crédit) d'une réservation effectuée en ligne
                (ex.: Hostelworld)
        </h3>
        <p class="alert alert-info">Il n'y a aucun dépôt si la réservation a été effectuée par téléphone ou en
            personne. Également, il n'y a pas de dépôt pour la grande majorité des réservations
            Internet. Dans ces cas, passer à l'étape suivante. </p>
        <p>Certains sites de réservation (ex: Hostelworld, mais pas Booking.com) chargent un dépôt au
            client lors de sa réservation. Ce montant apparaîtra dans la feuille de la réservation et il
            devra être crédité du montant à payer.</p>
        <p class="alert alert-warning">Cette fonctionnalité de crédit (dépôt) ne doit être utilisée
            <strong>que pour des dépôts</strong>.
            Ne jamais l'utiliser pour faire des remboursements, pour corriger une
            erreur, pour enregistrer un paiement, etc.</p>
        <p>Dans le bas de l'écran, appuyez sur «&nbsp;Ajouter un crédit&nbsp;». La fenêtre suivante
            s'affichera:</p>
        <p class="image"><img src="/img/help/add_credit.jpg"></p>
        <ul>
            <li><strong>Description</strong>: S'il s'agit du dépôt d'une réservation en ligne, inscrivez
                le numéro de la réservation (qui apparaît dans la feuille de la réservation).
            </li>
            <li><strong>Montant:</strong> Le montant du dépôt (ne pas mettre le montant en négatif). Par
                exemple: 2,38.
            </li>
        </ul>
        <p>Appuyez sur «&nbsp;Enregistrer&nbsp;», cette fenêtre se fermera et le dépôt sera ajouté à la
            fiche.</p>
        <p class="alert alert-info"><strong><a name="edit-credit"></a>Modifier/supprimer un
            crédit:</strong> Pour modifier un
            crédit, appuyez dessus. Pour le supprimer, glissez-le vers la gauche et un
            bouton «&nbsp;Retirer&nbsp;» apparaîtra.</p>
        <h3><a name="save-payment"></a>4) Faire payer le client et enregistrer son paiement</h3>
        <p>Après que le client vous a payé (soit en argent ou par carte de crédit ou débit), il faut
            enregistrer son paiement dans l'application. Appuyez sur le bouton «&nbsp;Ajouter le
            paiement&nbsp;» dans le bas de l'écran. La fenêtre suivante apparaîtra:</p>
        <p class="alert alert-info">Le bouton «&nbsp;Ajouter le paiement&nbsp;» apparaît seulement si les
            informations du
            client ont été saisies, sinon c'est le bouton «&nbsp;Saisir informations client&nbsp;» qui apparaît
            à la place. Dans ce cas, commencez par saisir les informations du client.</p>
        <p class="image"><img src="/img/help/add_payment.jpg"></p>
        <ul>
            <li><strong>Mode de paiement</strong>: Sélectionnez le mode de paiement: carte
                (débit/crédit) ou argent.
            </li>
            <li><strong>Montant</strong>: Le montant du paiement. Par défaut, le montant total est
                indiqué, mais vous pouvez modifier ce montant (pour le séparer en deux, par
                exemple). <a href="#multiple-payments">Voir la note ci-dessous</a>.
            </li>
        </ul>
        <p>Appuyez sur «&nbsp;Enregistrer&nbsp;» pour enregistrer le paiement. La fenêtre se fermera et
            le paiement est ajouté à la fiche.</p>
        <p class="alert alert-info"><strong><a name="edit-payment"></a>Modifier/supprimer un
            paiement:</strong> Tant que la fiche
            n'est pas enregistrée, vous pouvez modifier un montant en appuyant dessus,
            et vous pouvez le supprimer en le glissant vers la gauche (un bouton «&nbsp;Retirer&nbsp;»
            apparaîtra). Mais une fois la fiche enregistrée, il ne sera plus possible de modifier ou de
            supprimer un paiement. Dans ce cas, laissez la fiche ainsi, mais rajoutez-lui une
            note expliquant l'erreur (dans le haut de la fiche, à droite, appuyez sur «&nbsp;Éditer les
            notes&nbsp;»).</p>
        <p class="alert alert-info"><strong><a name="multiple-payments"></a>Plus d'un paiement :</strong>
            Il est possible d'enregistrer
            plus d'un paiement pour une fiche. Exemples: chaque
            membre d'un groupe paie son lit séparément; ou un couple souhaite chacun payer la moitié
            d'une chambre privée. Dans ce cas, dans la fenêtre permettant d'enregistrer le paiement,
            modifiez la
            valeur du «&nbsp;Montant&nbsp;» pour y mettre le montant du premier paiement, enregistrez ce
            paiement
            et appuyez à nouveau sur le bouton «&nbsp;Enregistrer le paiement&nbsp;» pour enregistrer un
            autre paiement.</p>
        <h3>5) Enregistrer la fiche</h3>
        <p class="alert alert-info">Il est possible d'enregistrer une fiche même si elle n'a pas encore été
            totalement payée. Le paiement manquant pourra être rajouté plus tard.</p>
        <p>Pour enregistrer la fiche, appuyez sur le bouton «&nbsp;Enregistrer&nbsp;» dans le bas de
            l'écran. Si
            la fiche a été payée en totalité, ce bouton est vert. S'il reste un montant à payer, il est
            gris, mais il est quand même possible d'appuyer dessus (un avertissement vous demandant de
            confirmer s'affichera).</p>
        <hr>
        <h1 data-toc="element"><a name="view-orders"></a>Voir les fiches/retrouver une fiche</h1>
        <p>Vous pouvez retrouver des fiches créées précédemment pour voir leur détail ou pour les
            modifier.</p>
        <p>Depuis l'écran d'accueil, appuyez sur «&nbsp;Voir les fiches&nbsp;». L'écran qui s'affichera
            listera
            toutes les fiches créées, triées par date de départ. Cette liste vous permet, entre autres,
            de : </p>
        <ul>
            <li>Voir quels clients quittent aujourd'hui et lesquels restent une autre nuit.</li>
            <li>Voir quels clients n'ont pas encore payé en totalité leur fiche.</li>
            <li>En appuyant sur une fiche, voir le détail et pouvoir la modifier (pour rajouter un item
                ou faire un remboursement par exemple).
            </li>
        </ul>
        <p class="alert alert-info">Il est possible d'accéder à la liste des fiches même si la caisse est fermée,
            mais
            il ne sera pas possible d'enregistrer un paiement ou un remboursement avant que la caisse ne
            soit ouverte.</p>
        <p>Pour voir le détail d'une fiche ou pour la modifier, appuyez dessus.</p>
        <p class="alert alert-info">Il n'est malheureusement pas possible d'effectuer une recherche.</p>
        <hr>
        <h1 data-toc="element"><a name="close-register"></a>Fermer la caisse</h1>
        <p>À la fin de la journée (ou, en basse saison, à la fin de quelques jours), l'aubergiste doit
            fermer la caisse. Sur l'écran d'accueil, appuyez sur «&nbsp;Fermer la caisse&nbsp;» et
            l'écran suivant apparaîtra. </p>
        <p class="alert alert-info">Le bouton «&nbsp;Fermer la caisse&nbsp;» apparaît seulement si la caisse est
            ouverte.</p>
        <p class="image"><img src="/img/help/close_register.jpg"></p>
        <p>Sur la machine de cartes de crédit (le TPV &dash; terminal de point de vente), fermez le
            lot, prenez le reçu (le lot) et remplissez les champs suivants dans l'application :</p>
        <ul>
            <li><strong>Numéro de lot :</strong> Ce numéro apparaît sur le reçu de fermeture de lot du
                TPV.
            </li>
            <li><strong>Montant du lot :</strong> Inscrivez le montant du <em>grand total</em> indiqué
                sur le reçu du TPV.
            </li>
        </ul>
        <p>Ensuite, dans la caisse, comptez le nombre de chaque pièce et de chaque billet et remplissez
            les champs de l'écran. Ne soustrayez <strong>pas</strong> le fond de caisse (le montant de
            100 $ qui devra rester dans la caisse), comptez <strong>tous</strong>
            les billets et toutes les pièces.
        </p>
        <p>Recomptez une deuxième et troisième fois si nécessaire. Faites un effort honnête pour limiter
            les erreurs !</p>
        <p>Appuyez sur «&nbsp;Fermer la caisse&nbsp;». Vous serez redirigé à l'écran d'accueil.</p>
        <hr>
        <h1 data-toc="element"><a name="special-cases"></a>Cas particuliers</h1>
        <h2 data-toc="element"><a name="refund-item"></a>Rembourser un item</h2>
        <p class="alert alert-info">La caisse doit être ouverte pour enregistrer le remboursement.</p>
        <ol>
            <li>Depuis l'accueil, appuyez sur «&nbsp;Voir les fiches&nbsp;» et appuyez sur la fiche
                souhaitée.
            </li>
            <li>Glissez l'item à rembourser vers la gauche. Un bouton «&nbsp;Rembourser&nbsp;»
                apparaîtra. Appuyez dessus. Si c'est un bouton rouge «&nbsp;Retirer&nbsp;» qui
                apparaît, appuyez dessus et sautez à l'étape
                <a href="#if-must-redund">«&nbsp;Si vous devez rembourser ...&nbsp;»</a>.
            </li>
            <li>Une fenêtre apparaîtra vous demandant la quantité à rembourser (donc, il est possible de
                rembourser, par exemple, une seule nuit si la personne avait payé pour deux nuits).
            </li>
            <li>Appuyez «&nbsp;Enregistrer&nbsp;» et la fenêtre se fermera. Le remboursement sera ajouté
                à la liste des items (avec une quantité négative). Si vous n'avez pas encore
                enregistré la fiche, vous pouvez supprimer ce remboursement en le glissant vers la
                gauche.
            </li>
        </ol>
        <p><strong><a name="if-must-redund"></a>Si vous devez rembourser de l'argent au client:</strong>
            le montant à rembourser
            apparaîtra en jaune-orange dans le bas de l'écran avec la mention
            «&nbsp;À rembourser&nbsp;».</p>
        <ol>
            <li>Remboursez le client (dans la «&nbsp;vraie&nbsp;» vie !) avec la même méthode qu'il a
                initialement utilisée pour payer (en argent, sur la même carte de débit, de crédit,
                etc.).
            </li>
            <li>Appuyez sur le bouton vert «&nbsp;Ajouter le remboursement&nbsp;» dans le bas de l'écran.
                Assurez-vous de sélectionner le bon mode de remboursement.
            </li>
            <li><strong>Important :</strong> rajoutez une note à la fiche (appuyez sur le bouton étoilé
                «&nbsp;Éditer les notes&nbsp;» en haut à droite de la fiche) expliquant pourquoi un
                remboursement a été accordé.
            </li>
        </ol>
        <p class="alert alert-warning">Toujours rembourser le client sur <strong>la même
                carte</strong> que celle qu'il a utilisée (s'il a payé par carte). Toujours rembourser
            en argent s'il a payé en argent.
        </p>
        <h2 data-toc="element"><a name="client-stays-other-night"></a>Un client veut rester une
            autre nuit</h2>
        <p>Suivez les instructions de
            <a href="#add-product-to-existing-order">«&nbsp;Rajouter un produit à une fiche
                existante&nbsp;»</a>.</p>
        <h2 data-toc="element"><a name="add-product-to-existing-order"></a>Rajouter un produit à une fiche existante</h2>
        <p class="alert alert-info">La caisse doit être ouverte pour enregistrer le paiement.</p>
        <ol>
            <li>Depuis l'accueil, appuyez sur «&nbsp;Voir les fiches&nbsp;» et appuyez sur la fiche
                souhaitée.
            </li>
            <li>Dans l'écran de la fiche, appuyez, à droite, sur le produit à ajouter.</li>
            <li><a href="#save-payment">Faites payer le client et enregistrez le paiement</a>.</li>
            <li>Enregistrez la fiche.</li>
        </ol>
        <p class="alert alert-info">Si le client souhaite rester une autre nuit, n'oubliez pas de
            modifier la date de départ de la fiche dans <a href="#client-information">la fenêtre des
            informations du client</a>.</p>
        <p class="alert alert-info">De même, n'oubliez pas d'enregistrer toute modification de
            <a href="#edit-rooms">chambre ou du nombre de personnes</a>.</p>
        <h2 data-toc="element"><a name="edit-rooms"></a>Modifier le numéro de chambre/les chambres utilisées/le nombre de
                personnes</h2>
        <ol>
            <li>Depuis l'accueil, appuyez sur «&nbsp;Voir les fiches&nbsp;» et appuyez sur la fiche
                souhaitée.
            </li>
            <li>Appuyez sur «&nbsp;Modifier les informations&nbsp;» (en haut de la fiche, sous les informations du
                client).
            </li>
            <li>Modifiez le numéro de chambre ou le nombre de personnes.</li>
            <li>Si une chambre doit être rajoutée, appuyez sur «&nbsp;Ajouter une chambre&nbsp;».</li>
            <li>Si une chambre doit être retirée, glisser la ligne de la chambre vers la gauche pour
                faire apparaître le bouton «&nbsp;Supprimer&nbsp;».
            </li>
        </ol>
        <h2 data-toc="element"><a name="save-online-credit"></a>Enregistrer le dépôt d'une
            réservation faite en ligne</h2>
        <p><a href="#credit">Voyez les instructions dans «&nbsp;créer une nouvelle
            fiche&nbsp;».</a></p>
        <h2 data-toc="element"><a name="client-wants-multiple-payments"></a>Un client souhaite
            payer en 2 paiements (ou plus). Par exemple, un couple souhaite chacun
            payer la moitié du prix de la chambre privée.</h2>
        <p><a href="#multiple-payments">Voyez la note dans «&nbsp;Faire payer le client et
            enregistrer le paiement&nbsp;»</a></p>
        <h2 data-toc="element"><a name="client-stays-free"></a>Un client reste gratuitement (ex.:
            une employée d'une autre auberge)</h2>
        <ol>
            <li>Créez quand même une fiche. Saisisez toutes les informations du client, mais ne rajoutez
                aucun produit.
            </li>
            <li>Appuyez sur le bouton «&nbsp;Éditer les notes&nbsp;» en haut à droite pour y rajouter un
                commentaire
                expliquant la situation (par exemple: «&nbsp;Nuit gratuite car employée de l'auberge HI
                Québec&nbsp;»).
            </li>
        </ol>
        <h2 data-toc="element"><a name="special-item"></a>Prix spéciaux (ex.: prix de groupe, prix spécial pour un événement ou
                un client, etc.)
        </h2>
        <p class="alert alert-info">S'il s'agit d'un groupe, n'oubliez pas
            <a href="#edit-rooms">d'enregistrer toutes
                les chambres dans la même fiche</a>.</p>
        <ol>
            <li>Créez une fiche. S'il s'agit d'un groupe, ne faites qu'une seule fiche au nom du
                groupe.
            </li>
            <li>Dans la colonne de droite (où sont les boutons des produits), appuyez sur le bouton vert
                «&nbsp;Produit spécial&nbsp;».
            </li>
            <li>Dans la fenêtre qui apparaît, saisissez le nom du produit (ex.: «&nbsp;Dortoir groupe
                hockey&nbsp;») et indiquez le prix <strong>unitaire</strong> (ex.: le prix pour un seul
                lit, une seule nuit, un seul item, ...). La quantité sera spécifiée après.
                Appuyez «&nbsp;Enregistrer&nbsp;» pour fermer cette fenêtre et l'item sera rajouté à la
                liste des items.
            </li>
            <li>Modifiez sa quantité (par exemple, pour 3 lits en dortoirs à prix spécial et pour deux
                nuits, mettez la quantité «&nbsp;6&nbsp;»).
            </li>
        </ol>
        <p class="alert alert-info"><strong>Modifier le nom/prix</strong> Voyez la section
            «&nbsp;<a href="#error-special-item">J'ai mis le mauvais prix d'un produit spécial</a>&nbsp;».</p>
        <p class="alert alert-info">Si le total est payé en différents paiements (par exemple,
            chaque membre du groupe
            paie séparément son lit), voyez <a href="#multiple-payments">la note concernant les
            paiements multiples</a> dans la section sur le paiement.</p>
        <p class="alert alert-info">Cette fiche peut aussi contenir des items à prix réguliers. Il est fréquent
            qu'un
            groupe ait un prix spécial pour le dortoir, mais qu'un membre du groupe souhaite une chambre
            privée. Rajoutez tout simplement la chambre à cette fiche. </p>
        <h2 data-toc="element"><a name="cash-movement"></a>Enregistrer une sortie ou entrée d'argent (ex.: pour faire un
            achat en argent)</h2>
        <p class="alert alert-info">La caisse doit être ouverte pour pouvoir enregistrer une sortie ou un ajout
            d'argent.</p>
        <p>Vous devez enregistrer tout argent liquide (billet, monnaie) qui est ajouté ou retiré de la
            caisse autrement que par un paiement/remboursement. Exemple : de l'argent est pris dans la
            caisse pour faire une épicerie; un 25 sous a été trouvé sous la caisse et il est rajouté; de
            la monnaie est rajoutée à la caisse (sans que des billets soient retirés).</p>
        <ul>
            <li>Sur l'écran d'accueil, appuyer sur «&nbsp;Entrée/sortie d'argent&nbsp;».</li>
            <li>Dans l'écran qui apparaît, appuyez sur «&nbsp;Ajouter une opération&nbsp;».</li>
            <li>Dans la fenêtre qui s'ouvre, indiquer le type d'opération (sortie ou entrée d'argent
                selon que de
                l'argent a été retiré ou ajouté), le montant exact et une description
                (ex.: «&nbsp;Épicerie&nbsp;», «&nbsp;Ajout de monnaie&nbsp;»).
            </li>
            <li>Appuyez sur «&nbsp;Enregistrer&nbsp;» et l'opération sera rajoutée à la liste.</li>
        </ul>
        <p class="alert alert-info"><strong>Modifier ou supprimer une entrée :</strong> Vous ne pouvez
            pas modifier une entrée, vous devrez la supprimer et en créer une nouvelle. Vous pouvez la
            supprimer en la glissant
            vers la gauche et en appuyant sur «&nbsp;Supprimer&nbsp;». </p>
        <p class="alert alert-info">Ajoutez une entrée seulement pour la différence <em>réelle</em>
            d'argent. Par exemple, si vous prenez un billet de 20&nbsp;$ pour faire une épicerie et
            que vous remettez ensuite dans la caisse le change
            de 1,80&nbsp;$, ne faites qu'une seule entrée pour une sortie de 18,20&nbsp;$ &dash;
            ne faites <strong>pas</strong> une sortie de 20&nbsp;$ suivie d'une entrée de 1,80&nbsp;$.
            De  même, si vous échangez un billet de 20&nbsp;$ pour dix pièces de 2&nbsp;$,
            n'inscrivez <strong>aucune</strong> sortie ou entrée (le même montant d'argent est
            encore dans la caisse).
        </p>
        <hr>
        <h1 data-toc="element"><a name="faq"></a>FAQ</h1>
        <h2 data-toc="element"><a name="pay-later"></a>Est-ce qu'un client peut payer plus tard
            ?</h2>
        <p>Il est possible d'enregistrer une fiche sans avoir saisi tous les paiements.
            <a href="#view-orders">L'écran listant les fiches</a> montre également quelles fiches
            n'ont pas encore été payées en totalité.
            Par contre, évitez le plus possible cette situation pour éviter les oublis et les
            erreurs.</p>
        <h2 data-toc="element"><a name="close-everyday"></a>Doit-on fermer la caisse tous les jours
            ?</h2>
        <p>Oui, sauf en basse saison. Vérifiez avec le directeur de l'auberge.</p>
        <h2 data-toc="element"><a name="returning-client"></a>Un ancien client revient, est-ce que
            je dois refaire une nouvelle fiche ?</h2>
        <p>Oui, car il n'est pas possible d'avoir une fiche pour des journées non contigües. Il n'est
            malheureusement pas non plus possible «&nbsp;d'importer&nbsp;» les informations du client depuis une
            ancienne
            fiche, elles devront être saisies de nouveau (comme si c'était un nouveau client).</p>
        <h2 data-toc="element"><a name="receipt"></a>Le client souhaite avoir un reçu, comment
            faire ?</h2>
        <p>L'application ne permet pas d'imprimer ou d'envoyer des reçus par courriel.</p>
        <p>Vous devez le faire à l'extérieur de l'application. Par exemple, utiliser un carnet de
            reçus:</p>
        <ol>
            <li>Trouvez une nouvelle page du carnet et assurez-vous de mettre le carton séparateur entre
                ce reçu et le prochain (pour éviter d'écrire sur les prochains reçus).
            </li>
            <li>Il y a une étampe qui permet d'ajouter le nom, l'adresse de l'auberge et ses numéros de
                taxes.
                Utilisez là dans le haut du reçu.
            </li>
            <li>Sur la tablette, dans la fiche du client, se trouve dans bas de
                la fiche (à côté du mot «&nbsp;Payé&nbsp;» ou du montant à payer) un bouton
                «&nbsp;Détails&nbsp;».
                Appuyez dessus pour faire afficher le détail des taxes.
            </li>
            <li>Détaillez sur le reçu les items achetés, leur quantité et leur prix avant taxes.</li>
            <li>Dans le bas du reçu, indiquez le total des taxes (tel qu'indiqué dans la tablette) et le
                grand total.
            </li>
        </ol>
        <hr>
        <h1 data-toc="element"><a name="fix-error"></a>Corriger une erreur</h1>
        <h2 data-toc="element"><a name="error-member-non-member"></a>J'ai chargé le prix non
            membre, mais le client est membre</h2>
        <p>Si vous n'avez pas encore enregistré la fiche, simplement appuyer sur la liste déroulante de
            l'item pour choisir «&nbsp;Membre&nbsp;».</p>
        <p>Si la fiche a déjà été enregistrée, il n'est pas possible de modifier un item existant.
            <a href="#refund-item">Vous devez rembourser l'item incorrect</a> pour ensuite
            <a href="#add-items">rajouter le nouvel item</a>.</p>
        <p>Si le client doit être remboursé,
            <a href="#if-must-redund">effectuez le remboursement</a>. Notez que si la fiche n'a
            pas encore été enregistrée depuis le paiement, il peut être possible de simplement
            <a href="#edit-payment">supprimer le paiement</a>.</p>
        <h2 data-toc="element"><a name="error-credit-forgotten"></a>J'ai oublié de mettre le
            dépôt</h2>
        <p>Ouvrir la fiche et suivre les <a href="#credit">instructions pour rajouter un
            dépôt</a>.</p>
        <h2 data-toc="element"><a name="error-credit-amount"></a>J'ai mis le mauvais montant de
            dépôt</h2>
        <p>Ouvrir la fiche et suivre les <a href="#edit-credit">instructions pour modifier un
                dépôt</a>.</p>
        <h2 data-toc="element"><a name="error-item-quantity"></a>J'ai indiqué la mauvaise quantité
            pour un item</h2>
        <p>Si vous n'avez pas encore enregistré la fiche, simplement appuyer sur le «&nbsp;+&nbsp;» ou
            le «&nbsp;-», ou
            appuyer directement sur le chiffre de la quantité pour faire apparaître un clavier. Vous
            pouvez également glisser un item vers la gauche pour faire apparaître un bouton pour
            suppression.</p>
        <p>Si la fiche a déjà été enregistrée, il n'est pas possible de modifier la quantité d'un item
            déjà vendu. Vous devrez soit <a href="#refund-item">rembourser la quantité en trop</a>
            (remboursement partiel) ou rajouter à nouveau le produit pour augmenter la quantité.</p>
        <h2 data-toc="element"><a name="error-wrong-product"></a>J'ai mis le mauvais produit</h2>
        <p>Si la fiche n'a <em>pas encore</em> été enregistrée, glisser l'item vers la gauche. Un bouton
            «&nbsp;Retirer&nbsp;» apparaîtra.</p>
        <p>Si la fiche a déjà été enregistrée, vous pourrez seulement <a href="#refund-item">rembourser
            l'item</a></p>
        <h2 data-toc="element"><a name="error-special-item"></a>J'ai mis le mauvais nom/prix à un
            produit spécial</h2>
        <p>Si la fiche n'a pas encore été enregistrée, appuyez sur l'item du produit spécial. Une
            fenêtre apparaîtra vous permettant de modifier le nom et le prix du produit spécial.</p>
        <p>Si la fiche a été enregistrée, vous devrez premièrement <a href="#refund-item">rembourser
                l'item</a> et ensuite le <a href="#add-items">rajouter avec les bonnes informations</a>.
        <h2 data-toc="element"><a name="error-payment-mode"></a>J'ai indiqué le mauvais mode de
            paiement (ex.: «&nbsp;par carte&nbsp;» au lieu de «&nbsp;en argent&nbsp;») ou j'ai mis le mauvais montant.</h2>
        <p>Si la fiche n'a pas encore été enregistrée, appuyez sur la ligne du paiement. Une fenêtre
            apparaîtra vous permettant de modifier le mode de paiement et le montant. Vous pouvez
            également la glisser vers la gauche pour faire apparaître un bouton de suppression. </p>
        <p>Si la fiche a déjà été enregistrée, il n'est malheureusement pas possible de modifier les
            paiements déjà enregistrés. Dans ce cas, ajoutez une note à la fiche indiquant
            l'erreur en appuyant sur le bouton «&nbsp;Éditer les notes&nbsp;» en haut à droite de la fiche.</p>
        <hr>
        <h1 data-toc="element"><a name="autorisation"></a>Code d'autorisation et
            nouvelle tablette</h1>
        <h2 data-toc="element"><a name="autorisation-app-asks"></a>L'application demande un code
            d'autorisation</h2>
        <p class="image"><img src="/img/help/autorization.jpg"></p>
        <p>Cette situation ne devrait pas arriver (normalement), à moins d'utiliser une nouvelle
            tablette ou si la tablette a été désactivée depuis l'outil d'administration. </p>
        <ol>
            <li>Si vous n'êtes pas dans une de ces situations, commencez par quitter l'application en
                appuyant
                un ou plusieurs fois sur le bouton «&nbsp;retour&nbsp;» de la tablette (à côté du bouton
                principal de la tablette) jusqu'à ce que l'application se ferme.</li>
            <li>Ouvrez à nouveau l'application.</li>
        </ol>
        <p>Si le problème persiste, suivez les <a href="#autorize">instructions pour saisir un
                nouveau code d'autorisation</a>.</p>
        <h2 data-toc="element"><a name="autorize"></a>Saisir un nouveau code d'autorisation</h2>
        <p class="alert alert-info">Vous pouvez saisir un code d'autorisation seulement si la tablette le
            demande.</p>
        <p class="alert alert-warning"><strong>Attention:</strong> les étapes suivantes vont forcer la
            tablette à se déconnecter du système, donc ne faites pas ces étapes uniquement
            «&nbsp;pour tester&nbsp;».
        </p>
        <ol>
            <li>Connectez-vous, depuis n'importe quel ordinateur ou avec le navigateur web de la
                tablette, à l'outil de gestion à
                <a href="https://venteshirdl.com/login" target=_blank>https://venteshirdl.com</a>
                à l'aide des informations de connexion (voir cahier des codes «&nbsp;Guide d'utilisation
                des systèmes de réservation&nbsp;» ou ailleurs).
            </li>
            <li>Une fois connecté, dans le menu du haut, cliquez sur «&nbsp;Appareils&nbsp;».</li>
            <li>À droite de l'appareil appelé «&nbsp;Accueil&nbsp;», cliquez sur «&nbsp;Obtenir code
                d'autorisation&nbsp;».</li>
            <li>Un code à quatre chiffres apparaîtra. Inscrivez ce code dans le champ «&nbsp;Code
                d'autorisation&nbsp;»
                de l'écran d'authentification de la tablette.
            </li>
        </ol>
        <p class="alert alert-warning">Le code doit être saisi dans les minutes suivant son affichage,
            sinon il expirera et un nouveau code devra être généré.</p>
        <p class="alert alert-info">La tablette reprendra là où elle était. Si le code a été saisi sur une nouvelle
            tablette, elle reprendra également là où l'ancienne tablette était rendue. À noter qu'une
            seule tablette peut être utilisée à la fois.</p>
        <h2 data-toc="element"><a name="autorisation-new-tablet"></a>Une nouvelle tablette Android
            est utilisée</h2>
        <ol>
            <li>Si l'application n'est pas déjà installée sur la nouvelle tablette, suivez les
                instructions suivantes :
                <ol>
                    <li>Permettez à la tablette d'installer des applications de sources inconnues
                        (par exemple, recherchez sur Google «&nbsp;android autoriser sources inconnues&nbsp;»).
                    </li>
                    <li>Depuis le navigateur web de <strong>la tablette</strong>, visitez
                        <a href="https://venteshirdl.com/app.apk" target="_blank"
                            >https://venteshirdl.com/app.apk</a>.
                        Ceci va télécharger l'application sur la tablette.
                    </li>
                    <li>Une fois le téléchargement terminé, exécutez le fichier téléchargé (si le
                        navigateur n'offre pas automatiquement cette option, allez dans les fichiers
                        téléchargés de la tablette et appuyez sur l'application).
                    </li>
                    <li>L'application devrait maintenant être installée.</li>
                    <li>Il est également recommandé de rajouter sur le bureau de la tablette un
                        lien vers cette page de documentation (la page que vous lisez présentement).
                    </li>
                </ol>
            <li>Si vous ouvrez l'application, elle devrait vous demander un code d'autorisation. Suivez
                les <a href="#autorize">instructions pour en générer un nouveau</a>.
            </li>
        </ol>
        <h2 data-toc="element"><a name="autorisation-stolen-tablet"></a>La tablette est volée ou
            perdue de façon permanente</h2>
        <p>Suivez les <a href="#autorize">instructions pour générer un nouveau code</a>. Ceci aura
            pour effet de déconnecter les tablettes qui seraient encore connectées.</p>
    </div>
@endsection
