<?php

namespace App\Controller;

use App\Entity\MoviesList;
use App\Form\ListFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MoviesListController extends AbstractController
{
    /**
     * @Route("/favorites", name="movies_list")
     */
    public function index()
    {
        return $this->render('movies_list/index.html.twig', [
            'controller_name' => 'MoviesListController',
        ]);
    }
    /**
     * @Route("/favorites/new", name="movies_list_new")
     */
    public function register(Request $request):Response
    {
    	$lista= new MoviesList();
    	$form = $this->createForm(ListFormType::class, $lista);
    	$form->handleRequest($request);
    	if ($form->isSubmitted() && $form->isValid()) {
    		$exists=false;
    		foreach ($this->getUser()->getLists() as  $list) {
    			if($list->getName()==$form->get('name')->getData()) {
    				$exists =true;
    			}
    		}
    		if(! $exists) {
    			$lista->setName($form->get('name')->getData());
    		$lista->addUser($this->getUser());
    		$entityManager = $this->getDoctrine()->getManager();
    		
            $entityManager->persist($lista);
            $entityManager->flush();
    		}
    		return $this->redirectToRoute("app_index",["statusCode"=>301]);
    	}
    	return $this->render('movies_list/index.html.twig', [
            'listCreateForm' => $form->createView(),
        ]);
    }
}
