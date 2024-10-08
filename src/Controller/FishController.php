<?php

namespace App\Controller;

use App\Entity\Fish;
use App\Form\AddFishType;
use App\Form\FilterParametersType;
use App\Form\SearchType;
use App\Repository\FishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class FishController extends AbstractController
{
    // AFFICHAGE DE TOUTES LES FICHES OU DE CELLES QUI CORRESPONDENT A LA RECHERCHE
    #[Route('/poissons', name: 'fishes_list', methods: ['GET'])]
    public function list(Request $request, FishRepository $fishRepository, UrlGeneratorInterface $urlGenerator): Response
    {
        $fishes = $fishRepository->findAllByDescendingId();
        $visibleFishes = array_filter($fishes, function($fish) {
            return $fish->isVisible();
        });

        $familiesCount = $fishRepository->countByFamily();
        $originsCount = $fishRepository->countByOrigin();

        //BARRE DE RECHERCHE
        $searchForm = $this->createForm(SearchType::class);
        $searchForm->handleRequest($request);

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            // Récupérer les données du formulaire
            $data = $searchForm->getData();
            $query = $data['query'];

            // Effectuer la recherche dans la base de données avec la fonction findBySearchQuery
            $fishes = $fishRepository->findBySearchQuery($query);
            $visibleFishes = array_filter($fishes, function($fish) {
                return $fish->isVisible();
            });

            if (empty($fishes)) {
                $this->addFlash('info', 'Aucun poisson trouvé pour cette recherche.');
            }
        }

        //RECHERCHE PAR FILTRES AU NIVEAU DES PARAMETRES
        $filterForm = $this->createForm(FilterParametersType::class);
        $filterForm->handleRequest($request);

        $criteria = [];

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $data = $filterForm->getData();

            // Gestion de la température
            if ($data['temperature']) {
                $tempRange = explode('-', $data['temperature']);
                $criteria['minTemp'] = $tempRange[0];
                $criteria['maxTemp'] = $tempRange[1];
            }

            // Gestion du pH
            if ($data['ph']) {
                $phRange = explode('-', $data['ph']);
                $criteria['minPh'] = $phRange[0];
                $criteria['maxPh'] = $phRange[1];
            }

            // Gestion du GH
            if ($data['gh']) {
                $ghRange = explode('-', $data['gh']);
                $criteria['minGh'] = $ghRange[0];
                $criteria['maxGh'] = $ghRange[1];
            }

            // Gestion de la taille adulte
            if ($data['adultSize']) {
                $sizeRange = explode('-', $data['adultSize']);
                $criteria['minAdultSize'] = $sizeRange[0];
                $criteria['maxAdultSize'] = $sizeRange[1] ?? null;
            }

            $fishes = $fishRepository->findByFilters($criteria);
            $visibleFishes = array_filter($fishes, function($fish) {
                return $fish->isVisible();
            });

        }

        // Générer l'URL actuelle sans les paramètres GET pour la réinitialisation
        $currentUrl = $urlGenerator->generate($request->attributes->get('_route'), [], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->render('fish/list.html.twig', [
            'searchForm' => $searchForm->createView(),
            'filterForm' => $filterForm->createView(),
            'fishes' => $visibleFishes,
            'familiesCount' => $familiesCount,
            'originsCount' => $originsCount,
            'currentUrl' => $currentUrl,  // URL sans paramètres GET pour la réinitialisation
            ]);

    }


    // AFFICHAGE DE LA FICHE D'UN POISSON (ajout d'une contrainte "nombre" sur l'id pr éviter un problème avec la route '/poissons/add' )
    #[Route('/poissons/{id}', name: 'fish_item', requirements: ['id' => '\d+'])]
    public function item(Fish $fish): Response
    {   

         return $this->render('fish/item.html.twig', [
                'fish' => $fish,
        ]);     // ERREUR 404 générée automatiquement

    }


    //AJOUT D'UNE FICHE DE POISSON
    #[Route('/poissons/ajout', name: 'fishes_add', methods: ['GET', 'POST'])]
    public function addFish(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ): Response
    {   
        $fish = new Fish();
        $form = $this->createForm(AddFishType::class, $fish);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $imageName **/
            $imageName = $form->get('imageName')->getData();

            if ($imageName) {
                $originalFilename = pathinfo($imageName->getClientOriginalName(), PATHINFO_FILENAME);

                $safeFilename = $slugger->slug($originalFilename);

                $filename = $safeFilename . '-' . uniqid() . '.' . $imageName->guessExtension();

                try {
                    $imageName->move(
                        'uploads/fishes/',
                        $filename
                    );
                    // Si on n'est pas passé dans le catch, alors on peut enregistrer le nom du fichier
                    // dans la propriété profilePicFilename de l'utilisateur
                    $fish->setPicFilename($filename);
                } catch (FileException $e) {
                    $form->addError(new FormError("Erreur lors de l'upload du fichier"));
                }
            }
            
            $em->persist($fish);
            $em->flush();

            $this->addFlash('success', 'Votre fiche a bien été ajoutée. Merci!');
            
            return $this->redirectToRoute('fish_item', [
                'id' => $fish->getId(),
            ]);

        }    

        return $this->render('fish/add.html.twig', [
                'addFish' => $form,
        ]);     // ERREUR 404 générée automatiquement

    }


    // #[Route('/poissons/{name}', name: 'fishfamily_item')]
    // public function familylist(FishRepository $fishRepository, FishFamily $fishfamily): Response
    // {      
    //     $fishes = $fishRepository->findBy(family_id)

    //     return $this->render('fish/list.html.twig', [
    //             'fishes' => $fishes,
    //     ]);     // ERREUR 404 générée automatiquement

    // }


}
