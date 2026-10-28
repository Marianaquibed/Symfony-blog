<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Post;
use App\Form\PostFormType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Filesystem\Filesystem;

#[IsGranted('ROLE_USER')]
final class PostController extends AbstractController
{
    #[Route('/blog/new', name: 'new_post')]
    public function newPost(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData(); 
            $image = $form->get('image')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                // El Slugger hace que el nombre del archivo sea seguro en cuanto a 
                // caracteres especiales como espacios o acentos
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                // El servidor almacena el archivo en un directorio temporal y
                // debemos moverlo a su ubicación definitiva, dentro de una ruta que
                // hemos definido en los parámetros de configuración (services.yaml)
                // y que debe existir previamente dentro de la carpeta `public` proyecto
                try {

                    // Primero lo movemos al directorio de imágenes
                    $image->move(
                        $this->getParameter('images_directory'), $newFilename
                    );
                    $filesystem = new Filesystem();
                    // Y ahora lo duplicamos en el directorio de portfolio
                    $filesystem->copy(
                        $this->getParameter('images_directory') . '/'. $newFilename, 
                        $this->getParameter('portfolio_directory') . '/'.  $newFilename, true);

                } catch (FileException $e) {
                    return new Response("Error al subir el archivo: " . $e->getMessage());
                }

                // asignamos el nombre del archivo, que se llama `file`, a la entidad Image
                $post->setImage($newFilename);
            }

            // Quitamos los caracteres especiales del título para crear el slug
            $post->setSlug(strtolower($slugger->slug($post->getTitle())));
            
            // Guardamos el usuario que crea el post y los contadores a 0            $post->setPostUser($this->getUser());
            $post->setPostUser($this->getUser());
            $post->setNumLikes(0);
            $post->setNumComments(0);

            $entityManager = $doctrine->getManager();    
            $entityManager->persist($post);
            $entityManager->flush();
            return $this->redirectToRoute('new_post');
        }

        return $this->render('blog/new_post.html.twig', [
            'form' => $form->createView()    
        ]);
    }
    #[Route('/single_post/{slug}', name: 'single_post')]
    public function post(ManagerRegistry $doctrine, $slug): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $post = $repository->findOneBy(["slug"=>$slug]);
        $recents = $repository->findRecents();
        return $this->render('blog/single_post.html.twig', [
            'post' => $post,
            'recents' => $recents
        ]);
    }
    #[Route('/blog/{page}', name: 'blog', requirements: ['page' => '\d+'])]
    public function index(ManagerRegistry $doctrine, int $page = 1): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $posts = $repository->findAll($page);

        return $this->render('blog/blog.html.twig', [
            'posts' => $posts,
        ]);
    }
}
