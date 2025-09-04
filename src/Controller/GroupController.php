<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Group;
use App\Form\EventType;
use App\Form\GroupType;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GroupController extends AbstractController
{
    private GroupRepository $groupRepository;
    private EntityManagerInterface $em;

    public function __construct(GroupRepository $groupRepository, EntityManagerInterface $em)
    {
        $this->groupRepository = $groupRepository;
        $this->em = $em;
    }

    #[Route('/groups', name: 'groups')]
    public function index(): Response
    {
        $groupFounder = $this->getUser();
        // Get groups from connected user
        $groups = $this->groupRepository->findBy(['groupFounder' => $groupFounder]);
        return $this->render('group/index.html.twig', [
            'groups' => $groups,
        ]);
    }

    // Requirement id for mgt of creation / modification of groups
    #[Route('/groups/edit/{id}', name: 'group_edit', requirements: ['id' => '\d+'], defaults: ['id' => null])]
    public function editGroup(?Group $group, Request $request): Response
    {
        if (!$group) {
            $group = new Group();
        }

        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group->setGroupFounder($this->getUser());
            $this->em->persist($group);
            $this->em->flush();

            $this->addFlash('success', "Le groupe {$group->getName()} a bien été créé");

            return $this->redirectToRoute('group_detail', ['id' => $group->getId()]);
        }

        return $this->render('group/edit.html.twig', [
            'form' => $form->createView(),
            'group' => $group,
        ]);
    }

     #[\Symfony\Component\Routing\Attribute\Route('/group/detail/{id}', name: 'group_detail', requirements: ['id' => '\d+'])]
    public function detail(Group $group): Response
    {
        return $this->render('group/detail.html.twig', [
            "group" => $group,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/group/delete/{id}', name: 'group_delete', requirements: ['id' => '\d+'])]
    public function delete(Group $group): Response
    {
        $user = $this->getUser(); //connected user

        if ($user === $group->getGroupFounder()) {
            $this->em->remove($group);
            $this->em->flush();
            $this->addFlash('success', "Le groupe {$group->getName()} a bien été supprimé");
        }
        return $this->redirectToRoute("groups");
    }

}
