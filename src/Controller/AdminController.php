<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\User;
use App\Form\UserImportType;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration', name: 'admin')]
final class AdminController extends AbstractController
{
    #[Route('/interface', name: '_interface')]
    public function index(EventRepository $eventRepository, UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $events = $eventRepository->findAll();


        return $this->render('admin/interface.html.twig', ['events' => $events, 'users' => $users]);
    }

    #[Route('/{id}/desactiver', name: '_desactivate')]
    public function desactivate(User $user, EntityManagerInterface $em): Response
    {

        $user->setIsActive(false);
        $em->flush();

        $this->addFlash('success', "L'utilisateur {$user->getName()} a bien été désactivé.");
        return $this->redirectToRoute('admin_interface');
    }

    #[Route('/{id}/reactiver', name: '_reactivate')]
    public function reactivate(User $user, EntityManagerInterface $em): Response
    {

        $user->setIsActive(true);
        $em->flush();

        $this->addFlash('success', "L'utilisateur {$user->getName()} a bien été réactivé.");
        return $this->redirectToRoute('admin_interface');
    }

    #[Route('/{id}/supprimer', name: '_delete')]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('danger', "L'utilisateur avec l'ID $id n'existe pas.");
            return $this->redirectToRoute('admin_interface');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', "L'utilisateur {$user->getName()} a bien été supprimé.");
        return $this->redirectToRoute('admin_interface');
    }

    #[Route('/import', name: '_import')]
    public function import(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SessionInterface $session
    ): Response
    {
        $form = $this->createForm(UserImportType::class);
        $form->handleRequest($request);

        $csvPreview = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('csvFile')->getData();

            if (!$file) {
                $this->addFlash('error', 'Fichier non trouvé.');
                return $this->redirectToRoute('admin_import');
            }

            // Sauvegarder le fichier temporairement
            $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $tempFileName = 'import_' . uniqid() . '.csv';
            $tempFilePath = $uploadDir . $tempFileName;
            $file->move($uploadDir, $tempFileName);

            // Stocker le chemin en session
            $session->set('import_file_path', $tempFilePath);

            // Prévisualisation
            $csvPreview = $this->previewCSV($tempFilePath);
        }
        // Si on confirme l'import avec un fichier déjà uploadé
        elseif ($request->request->get('action') === 'import') {
            $tempFilePath = $session->get('import_file_path');

            if ($tempFilePath && file_exists($tempFilePath)) {
                try {
                    $result = $this->importUsers($tempFilePath, $em, $passwordHasher);
                    $this->addImportMessage($result);

                    // Nettoyer le fichier temporaire
                    unlink($tempFilePath);
                    $session->remove('import_file_path');

                    return $this->redirectToRoute('admin_import');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'import : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Fichier temporaire introuvable.');
            }
        }
        // Si on annule (changer de fichier)
        elseif ($request->request->get('action') === 'cancel') {
            $tempFilePath = $session->get('import_file_path');
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            $session->remove('import_file_path');
            return $this->redirectToRoute('admin_import');
        }

        return $this->render('admin/import.html.twig', [
            'form' => $form->createView(),
            'csvPreview' => $csvPreview
        ]);
    }

    private function previewCSV(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        // Lecture du header
        $headerLine = fgets($handle);
        $headerLine = preg_replace('/\x{FEFF}/u', '', $headerLine); // supprime BOM
        $header = array_map('trim', str_getcsv($headerLine, ';'));

        $preview = [
            'header' => $header,
            'rows' => [],
            'total_lines' => 0,
            'empty_lines' => 0,
            'errors' => []
        ];

        $lineNumber = 1;
        $maxPreviewRows = 10; // Limite d'affichage

        while (($data = fgetcsv($handle, 1000, ';')) !== false) {
            $lineNumber++;
            $preview['total_lines']++;

            $data = array_map('trim', $data);

            // Compter les lignes vides
            if (empty(array_filter($data))) {
                $preview['empty_lines']++;
                continue;
            }

            // Vérifier nombre de colonnes
            if (count($data) !== count($header)) {
                $preview['errors'][] = "Ligne $lineNumber : nombre de colonnes incorrect";
                continue;
            }

            $row = array_combine($header, $data);

            // Vérifications basiques
            if (empty($row['email'])) {
                $preview['errors'][] = "Ligne $lineNumber : email manquant";
            }

            // Ajouter à la prévisualisation (limité)
            if (count($preview['rows']) < $maxPreviewRows) {
                $preview['rows'][] = [
                    'line' => $lineNumber,
                    'data' => $row
                ];
            }
        }

        fclose($handle);
        return $preview;
    }

    private function importUsers(string $filePath, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): array
    {
        $handle = fopen($filePath, 'r');

        // Lecture du header
        $headerLine = fgets($handle);
        $headerLine = preg_replace('/\x{FEFF}/u', '', $headerLine); // supprime BOM
        $header = array_map('trim', str_getcsv($headerLine, ';'));

        $imported = 0;
        $skipped = 0;
        $errors = [];

        while (($data = fgetcsv($handle, 1000, ';')) !== false) {
            try {
                if ($this->createUserFromLine($data, $header, $em, $passwordHasher)) {
                    $imported++;

                    // Sauvegarde tous les 50 utilisateurs
                    if ($imported % 50 === 0) {
                        $em->flush();
                    }
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = $e->getMessage();
            }
        }

        fclose($handle);
        $em->flush(); // Sauvegarde finale

        return compact('imported', 'skipped', 'errors');
    }

    private function createUserFromLine(array $data, array $header, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): bool
    {
        $data = array_map('trim', $data);

        // Ignorer lignes vides
        if (empty(array_filter($data))) {
            return false;
        }

        // Vérifier nombre de colonnes
        if (count($data) !== count($header)) {
            throw new \Exception('Nombre de colonnes incorrect');
        }

        $row = array_combine($header, $data);

        // Vérifier email obligatoire
        if (empty($row['email'])) {
            throw new \Exception('Email manquant');
        }

        // Vérifier si utilisateur existe déjà
        if ($em->getRepository(User::class)->findOneBy(['email' => $row['email']])) {
            throw new \Exception("Utilisateur {$row['email']} existe déjà");
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($row['email']);
        $user->setPseudo($row['pseudo'] ?? '');
        $user->setName($row['name'] ?? '');
        $user->setFirstName($row['firstName'] ?? '');
        $user->setPhone($row['phone'] ?? '');

        // Rôles
        $roles = !empty($row['roles']) ? explode(',', $row['roles']) : ['ROLE_USER'];
        $user->setRoles($roles);

        // Mot de passe
        $password = !empty($row['password']) ? $row['password'] : '123456';
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        // Booléens (1/0 ou true/false)
        $user->setIsAdmin($row['isAdmin'] === '1' || $row['isAdmin'] === 'true');
        $user->setIsActive($row['isActive'] === '1' || $row['isActive'] === 'true');

        // Site (optionnel)
        if (!empty($row['site'])) {
            $site = $em->getRepository(Site::class)->findOneBy(['name' => $row['site']]);
            if ($site) {
                $user->setSite($site);
            }
        }

        $em->persist($user);
        return true;
    }

    private function addImportMessage(array $result): void
    {
        $message = "Import terminé. Importés : {$result['imported']}. Ignorés : {$result['skipped']}.";

        if (!empty($result['errors'])) {
            $message .= ' Erreurs : ' . implode(' | ', array_slice($result['errors'], 0, 3)); // Max 3 erreurs
            if (count($result['errors']) > 3) {
                $message .= '...';
            }
        }

        $this->addFlash('success', $message);
    }


}
