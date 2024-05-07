<?php

namespace App\Controller;

use LogicException;
use App\Entity\ToyRequest;
use App\Form\ToyRequestType;
use App\Repository\ToyRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Bridge\Twig\Extension\WorkflowExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ToyRequestController extends AbstractController
{

    public function __construct(private Registry $registry)
    {
        $this->registry = $registry;
    }
    #[Route('/new', name: 'app_new')]

    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $toy = new ToyRequest();

        $toy->setUser($this->getUser());

        $workflow = $this->registry->get($toy, 'to_pending');
        dd($workflow);

        $form = $this->createForm(ToyRequestType::class, $toy);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {


            $toy = $form->getData();

            try {
                $this->registry->get($toy, 'to_pending');
            } catch (LogicException $exception) {
                //
            }

            $entityManager->persist($toy);
            $entityManager->flush();

            $this->addFlash('success', 'Demande enregistrée !');

            return $this->redirectToRoute('app_new');
        }


        return $this->render('toy_request/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/parent', name: 'app_parent')]
    public function parent(ToyRequestRepository $toyRequestRepository): Response
    {
        return $this->render('toy_request/parent.html.twig', [
            'toys' => $toyRequestRepository->findAll(),
        ]);
    }

    #[Route('/change/{id}/{to}', name: 'app_change')]
    public function change(ToyRequest $toyRequest, String $to, EntityManagerInterface $entityManager): Response
    {
        try {
            $this->registry->get($toyRequest, $to);
        } catch (LogicException $exception) {
            //
        }

        $entityManager->persist($toyRequest);
        $entityManager->flush();

        $this->addFlash('success', 'Action enregistrée !');

        return $this->redirectToRoute('app_parent');
    }
}