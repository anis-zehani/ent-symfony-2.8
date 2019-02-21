<?php

namespace PronoteBundle\Controller;

use PronoteBundle\Entity\Analytics;
use PronoteBundle\Entity\ObservationsGenerales;
use PronoteBundle\Entity\ActivitesScolaires;
use PronoteBundle\Entity\Classe;
use PronoteBundle\Entity\Matieres;
use PronoteBundle\Entity\Devoirs;
use PronoteBundle\Entity\Assiduite;
use PronoteBundle\Entity\Fautes;
use PronoteBundle\Entity\Eleves;
use PronoteBundle\Entity\Notes;
use PronoteBundle\Entity\Telechargements;
use PronoteBundle\Entity\Notifications;
use PronoteBundle\Entity\NotificationsClasse;
use PronoteBundle\Entity\Ecole;

use \Datetime;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use PronoteBundle\Entity\Bulletin;

class ProfesseursController extends Controller
{
    
    //variable qui vérifie que la session est encore active
    //Par la vérification qu'une variabl n'est pas vide
    public function sessionAction(Request $request)
    {
        $session = $this->get('session');
        return new JsonResponse($session->get('professeurSession'));
    }
    
    public function exitProfesseurSessionAction(Request $request)
    {
        $session = $this->get('session');
        //$session->invalidate();
        //$session->remove('idEcole');
        $session->remove('idProfesseur');
        $session->remove('nomProfesseur');
        $session->remove('professeurSession');
        
        return $this->redirectToRoute('professeur_Homepage');
    }
    
    
    public function indexAction(Request $request)
    {

            $repository = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('PronoteBundle:Professeurs')
            ;

            if ('POST' === $request->getMethod())
            {
                $login = $request->get('login');
                $password = $request->get('password');
                $professeur = $repository->findOneBy(array('login' => $login, 'password' => $password));


                if (empty($professeur))
                {
                    return $this->render('PronoteBundle:Professeurs:index.html.twig');
                }
                else
                {
                    
                    $session = $this->get('session');
                    $session->start();
                    
                    $session->set('idEcole', $professeur->getEcole()->getId());
                    $session->set('idProfesseur', $professeur->getId());
                    $session->set('nomProfesseur', $professeur->getNom());
                    $session->set('professeurSession', "SessionProfesseurIsOn");
                    
                    //Mise à jour compteur "professeur"
                    $analytics = $this->updateAnalytics($professeur->getEcole()->getId(),"professeur");
                    //return new JsonResponse($analytics);
                    
                    //Chemain du logo École
                    $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
                    $ecole = $repository->findOneBy(array('id' => $professeur->getEcole()->getId()), array());
                    
                    $logoEcole = 'bootstrap/img/logo.png';
                    
                    if(null !== $ecole->getLogo())
                    {
                        $logoEcole = $ecole->getLogo();
                    }
                    
                    return $this->render('PronoteBundle:Professeurs:main.html.twig',array('logoEcole'=> $logoEcole));
                }
            }
            return $this->render('PronoteBundle:Professeurs:index.html.twig');
            

    }

    public function mainAction(Request $request)
    {
        $session = $this->get('session');
        
        $idEcole = $session->get('idEcole');
        $idProfesseur = $session->get('idProfesseur');
        $nomProfesseur = $session->get('nomProfesseur');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Professeurs:main.html.twig',array('logoEcole'=> $logoEcole));

    }
      
    /**************************************** Contrôleurs de la classe Devoirs ****************************************/
    /******************************************************************************************************************/
    public function showAllDevoirsAction(Request $request)
    {
        
        $session = $this->get('session');
        
        $idEcole = $session->get('idEcole');
        $idProfesseur = $session->get('idProfesseur');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }

        return $this->render('PronoteBundle:Professeurs:mainDevoirs.html.twig', 
            array(
                'allDevoirs' => $this->showAllDevoirs($idProfesseur, $idEcole), 
                'allClasses' => $this->showAllClasses($idEcole), 
                'allMatieres' => $this->showAllMatieres($idEcole),
                'logoEcole'=> $logoEcole
            ));
        
    }
    
    public function deleteDevoirAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
           
            $idDevoir = $request->get('idDevoirDelete');
            
            try
            {
                $this->deleteDevoir($idDevoir);
                
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllDevoirsProfesseurs', [
                    'request' => $request
                ], 307);
            }
                return $this->redirectToRoute('showAllDevoirsProfesseurs', [
                    'request' => $request
                ], 307);
            
        }
    }
    
    public function addDevoirAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            
            $session = $this->get('session');
            $idProfesseur = $session->get('idProfesseur');
            $idEcole = $session->get('idEcole');
            
            $detailsDevoir = $request->get('detailsDevoir');
            $donneLe = date('Y-m-d');
            $donnePour = $request->get('donnePour');
            $idMatiereDevoir = $request->get('idMatiereDevoir');
            $idClasseDevoir = $request->get('idClasseDevoir');
            
            try
            {
                $this->addDevoir($detailsDevoir, $donneLe, $donnePour, $idMatiereDevoir, $idClasseDevoir, $idProfesseur, $idEcole);
                
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllDevoirsProfesseurs', [
                    'request' => $request
                ], 307);
            }
            
                return $this->redirectToRoute('showAllDevoirsProfesseurs', [
                    'request' => $request
                ], 307);
            
            
        }
    }
    
    public function updateDevoirAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $session = $this->get('session');
            $idProfesseur = $session->get('idProfesseur');
            
            $idDevoir = $request->get('idDevoir');
            $detailsDevoir = $request->get('detailsDevoir');
            $donnePour = $request->get('donnePour');
            $idMatiereDevoir = $request->get('idMatiereDevoir');
            $idClasseDevoir = $request->get('idClasseDevoir');
            
            try
            {
                $this->updateDevoir($idDevoir, $detailsDevoir, $donnePour, $idMatiereDevoir, $idClasseDevoir, $idProfesseur);
                
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllDevoirsProfesseurs', [
                    'request' => $request
                ], 307);
            }
            
                return $this->redirectToRoute('showAllDevoirsProfesseurs', [
                    'request' => $request
                ], 307);
            
        }
    }
    
    /********************************************* CRUD de la classe Devoirs ******************************************/
    /******************************************************************************************************************/
    private function findAllDevoirs($idProfesseur, $idEcole)
    {
        /*
        $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT d
            FROM
            PronoteBundle:Devoirs d
            WHERE
            d.professeur =:idProfesseur 
            AND
            d.ecole =:idEcole 
           
         ")
         ->setParameters(['idProfesseur' => $idProfesseur, 'idEcole' => $idEcole]);
         */
        
        //On n'affiche que les devoirs dont la date n'a pas encore eu lieu
        
        $dateTimeInput = new DateTime(date("Y-m-d"));
        
        $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT d
            FROM
            PronoteBundle:Devoirs d
            WHERE
            d.professeur =:idProfesseur 
            AND
            d.ecole =:idEcole 
            AND
            d.matiere is not null
            AND
            d.donnePour >=:dateTimeInput
         ")
         ->setParameters(['idProfesseur' => $idProfesseur, 'idEcole' => $idEcole, 'dateTimeInput' => $dateTimeInput]);
            
            $resultat = $query->getResult();
            
            return $resultat;

    }
    /********************************************** Afficher Tous les Devoirs *****************************************/
    public function showAllDevoirs($idProfesseur, $idEcole)
    {
        
        $listeFinaleDevoirs = array();
        
        $devoirs = $this->findAllDevoirs($idProfesseur, $idEcole);
        
        foreach ($devoirs as $devoir)
        {
            $listeDevoirs = Array(
                'idDevoir' => $devoir->getId(),
                'detailsDevoir' => $devoir->getDetailsDevoir(),
                'donneLe' => $devoir->getDonneLe()->format('d-m-Y'),
                'donnePour' => $devoir->getDonnePour()->format('d-m-Y'),
                'idMatiereDevoir' => $devoir->getMatiere()->getId(),
                'nomMatiereDevoir' => $devoir->getMatiere()->getNom(),
                'nomClasseDevoir' => $devoir->getClasse()->getNom(),
                'idClasseDevoir' => $devoir->getClasse()->getId()
                );
            
            $listeFinaleDevoirs[] = $listeDevoirs;
        }
        return $listeFinaleDevoirs;
        
    }
    
    /************************************* Afficher Un Seule Devoir Par ID*********************************************/
    public function showOneDevoir($idDevoir)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Devoirs')
        ;
        
        $devoirs = $repository->findOneBy(
            array('id' => $idDevoir)
            );
        
        foreach ($devoirs as $devoir)
        {
            $listeDevoirs = Array(
                'idDevoir' => $devoir->getId(),
                'detailsDevoir' => $devoir->getDetailsDevoir(),
                'donneLe' => $devoir->getDonneLe()->format('d-m-Y'),
                'donnePour' => $devoir->getDonnePour()->format('d-m-Y'),
                'matiereDevoir' => $devoir->getMatiere()->getId(),
                'classeDevoir' => $devoir->getClasse()->getId()
                );
        }
        return $listeDevoirs;
        
    }
    
    /*************************************** Ajouter un Devoir ********************************************************/
    public function addDevoir($detailsDevoir, $donneLe, $donnePour, $idMatiereDevoir, $idClasseDevoir, $idProfesseur, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Classe')
        ;
        
        $classeDevoir = $repository->findOneBy(
            array('id' => $idClasseDevoir)
            );
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Professeurs')
        ;
        
        $professeurDevoir = $repository->findOneBy(
            array('id' => $idProfesseur)
            );
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Matieres')
        ;
        
        $matiereDevoir = $repository->findOneBy(
            array('id' => $idMatiereDevoir)
            );
        
        
        $em = $this->getDoctrine()->getManager();
        $devoir = new Devoirs();
        
        $devoir->setDetailsDevoir($detailsDevoir);
        
        $dateTimeInput = new DateTime($donneLe);
        $devoir->setDonneLe($dateTimeInput);
        
        $dateTimeInput = new DateTime($donnePour);
        $devoir->setDonnePour($dateTimeInput);
        
        
        $devoir->setIdMatiere($matiereDevoir);
        $devoir->setClasse($classeDevoir);
        
        $devoir->setProfesseur($professeurDevoir);
        $devoir->setEcole($ecole);
        
        //Gestion de notification
        $detailsDevoirAvecDate = " >> Travail à faire : " . ucfirst($matiereDevoir->getNom()) ." (". $donneLe .")";
        
        $lien = "travailafaire/";
        
        $this->addNotificationClasse($idClasseDevoir, $detailsDevoirAvecDate, $lien, $idEcole);
        
        $em->persist($devoir);
        $em->flush();
    }
    
    /********************************************** Modifier un Devoir ************************************************/
    public function updateDevoir($idDevoir, $detailsDevoir, $donnePour, $idMatiereDevoir, $idClasseDevoir, $idProfesseur)
    {
        $em = $this->getDoctrine()->getManager();
        $devoir = $em->getRepository('PronoteBundle:Devoirs')->find($idDevoir);
        
        
        if (!$devoir) {
            throw $this->createNotFoundException(
                'Ce Devoir est introuvable, ID = '.$idDevoir
                );
        }
        
        if(!empty($detailsDevoir)){
            $devoir->setDetailsDevoir($detailsDevoir);
        }
        
        if(!empty($donnePour)){
            $dateTimeInput = new DateTime($donnePour);
            $devoir->setDonnePour($dateTimeInput);
        }
        
        if(!empty($idMatiereDevoir)){
            $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Matieres')
            ;
            
            $matiereDevoir = $repository->findOneBy(
                array('id' => $idMatiereDevoir)
                );
            $devoir->setIdMatiere($matiereDevoir);
        }
        
        
        if(!empty($idClasseDevoir)){
            $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Classe')
            ;
            
            $classeDevoir = $repository->findOneBy(
                array('id' => $idClasseDevoir)
                );
            $devoir->setClasse($classeDevoir);
        }
       
        
        $em->flush();
        
        
    }
    
    /********************************************** Supprimer un Devoir par ID*****************************************/
    public function deleteDevoir($idDevoir)
    {
        
        $em = $this->getDoctrine()->getManager();
        $devoir = $em->getRepository('PronoteBundle:Devoirs')->find($idDevoir);
        
        if (!$devoir) {
            throw $this->createNotFoundException(
                'Ce Devoir est introuvable, ID = '.$idDevoir
                );
        }
        $em->remove($devoir);
        $em->flush();
    }
    
    /********************************************** FIN CRUD de la classe Devoirs *************************************/
    

    /**************************************** Contrôleurs de la classe Notes ****************************************/
    /******************************************************************************************************************/

    public function showAllNotesAction(Request $request)
    {
        
        $session = $this->get('session');
        $idProfesseur = $session->get('idProfesseur');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Professeurs:mainNotes.html.twig', 
            array(
                'allNotes' => $this->showAllNotes($idProfesseur, $idEcole),
                'allClasses' => $this->showAllClasses($idEcole), 
                'allEleves' => $this->showAllEleves($idEcole), 
                'allMatieres' => $this->showAllMatieres($idEcole) ,
                'logoEcole'=> $logoEcole
            ));
        
    }

    public function deleteNoteAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $session = $this->get('session');
            $idProfesseur = $session->get('idProfesseur');
           
            $idNote = $request->get('idNoteDelete');

            try
            {
                $this->deleteNote($idNote);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllNotes', [
                    'request' => $request
                ], 307);
            }
                return $this->redirectToRoute('showAllNotes', [
                    'request' => $request
                ], 307);

        }
    }

    public function addNoteAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $session = $this->get('session');
            $idProfesseur = $session->get('idProfesseur');
            $idEcole = $session->get('idEcole');
            
            $noteEleve = $request->get('detailsNote');
            $idEleve = $request->get('idListeEleve');
            $idMatiere = $request->get('idMatiere');
            $dateNote = $request->get('dateNote');

            if(Empty($idEleve))
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Le champs Élève est vide');
                return $this->redirectToRoute('showAllNotes', [
                    'request' => $request
                ], 307);
            }

            try
            {
                $this->addNote($noteEleve, $idEleve, $idMatiere, $dateNote, $idProfesseur, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllNotes', [
                    'request' => $request
                ], 307);
            }
                return $this->redirectToRoute('showAllNotes', [
                    'request' => $request
                ], 307);


        }
    }

    public function updateNoteAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $session = $this->get('session');
            $idProfesseur = $session->get('idProfesseur');
            
            $idNote = $request->get('idNote');
            $noteEleve = $request->get('detailsNote');
            $idEleve = $request->get('idListeEleve');
            $idMatiere = $request->get('idMatiere');
            $dateNote = $request->get('dateNote');

            try
            {
                $this->updateNote($idNote, $noteEleve, $idEleve, $idMatiere, $dateNote, $idProfesseur);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllNotes', [
                    'request' => $request
                ], 307);
            }
                return $this->redirectToRoute('showAllNotes', [
                    'request' => $request
                ], 307);

        }
    }

    //Charge une liste déroulante à partir d'une action sur une autre liste
    public function updateListeElevesAction(Request $request)
    {
        if ('GET' === $request->getMethod())
        {
            $idClasse = $request->get('idClasse');

            $listeEleves = $this->findEleveFromClasse($idClasse);
            
            return new JsonResponse($listeEleves);

        }
    }

    /********************************************* CRUD de la classe Notes ******************************************/
    /******************************************************************************************************************/
    /*****************************************************************************************************************/
    private function findAllNotes($idProfesseur, $idEcole)
    {
        /*

         $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT n
            FROM
            PronoteBundle:Notes n
            WHERE
            n.professeur =:idProfesseur
            AND
            n.ecole =:idEcole
         ")
         ->setParameters(['idProfesseur' => $idProfesseur, 'idEcole' => $idEcole]);
     
         */
        
        //On affiche uniquement les notes dont le mois est supérieur ou égal au mois en cours
        $dateTimeInput = new DateTime(date("Y-m")."-01");
        
        $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT n
            FROM
            PronoteBundle:Notes n
            WHERE
            n.professeur =:idProfesseur
            AND
            n.ecole =:idEcole
            AND
            n.dateNote >= :dateTimeInput
            AND
            n.eleve IS NOT NULL
            AND
            n.matiere IS NOT NULL
         ")
         ->setParameters(['idProfesseur' => $idProfesseur, 'idEcole' => $idEcole, 'dateTimeInput' => $dateTimeInput]);
         
         $resultat = $query->getResult();
         
         return $resultat;
        
    }
    /********************************************** Afficher Tous les Notes *****************************************/
    public function showAllNotes($idProfesseur, $idEcole)
    {
       
        $listeFinaleNotes = array();
        
        $notes = $this->findAllNotes($idProfesseur, $idEcole);

        foreach ($notes as $note)
        {
            $listeNotes = Array(
                'idNote' => $note->getId(),
                'dateNote' => $note->getDateNote()->format('d-m-Y'),
                'detailsNote' => $note->getNote(),

                'idEleve' => $note->getEleve()->getId(),
                'nomEleve' => $note->getEleve()->getNom(),
                'prenomEleve' => $note->getEleve()->getPrenom(),

                'idClasseEleve' => $note->getEleve()->getClasse()->getId(),
                'nomClasseEleve' => $note->getEleve()->getClasse()->getNom(),

                'idMatiere' => $note->getMatiere()->getId(),
                'nomMatiere' => $note->getMatiere()->getNom()
            );

            $listeFinaleNotes[] = $listeNotes;
        }
        return $listeFinaleNotes;

    }

    /************************************* Afficher Un Seule Note Par ID*********************************************/
    public function showOneNote($idNote)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Notes')
        ;

        $notes = $repository->findOneBy(
            array('id' => $idNote)
        );

        foreach ($notes as $note)
        {
            $listeNotes = Array(
                'idNote' => $note->getId(),
                'dateNote' => $note->getDateNote()->format('d-m-Y'),
                'detailsNote' => $note->getNote(),

                'idEleve' => $note->getEleve()->getId(),
                'nomEleve' => $note->getEleve()->getNom(),
                'prenomEleve' => $note->getEleve()->getPrenom(),

                'idClasseEleve' => $note->getEleve()->getClasse()->getId(),
                'nomClasseEleve' => $note->getEleve()->getClasse()->getNom(),

                'idMatiere' => $note->getMatiere()->getId(),
                'nomMatiere' => $note->getMatiere()->getNom()
            );
        }
        return $listeNotes;

    }

    /*************************************** Ajouter un Note ********************************************************/
    public function addNote($noteEleve, $idEleve, $idMatiere, $dateNote,$idProfesseur, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Eleves')
        ;

        $eleve = $repository->findOneBy(
            array('id' => $idEleve)
        );
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Professeurs')
        ;
        
        $professeurNote = $repository->findOneBy(
            array('id' => $idProfesseur)
            );


        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Matieres')
        ;

        $matiere = $repository->findOneBy(
            array('id' => $idMatiere)
        );


        $em = $this->getDoctrine()->getManager();
        $note = new Notes();

        $note->setNote($noteEleve);
        
        if($dateNote == null)
        {
            $dateTimeInput = new DateTime(date("Y/m/d"));
        }
        else
        {
            $dateTimeInput = new DateTime($dateNote);
        }
        
        $note->setDateNote($dateTimeInput);

        $note->setEleve($eleve);
        $note->setIdMatiere($matiere);
        
        $note->setProfesseur($professeurNote);
        $note->setEcole($ecole);
        
        //Gestion de la notification avec lien cliquable
        $detailsNote = " >> Note : " .  $noteEleve . " -  " . $matiere->getNom() ." (". $dateTimeInput->format('Y/m/d') .")";
        
        $lien = "notes/".$eleve->getParent()->getId()."/".$idEleve;
        
        $this->addNotification($idEleve, $detailsNote, $lien, $idEcole);

        $em->persist($note);
        $em->flush();
    }

    /********************************************** Modifier un Note ************************************************/
    public function updateNote($idNote, $noteEleve, $idEleve, $idMatiere, $dateNote, $idProfesseur)
    {
        $em = $this->getDoctrine()->getManager();
        $note = $em->getRepository('PronoteBundle:Notes')->find($idNote);


        if (!$note) {
            throw $this->createNotFoundException(
                'Cette Note est introuvable, ID = '.$idNote
            );
        }

        if($dateNote == null)
        {
            $dateTimeInput = new DateTime(date("Y/m/d"));
        }
        else
        {
            $dateTimeInput = new DateTime($dateNote);
        }
        
        $note->setDateNote($dateTimeInput);

        if(isset($noteEleve)){
            $note->setNote($noteEleve);
        }


        if(!empty($idEleve)){
            $repository = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('PronoteBundle:Eleves')
            ;

            $eleve = $repository->findOneBy(
                array('id' => $idEleve)
            );
            $note->setEleve($eleve);
        }


        if(!empty($idMatiere)){
            $repository = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('PronoteBundle:Matieres')
            ;

            $matiere = $repository->findOneBy(
                array('id' => $idMatiere)
            );
            $note->setIdMatiere($matiere);
        }

        $em->flush();


    }

    /********************************************** Supprimer un Note par ID*****************************************/
    public function deleteNote($idNote)
    {

        $em = $this->getDoctrine()->getManager();
        $note = $em->getRepository('PronoteBundle:Notes')->find($idNote);

        if (!$note) {
            throw $this->createNotFoundException(
                'Cette Note est introuvable, ID = '.$idNote
            );
        }
        $em->remove($note);
        $em->flush();
    }

    /********************************************** FIN CRUD de la classe Notes *************************************/


    /**************************************** Contrôleurs de la classe Assiduites *************************************/
    /******************************************************************************************************************/
    public function showAllAssiduitesAction()
    {
        $session = $this->get('session');
        
        $idEcole = $session->get('idEcole');
        $idProfesseur = $session->get('idProfesseur');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Professeurs:mainAssiduites.html.twig', 
            array(
                'allAssiduites' => $this->showAllAssiduites($idProfesseur, $idEcole), 
                'allClasses' => $this->showAllClasses($idEcole), 
                'allFautes' => $this->showAllFautes() ,
                'logoEcole'=> $logoEcole
            ));
    }

    public function deleteAssiduiteAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idAssiduite = $request->get('idAssiduiteDelete');
            
            $this->deleteAssiduite($idAssiduite);
        }
        return $this->redirectToRoute('showAllAssiduites', [
            'request' => $request
        ], 307);

    }

    public function addAssiduiteAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $session = $this->get('session');
            $idProfesseur = $session->get('idProfesseur');
            $idEcole = $session->get('idEcole');
            
            $dateFaute = $request->get('dateFaute');
            $detailsFautes = $request->get('detailsAssiduite');

            $idEleve = $request->get('idListeEleve');
            $idTypeFaute = $request->get('inputFautes');

            if(Empty($idEleve))
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Le champs Élève est vide');
                return $this->redirectToRoute('showAllAssiduites', [
                    'request' => $request
                ], 307);
            }

            try
            {
                $this->addAssiduite($dateFaute, $detailsFautes, $idEleve, $idTypeFaute, $idProfesseur, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllAssiduites', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllAssiduites', [
                    'request' => $request
                ], 307);

        }
    }

    public function updateAssiduiteAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $session = $this->get('session');
            $idProfesseur = $session->get('idProfesseur');
            
            $idAssiduite = $request->get('idAssiduite');
            $dateFaute = $request->get('dateFaute');
            $detailsFautes = $request->get('detailsAssiduite');
            $idEleve = $request->get('idListeEleve');
            $idTypeFaute = $request->get('inputFautes');

            try
            {
                $this->updateAssiduite($idAssiduite, $dateFaute, $detailsFautes, $idEleve, $idTypeFaute, $idProfesseur);
                
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllAssiduites', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllAssiduites', [
                    'request' => $request
                ], 307);
        }
    }

    /********************************************* CRUD de la classe Assiduites ******************************************/
    /*****************************************************************************************************************/
    private function findAllAssiduites($idProfesseur, $idEcole)
    {
        /*
        $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT a
            FROM
            PronoteBundle:Assiduite a
            WHERE
            a.professeur =:idProfesseur
            AND
            a.ecole =:idEcole
          
         ")
         ->setParameters(['idProfesseur' => $idProfesseur, 'idEcole' => $idEcole]);
        */
        
        //On affiche uniquement les assiduités dont le mois est supérieur ou égal au mois en cours
        
        $dateTimeInput = new DateTime(date("Y-m")."-01");
        
        $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT a
            FROM
            PronoteBundle:Assiduite a
            WHERE
            a.professeur =:idProfesseur
            AND
            a.ecole =:idEcole
            AND
            a.dateFaute >= :dateTimeInput
            AND
            a.eleve IS NOT NULL
         ")
         ->setParameters(['idProfesseur' => $idProfesseur, 'idEcole' => $idEcole, 'dateTimeInput' => $dateTimeInput]);
         
         $resultat = $query->getResult();
         
         return $resultat;
        
    }
    /********************************************** Afficher Tous les Assiduites *****************************************/
    public function showAllAssiduites($idProfesseur, $idEcole)
    {
        
        $listeFinaleAssiduites = array();
        
        $assiduites = $this->findAllAssiduites($idProfesseur, $idEcole);

        foreach ($assiduites as $assiduite)
        {
            $listeAssiduites = Array(
                'idAssiduite' => $assiduite->getId(),
                'dateFaute' => $assiduite->getDateFaute()->format('d-m-Y'),
                'detailsFaute' => $assiduite->getDetailsFautes(),

                'idEleve' => $assiduite->getEleve()->getId(),
                'nomEleve' => $assiduite->getEleve()->getNom(),
                'prenomEleve' => $assiduite->getEleve()->getPrenom(),

                'idTypeFaute' => $assiduite->getTypeFaute()->getId(),
                'typeFaute' => $assiduite->getTypeFaute()->getTypeFaute(),

                'idClasseEleve' => $assiduite->getEleve()->getClasse()->getId(),
                'nomClasseEleve' => $assiduite->getEleve()->getClasse()->getNom()
            );

            $listeFinaleAssiduites[] = $listeAssiduites;
        }
        return $listeFinaleAssiduites;

    }

    /************************************* Afficher Un Seule Assiduite Par ID*********************************************/
    public function showOneAssiduite($idAssiduite)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Assiduite')
        ;

        $assiduites = $repository->findOneBy(
            array('id' => $idAssiduite)
        );

        foreach ($assiduites as $assiduite)
        {
            $listeAssiduites = Array(
                'idAssiduite' => $assiduite->getId(),
                'dateFaute' => $assiduite->getDateFaute()->format('d-m-Y'),
                'detailsFaute' => $assiduite->getDetailsFautes(),

                'idEleve' => $assiduite->getEleve()->getId(),
                'nomEleve' => $assiduite->getEleve()->getNom(),
                'prenomEleve' => $assiduite->getEleve()->getPrenom(),

                'idTypeFaute' => $assiduite->getTypeFaute()->getId(),
                'typeFaute' => $assiduite->getTypeFaute()->getTypeFaute(),

                'idClasseEleve' => $assiduite->getEleve()->getClasse()->getId(),
                'nomClasseEleve' => $assiduite->getEleve()->getClasse()->getNom()
            );
        }
        return $listeAssiduites;

    }

    /*************************************** Ajouter un Assiduite ********************************************************/
    public function addAssiduite($dateFaute, $detailsFautes,  $idEleve, $idTypeFaute, $idProfesseur, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Eleves')
        ;

        $eleve = $repository->findOneBy(
            array('id' => $idEleve)
        );
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Professeurs')
        ;
        
        $professeurNote = $repository->findOneBy(
            array('id' => $idProfesseur)
            );

        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Fautes')
        ;

        $typeFaute = $repository->findOneBy(
            array('id' => $idTypeFaute)
        );


        $em = $this->getDoctrine()->getManager();
        $assiduite = new Assiduite();

        $assiduite->setDetailsFautes($detailsFautes);
        
        if($dateFaute == null)
        {
            $dateTimeInput = new DateTime(date("Y/m/d"));
        }
        else 
        {
            $dateTimeInput = new DateTime($dateFaute);
        }
        
        $assiduite->setDateFaute($dateTimeInput);

        $assiduite->setEleve($eleve);
        $assiduite->setTypeFaute($typeFaute);
        
        $assiduite->setProfesseur($professeurNote);
        $assiduite->setEcole($ecole);
        
        //Gestion de notification
        $detailsFautesAvecDate = " >> " . ucfirst($detailsFautes) ." (". $dateTimeInput->format('Y/m/d') .")";
        
        $lien = "assiduite/".$eleve->getParent()->getId()."/".$idEleve;
        
        $this->addNotification($idEleve, $detailsFautesAvecDate, $lien, $idEcole);

        $em->persist($assiduite);
        $em->flush();
    }

    /********************************************** Modifier un Assiduite ************************************************/
    public function updateAssiduite($idAssiduite, $dateFaute, $detailsFautes,  $idEleve, $idTypeFaute, $idProfesseur)
    {
        $em = $this->getDoctrine()->getManager();
        $assiduite = $em->getRepository('PronoteBundle:Assiduite')->find($idAssiduite);


        if (!$assiduite) {
            throw $this->createNotFoundException(
                'Ce Assiduite est introuvable, ID = '.$idAssiduite
            );
        }

        if($dateFaute == null)
        {
            $dateTimeInput = new DateTime(date("Y/m/d"));
        }
        else
        {
            $dateTimeInput = new DateTime($dateFaute);
        }
        
        $assiduite->setDateFaute($dateTimeInput);

        if(!empty($detailsFautes)){
            $assiduite->setDetailsFautes($detailsFautes);
        }


        if(!empty($idEleve)){
            $repository = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('PronoteBundle:Eleves')
            ;

            $eleve = $repository->findOneBy(
                array('id' => $idEleve)
            );
            $assiduite->setEleve($eleve);
        }


        if(!empty($idTypeFaute)){
            $repository = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('PronoteBundle:Fautes')
            ;

            $typeFaute = $repository->findOneBy(
                array('id' => $idTypeFaute)
            );
            $assiduite->setTypeFaute($typeFaute);
        }
        
        
        $em->flush();
        

    }

    /********************************************** Supprimer un Assiduite par ID*****************************************/
    public function deleteAssiduite($idAssiduite)
    {

        $em = $this->getDoctrine()->getManager();
        $assiduite = $em->getRepository('PronoteBundle:Assiduite')->find($idAssiduite);

        if (!$assiduite) {
            throw $this->createNotFoundException(
                'Cette Assiduite est introuvable, ID = '.$idAssiduite
            );
        }
        
        $em->remove($assiduite);
        $em->flush();
    }

    /********************************************** FIN CRUD de la classe Assiduites *************************************/

    
    /**************************************** Contrôleurs de la classe Téléchargements**************************/
    /******************************************************************************************************************/
    public function showAllTelechargementsAction()
    {
        $session = $this->get('session');
        
        $idEcole = $session->get('idEcole');
        $idProfesseur = $session->get('idProfesseur');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Professeurs:mainTelechargements.html.twig', 
            array(
                'allTelechargements' => $this->showAllTelechargements($idProfesseur, $idEcole), 
                'allClasses' => $this->showAllClasses($idEcole),
                'logoEcole'=> $logoEcole
            ));
    }
    
    public function deleteTelechargementAction(Request $request)
    {
        
        if ('POST' === $request->getMethod())
        {
            $idTelechargement = $request->get('idTelechargementDelete');
            
            try
            {
                $this->deleteTelechargement($idTelechargement);
                
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllTelechargementsProfesseur', [
                    'request' => $request
                ], 307);
            }
            
                return $this->redirectToRoute('showAllTelechargementsProfesseur', [
                    'request' => $request
                ], 307);
            
        }
    }
    
    public function addTelechargementAction(Request $request)
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        $idProfesseur = $session->get('idProfesseur');
        
        if ('POST' === $request->getMethod())
        {
            $descriptionTelechargement = $request->get('descriptionTelechargement');
            $idClasseTelechargement = $request->get('classeTelechargement');
            
            try
            {
                $filename = $_FILES["fileTelechargement"]["name"];
                $file_basename = substr($filename, 0, strripos($filename, '.')); // get file extention
                $file_ext = substr($filename, strripos($filename, '.')); // get file name
                $filesize = $_FILES["fileTelechargement"]["size"];
                $allowed_file_types = array('.doc','.docx','.jpg','.jpeg','.gif','.png','.pdf','.DOC','.DOCX','.JPG','.JPEG','.GIF','.PNG','.PDF');
                
                if (in_array($file_ext,$allowed_file_types) && ($filesize < 8388608 ))
                {
                    // Rename file
                    $newfilename = md5(uniqid()).$file_ext;
                    if (file_exists("uploads/".$idEcole."/ressourcesProf/".$newfilename))
                    {
                        // file already exists error
                        echo "You have already uploaded this file.";
                    }
                    else
                    {
                        move_uploaded_file($_FILES["fileTelechargement"]["tmp_name"], "uploads/".$idEcole."/ressourcesProf/".$newfilename);
                        echo "File uploaded successfully.";
                    }
                }
                elseif (empty($file_basename))
                {
                    // file selection error
                    echo "Please select a file to upload.";
                }
                elseif ($filesize > 8388608 )
                {
                    // file size error
                    echo "The file you are trying to upload is too large.";
                }
                else
                {
                    // file type error
                    echo "Only these file typs are allowed for upload: " . implode(', ',$allowed_file_types);
                    unlink($_FILES["fileTelechargement"]["tmp_name"]);
                }
                
                
                
                $this->addTelechargement($descriptionTelechargement,$idClasseTelechargement,$newfilename,$idProfesseur, $idEcole);
                
                
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllTelechargementsProfesseur', [
                    'request' => $request
                ], 307);
            }
            
                return $this->redirectToRoute('showAllTelechargementsProfesseur', [
                    'request' => $request
                ], 307);
            
            
        }
    }
    
    public function updateTelechargementAction(Request $request)
    {
        
        if ('POST' === $request->getMethod())
        {
            $idTelechargement = $request->get('idTelechargement');
            $descriptionTelechargement = $request->get('descriptionTelechargement');
            $idClasseTelechargement = $request->get('classeTelechargement');
            
            try
            {

                $this->updateTelechargement($idTelechargement,$descriptionTelechargement,$idClasseTelechargement);
            }
            
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllTelechargementsProfesseur', [
                    'request' => $request
                ], 307);
            }
            
                return $this->redirectToRoute('showAllTelechargementsProfesseur', [
                    'request' => $request
                ], 307);
            
        }
    }
    
    /********************************************* CRUD de la classe Téléchargement******************************/
    /******************************************************************************************************************/
    private function findAllTelechargements($idProfesseur, $idEcole)
    {
        /*$repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Telechargements')
        ;
        
        return $repository->findBy(array('professeur' => $idProfesseur)/*, array('ecole' => $idEcole)*///);
        
        $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT t
            FROM
            PronoteBundle:Telechargements t
            WHERE
            t.professeur =:idProfesseur
            AND
            t.ecole =:idEcole
         ")
         ->setParameters(['idProfesseur' => $idProfesseur, 'idEcole' => $idEcole]);
         
         $resultat = $query->getResult();
         
         return $resultat;
    }
    /********************************************** Afficher Tous les Téléchargement ****************************/
    public function showAllTelechargements($idProfesseur, $idEcole)
    {
        
        $listeFinaleTelechargements = array();
        
        $Telechargements = $this->findAllTelechargements($idProfesseur, $idEcole);
        
        foreach ($Telechargements as $Telechargement)
        {
            $listeTelechargements = Array(
                'idTelechargement' => $Telechargement->getId(),
                'descriptionTelechargement' => $Telechargement->getDescription(),
                'fileTelechargement' => "uploads/".$Telechargement->getFile(),
                'classeTelechargement' => $Telechargement->getClasse()->getId(),
                'nomclasseTelechargement' => $Telechargement->getClasse()->getNom()
                );
            
            $listeFinaleTelechargements[] = $listeTelechargements;
        }
        return $listeFinaleTelechargements;
        
    }
    
    /************************************* Afficher Une Seule Téléchargement Par ID******************************/
    public function showOneTelechargement($idTelechargement)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Telechargements')
        ;
        
        $Telechargements = $repository->findOneBy(
            array('id' => $idTelechargement)
            );
        
        $listeTelechargements = array();
        
        foreach ($Telechargements as $Telechargement)
        {
            $listeTelechargements = Array(
                'idTelechargement' => $Telechargement->getId(),
                'descriptionTelechargement' => $Telechargement->getDescription(),
                'fileTelechargement' => "uploads/".$Telechargement->getFile(),
                'classeTelechargement' => $Telechargement->getClasse()->getId(),
                'nomclasseTelechargement' => $Telechargement->getClasse()->getNom()
                );
        }
        return $listeTelechargements;
        
    }
    
    /*************************************** Ajouter une Téléchargement *****************************************/
    public function addTelechargement($descriptionTelechargement, $idClasseTelechargement, $file, $idProfesseur, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Classe')
        ;
        
        $classeTelechargement = $repository->findOneBy(
            array('id' => $idClasseTelechargement)
            );
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Professeurs')
        ;
        
        $professeurTelechargement = $repository->findOneBy(
            array('id' => $idProfesseur)
            );
        
        $em = $this->getDoctrine()->getManager();
        $Telechargement = new Telechargements();
        
        $Telechargement->setDescription($descriptionTelechargement);
        $Telechargement->setClasse($classeTelechargement);
        $Telechargement->setFile($idEcole."/ressourcesProf/".$file);
        $Telechargement->setProfesseur($professeurTelechargement);
        $Telechargement->setEcole($ecole);
        
        //Gestion de notification
        $detailsRessourcePedagogique = " >> Ressource pédagogique ajoutée"." (". date("Y/m/d") .")";
        
        $lien = "login/";
        
        $this->addNotificationClasse($idClasseTelechargement, $detailsRessourcePedagogique, $lien, $idEcole);
        
        
        $em->persist($Telechargement);
        $em->flush();
    }
    
    /********************************************** Modifier une Téléchargement *********************************/
    public function updateTelechargement($idTelechargement, $descriptionTelechargement, $idClasseTelechargement)
    {
        $em = $this->getDoctrine()->getManager();
        $Telechargement = $em->getRepository('PronoteBundle:Telechargements')->find($idTelechargement);
        
        
        if (!$Telechargement) {
            throw $this->createNotFoundException(
                'Ce Téléchargement est introuvable, ID = '.$idTelechargement
                );
        }
        
        if(!empty($descriptionTelechargement)){
            $Telechargement->setDescription($descriptionTelechargement);
        }
        
        if(!empty($idClasseTelechargement)){
            $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Classe')
            ;
            
            $classeTelechargement = $repository->findOneBy(
                array('id' => $idClasseTelechargement)
                );
            $Telechargement->setClasse($classeTelechargement);
        }

        $em->flush();
        
    }
    
    /********************************************** Supprimer une Téléchargement par ID**************************/
    public function deleteTelechargement($idTelechargement)
    {
        
        $em = $this->getDoctrine()->getManager();
        $Telechargement = $em->getRepository('PronoteBundle:Telechargements')->find($idTelechargement);
        $path = $Telechargement->getFile();
        unlink("uploads/".$path);
        
        if (!$Telechargement) {
            throw $this->createNotFoundException(
                'Ce Téléchargement est introuvable, ID = '.$idTelechargement
                );
        }
        $em->remove($Telechargement);
        $em->flush();
    }
    
    /********************************************** FIN CRUD de la classe Activité Scolaires***************************/  
    
    /**************************************** Contrôleurs de la classe Observations Générales**************************/
    /******************************************************************************************************************/
    public function showAllObservationsGeneralesAction()
    {
        $session = $this->get('session');
        
        $idEcole = $session->get('idEcole');
        $idProfesseur = $session->get('idProfesseur');

        
        return $this->render('PronoteBundle:Professeurs:mainObservationsGenerales.html.twig', 
            array(
                'allObservationsGenerales' => $this->showAllObservationsGenerales($idProfesseur, $idEcole),
                'allClasses' => $this->showAllClasses($idEcole) 
            ));
            
    }

    public function deleteObservationGeneraleAction(Request $request)
    {

        if ('POST' === $request->getMethod())
        {
            $idObservationGenerale = $request->get('idObservationGeneraleDelete');
            $this->deleteObservationGenerale($idObservationGenerale);

            return $this->redirectToRoute('showAllObservationsGeneralesProfesseurs', [
                'request' => $request
            ], 307);
        }
    }

    public function addObservationGeneraleAction(Request $request)
    {
        $session = $this->get('session');
        
        $idEcole = $session->get('idEcole');
        $idProfesseur = $session->get('idProfesseur');
        
        if ('POST' === $request->getMethod())
        {
            $descriptionObservationGenerale = $request->get('descriptionObservationGenerale');
            $idClasseObservationGenerale = $request->get('classeObservationGenerale');

            try
            {
                $this->addObservationGenerale($descriptionObservationGenerale,$idClasseObservationGenerale,$idProfesseur);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de cette opération');
                return $this->redirectToRoute('showAllObservationsGeneralesProfesseurs', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllObservationsGeneralesProfesseurs', [
                    'request' => $request
                ], 307);


        }
    }

    public function updateObservationGeneraleAction(Request $request)
    {
       
        if ('POST' === $request->getMethod())
        {
            $idObservationGenerale = $request->get('idObservationGenerale');
            $descriptionObservationGenerale = $request->get('descriptionObservationGenerale');
            $idClasseObservationGenerale = $request->get('classeObservationGenerale');

            $this->updateObservationGenerale($idObservationGenerale,$descriptionObservationGenerale,$idClasseObservationGenerale);

            return $this->redirectToRoute('showAllObservationsGeneralesProfesseurs', [
                'request' => $request
            ], 307);

        }
    }

    /**************************************** Contrôleurs de la classe Activité Scolaires******************************/
    /******************************************************************************************************************/
    public function showAllActivitesScolairesAction()
    {
        $session = $this->get('session');
        
        $idEcole = $session->get('idEcole');
        $idProfesseur = $session->get('idProfesseur');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Professeurs:mainActivitesScolaires.html.twig', 
            array(
                'allActiviteScolaires' => $this->showAllActivitesScolaires($idProfesseur, $idEcole),
                'allClasses' => $this->showAllClasses($idEcole),
                'logoEcole'=> $logoEcole
            ));
    }

    public function deleteActiviteScolaireAction(Request $request)
    {

        if ('POST' === $request->getMethod())
        {
            $idActiviteScolaire = $request->get('idActiviteScolaireDelete');
            $this->deleteActiviteScolaire($idActiviteScolaire);

            return $this->redirectToRoute('showAllActivitesScolairesProfesseurs', [
                'request' => $request
            ], 307);

        }
    }

    public function addActiviteScolaireAction(Request $request)
    {
        $session = $this->get('session');
        
        $idEcole = $session->get('idEcole');
        $idProfesseur = $session->get('idProfesseur');
        
        if ('POST' === $request->getMethod())
        {
            $descriptionActiviteScolaire = $request->get('descriptionActiviteScolaire');
            $dateActiviteScolaire = $request->get('dateActiviteScolaire');
            $idClasseActiviteScolaire = $request->get('classeActiviteScolaire');

            try
            {
                $this->addActiviteScolaire($descriptionActiviteScolaire,$dateActiviteScolaire,$idClasseActiviteScolaire,$idProfesseur, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de cette opération');
                return $this->redirectToRoute('showAllActivitesScolairesProfesseurs', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllActivitesScolairesProfesseurs', [
                    'request' => $request
                ], 307);


        }
    }

    public function updateActiviteScolaireAction(Request $request)
    {
        
        if ('POST' === $request->getMethod())
        {
            $idActiviteScolaire = $request->get('idActiviteScolaire');
            $descriptionActiviteScolaire = $request->get('descriptionActiviteScolaire');
            $dateActiviteScolaire = $request->get('dateActiviteScolaire');
            $idClasseActiviteScolaire = $request->get('classeActiviteScolaire');

            $this->updateActiviteScolaire($idActiviteScolaire,$descriptionActiviteScolaire,$dateActiviteScolaire,$idClasseActiviteScolaire);

            return $this->redirectToRoute('showAllActivitesScolairesProfesseurs', [
                'request' => $request
            ], 307);

        }
    }

    /********************************************* CRUD de la classe Activité Scolaires********************************/
    /******************************************************************************************************************/
    private function findAllActivitesScolaires($idProfesseur, $idEcole)
    {
        /*
        $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT a
            FROM
            PronoteBundle:ActivitesScolaires a
            WHERE
            a.professeur =:idProfesseur
            AND
            a.ecole =:idEcole
         ")
         ->setParameters(['idProfesseur' => $idProfesseur, 'idEcole' => $idEcole]);
        */
        
        //On affiche uniquement les activités scolaires dont la date n'a pas encore eu lieu
        
        $dateTimeInput = new DateTime(date("Y-m")."-01");
        
        $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT a
            FROM
            PronoteBundle:ActivitesScolaires a
            WHERE
            a.professeur =:idProfesseur
            AND
            a.ecole =:idEcole
            AND
            a.dateActivite >= :dateTimeInput
         ")
         ->setParameters(['idProfesseur' => $idProfesseur, 'idEcole' => $idEcole, 'dateTimeInput' => $dateTimeInput]);
         
         $resultat = $query->getResult();
         
         return $resultat;
    }
    /********************************************** Afficher Tous les Activité Scolaires*******************************/
    public function showAllActivitesScolaires($idProfesseur, $idEcole)
    {
        
        $listeFinaleActiviteScolaires = Array();

        $activiteScolaires = $this->findAllActivitesScolaires($idProfesseur, $idEcole);

        foreach ($activiteScolaires as $activiteScolaire)
        {
            $listeActiviteScolaires = Array(
                'idActiviteScolaire' => $activiteScolaire->getId(),
                'descriptionActiviteScolaire' => $activiteScolaire->getDescription(),
                'dateActiviteScolaire' => $activiteScolaire->getDateActivite()->format('d-m-Y'),
                'classeActiviteScolaire' => $activiteScolaire->getClasse()->getId(),
                'nomclasseActiviteScolaire' => $activiteScolaire->getClasse()->getNom(),
                'nomProfesseur' => $activiteScolaire->getProfesseur()->getNom()
            );

            $listeFinaleActiviteScolaires[] = $listeActiviteScolaires;
        }
        return $listeFinaleActiviteScolaires;

    }

    /********************************************** Afficher Un Seul Activité Scolaire Par ID**************************/
    public function showOneActiviteScolaire($idActiviteScolaire)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:ActivitesScolaires')
        ;

        $activiteScolaires = $repository->findOneBy(
            array('id' => $idActiviteScolaire)
        );

        foreach ($activiteScolaires as $activiteScolaire)
        {
            $listeActiviteScolaires = Array(
                'idActiviteScolaire' => $activiteScolaire->getId(),
                'descriptionActiviteScolaire' => $activiteScolaire->getDescription(),
                'dateActiviteScolaire' => $activiteScolaire->getDateActivite()->format('d-m-Y'),
                'classeActiviteScolaire' => $activiteScolaire->getClasse()->getId()
            );
        }
        return $listeActiviteScolaires;

    }

    /********************************************** Ajouter un Activité Scolaire***************************************/
    public function addActiviteScolaire($descriptionActiviteScolaire, $dateActiviteScolaire, $idClasseActiviteScolaire, $idProfesseur, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Classe')
        ;

        $classeActiviteScolaire = $repository->findOneBy(
            array('id' => $idClasseActiviteScolaire)
        );
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Professeurs')
        ;
        
        $professeurActiviteScolaire = $repository->findOneBy(
            array('id' => $idProfesseur)
            );
        

        $em = $this->getDoctrine()->getManager();
        $activiteScolaire = new ActivitesScolaires();

        $activiteScolaire->setDescription($descriptionActiviteScolaire);

        $dateTimeInput = new DateTime($dateActiviteScolaire);
        $activiteScolaire->setDateActivite($dateTimeInput);

        $activiteScolaire->setClasse($classeActiviteScolaire);
        $activiteScolaire->setProfesseur($professeurActiviteScolaire);
        $activiteScolaire->setEcole($ecole);
        
        $em->persist($activiteScolaire);
        $em->flush();
    }

    /********************************************** Modifier un Activité Scolaire**************************************/
    public function updateActiviteScolaire($idActiviteScolaire, $descriptionActiviteScolaire, $dateActiviteScolaire, $idClasseActiviteScolaire)
    {
        $em = $this->getDoctrine()->getManager();
        $activiteScolaire = $em->getRepository('PronoteBundle:ActivitesScolaires')->find($idActiviteScolaire);


        if (!$activiteScolaire) {
            throw $this->createNotFoundException(
                'Cette Activité Scolaire est introuvable, ID = '.$idActiviteScolaire
            );
        }

        if(!empty($descriptionActiviteScolaire)){
            $activiteScolaire->setDescription($descriptionActiviteScolaire);
        }


        if(!empty($dateActiviteScolaire)){
            $dateTimeInput = new DateTime($dateActiviteScolaire);
            $activiteScolaire->setDateActivite($dateTimeInput);
        }

        if(!empty($idClasseActiviteScolaire)){
            $repository = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('PronoteBundle:Classe')
            ;

            $classeActiviteScolaire = $repository->findOneBy(
                array('id' => $idClasseActiviteScolaire)
            );
            $activiteScolaire->setClasse($classeActiviteScolaire);
        }

        $em->flush();


    }

    /********************************************** Supprimer un Activité Scolaire par ID******************************/
    public function deleteActiviteScolaire($idActiviteScolaire)
    {

        $em = $this->getDoctrine()->getManager();
        $activiteScolaire = $em->getRepository('PronoteBundle:ActivitesScolaires')->find($idActiviteScolaire);

        if (!$activiteScolaire) {
            throw $this->createNotFoundException(
                'Cette Activité Scolaire est introuvable, ID = '.$idActiviteScolaire
            );
        }
        $em->remove($activiteScolaire);
        $em->flush();
    }

    /********************************************** FIN CRUD de la classe Activité Scolaires***************************/
    private function findAllClasses($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Classe')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('nom' => 'ASC'));
    }
    /******************************************** Afficher Toutes les Classes******************************************/
    public function showAllClasses($idEcole)
    {
        $listeFinaleClasses = array();
        
        $classes = $this->findAllClasses($idEcole);
        
        foreach ($classes as $classe)
        {
            $listeClasses = Array(
                'idClasse' => $classe->getId(),
                'nomClasse' => $classe->getNom()
            );

            $listeFinaleClasses[] = $listeClasses;
        }
        return $listeFinaleClasses;

    }
    
    /*****************************************************************************************************************/
    private function findAllMatieres($idEcole)
    {

        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Matieres')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('nom' => 'ASC'));
    }
    /********************************************** Afficher Tous les Matieres*****************************************/
    public function showAllMatieres($idEcole)
    {
        $listeFinaleMatieres = array();
        
        $matieres = $this->findAllMatieres($idEcole);

        foreach ($matieres as $matiere)
        {
            $listeMatieres = Array(
                'idMatiere' => $matiere->getId(),
                'nomMatiere' => ucfirst($matiere->getNom())
            );

            $listeFinaleMatieres[] = $listeMatieres;
        }
        return $listeFinaleMatieres;

    }
    
    /*****************************************************************************************************************/
    private function findAllFautes()
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Fautes')
        ;
        
        return $repository->findBy(array(), array('typeFaute' => 'ASC'));
    }
    /******************************************** Afficher Toutes les Fautes******************************************/
    public function showAllFautes()
    { 
        
        $listeFinaleFautes = array();
        
        $fautes = $this->findAllFautes();

        foreach ($fautes as $faute)
        {
            $listeFautes = Array(
                'idFaute' => $faute->getId(),
                'typeFaute' => $faute->getTypeFaute()
            );

            $listeFinaleFautes[] = $listeFautes;
        }
        return $listeFinaleFautes;

    }

    /*****************************************************************************************************************/
    private function findAllEleves($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('prenom' => 'ASC'));
    }
    /********************************************** Afficher Tous les Élèves******************************************/
    public function showAllEleves($idEcole)
    {
        
        $listeFinaleEleves= array();
        
        $eleves = $this->findAllEleves($idEcole);

        foreach ($eleves as $eleve)
        {
            $listeEleves = Array(
                'idEleve' => $eleve->getId(),
                'prenomEleve' => ucfirst($eleve->getPrenom()),
                'nomEleve' => ucfirst($eleve->getNom()),
                'idParentEleve' => $eleve->getParent()->getId(),
                'idClasseEleve' => $eleve->getClasse()->getId(),
                'prenomParentEleve' => ucfirst($eleve->getParent()->getPrenom()),
                'nomParentEleve' => ucfirst($eleve->getParent()->getNom()),
                'nomClasseEleve' => $eleve->getClasse()->getNom(),
            );

            $listeFinaleEleves[] = $listeEleves;
        }
        return $listeFinaleEleves;

    }

    //******APPEL AJAX : Retourne un Array qui contient tous les élèves d'une Classe
    public function findEleveFromClasse($idClasse)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Eleves')
        ;
        
        //uniquement les élèves de la classe sélectionnée
        $eleves = $repository->findBy(
            array('classe' => $idClasse),
            array('prenom' => 'ASC')
        );

        $listeFinaleEleves = array();

        foreach ($eleves as $eleve){
            $listeEleves = Array(
                    'idEnfant' => $eleve->getId(),
                    'prenomEnfant' => $eleve->getPrenom(),
                    'nomEnfant' => $eleve->getNom(),
                    'idClasse' => $eleve->getClasse()->getId(),
                    'nomClasse' => $eleve->getClasse()->getNom()
                );

            $listeFinaleEleves[] = $listeEleves;
        }
        
        return $listeFinaleEleves;

    }
    
    //Fonction générique qui ajoute une ligne dans la table Notification
    
    /**************Prévoir l'envoi de mail aux parents********************/
    private function addNotification($idEleve, $descriptionNotification, $lienNotification, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        $eleve = $repository->find($idEleve);
        
        $notification = new Notifications();
       
        $notification->setEleve($eleve);
        $notification->setDescriptionNotification($descriptionNotification);
        $notification->setEtatNotification("NVU");
        $notification->setLienNotification($lienNotification);
        $notification->setEcole($ecole);
        
        $this->getDoctrine()->getManager()->persist($notification);
        $this->getDoctrine()->getManager()->flush();
    }
    
    
    //Notification Classe
    private function addNotificationClasse($idClasse, $descriptionNotification, $lienNotification, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Classe')
        ;
        
        $classe = $repository->find($idClasse);
        
        $notificationClasse = new NotificationsClasse();
        
        $notificationClasse->setClasse($classe);
        $notificationClasse->setDescriptionNotification($descriptionNotification);
        $notificationClasse->setEtatNotification("NVU");
        $notificationClasse->setLienNotification($lienNotification);
        $notificationClasse->setEcole($ecole);
        
        $this->getDoctrine()->getManager()->persist($notificationClasse);
        $this->getDoctrine()->getManager()->flush();
    }
    
    //Fonction générique qui supprime une notification de la table Notification
    private function deleteNotification($idNotification)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Notifications')
        ;
        
        $notification = $repository->findOneBy(array('id' => $idNotification));
        
        $this->getDoctrine()->getManager()->remove($notification);
        $this->getDoctrine()->getManager()->flush();
    }
    
    /**************************************** Contrôleurs de la classe Bulletin**************************/
    /******************************************************************************************************************/
    public function showAllBulletinsAction()
    {
        $session = $this->get('session');
        
        $idEcole = $session->get('idEcole');
        $idProfesseur = $session->get('idProfesseur');

        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Professeurs:mainBulletin.html.twig', 
            array(
                'allBulletin' => $this->showAllBulletin($idProfesseur, $idEcole), 
                'allClasses' => $this->showAllClasses($idEcole), 
                'allEleves' => $this->showAllEleves($idEcole),
                'logoEcole'=> $logoEcole
            ));
        
    }
    
    
    public function addBulletinAction(Request $request)
    {
        
        if ('POST' === $request->getMethod())
        {
            $session = $this->get('session');
            
            $idEcole = $session->get('idEcole');
            $idProfesseur = $session->get('idProfesseur');
            
            $descriptionBulletin = $request->get('descriptionBulletin');
            $idClasseBulletin = $request->get('idClasseBulletin');
            $idEleveBulletin = $request->get('idListeEleve');
            
            if(isset($idClasseBulletin) && isset($idEleveBulletin))
            {
                    try
                    {
                        $filename = $_FILES["fileBulletin"]["name"];
                        $file_basename = substr($filename, 0, strripos($filename, '.')); // get file extention
                        $file_ext = substr($filename, strripos($filename, '.')); // get file name
                        $filesize = $_FILES["fileBulletin"]["size"];
                        $allowed_file_types = array('.doc','.docx','.jpg','.jpeg','.gif','.png','.pdf','.DOC','.DOCX','.JPG','.JPEG','.GIF','.PNG','.PDF');
                        
                        if (in_array($file_ext,$allowed_file_types) && ($filesize < 8388608 ))
                        {
                            // Rename file
                            $newfilename = md5(uniqid()).$file_ext;
                            if (file_exists("uploads/".$idEcole."/bulletins/".$newfilename))
                            {
                                // file already exists error
                                echo "You have already uploaded this file.";
                            }
                            else
                            {
                                move_uploaded_file($_FILES["fileBulletin"]["tmp_name"], "uploads/".$idEcole."/bulletins/".$newfilename);
                                echo "File uploaded successfully.";
                            }
                        }
                        elseif (empty($file_basename))
                        {
                            // file selection error
                            echo "Please select a file to upload.";
                        }
                        elseif ($filesize > 8388608 )
                        {
                            // file size error
                            echo "The file you are trying to upload is too large.";
                        }
                        else
                        {
                            // file type error
                            echo "Only these file typs are allowed for upload: " . implode(', ',$allowed_file_types);
                            unlink($_FILES["fileBulletin"]["tmp_name"]);
                        }
                        
                        
                        
                        $this->addBulletin($descriptionBulletin,$idEleveBulletin,$newfilename,$idProfesseur,$idEcole);
                    }
                    catch(\Doctrine\DBAL\DBALException $e)
                    {
                        $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                        return $this->redirectToRoute('showAllBulletins', [
                            'request' => $request
                        ], 307);
                    }
            }
            else
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Vous devez remplir tous les champs');
            }
            
                return $this->redirectToRoute('showAllBulletins', [
                    'request' => $request
                ], 307);
            
            
        }
    }
    
    public function updateBulletinAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $descriptionBulletin = $request->get('descriptionBulletin');
            $idClasseBulletin = $request->get('idClasseBulletin');
            $idEleveBulletin = $request->get('idListeEleve');
            $idBulletin = $request->get('idBulletin');
            
            if(isset($idClasseBulletin) && isset($idEleveBulletin))
            {
                try
                {
                    
                    $this->updateBulletin($idBulletin, $descriptionBulletin,$idEleveBulletin);
                }
                catch(\Doctrine\DBAL\DBALException $e)
                {
                    $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                    return $this->redirectToRoute('showAllBulletins', [
                        'request' => $request
                    ], 307);
                }
            }
            else
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Vous devez remplir tous les champs');
            }
            
                return $this->redirectToRoute('showAllBulletins', [
                    'request' => $request
                ], 307);
            
            
        }
    }
    
    public function deleteBulletinAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idBulletin = $request->get('idBulletinDelete');
            
            try
            {
                $this->deleteBulletin($idBulletin);
                
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllBulletins', [
                    'request' => $request
                ], 307);
            }
            
                return $this->redirectToRoute('showAllBulletins', [
                    'request' => $request
                ], 307);
            
        }
    }
    
    /*****************************************************************************************************************/
    private function findAllBulletin($idProfesseur, $idEcole)
    {
        
        $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT b
            FROM
            PronoteBundle:Bulletin b
            WHERE
            b.professeur =:idProfesseur
            AND
            b.ecole =:idEcole
            AND
            b.eleve IS NOT NULL
            ORDER BY 
            b.dateBulletin DESC
         ")
         ->setParameters(['idProfesseur' => $idProfesseur, 'idEcole' => $idEcole]);
         
         $resultat = $query->getResult();
         
         return $resultat;
    }
    /********************************************* CRUD de la classe Bulletin******************************/
    public function showAllBulletin($idProfesseur, $idEcole)
    {
        
        $listeFinaleBulletins = array();
        
        $Bulletins = $this->findAllBulletin($idProfesseur, $idEcole);
        
        foreach ($Bulletins as $Bulletin)
        {
            
            $listeBulletins = Array(
                'idBulletin' => $Bulletin->getId(),
                'dateBulletin' => $Bulletin->getDateBulletin()->format('d-m-Y'),
                'descriptionBulletin' => $Bulletin->getDescription(),
                'fileBulletin' => $Bulletin->getFile(),
                'classeBulletin' => $Bulletin->getEleve()->getClasse()->getId(),
                'nomclasseBulletin' => $Bulletin->getEleve()->getClasse()->getNom(),
                'eleveBulletin' => $Bulletin->getEleve()->getId(),
                'prenomeleveBulletin' => $Bulletin->getEleve()->getPrenom(),
                'nomeleveBulletin' => $Bulletin->getEleve()->getNom()
                );
            
            $listeFinaleBulletins[] = $listeBulletins;
        }
        return $listeFinaleBulletins;
        
    }
    
    public function addBulletin($descriptionBulletin, $idEleveBulletin, $file, $idProfesseur, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Professeurs')
        ;
        $professeur = $repository->findOneBy(array('id' => $idProfesseur));

        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        $eleveBulletin = $repository->findOneBy(
            array('id' => $idEleveBulletin)
            );
        
        $em = $this->getDoctrine()->getManager();
        $Bulletin = new Bulletin();
        
        $Bulletin->setDescription($descriptionBulletin);
        $Bulletin->setDateBulletin(new DateTime(date("Y/m/d")));
        $Bulletin->setEleve($eleveBulletin);
        $Bulletin->setFile("uploads/".$idEcole."/bulletins/".$file);
        
        $Bulletin->setProfesseur($professeur);
        $Bulletin->setEcole($ecole);
        
        //Gestion de notification
        $detailsBulletin = " >> Bulletin des notes ajouté"." (". date("Y/m/d") .")";
        $lien = "login/".$eleveBulletin->getParent()->getId()."/".$idEleveBulletin;
        $this->addNotification($idEleveBulletin, $detailsBulletin, $lien, $idEcole);
        
        
        $em->persist($Bulletin);
        $em->flush();
    }
    
    public function updateBulletin($idBulletin, $descriptionBulletin, $idEleveBulletin)
    {
        //On récupére le BULLETIN
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Bulletin')
        ;
        
        $Bulletin = $repository->findOneBy(
            array('id' => $idBulletin)
            );
        
        //Update IDEleve Bulletin
        if($Bulletin->getEleve()->getId() != $idEleveBulletin)
        {
            $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Eleves')
            ;
            
            $eleveBulletin = $repository->findOneBy(
                array('id' => $idEleveBulletin)
                );
            $Bulletin->setEleve($eleveBulletin);
        }
        
        $Bulletin->setDescription($descriptionBulletin);
        $Bulletin->setDateBulletin(new DateTime(date("Y/m/d")));

        
        //Gestion de notification
        /*$detailsBulletin = " >> Bulletin des notes modifié"." (". date("Y/m/d") .")";
        $lien = "login/".$eleveBulletin->getParent()->getId()."/".$idEleveBulletin;
        $this->addNotification($idEleveBulletin, $detailsBulletin, $lien);*/
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($Bulletin);
        $em->flush();
    }
    
    public function deleteBulletin($idBulletin)
    {
        
        $em = $this->getDoctrine()->getManager();
        $Bulletin = $em->getRepository('PronoteBundle:Bulletin')->find($idBulletin);
        $path = $Bulletin->getFile();
        unlink($path);
        
        if (!$Bulletin) {
            throw $this->createNotFoundException(
                'Ce Bulletin est introuvable, ID = '.$idBulletin
                );
        }
        $em->remove($Bulletin);
        $em->flush();
    }
    
    /********************************************* CRUD de la classe Observation Générale******************************/
    /******************************************************************************************************************/
    private function findAllObservationsGenerales($idProfesseur, $idEcole)
    {
        /*$repository = $this
         ->getDoctrine()
         ->getManager()
         ->getRepository('PronoteBundle:ObservationsGenerales')
         ;
         
         return $repository->findBy(array('professeur' => $idProfesseur)/*, array('ecole' => $idEcole)*///);
        
    }
    /********************************************** Afficher Tous les Observation Générale ****************************/
    public function showAllObservationsGenerales($idProfesseur, $idEcole)
    {
        
        $listeFinaleObservationGenerales = Array();
        
        $observationGenerales = $this->findAllObservationsGenerales($idProfesseur, $idEcole);
        
        foreach ($observationGenerales as $observationGenerale)
        {
            $listeObservationGenerales = Array(
                'idObservationGenerale' => $observationGenerale->getId(),
                'descriptionObservationGenerale' => $observationGenerale->getDescription(),
                'classeObservationGenerale' => $observationGenerale->getClasse()->getId(),
                'nomclasseObservationGenerale' => $observationGenerale->getClasse()->getNom()
                );
            
            $listeFinaleObservationGenerales[] = $listeObservationGenerales;
        }
        return $listeFinaleObservationGenerales;
        
    }
    
    /************************************* Afficher Une Seule Observation Générale Par ID******************************/
    public function showOneObservationGenerale($idObservationGenerale)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:ObservationsGenerales')
        ;
        
        $observationGenerales = $repository->findOneBy(
            array('id' => $idObservationGenerale)
            );
        
        foreach ($observationGenerales as $observationGenerale)
        {
            $listeObservationGenerales = Array(
                'idObservationGenerale' => $observationGenerale->getId(),
                'descriptionObservationGenerale' => $observationGenerale->getDescription(),
                'classeObservationGenerale' => $observationGenerale->getClasse()->getId()
                );
        }
        return $listeObservationGenerales;
        
    }
    
    /*************************************** Ajouter une Observation Générale *****************************************/
    public function addObservationGenerale($descriptionObservationGenerale, $idClasseObservationGenerale, $idProfesseur)
    {
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Classe')
        ;
        
        $classeObservationGenerale = $repository->findOneBy(
            array('id' => $idClasseObservationGenerale)
            );
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Professeurs')
        ;
        
        $professeurObservationGenerale = $repository->findOneBy(
            array('id' => $idProfesseur)
            );
        
        $em = $this->getDoctrine()->getManager();
        $observationGenerale = new ObservationsGenerales();
        
        $observationGenerale->setDescription($descriptionObservationGenerale);
        $observationGenerale->setClasse($classeObservationGenerale);
        $observationGenerale->setProfesseur($professeurObservationGenerale);
        
        $em->persist($observationGenerale);
        $em->flush();
    }
    
    /********************************************** Modifier une Observation Générale *********************************/
    public function updateObservationGenerale($idObservationGenerale, $descriptionObservationGenerale, $idClasseObservationGenerale)
    {
        $em = $this->getDoctrine()->getManager();
        $observationGenerale = $em->getRepository('PronoteBundle:ObservationsGenerales')->find($idObservationGenerale);
        
        
        if (!$observationGenerale) {
            throw $this->createNotFoundException(
                'Cette Observation Générale est introuvable, ID = '.$idObservationGenerale
                );
        }
        
        if(!empty($descriptionObservationGenerale)){
            $observationGenerale->setDescription($descriptionObservationGenerale);
        }
        
        if(!empty($idClasseObservationGenerale)){
            $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Classe')
            ;
            
            $classeObservationGenerale = $repository->findOneBy(
                array('id' => $idClasseObservationGenerale)
                );
            $observationGenerale->setClasse($classeObservationGenerale);
        }
        
        $em->flush();
        
        
    }
    
    /********************************************** Supprimer une Observation Générale par ID**************************/
    public function deleteObservationGenerale($idObservationGenerale)
    {
        
        $em = $this->getDoctrine()->getManager();
        $observationGenerale = $em->getRepository('PronoteBundle:ObservationsGenerales')->find($idObservationGenerale);
        
        if (!$observationGenerale) {
            throw $this->createNotFoundException(
                'Cette Observation Générale est introuvable, ID = '.$idObservationGenerale
                );
        }
        $em->remove($observationGenerale);
        $em->flush();
    }
    
    /****************************************** FIN CRUD de la classe Observations Générales***************************/
    public function updateAnalytics($idEcole,$profil)
    {
        
        $em = $this->getDoctrine()->getManager();
        
        $repository = $this->getDoctrine()->getRepository(Analytics::class);
        
        //je récupère la ligne à incrémenter
        $analytics = $repository->createQueryBuilder('a')
        ->where('a.ecole = ?1')
        ->andWhere('a.profil = ?2')
        ->setParameters(array('1'=> $idEcole, '2' => $profil))
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
        
        //Je récupère l'objet et je fais la mise à jour compteur
        $ligneAIncrementer = $repository->find($analytics->getId());
        $ligneAIncrementer->setCompteur($ligneAIncrementer->getCompteur()+1);
        
        $em->persist($ligneAIncrementer);
        $em->flush();
        
        return $ligneAIncrementer->getCompteur();
        
    }
    
    
}
