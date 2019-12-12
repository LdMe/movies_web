<?php

namespace App\Controller;
use App\Entity\Movie;
use App\Entity\MoviesList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use App\Form\MovieFindType;
class MovieApiController extends AbstractController
{
    /**
     * @Route("/movie", name="movie_api")
     */
    public function index(Request $request)
    {
        $usr= $this->getUser();
        $choices=[];
        foreach ($usr->getLists() as $key => $value) {
            $choices[$value->getName() ] = $value->getId();
        }

        $id=$request->query->get("movie_id");
        if($id ==null){
            $id=$request->query->get("form")["movie_id"];
        }
        $movie = $this->getDoctrine()->getRepository(Movie::class)->find($id);
        $form= $this->createFormBuilder($movie)->setAction($this->generateUrl('movie_api'))
            ->setMethod('GET')
            ->add('list_id', ChoiceType::class,['mapped'=> false,'choices'=> $choices,])
            ->add('movie_id',HiddenType::class,['mapped'=> false,'data'=>$id])
            ->add('add', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted()){
            $lista = $this->getDoctrine()->getRepository(MoviesList::class)->find($request->query->get("form")["list_id"]);
            $in_list= false;
            foreach ($lista->getMovies() as $key => $value) {
                if($value->getId()== $id){
                    $in_list=true;
                }
            }
            if(!$in_list){
                $lista->addMovie($movie);
                echo "movie added to list";
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($lista);
                $entityManager->flush();
            }

        }
        return $this->render('movie_api/index.html.twig', [
            'movieFindForm' => $form->createView(),
        
            'movie' => $movie,
        ]);
    }
    /**
     * @Route("/movie/search", name="movie_search")
     */
    public function search(Request $request): Response
    {
        $movie = new Movie();
    	$form = $this->createForm(MovieFindType::class, $movie);
        $form->handleRequest($request);
        $status=$request->query->get("statusCode");
        if($status){
            echo "movie not found";
        }
        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $client = HttpClient::create();
			$response = $client->request('GET', 'http://www.omdbapi.com/?t='. $form->get('Title')->getData() .'&apikey=7e05e929');

			$statusCode = $response->getStatusCode();
			// $statusCode = 200
			$contentType = $response->getHeaders()['content-type'][0];
			// $contentType = 'application/json'
			$content = $response->getContent();
			// $content = '{"id":521583, "name":"symfony-docs", ...}'
			$content = $response->toArray();
			// $content = ['id' => 521583, 'name' => 'symfony-docs', ...]

            if($content["Response"]=="False"){
                echo "movie not found";

                return $this->redirectToRoute("movie_search",["statusCode"=>404]);
            }
            $repo= $this->getDoctrine()->getRepository(Movie::class);
            $movie= $repo->findOneBy(['Title' =>$content["Title"]]);
            if($movie== null){
                $title= $content["Title"];
                $summary=$content["Plot"];
                $image= $content["Poster"];
                $year=$content["Released"];
                $movie= new Movie();
                $movie->setTitle($title);
                $movie->setSummary($summary);
                $movie->setImage($image);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($movie);
                $entityManager->flush();
            }
			
            return $this->redirectToRoute("movie_api", ['movie_id' => $movie->getId()], 301);
           
        }

        return $this->render('movie_api/search.html.twig', [
            'movieFindForm' => $form->createView(),
        ]);

		
		//$movie->setYear($year);
		    return $this->index();

	}


}
