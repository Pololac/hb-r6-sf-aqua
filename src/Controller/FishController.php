<?php

namespace App\Controller;

use App\Entity\Fish;
use App\Form\AddFishType;
use App\Form\SearchType;
use App\Repository\FishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FishController extends AbstractController
{
    // AFFICHAGE DE TOUTES LES FICHES OU DE CELLES QUI CORRESPONDENT A LA RECHERCHE
    #[Route('/poissons', name: 'fishes_list', methods: ['GET'])]
    public function list(Request $request, FishRepository $fishRepository): Response
    {
        $fishes = $fishRepository->findAll();

        //BARRE DE RECHERCHE
        $form = $this->createForm(SearchType::class);
                
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données du formulaire
            $data = $form->getData();
            $query = $data['query'];

            // Effectuer la recherche dans la base de données avec la fonction findBySearchQuery
            $fishes = $fishRepository->findBySearchQuery($query);

            if (empty($fishes)) {
                $this->addFlash('info', 'Aucun poisson trouvé pour cette recherche.');
            }

           return $this->render('fish/list.html.twig', [
            'form' => $form->createView(),
            'fishes' => $fishes,
            ]);
        }


        return $this->render('fish/list.html.twig', [
            'form' => $form->createView(),
            'fishes' => $fishes,
        ]);
    }

    // AFFICHAGE DE LA FICHE D'UN POISSON
    #[Route('/poisson/{id}', name: 'fish_item')]
    public function item(Fish $fish): Response
    {        
         return $this->render('fish/item.html.twig', [
                'fish' => $fish,
        ]);     // ERREUR 404 générée automatiquement

    }

    //AJOUT D'UNE FICHE DE POISSON
    #[Route('/poissons/add', name: 'fishes_add', methods: ['GET', 'POST'])]
    public function addFish(
        Request $request,
        EntityManagerInterface $em,
        ): Response
    {   
        $fish = new Fish();
        $form = $this->createForm(AddFishType::class, $fish);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($fish);
            $em->flush();

            $this->addFlash('success', 'Votre fiche a bien été ajoutée. Merci!');
            
            return $this->render('fish/item.html.twig', [
                'fish' => $fish,
        ]);     // ERREUR 404 générée automatiquement

            // return $this->redirectToRoute('add_confirm');
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
