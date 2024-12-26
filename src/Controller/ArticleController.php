<?php
namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ArticleController extends AbstractController
{
    ////////////////////////////////////////
    ///////////// CREATE ARTICLE ///////////
    ////////////////////////////////////////

    #[Route('/article/creer', name: 'article_create')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imagesDirectory
    ): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            // this condition is needed because the 'image' field is not required
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                // Move the file to the directory where images are stored
                try {
                    $imageFile->move($imagesDirectory, $newFilename);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                // instead of its contents
                $article->setImage($newFilename);
            }

            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Votre article ' . $article->getId() . ' a été ajouté');

            return $this->redirectToRoute('article_liste');
        }

        return $this->render('article/creer.html.twig', [
            'controller_name' => 'ArticleController',
            'form' => $form->createView(),
        ]);
    }

    ////////////////////////////////////////
    ///////////// LIST ARTICLE /////////////
    ////////////////////////////////////////

    #[Route('article/liste', name: 'article_liste')]
    public function show(EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->findAll();

        // dd($article);

        return $this->render('article/liste.html.twig', [
            'controller_name' => 'ArticleController',
            'article' => $article,
        ]);
    }

    ////////////////////////////////////////
    ///////////// EDIT ARTICLE /////////////
    ////////////////////////////////////////

    #[Route('/article/modifier/{id}', name: 'article_edit')]
    #[IsGranted('ROLE_USER')]

    public function update(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'L\'article d\'id '. $id . ' n\'existe pas'
            );
        }

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();
            $this->addFlash('success','Votre article ' . $article->getId() .' a été modifié');
        }

        return $this->render('article/modifier.html.twig', [
            'controller_name' => 'ArticleController',
            'form' => $form,
        ]);
    }
       

    ////////////////////////////////////////
    ///////////// DELETE ARTICLE ///////////
    ////////////////////////////////////////

    #[Route('/article/supprimer/{id}', name:'supprimer_article')]
    #[IsGranted('ROLE_USER')]

    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'No article found for id '.$id
            );
        }

        $entityManager->remove($article);
        $entityManager->flush();

        return $this->render('article/supprimer.html.twig', [
            'controller_name' => 'ArticleController',
            'article' => $article,
        ]);
    }  
}