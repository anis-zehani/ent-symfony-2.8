<?php

namespace PronoteBundle\Controller;

use PronoteBundle\Entity\Seance;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Session\Session;

use PronoteBundle\Entity\Telechargements;
use PronoteBundle\Entity\Emploi;
use PronoteBundle\Entity\ObservationsGenerales;
use PronoteBundle\Entity\ActivitesScolaires;
use PronoteBundle\Entity\Matieres;
use PronoteBundle\Entity\Admin;
use PronoteBundle\Entity\Salles;
use PronoteBundle\Entity\Classe;
use PronoteBundle\Entity\Parents;
use PronoteBundle\Entity\Eleves;
use PronoteBundle\Entity\Professeurs;
use PronoteBundle\Entity\MenuCantine;
use PronoteBundle\Entity\NotificationsClasse;
use PronoteBundle\Entity\Analytics;

use \Datetime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminController extends Controller
{
    //variable qui vérifie que la session est encore active
    //Par la vérification qu'une variabl n'est pas vide
    public function sessionAction(Request $request)
    {
        $session = $this->get('session');
        return new JsonResponse($session->get('adminSession'));
    }
    
    public function exitAdminSessionAction(Request $request)
    {
        $session = $this->get('session');
        //$session->invalidate();
        //$session->remove('idEcole');
        $session->remove('adminSession');
        
        return $this->redirectToRoute('admin_Homepage');
    }
    
    public function indexAction(Request $request)
    {

            $repository = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('PronoteBundle:Admin')
            ;

            if ('POST' === $request->getMethod())
            {
                $login = $request->get('login');
                $password = $request->get('password');
                
                $admin = $repository->findOneBy(array('login' => $login, 'password' => $password));
                
                if (empty($admin))
                {
                    return $this->render('PronoteBundle:Admin:index.html.twig');
                }
                else
                {
                    $idEcole = $admin->getEcole()->getId();
                    $informationsPratiques = $this->informationsPratiques($idEcole);
                    //Récupérer le IdEcole dans la Session
                    $session = $this->get('session');
                    $session->start();
                    $session->set('idEcole', $idEcole);
                    $session->set('adminSession', "SessionAdminIsOn");
                    
                    //Mise à jour compteur "superadmin" ou bien "admin"
                    if($admin->getProfil()=='superadmin')
                    {
                        $analytics = $this->updateAnalytics($idEcole,"superadmin");
                    }
                    else 
                    {
                        $analytics = $this->updateAnalytics($idEcole,"admin");
                    }
                    
                    //return new JsonResponse($analytics);
                    
                    return $this->render('PronoteBundle:Admin:main.html.twig', $informationsPratiques);

                }
            }
            
            return $this->render('PronoteBundle:Admin:index.html.twig');

    }

    //Écran principal : statistiques école + liens vers les services
    public function mainAction(Request $request)
    {
        
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Logo École est inclu dèja dans $informationsPratiques
        $informationsPratiques = $this->informationsPratiques($idEcole);
        
        return $this->render('PronoteBundle:Admin:main.html.twig', $informationsPratiques);
        //return new JsonResponse($informationsPratiques);
    }

    /**************************************** Contrôleurs de la classe Emploi du Temps ********************************/
    /******************************************************************************************************************/
    public function showAllEmploisDuTempsAction(Request $request)
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(!empty($ecole->getLogo()))
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render(
            'PronoteBundle:Admin:mainEmploi.html.twig', 
            array(
            'allEmploisDuTemps' => $this->showAllEmploisDuTemps($idEcole), 
            'allClasses' => $this->showAllClassesWithoutEmploi($idEcole), 
            'allSalles' => $this->showAllSalles($idEcole) , 
            'allMatieres' => $this->showAllMatieres($idEcole),
            'logoEcole'=> $logoEcole
            ));
       
    }
    
    
    /******************************************************************************************************************/
    public function showOneEmploiDuTempsAction(Request $request)
    {
        
        $idEmploi = $request->get('idEmploiDuTemps');
        
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }

        return $this->render(
            'PronoteBundle:Admin:modifierEmploiPopModale.html.twig', 
            array(
            'allEmplois' => $this->findEmploiFromClasse($idEmploi), 
            'classeAndSalle' => $this->showClasseAndSalleForEmploi($idEmploi) , 
            'idEmploi' => $idEmploi,
            'allClasses' => $this->showAllClassesWithoutEmploi($idEcole), 
            'allSalles' => $this->showAllSalles($idEcole), 
            'allMatieres' => $this->showAllMatieres($idEcole),
            'logoEcole'=> $logoEcole
            ));
    }


    /********************************************** Afficher Tous les Emplois du Temps *********************************/
    public function showClasseAndSalleForEmploi($idEmploi)
    {
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Emploi')
        ;

        $emploiDuTemps = $repository->find($idEmploi);
        
        //Si Emploi sans Classe
        if(null ==$emploiDuTemps->getClasse())
        {
            $classeEmploiDuTemps = "NULL";
            $nomClasseEmploiDuTemps = "Aucune Classe";
        }
        else
        {
            $classeEmploiDuTemps = $emploiDuTemps->getClasse()->getId();
            $nomClasseEmploiDuTemps = $emploiDuTemps->getClasse()->getNom();
        }
        
        //Si Emploi sans Salle
        if(null ==$emploiDuTemps->getSalle())
        {
            $salleEmploiDuTemps = "NULL";
            $nomSalleEmploiDuTemps = "Aucune Salle";
        }
        else
        {
            $salleEmploiDuTemps = $emploiDuTemps->getSalle()->getId();
            $nomSalleEmploiDuTemps = $emploiDuTemps->getSalle()->getIdentificateur();
        }

            $listeAllEmploisDuTemps = Array(
                'idEmploiDuTemps' => $emploiDuTemps->getId(),
                'idClasse' => $classeEmploiDuTemps,
                'nomClasse' => $nomClasseEmploiDuTemps,
                'idSalle' => $salleEmploiDuTemps,
                'nomSalle' => $nomSalleEmploiDuTemps,
                'designation' => $emploiDuTemps->getDesignation()
            );


        return $listeAllEmploisDuTemps;
    }

    /********************************************* Gestion Emploi du temps ********************************************/
    /******************************************************************************************************************/
    private function findAllEmploisDuTemps($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Emploi')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('designation' => 'ASC'));
    }
    /******************************************************************************************************************/
    public function showAllEmploisDuTemps($idEcole)
    {
        
        $listeFinaleAllEmploisDuTemps = array();

        $allEmploisDuTemps = $this->findAllEmploisDuTemps($idEcole);

        foreach ($allEmploisDuTemps as $emploiDuTemps)
        {
            
            //Si Emploi sans Classe
            if(null ==$emploiDuTemps->getClasse())
            {
                $classeEmploiDuTemps = "NULL";
                $nomClasseEmploiDuTemps = "Aucune Classe";
            }
            else
            {
                $classeEmploiDuTemps = $emploiDuTemps->getClasse()->getId();
                $nomClasseEmploiDuTemps = $emploiDuTemps->getClasse()->getNom();
            }
            
            //Si Emploi sans Salle
            if(null ==$emploiDuTemps->getSalle())
            {
                $salleEmploiDuTemps = "NULL";
                $nomSalleEmploiDuTemps = "Aucune Salle";
            }
            else
            {
                $salleEmploiDuTemps = $emploiDuTemps->getSalle()->getId();
                $nomSalleEmploiDuTemps = $emploiDuTemps->getSalle()->getIdentificateur();
            }
            
            //Liste tous les emplois : Avec ou Sans (Classe/Salle)
            $listeAllEmploisDuTemps = Array(
                'idEmploiDuTemps' => $emploiDuTemps->getId(),
                'classeEmploiDuTemps' => $classeEmploiDuTemps,
                'nomClasseEmploiDuTemps' => $nomClasseEmploiDuTemps,
                'salleEmploiDuTemps' => $salleEmploiDuTemps,
                'nomSalleEmploiDuTemps' => $nomSalleEmploiDuTemps,
                'designationEmploiDuTemps' => $emploiDuTemps->getDesignation()
            );
            

            $listeFinaleAllEmploisDuTemps[] = $listeAllEmploisDuTemps;
        }
        return $listeFinaleAllEmploisDuTemps;

    }

    public function updateEmploiDuTempsAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {

            $this->updateEmploiDuTemps($request);

            return $this->redirectToRoute('showAllEmploisDuTemps', [
                'request' => $request
            ], 307);

        }
    }

    public function updateEmploiDuTemps($request)
    {
        $idEmploiDuTemps = $request->get('idEmploiDuTemps');
        
        $idSalleEmploiDuTemps = $request->get('salleEmploiDuTemps');
        $idClasseEmploiDuTemps = $request->get('classeEmploiDuTemps');
        
        $designation = $request->get('idDesignation');

        $listeEnfants_1_1 = $request->get('listeEnfants_1_1');
        $listeEnfants_1_2 = $request->get('listeEnfants_1_2');
        $listeEnfants_1_3 = $request->get('listeEnfants_1_3');
        $listeEnfants_1_4 = $request->get('listeEnfants_1_4');
        $listeEnfants_1_5 = $request->get('listeEnfants_1_5');
        $listeEnfants_1_6 = $request->get('listeEnfants_1_6');
        $listeEnfants_1_7 = $request->get('listeEnfants_1_7');
        $listeEnfants_1_8 = $request->get('listeEnfants_1_8');
        $listeEnfants_1_9 = $request->get('listeEnfants_1_9');
        $listeEnfants_1_10 = $request->get('listeEnfants_1_10');

        $listeEnfants_2_1 = $request->get('listeEnfants_2_1');
        $listeEnfants_2_2 = $request->get('listeEnfants_2_2');
        $listeEnfants_2_3 = $request->get('listeEnfants_2_3');
        $listeEnfants_2_4 = $request->get('listeEnfants_2_4');
        $listeEnfants_2_5 = $request->get('listeEnfants_2_5');
        $listeEnfants_2_6 = $request->get('listeEnfants_2_6');
        $listeEnfants_2_7 = $request->get('listeEnfants_2_7');
        $listeEnfants_2_8 = $request->get('listeEnfants_2_8');
        $listeEnfants_2_9 = $request->get('listeEnfants_2_9');
        $listeEnfants_2_10 = $request->get('listeEnfants_2_10');

        $listeEnfants_3_1 = $request->get('listeEnfants_3_1');
        $listeEnfants_3_2 = $request->get('listeEnfants_3_2');
        $listeEnfants_3_3 = $request->get('listeEnfants_3_3');
        $listeEnfants_3_4 = $request->get('listeEnfants_3_4');
        $listeEnfants_3_5 = $request->get('listeEnfants_3_5');
        $listeEnfants_3_6 = $request->get('listeEnfants_3_6');
        $listeEnfants_3_7 = $request->get('listeEnfants_3_7');
        $listeEnfants_3_8 = $request->get('listeEnfants_3_8');
        $listeEnfants_3_9 = $request->get('listeEnfants_3_9');
        $listeEnfants_3_10 = $request->get('listeEnfants_3_10');

        $listeEnfants_4_1 = $request->get('listeEnfants_4_1');
        $listeEnfants_4_2 = $request->get('listeEnfants_4_2');
        $listeEnfants_4_3 = $request->get('listeEnfants_4_3');
        $listeEnfants_4_4 = $request->get('listeEnfants_4_4');
        $listeEnfants_4_5 = $request->get('listeEnfants_4_5');
        $listeEnfants_4_6 = $request->get('listeEnfants_4_6');
        $listeEnfants_4_7 = $request->get('listeEnfants_4_7');
        $listeEnfants_4_8 = $request->get('listeEnfants_4_8');
        $listeEnfants_4_9 = $request->get('listeEnfants_4_9');
        $listeEnfants_4_10 = $request->get('listeEnfants_4_10');

        $listeEnfants_5_1 = $request->get('listeEnfants_5_1');
        $listeEnfants_5_2 = $request->get('listeEnfants_5_2');
        $listeEnfants_5_3 = $request->get('listeEnfants_5_3');
        $listeEnfants_5_4 = $request->get('listeEnfants_5_4');
        $listeEnfants_5_5 = $request->get('listeEnfants_5_5');
        $listeEnfants_5_6 = $request->get('listeEnfants_5_6');
        $listeEnfants_5_7 = $request->get('listeEnfants_5_7');
        $listeEnfants_5_8 = $request->get('listeEnfants_5_8');
        $listeEnfants_5_9 = $request->get('listeEnfants_5_9');
        $listeEnfants_5_10 = $request->get('listeEnfants_5_10');


        $em = $this->getDoctrine()->getManager();
        
        $emploiDuTemps = $em->getRepository('PronoteBundle:Emploi')->find($idEmploiDuTemps);

        //Ajout de la Désignation si non vide
        if(!empty($designation))
        {
            $emploiDuTemps->setDesignation($designation);
        }

        //Ajout de la Salle si non vide
        
        if((!empty($idSalleEmploiDuTemps)) and $idSalleEmploiDuTemps!='0')
        {
            $salle = $em->getRepository('PronoteBundle:Salles')->find($idSalleEmploiDuTemps);
            
        }
        else
        {
            $salle = $em->getRepository('PronoteBundle:Salles')->findOneBy(array('id' => '0'));
        }
        $emploiDuTemps->setSalle($salle);
        
        //Ajout de la Classe si non vide
        
        if((!empty($idClasseEmploiDuTemps)) and $idClasseEmploiDuTemps!='0')
        {
            $classe = $em->getRepository('PronoteBundle:Classe')->find($idClasseEmploiDuTemps);
            
        }
        else
        {
            $classe = $em->getRepository('PronoteBundle:Classe')->findOneBy(array('id' => '0'));
        }
        $emploiDuTemps->setClasse($classe);
        
        //Ces 50 tests cherchent les 50 séances de l'emploi et remplacent l'ID de la matière
        
        if(!empty($listeEnfants_1_1))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_1_1);
            
            if($emploiDuTemps->getSeance_1_1()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_1_1());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_1($seance->getId());
            
        }
        
        if(!empty($listeEnfants_1_2))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_1_2);
            
            if($emploiDuTemps->getSeance_1_2()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_1_2());
            }
            else 
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_2($seance->getId());
        }
        
        if(!empty($listeEnfants_1_3))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_1_3);
            
            if($emploiDuTemps->getSeance_1_3()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_1_3());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_3($seance->getId());
            
        }
        
        if(!empty($listeEnfants_1_4))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_1_4);
            
            if($emploiDuTemps->getSeance_1_4()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_1_4());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_4($seance->getId());
            
        }
        
        if(!empty($listeEnfants_1_5))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_1_5);
            
            if($emploiDuTemps->getSeance_1_5()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_1_5());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_5($seance->getId());
            
        }
        
        if(!empty($listeEnfants_1_6))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_1_6);
            
            if($emploiDuTemps->getSeance_1_6()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_1_6());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_6($seance->getId());
            
        }
        
        if(!empty($listeEnfants_1_7))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_1_7);
            
            if($emploiDuTemps->getSeance_1_7()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_1_7());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_7($seance->getId());
            
        }
        
        if(!empty($listeEnfants_1_8))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_1_8);
            
            if($emploiDuTemps->getSeance_1_8()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_1_8());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_8($seance->getId());
            
        }
        
        if(!empty($listeEnfants_1_9))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_1_9);
            
            if($emploiDuTemps->getSeance_1_9()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_1_9());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_9($seance->getId());
            
        }
        
        if(!empty($listeEnfants_1_10))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_1_10);
            
            if($emploiDuTemps->getSeance_1_10()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_1_10());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_10($seance->getId());
            
        }
        
        
        if(!empty($listeEnfants_2_1))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_2_1);
            
            if($emploiDuTemps->getSeance_2_1()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_2_1());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_1($seance->getId());
            
        }
        
        if(!empty($listeEnfants_2_2))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_2_2);
            
            if($emploiDuTemps->getSeance_2_2()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_2_2());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_2($seance->getId());
        }
        
        if(!empty($listeEnfants_2_3))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_2_3);
            
            if($emploiDuTemps->getSeance_2_3()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_2_3());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_3($seance->getId());
            
        }
        
        if(!empty($listeEnfants_2_4))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_2_4);
            
            if($emploiDuTemps->getSeance_2_4()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_2_4());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_4($seance->getId());
            
        }
        
        if(!empty($listeEnfants_2_5))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_2_5);
            
            if($emploiDuTemps->getSeance_2_5()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_2_5());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_5($seance->getId());
            
        }
        
        if(!empty($listeEnfants_2_6))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_2_6);
            
            if($emploiDuTemps->getSeance_2_6()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_2_6());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_6($seance->getId());
            
        }
        
        if(!empty($listeEnfants_2_7))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_2_7);
            
            if($emploiDuTemps->getSeance_2_7()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_2_7());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_7($seance->getId());
            
        }
        
        if(!empty($listeEnfants_2_8))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_2_8);
            
            if($emploiDuTemps->getSeance_2_8()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_2_8());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_8($seance->getId());
            
        }
        
        if(!empty($listeEnfants_2_9))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_2_9);
            
            if($emploiDuTemps->getSeance_2_9()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_2_9());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_9($seance->getId());
            
        }
        
        if(!empty($listeEnfants_2_10))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_2_10);
            
            if($emploiDuTemps->getSeance_2_10()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_2_10());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_10($seance->getId());
            
        }
        
        if(!empty($listeEnfants_3_1))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_3_1);
            
            if($emploiDuTemps->getSeance_3_1()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_3_1());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_1($seance->getId());
            
        }
        
        if(!empty($listeEnfants_3_2))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_3_2);
            
            if($emploiDuTemps->getSeance_3_2()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_3_2());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_2($seance->getId());
        }
        
        if(!empty($listeEnfants_3_3))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_3_3);
            
            if($emploiDuTemps->getSeance_3_3()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_3_3());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_3($seance->getId());
            
        }
        
        if(!empty($listeEnfants_3_4))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_3_4);
            
            if($emploiDuTemps->getSeance_3_4()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_3_4());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_4($seance->getId());
            
        }
        
        if(!empty($listeEnfants_3_5))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_3_5);
            
            if($emploiDuTemps->getSeance_3_5()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_3_5());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_5($seance->getId());
            
        }
        
        if(!empty($listeEnfants_3_6))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_3_6);
            
            if($emploiDuTemps->getSeance_3_6()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_3_6());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_6($seance->getId());
            
        }
        
        if(!empty($listeEnfants_3_7))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_3_7);
            
            if($emploiDuTemps->getSeance_3_7()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_3_7());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_7($seance->getId());
            
        }
        
        if(!empty($listeEnfants_3_8))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_3_8);
            
            if($emploiDuTemps->getSeance_3_8()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_3_8());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_8($seance->getId());
            
        }
        
        if(!empty($listeEnfants_3_9))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_3_9);
            
            if($emploiDuTemps->getSeance_3_9()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_3_9());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_9($seance->getId());
            
        }
        
        if(!empty($listeEnfants_3_10))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_3_10);
            
            if($emploiDuTemps->getSeance_3_10()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_3_10());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_10($seance->getId());
            
        }
        
        
        if(!empty($listeEnfants_4_1))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_4_1);
            
            if($emploiDuTemps->getSeance_4_1()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_4_1());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_1($seance->getId());
            
        }
        
        if(!empty($listeEnfants_4_2))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_4_2);
            
            if($emploiDuTemps->getSeance_4_2()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_4_2());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_2($seance->getId());
        }
        
        if(!empty($listeEnfants_4_3))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_4_3);
            
            if($emploiDuTemps->getSeance_4_3()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_4_3());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_3($seance->getId());
            
        }
        
        if(!empty($listeEnfants_4_4))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_4_4);
            
            if($emploiDuTemps->getSeance_4_4()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_4_4());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_4($seance->getId());
            
        }
        
        if(!empty($listeEnfants_4_5))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_4_5);
            
            if($emploiDuTemps->getSeance_4_5()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_4_5());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_5($seance->getId());
            
        }
        
        if(!empty($listeEnfants_4_6))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_4_6);
            
            if($emploiDuTemps->getSeance_4_6()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_4_6());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_6($seance->getId());
            
        }
        
        if(!empty($listeEnfants_4_7))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_4_7);
            
            if($emploiDuTemps->getSeance_4_7()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_4_7());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_7($seance->getId());
            
        }
        
        if(!empty($listeEnfants_4_8))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_4_8);
            
            if($emploiDuTemps->getSeance_4_8()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_4_8());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_8($seance->getId());
            
        }
        
        if(!empty($listeEnfants_4_9))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_4_9);
            
            if($emploiDuTemps->getSeance_4_9()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_4_9());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_9($seance->getId());
            
        }
        
        if(!empty($listeEnfants_4_10))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_4_10);
            
            if($emploiDuTemps->getSeance_4_10()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_4_10());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_10($seance->getId());
            
        }
        
        if(!empty($listeEnfants_5_1))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_5_1);
            
            if($emploiDuTemps->getSeance_5_1()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_5_1());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_1($seance->getId());
            
        }
        
        if(!empty($listeEnfants_5_2))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_5_2);
            
            if($emploiDuTemps->getSeance_5_2()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_5_2());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_2($seance->getId());
        }
        
        if(!empty($listeEnfants_5_3))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_5_3);
            
            if($emploiDuTemps->getSeance_5_3()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_5_3());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_3($seance->getId());
            
        }
        
        if(!empty($listeEnfants_5_4))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_5_4);
            
            if($emploiDuTemps->getSeance_5_4()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_5_4());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_4($seance->getId());
            
        }
        
        if(!empty($listeEnfants_5_5))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_5_5);
            
            if($emploiDuTemps->getSeance_5_5()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_5_5());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_5($seance->getId());
            
        }
        
        if(!empty($listeEnfants_5_6))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_5_6);
            
            if($emploiDuTemps->getSeance_5_6()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_5_6());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_6($seance->getId());
            
        }
        
        if(!empty($listeEnfants_5_7))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_5_7);
            
            if($emploiDuTemps->getSeance_5_7()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_5_7());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_7($seance->getId());
            
        }
        
        if(!empty($listeEnfants_5_8))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_5_8);
            
            if($emploiDuTemps->getSeance_5_8()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_5_8());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_8($seance->getId());
            
        }
        
        if(!empty($listeEnfants_5_9))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_5_9);
            
            if($emploiDuTemps->getSeance_5_9()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_5_9());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_9($seance->getId());
            
        }
        
        if(!empty($listeEnfants_5_10))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($listeEnfants_5_10);
            
            if($emploiDuTemps->getSeance_5_10()!=0)
            {
                $seance = $em->getRepository('PronoteBundle:Seance')->find($emploiDuTemps->getSeance_5_10());
            }
            else
            {
                $seance = new Seance();
            }
            
            $seance->setMatiere($matiere);
            $seance->setSalle($salle);
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_10($seance->getId());
            
        }
        
        
        //Gestion de notification
        $detailsEmploiUpdate = " >> Emploi du Temps mis à jour"." (". date("Y/m/d") .")";
        
        $lien = "emploi/";
        
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        if(isset($idClasseEmploiDuTemps) and (null!=$idClasseEmploiDuTemps))
        {
            $this->addNotificationClasse($idClasseEmploiDuTemps, $detailsEmploiUpdate, $lien, $idEcole);
        }
        

        $em->persist($emploiDuTemps);
        $em->flush();

    }

    public function addEmploiDuTempsAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');
            
            try
            {
                
                $this->addEmploiDuTemps($request, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l ajout d un emploi');
                return $this->redirectToRoute('showAllEmploisDuTemps', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllEmploisDuTemps', [
                    'request' => $request
                ], 307);

        }
    }

    public function addEmploiDuTemps($request, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        
        $em = $this->getDoctrine()->getManager();
        
        $idSalleEmploiDuTemps = $request->get('idSalleAjout');
        $idClasseEmploiDuTemps = $request->get('idClasseAjout');
        
        $designation = $request->get('idDesignation');


        $idMatiere_1_1 = $request->get('idMatiere_1_1');
        $idMatiere_1_2 = $request->get('idMatiere_1_2');
        $idMatiere_1_3 = $request->get('idMatiere_1_3');
        $idMatiere_1_4 = $request->get('idMatiere_1_4');
        $idMatiere_1_5 = $request->get('idMatiere_1_5');
        $idMatiere_1_6 = $request->get('idMatiere_1_6');
        $idMatiere_1_7 = $request->get('idMatiere_1_7');
        $idMatiere_1_8 = $request->get('idMatiere_1_8');
        $idMatiere_1_9 = $request->get('idMatiere_1_9');
        $idMatiere_1_10 = $request->get('idMatiere_1_10');

        $idMatiere_2_1 = $request->get('idMatiere_2_1');
        $idMatiere_2_2 = $request->get('idMatiere_2_2');
        $idMatiere_2_3 = $request->get('idMatiere_2_3');
        $idMatiere_2_4 = $request->get('idMatiere_2_4');
        $idMatiere_2_5 = $request->get('idMatiere_2_5');
        $idMatiere_2_6 = $request->get('idMatiere_2_6');
        $idMatiere_2_7 = $request->get('idMatiere_2_7');
        $idMatiere_2_8 = $request->get('idMatiere_2_8');
        $idMatiere_2_9 = $request->get('idMatiere_2_9');
        $idMatiere_2_10 = $request->get('idMatiere_2_10');

        $idMatiere_3_1 = $request->get('idMatiere_3_1');
        $idMatiere_3_2 = $request->get('idMatiere_3_2');
        $idMatiere_3_3 = $request->get('idMatiere_3_3');
        $idMatiere_3_4 = $request->get('idMatiere_3_4');
        $idMatiere_3_5 = $request->get('idMatiere_3_5');
        $idMatiere_3_6 = $request->get('idMatiere_3_6');
        $idMatiere_3_7 = $request->get('idMatiere_3_7');
        $idMatiere_3_8 = $request->get('idMatiere_3_8');
        $idMatiere_3_9 = $request->get('idMatiere_3_9');
        $idMatiere_3_10 = $request->get('idMatiere_3_10');

        $idMatiere_4_1 = $request->get('idMatiere_4_1');
        $idMatiere_4_2 = $request->get('idMatiere_4_2');
        $idMatiere_4_3 = $request->get('idMatiere_4_3');
        $idMatiere_4_4 = $request->get('idMatiere_4_4');
        $idMatiere_4_5 = $request->get('idMatiere_4_5');
        $idMatiere_4_6 = $request->get('idMatiere_4_6');
        $idMatiere_4_7 = $request->get('idMatiere_4_7');
        $idMatiere_4_8 = $request->get('idMatiere_4_8');
        $idMatiere_4_9 = $request->get('idMatiere_4_9');
        $idMatiere_4_10 = $request->get('idMatiere_4_10');

        $idMatiere_5_1 = $request->get('idMatiere_5_1');
        $idMatiere_5_2 = $request->get('idMatiere_5_2');
        $idMatiere_5_3 = $request->get('idMatiere_5_3');
        $idMatiere_5_4 = $request->get('idMatiere_5_4');
        $idMatiere_5_5 = $request->get('idMatiere_5_5');
        $idMatiere_5_6 = $request->get('idMatiere_5_6');
        $idMatiere_5_7 = $request->get('idMatiere_5_7');
        $idMatiere_5_8 = $request->get('idMatiere_5_8');
        $idMatiere_5_9 = $request->get('idMatiere_5_9');
        $idMatiere_5_10 = $request->get('idMatiere_5_10');

        //On définit un nouveau Emploi du temps
        $emploiDuTemps = new Emploi();

        $emploiDuTemps->setEcole($ecole);

        //Ajout de la Désignation si non vide
        if(!empty($designation))
        {
            $emploiDuTemps->setDesignation($designation);
        }
        
        //Ajout de la Salle si non vide
        $salle = null;
        if(!empty($idSalleEmploiDuTemps) and strcmp("NULL",$idSalleEmploiDuTemps)!=0)
        {
            $salle = $em->getRepository('PronoteBundle:Salles')->find($idSalleEmploiDuTemps);
            $emploiDuTemps->setSalle($salle);
        }
        
        //Ajout de la Classe si non vide
        $classe = null;
        if(!empty($idClasseEmploiDuTemps) and strcmp("NULL",$idClasseEmploiDuTemps)!=0)
        {
            $classe = $em->getRepository('PronoteBundle:Classe')->find($idClasseEmploiDuTemps);
            $emploiDuTemps->setClasse($classe);
        }
        
        
        if(!empty($idMatiere_1_1))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_1_1);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_1($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_1_1(0);
        }
        
        if(!empty($idMatiere_1_2))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_1_2);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_2($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_1_2(0);
        }
        
        if(!empty($idMatiere_1_3))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_1_3);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_3($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_1_3(0);
        }
        
        if(!empty($idMatiere_1_4))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_1_4);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_4($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_1_4(0);
        }
        
        if(!empty($idMatiere_1_5))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_1_5);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_5($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_1_5(0);
        }
        
        if(!empty($idMatiere_1_6))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_1_6);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_6($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_1_6(0);
        }
        
        if(!empty($idMatiere_1_7))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_1_7);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_7($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_1_7(0);
        }
        
        if(!empty($idMatiere_1_8))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_1_8);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_8($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_1_8(0);
        }
        
        if(!empty($idMatiere_1_9))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_1_9);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_9($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_1_9(0);
        }
        
        if(!empty($idMatiere_1_10))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_1_10);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_1_10($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_1_10(0);
        }
        
        if(!empty($idMatiere_2_1))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_2_1);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_1($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_2_1(0);
        }
        
        if(!empty($idMatiere_2_2))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_2_2);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_2($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_2_2(0);
        }
        
        if(!empty($idMatiere_2_3))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_2_3);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_3($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_2_3(0);
        }
        
        if(!empty($idMatiere_2_4))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_2_4);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_4($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_2_4(0);
        }
        
        if(!empty($idMatiere_2_5))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_2_5);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_5($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_2_5(0);
        }
        
        if(!empty($idMatiere_2_6))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_2_6);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_6($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_2_6(0);
        }
        
        if(!empty($idMatiere_2_7))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_2_7);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_7($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_2_7(0);
        }
        
        if(!empty($idMatiere_2_8))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_2_8);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_8($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_2_8(0);
        }
        
        if(!empty($idMatiere_2_9))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_2_9);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_9($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_2_9(0);
        }
        
        if(!empty($idMatiere_2_10))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_2_10);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_2_10($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_2_10(0);
        }
        if(!empty($idMatiere_3_1))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_3_1);
            
            
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_1($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_3_1(0);
        }
        
        if(!empty($idMatiere_3_2))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_3_2);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_2($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_3_2(0);
        }
        
        if(!empty($idMatiere_3_3))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_3_3);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_3($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_3_3(0);
        }
        
        if(!empty($idMatiere_3_4))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_3_4);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_4($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_3_4(0);
        }
        
        if(!empty($idMatiere_3_5))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_3_5);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_5($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_3_5(0);
        }
        
        if(!empty($idMatiere_3_6))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_3_6);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_6($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_3_6(0);
        }
        
        if(!empty($idMatiere_3_7))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_3_7);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_7($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_3_7(0);
        }
        
        if(!empty($idMatiere_3_8))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_3_8);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_8($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_3_8(0);
        }
        
        if(!empty($idMatiere_3_9))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_3_9);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_9($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_3_9(0);
        }
        
        if(!empty($idMatiere_3_10))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_3_10);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_3_10($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_3_10(0);
        }
        if(!empty($idMatiere_4_1))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_4_1);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_1($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_4_1(0);
        }
        
        if(!empty($idMatiere_4_2))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_4_2);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_2($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_4_2(0);
        }
        
        if(!empty($idMatiere_4_3))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_4_3);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_3($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_4_3(0);
        }
        
        if(!empty($idMatiere_4_4))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_4_4);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_4($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_4_4(0);
        }
        
        if(!empty($idMatiere_4_5))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_4_5);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_5($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_4_5(0);
        }
        
        if(!empty($idMatiere_4_6))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_4_6);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_6($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_4_6(0);
        }
        
        if(!empty($idMatiere_4_7))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_4_7);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_7($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_4_7(0);
        }
        
        if(!empty($idMatiere_4_8))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_4_8);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_8($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_4_8(0);
        }
        
        if(!empty($idMatiere_4_9))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_4_9);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_9($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_4_9(0);
        }
        
        if(!empty($idMatiere_4_10))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_4_10);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_4_10($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_4_10(0);
        }
        
        if(!empty($idMatiere_5_1))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_5_1);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_1($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_5_1(0);
        }
        
        if(!empty($idMatiere_5_2))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_5_2);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_2($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_5_2(0);
        }
        
        if(!empty($idMatiere_5_3))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_5_3);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            
            $emploiDuTemps->setSeance_5_3($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_5_3(0);
        }
        
        if(!empty($idMatiere_5_4))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_5_4);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_4($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_5_4(0);
        }
        
        if(!empty($idMatiere_5_5))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_5_5);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_5($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_5_5(0);
        }
        
        if(!empty($idMatiere_5_6))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_5_6);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_6($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_5_6(0);
        }
        
        if(!empty($idMatiere_5_7))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_5_7);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_7($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_5_7(0);
        }
        
        if(!empty($idMatiere_5_8))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_5_8);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_8($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_5_8(0);
        }
        
        if(!empty($idMatiere_5_9))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_5_9);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_9($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_5_9(0);
        }
        
        if(!empty($idMatiere_5_10))
        {
            $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere_5_10);
            $seance = new Seance();
            $seance->setMatiere($matiere);
            if(null!=$salle){$seance->setSalle($salle);}
            $em->persist($seance);
            $em->flush();
            $emploiDuTemps->setSeance_5_10($seance->getId());
        }
        else
        {
            $emploiDuTemps->setSeance_5_10(0);
        }
        
        //Gestion de notification
        $detailsEmploiAdd = " >> Emploi du Temps ajouté"." (". date("Y/m/d") .")";
        
        $lien = "emploi/";
        
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        if(isset($idClasseEmploiDuTemps) and (null!=$idClasseEmploiDuTemps))
        {
            $this->addNotificationClasse($idClasseEmploiDuTemps, $detailsEmploiAdd, $lien, $idEcole);
        }
        
        
        $em->persist($emploiDuTemps);
        $em->flush();


    }
    
   
    public function deleteEmploiDuTempsAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idEmploiDuTemps = $request->get('idEmploiDuTempsDelete');

            $this->deleteEmploiDuTemps($idEmploiDuTemps);

            return $this->redirectToRoute('showAllEmploisDuTemps', [
                'request' => $request
            ], 307);

        }
    }

   
    public function deleteEmploiDuTemps($idEmploiDuTemps)
    {

        $em = $this->getDoctrine()->getManager();
        
        $emploiDuTemps = $em->getRepository('PronoteBundle:Emploi')->find($idEmploiDuTemps);

        if (!$emploiDuTemps)
        {
            throw $this->createNotFoundException(
                'Cet emploi du temps est introuvable, ID = '.$idEmploiDuTemps
            );
        }

        else
        {

            try
            {
                if(!empty($emploiDuTemps->getSeance_1_1()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_1_1());
                }
                if(!empty($emploiDuTemps->getSeance_1_2()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_1_2());
                }
                if(!empty($emploiDuTemps->getSeance_1_3()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_1_3());
                }
                if(!empty($emploiDuTemps->getSeance_1_4()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_1_4());
                }
                if(!empty($emploiDuTemps->getSeance_1_5()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_1_5());
                }
                if(!empty($emploiDuTemps->getSeance_1_6()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_1_6());
                }
                if(!empty($emploiDuTemps->getSeance_1_7()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_1_7());
                }
                if(!empty($emploiDuTemps->getSeance_1_8()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_1_8());
                }
                if(!empty($emploiDuTemps->getSeance_1_9()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_1_9());
                }
                if(!empty($emploiDuTemps->getSeance_1_10()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_1_10());
                }

                if(!empty($emploiDuTemps->getSeance_2_1()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_2_1());
                }
                if(!empty($emploiDuTemps->getSeance_2_2()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_2_2());
                }
                if(!empty($emploiDuTemps->getSeance_2_3()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_2_3());
                }
                if(!empty($emploiDuTemps->getSeance_2_4()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_2_4());
                }
                if(!empty($emploiDuTemps->getSeance_2_5()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_2_5());
                }
                if(!empty($emploiDuTemps->getSeance_2_6()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_2_6());
                }
                if(!empty($emploiDuTemps->getSeance_2_7()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_2_7());
                }
                if(!empty($emploiDuTemps->getSeance_2_8()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_2_8());
                }
                if(!empty($emploiDuTemps->getSeance_2_9()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_2_9());
                }
                if(!empty($emploiDuTemps->getSeance_2_10()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_2_10());
                }

                if(!empty($emploiDuTemps->getSeance_3_1()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_3_1());
                }
                if(!empty($emploiDuTemps->getSeance_3_2()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_3_2());
                }
                if(!empty($emploiDuTemps->getSeance_3_3()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_3_3());
                }
                if(!empty($emploiDuTemps->getSeance_3_4()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_3_4());
                }
                if(!empty($emploiDuTemps->getSeance_3_5()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_3_5());
                }
                if(!empty($emploiDuTemps->getSeance_3_6()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_3_6());
                }
                if(!empty($emploiDuTemps->getSeance_3_7()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_3_7());
                }
                if(!empty($emploiDuTemps->getSeance_3_8()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_3_8());
                }
                if(!empty($emploiDuTemps->getSeance_3_9()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_3_9());
                }
                if(!empty($emploiDuTemps->getSeance_3_10()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_3_10());
                }

                if(!empty($emploiDuTemps->getSeance_4_1()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_4_1());
                }
                if(!empty($emploiDuTemps->getSeance_4_2()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_4_2());
                }
                if(!empty($emploiDuTemps->getSeance_4_3()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_4_3());
                }
                if(!empty($emploiDuTemps->getSeance_4_4()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_4_4());
                }
                if(!empty($emploiDuTemps->getSeance_4_5()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_4_5());
                }
                if(!empty($emploiDuTemps->getSeance_4_6()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_4_6());
                }
                if(!empty($emploiDuTemps->getSeance_4_7()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_4_7());
                }
                if(!empty($emploiDuTemps->getSeance_4_8()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_4_8());
                }
                if(!empty($emploiDuTemps->getSeance_4_9()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_4_9());
                }
                if(!empty($emploiDuTemps->getSeance_4_10()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_4_10());
                }

                if(!empty($emploiDuTemps->getSeance_5_1()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_5_1());
                }
                if(!empty($emploiDuTemps->getSeance_5_2()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_5_2());
                }
                if(!empty($emploiDuTemps->getSeance_5_3()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_5_3());
                }
                if(!empty($emploiDuTemps->getSeance_5_4()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_5_4());
                }
                if(!empty($emploiDuTemps->getSeance_5_5()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_5_5());
                }
                if(!empty($emploiDuTemps->getSeance_5_6()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_5_6());
                }
                if(!empty($emploiDuTemps->getSeance_5_7()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_5_7());
                }
                if(!empty($emploiDuTemps->getSeance_5_8()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_5_8());
                }
                if(!empty($emploiDuTemps->getSeance_5_9()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_5_9());
                }
                if(!empty($emploiDuTemps->getSeance_5_10()))
                {
                    $this->deleteSeance($emploiDuTemps->getSeance_5_10());
                }
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression d une séance');
                return $this->redirectToRoute('showAllEmploisDuTemps');
            }
        }

        $em->remove($emploiDuTemps);
        $em->flush();
    }

    
    public function deleteSeance($idSeance)
    {

        $em = $this->getDoctrine()->getManager();
        
        $seance = $em->getRepository('PronoteBundle:Seance')->find($idSeance);

        if ($seance)
        {
            $em->remove($seance);
            $em->flush();
        }

    }

    
    //Retourne une ligne Emploi à partir d'un ID de classe (ex: 4ème A (ID=1))
    public function findEmploiFromClasse($idEmploi)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Emploi')
        ;

        $ligneEmploi = $repository->findOneBy(array('id' => $idEmploi));
        

        $Emploi  = Array(
            'Emploi_ID' => $ligneEmploi->getId(),
            'seance_1_1' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_1_1())),
            'seance_1_2' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_1_2())),
            'seance_1_3' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_1_3())),
            'seance_1_4' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_1_4())),
            'seance_1_5' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_1_5())),
            'seance_1_6' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_1_6())),
            'seance_1_7' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_1_7())),
            'seance_1_8' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_1_8())),
            'seance_1_9' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_1_9())),
            'seance_1_10'=> $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_1_10())),

            'seance_2_1' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_2_1())),
            'seance_2_2' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_2_2())),
            'seance_2_3' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_2_3())),
            'seance_2_4' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_2_4())),
            'seance_2_5' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_2_5())),
            'seance_2_6' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_2_6())),
            'seance_2_7' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_2_7())),
            'seance_2_8' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_2_8())),
            'seance_2_9' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_2_9())),
            'seance_2_10'=> $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_2_10())),

            'seance_3_1' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_3_1())),
            'seance_3_2' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_3_2())),
            'seance_3_3' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_3_3())),
            'seance_3_4' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_3_4())),
            'seance_3_5' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_3_5())),
            'seance_3_6' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_3_6())),
            'seance_3_7' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_3_7())),
            'seance_3_8' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_3_8())),
            'seance_3_9' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_3_9())),
            'seance_3_10'=> $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_3_10())),

            'seance_4_1' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_4_1())),
            'seance_4_2' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_4_2())),
            'seance_4_3' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_4_3())),
            'seance_4_4' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_4_4())),
            'seance_4_5' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_4_5())),
            'seance_4_6' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_4_6())),
            'seance_4_7' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_4_7())),
            'seance_4_8' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_4_8())),
            'seance_4_9' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_4_9())),
            'seance_4_10'=> $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_4_10())),

            'seance_5_1' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_5_1())),
            'seance_5_2' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_5_2())),
            'seance_5_3' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_5_3())),
            'seance_5_4' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_5_4())),
            'seance_5_5' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_5_5())),
            'seance_5_6' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_5_6())),
            'seance_5_7' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_5_7())),
            'seance_5_8' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_5_8())),
            'seance_5_9' => $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_5_9())),
            'seance_5_10'=> $this->contenuTexteSeance($this->seanceFromIdToColonnes($ligneEmploi->getSeance_5_10())),
            'designation' => $ligneEmploi->getDesignation()

        );

        return $Emploi;

    }

    public function seanceFromIdToColonnes($SeanceId)
    {
        $em = $this
            ->getDoctrine()
            ->getManager()
        ;

        $query = 
        $em->createQuery(
            
            "SELECT IDENTITY(s.matiere) as matiere , IDENTITY(s.salle) as salle
            FROM PronoteBundle:Seance s WHERE s.id =:idSeance")
            
            ->setParameter('idSeance' , $SeanceId);

        $resultat = $query->getResult();
        
        if(!empty($resultat))
        {
            $ArrayGlobal = array($resultat[0]["matiere"],  $resultat[0]["salle"]);
        }
        else
        {
            $ArrayGlobal = null;
        }
        
        return $ArrayGlobal;
    }

    public function contenuTexteSeance($params)
    {
        $em = $this
            ->getDoctrine()
            ->getManager()
        ;
        
        if(null!=$params[1])
        {
            $query = $em->createQuery(
            "SELECT m.nom as Matiere, s.identificateur as Salle,  m.id
            FROM
            PronoteBundle:Matieres m, PronoteBundle:Professeurs p, PronoteBundle:Salles s
            WHERE
            m.id =:idMatiere AND
            s.id =:idSalle ")
            ->setParameters(['idMatiere' => $params[0],'idSalle' => $params[1]]);
            
        }
        else 
        {
            $query = $em->createQuery(
            "SELECT m.nom as Matiere,  m.id
            FROM
            PronoteBundle:Matieres m, PronoteBundle:Professeurs p
            WHERE
            m.id =:idMatiere")
            ->setParameters(['idMatiere' => $params[0]]);
        }
        
        $resultat = $query->getResult();
        
        if(!empty($resultat)) 
        {
            if(null!=$params[1])
            {
                $chaineResultat = $resultat[0]["Matiere"] . "@@" . $resultat[0]["Salle"]. "@@" . $resultat[0]["id"];  
            }
            else
            {
                $chaineResultat = $resultat[0]["Matiere"] . "@@" . $resultat[0]["Matiere"]. "@@" . $resultat[0]["id"];
            }
        }
        else
        {
            $chaineResultat='';
        }

        return $chaineResultat;
    }

    /******************************************* FIN CRUD de la classe Emlpoi du Temps ********************************/
    

    /**************************************** Contrôleurs de la classe Téléchargements**************************/
    /******************************************************************************************************************/
    public function showAllTelechargementsAction()
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Admin:mainTelechargements.html.twig', array('allTelechargements' => $this->showAllTelechargements($idEcole), 'allClasses' => $this->showAllClasses($idEcole), 'logoEcole'=> $logoEcole));
       
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
                return $this->redirectToRoute('showAllTelechargements', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllTelechargements', [
                    'request' => $request
                ], 307);

        }
    }

    public function addTelechargementAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $descriptionTelechargement = $request->get('descriptionTelechargement');
            $idClasseTelechargement = $request->get('classeTelechargement');
            
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');

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
                if (file_exists("uploads/".$idEcole."/ressourcesAdmin/".$newfilename))
                {
                    // file already exists error
                    echo "You have already uploaded this file.";
                }
                else
                {
                    move_uploaded_file($_FILES["fileTelechargement"]["tmp_name"], "uploads/".$idEcole."/ressourcesAdmin/".$newfilename);
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



                $this->addTelechargement($descriptionTelechargement,$idClasseTelechargement,$newfilename, $idEcole);
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllTelechargements', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllTelechargements', [
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
                return $this->redirectToRoute('showAllTelechargements', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllTelechargements', [
                    'request' => $request
                ], 307);

        }
    }

    /********************************************* CRUD de la classe Téléchargement******************************/
    /******************************************************************************************************************/
    private function findAllTelechargements($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Telechargements')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('description' => 'ASC'));
    }
    /********************************************** Afficher Tous les Téléchargement ****************************/
    public function showAllTelechargements($idEcole)
    {
        
        $listeFinaleTelechargements = array();

        $Telechargements = $this->findAllTelechargements($idEcole);

        foreach ($Telechargements as $Telechargement)
        {
            if($Telechargement->getProfesseur()!== null)
            {
                $nomProfesseur = $Telechargement->getProfesseur()->getNom();
            }
            else 
            {
                $nomProfesseur = "Administrateur";
            }
            $listeTelechargements = Array(
                'idTelechargement' => $Telechargement->getId(),
                'descriptionTelechargement' => $Telechargement->getDescription(),
                'fileTelechargement' => "uploads/".$Telechargement->getFile(),
                'classeTelechargement' => $Telechargement->getClasse()->getId(),
                'nomclasseTelechargement' => $Telechargement->getClasse()->getNom(),
                'nomProfesseur' => $nomProfesseur
                
            );

            $listeFinaleTelechargements[] = $listeTelechargements;
        }
        return $listeFinaleTelechargements;

    }

    /************************************* Afficher Un Seule Téléchargement Par ID******************************/
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

    /*************************************** Ajouter un Téléchargement *****************************************/
    public function addTelechargement($descriptionTelechargement, $idClasseTelechargement, $file, $idEcole)
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

        $em = $this->getDoctrine()->getManager();
        $Telechargement = new Telechargements();

        $Telechargement->setDescription($descriptionTelechargement);
        $Telechargement->setClasse($classeTelechargement);
        $Telechargement->setFile($idEcole."/ressourcesAdmin/".$file);
        $Telechargement->setEcole($ecole);
        
        //Gestion de notification
        $detailsRessourcePedagogique = " >> Ressource pédagogique ajoutée"." (". date("Y/m/d") .")";
        
        $lien = "login/";
        
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        $this->addNotificationClasse($idClasseTelechargement, $detailsRessourcePedagogique, $lien, $idEcole);

        $em->persist($Telechargement);
        $em->flush();
    }

    /********************************************** Modifier un Téléchargement *********************************/
    public function updateTelechargement($idTelechargement, $descriptionTelechargement, $idClasseTelechargement)
    {
        $em = $this->getDoctrine()->getManager();
        $Telechargement = $em->getRepository('PronoteBundle:Telechargements')->find($idTelechargement);


        if (!$Telechargement) {
            throw $this->createNotFoundException(
                'Cette Téléchargement est introuvable, ID = '.$idTelechargement
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

    /********************************************** Supprimer un Téléchargement par ID**************************/
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

    /********************************************** FIN CRUD de la classe Telechargement***************************/



    /**************************************** Contrôleurs de la classe MenuCantine**************************/
    /******************************************************************************************************************/
    public function showAllMenuCantineAction()
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Admin:mainMenuCantine.html.twig', array('allMenuCantine' => $this->showAllMenuCantine($idEcole), 'allClasses' => $this->showAllClasses($idEcole), 'logoEcole'=> $logoEcole));
        
    }
    
    public function deleteMenuCantineAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idMenuCantine = $request->get('idMenuCantineDelete');
            
            try
            {
                $this->deleteMenuCantine($idMenuCantine);
                
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllMenuCantine', [
                    'request' => $request
                ], 307);
            }
            
                return $this->redirectToRoute('showAllMenuCantine', [
                    'request' => $request
                ], 307);
            
        }
    }
    
    public function addMenuCantineAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $descriptionMenuCantine = $request->get('descriptionMenuCantine');
            $idClasseMenuCantine = $request->get('classeMenuCantine');
            
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');
            
            try
            {
                $filename = $_FILES["fileMenuCantine"]["name"];
                $file_basename = substr($filename, 0, strripos($filename, '.')); // get file extention
                $file_ext = substr($filename, strripos($filename, '.')); // get file name
                $filesize = $_FILES["fileMenuCantine"]["size"];
                $allowed_file_types = array('.doc','.docx','.jpg','.jpeg','.gif','.png','.pdf','.DOC','.DOCX','.JPG','.JPEG','.GIF','.PNG','.PDF');
                
                if (in_array($file_ext,$allowed_file_types) && ($filesize < 8388608 ))
                {
                    // Rename file
                    $newfilename = md5(uniqid()).$file_ext;
                    if (file_exists("uploads/".$idEcole."/cantine/".$newfilename))
                    {
                        // file already exists error
                        echo "You have already uploaded this file.";
                    }
                    else
                    {
                        move_uploaded_file($_FILES["fileMenuCantine"]["tmp_name"], "uploads/".$idEcole."/cantine/".$newfilename);
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
                    unlink($_FILES["fileMenuCantine"]["tmp_name"]);
                }
                
                
                
                $this->addMenuCantine($descriptionMenuCantine,$idClasseMenuCantine,$newfilename, $idEcole);
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllMenuCantine', [
                    'request' => $request
                ], 307);
            }
            
                return $this->redirectToRoute('showAllMenuCantine', [
                    'request' => $request
                ], 307);
            
            
        }
    }
    
    public function updateMenuCantineAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idMenuCantine = $request->get('idMenuCantine');
            $descriptionMenuCantine = $request->get('descriptionMenuCantine');
            $idClasseMenuCantine = $request->get('classeMenuCantine');
            
            try
            {
                
                $this->updateMenuCantine($idMenuCantine,$descriptionMenuCantine,$idClasseMenuCantine);
            }
            
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllMenuCantine', [
                    'request' => $request
                ], 307);
            }
            
                return $this->redirectToRoute('showAllMenuCantine', [
                    'request' => $request
                ], 307);
            
        }
    }
    
    /********************************************* CRUD de la classe MenuCantine******************************/
    /******************************************************************************************************************/
    private function findAllMenuCantine($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:MenuCantine')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('description' => 'ASC'));
    }
    /********************************************** Afficher Tous les Menu Cantine ****************************/
    public function showAllMenuCantine($idEcole)
    {
        $listeFinaleMenuCantine = array();
        
        $MenuCantines = $this->findAllMenuCantine($idEcole);
        
        foreach ($MenuCantines as $MenuCantine)
        {
            if($MenuCantine->getProfesseur()!== null)
            {
                $nomProfesseur = $MenuCantine->getProfesseur()->getNom();
            }
            else
            {
                $nomProfesseur = "Administrateur";
            }
            $listeMenuCantine = Array(
                'idMenuCantine' => $MenuCantine->getId(),
                'descriptionMenuCantine' => $MenuCantine->getDescription(),
                'fileMenuCantine' => "uploads/".$MenuCantine->getFile(),
                'classeMenuCantine' => $MenuCantine->getClasse()->getId(),
                'nomclasseMenuCantine' => $MenuCantine->getClasse()->getNom(),
                'nomProfesseur' => $nomProfesseur
                
                );
            
            $listeFinaleMenuCantine[] = $listeMenuCantine;
        }
        return $listeFinaleMenuCantine;
        
    }
    
    /************************************* Afficher Un Seule MenuCantine Par ID******************************/
    public function showOneMenuCantine($idMenuCantine)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:MenuCantine')
        ;
        
        $MenuCantines = $repository->findOneBy(
            array('id' => $idMenuCantine)
            );
        
        $listeMenuCantine = array();
        
        foreach ($MenuCantines as $MenuCantine)
        {
            $listeMenuCantine = Array(
                'idMenuCantine' => $MenuCantine->getId(),
                'descriptionMenuCantine' => $MenuCantine->getDescription(),
                'fileMenuCantine' => "uploads/".$MenuCantine->getFile(),
                'classeMenuCantine' => $MenuCantine->getClasse()->getId(),
                'nomclasseMenuCantine' => $MenuCantine->getClasse()->getNom()
                );
        }
        return $listeMenuCantine;
        
    }
    
    /*************************************** Ajouter un MenuCantine *****************************************/
    public function addMenuCantine($descriptionMenuCantine, $idClasseMenuCantine, $file, $idEcole)
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
        
        $classeMenuCantine = $repository->findOneBy(
            array('id' => $idClasseMenuCantine)
            );
        
        $em = $this->getDoctrine()->getManager();
        $MenuCantine = new MenuCantine();
        
        $MenuCantine->setDescription($descriptionMenuCantine);
        $MenuCantine->setClasse($classeMenuCantine);
        $MenuCantine->setEcole($ecole);
        $MenuCantine->setFile($idEcole."/cantine/".$file);
        
        //Gestion de notification
        $detailsMenuCantine = " >> Menu de la cantine ajouté"." (". date("Y/m/d") .")";
        
        $lien = "login/";
        
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        $this->addNotificationClasse($idClasseMenuCantine, $detailsMenuCantine, $lien, $idEcole);
        
        $em->persist($MenuCantine);
        $em->flush();
    }
    
    /********************************************** Modifier un MenuCantine *********************************/
    public function updateMenuCantine($idMenuCantine, $descriptionMenuCantine, $idClasseMenuCantine)
    {
        $em = $this->getDoctrine()->getManager();
        $MenuCantine = $em->getRepository('PronoteBundle:MenuCantine')->find($idMenuCantine);
        
        
        if (!$MenuCantine) {
            throw $this->createNotFoundException(
                'Ce Menu Cantine est introuvable, ID = '.$idMenuCantine
                );
        }
        
        if(!empty($descriptionMenuCantine)){
            $MenuCantine->setDescription($descriptionMenuCantine);
        }
        
        if(!empty($idClasseMenuCantine)){
            $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Classe')
            ;
            
            $classeMenuCantine = $repository->findOneBy(
                array('id' => $idClasseMenuCantine)
                );
            $MenuCantine->setClasse($classeMenuCantine);
        }
        
        
        $em->flush();
        
    }
    
    /********************************************** Supprimer un MenuCantine par ID**************************/
    public function deleteMenuCantine($idMenuCantine)
    {
        
        $em = $this->getDoctrine()->getManager();
        $MenuCantine = $em->getRepository('PronoteBundle:MenuCantine')->find($idMenuCantine);
        $path = $MenuCantine->getFile();
        unlink("uploads/".$path);
        
        if (!$MenuCantine) {
            throw $this->createNotFoundException(
                'Ce Menu Cantine est introuvable, ID = '.$idMenuCantine
                );
        }
        $em->remove($MenuCantine);
        $em->flush();
    }
    
    /********************************************** FIN CRUD de la classe MenuCantine ***************************/
    
    
    /**************************************** Contrôleurs de la classe Observations Générales**************************/
    /******************************************************************************************************************/
    public function showAllObservationsGeneralesAction()
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Admin:mainObservationsGenerales.html.twig', array('allObservationsGenerales' => $this->showAllObservationsGenerales($idEcole), 'allClasses' => $this->showAllClasses($idEcole), 'logoEcole'=> $logoEcole));
    }

    public function deleteObservationGeneraleAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idObservationGenerale = $request->get('idObservationGeneraleDelete');

            try
            {
                $this->deleteObservationGenerale($idObservationGenerale);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllObservationsGenerales', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllObservationsGenerales', [
                    'request' => $request
                ], 307);

        }
    }

    public function addObservationGeneraleAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $descriptionObservationGenerale = $request->get('descriptionObservationGenerale');
            $idClasseObservationGenerale = $request->get('classeObservationGenerale');
            
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');

            try
            {
                $this->addObservationGenerale($descriptionObservationGenerale,$idClasseObservationGenerale, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllObservationsGenerales', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllObservationsGenerales', [
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

            try
            {
                $this->updateObservationGenerale($idObservationGenerale,$descriptionObservationGenerale,$idClasseObservationGenerale);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllObservationsGenerales', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllObservationsGenerales', [
                    'request' => $request
                ], 307);

        }
    }

    /********************************************* CRUD de la classe Observation Générale******************************/
    /******************************************************************************************************************/
    private function findAllObservationsGenerales($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:ObservationsGenerales')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('description' => 'ASC'));
    }
    /********************************************** Afficher Tous les Observation Générale ****************************/
    public function showAllObservationsGenerales($idEcole)
    {
        $listeFinaleObservationGenerales = array();
        
        $observationGenerales = $this->findAllObservationsGenerales($idEcole);

        foreach ($observationGenerales as $observationGenerale)
        {
            if($observationGenerale->getProfesseur()!== null)
            {
                $nomProfesseur = $observationGenerale->getProfesseur()->getNom();
            }
            else
            {
                $nomProfesseur = "Administrateur";
            }
            $listeObservationGenerales = Array(
                'idObservationGenerale' => $observationGenerale->getId(),
                'descriptionObservationGenerale' => $observationGenerale->getDescription(),
                'classeObservationGenerale' => $observationGenerale->getClasse()->getId(),
                'nomclasseObservationGenerale' => $observationGenerale->getClasse()->getNom(),
                'nomProfesseur' => $nomProfesseur
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
    public function addObservationGenerale($descriptionObservationGenerale, $idClasseObservationGenerale, $idEcole)
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

        $classeObservationGenerale = $repository->findOneBy(
            array('id' => $idClasseObservationGenerale)
        );

        $em = $this->getDoctrine()->getManager();
        $observationGenerale = new ObservationsGenerales();

        $observationGenerale->setDescription($descriptionObservationGenerale);
        $observationGenerale->setClasse($classeObservationGenerale);
        $observationGenerale->setEcole($ecole);

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

    /********************************************** FIN CRUD de la classe Activité Scolaires***************************/




    /**************************************** Contrôleurs de la classe Activité Scolaires******************************/
    /******************************************************************************************************************/
    public function showAllActivitesScolairesAction()
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Admin:mainActivitesScolaires.html.twig', array('allActiviteScolaires' => $this->showAllActivitesScolaires($idEcole), 'allClasses' => $this->showAllClasses($idEcole), 'logoEcole'=> $logoEcole));
        
    }

    public function deleteActiviteScolaireAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idActiviteScolaire = $request->get('idActiviteScolaireDelete');

            try
            {
                $this->deleteActiviteScolaire($idActiviteScolaire);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllActivitesScolaires', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllActivitesScolaires', [
                    'request' => $request
                ], 307);

        }
    }

    public function addActiviteScolaireAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $descriptionActiviteScolaire = $request->get('descriptionActiviteScolaire');
            $dateActiviteScolaire = $request->get('dateActiviteScolaire');
            $idClasseActiviteScolaire = $request->get('classeActiviteScolaire');
            
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');

            try
            {
                $this->addActiviteScolaire($descriptionActiviteScolaire,$dateActiviteScolaire,$idClasseActiviteScolaire, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllActivitesScolaires', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllActivitesScolaires', [
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

            try
            {
                $this->updateActiviteScolaire($idActiviteScolaire,$descriptionActiviteScolaire,$dateActiviteScolaire,$idClasseActiviteScolaire);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllActivitesScolaires', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllActivitesScolaires', [
                    'request' => $request
                ], 307);

        }
    }

    /********************************************* CRUD de la classe Activité Scolaires********************************/
    /******************************************************************************************************************/
    private function findAllActivitesScolaires($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:ActivitesScolaires')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('description' => 'ASC'));
    }
    /********************************************** Afficher Tous les Activité Scolaires*******************************/
    public function showAllActivitesScolaires($idEcole)
    {
        
        $listeFinaleActiviteScolaires = array();

        //On affiche toutes les activités scolaires SANS limite de date (même les dépassées)
        $activiteScolaires = $this->findAllActivitesScolaires($idEcole);
        
        foreach ($activiteScolaires as $activiteScolaire)
        {
            if($activiteScolaire->getProfesseur()!== null)
            {
                $nomProfesseur = $activiteScolaire->getProfesseur()->getNom();
            }
            else
            {
                $nomProfesseur = "Administrateur";
            }
            $listeActiviteScolaires = Array(
                'idActiviteScolaire' => $activiteScolaire->getId(),
                'descriptionActiviteScolaire' => $activiteScolaire->getDescription(),
                'dateActiviteScolaire' => $activiteScolaire->getDateActivite()->format('d-m-Y'),
                'classeActiviteScolaire' => $activiteScolaire->getClasse()->getId(),
                'nomclasseActiviteScolaire' => $activiteScolaire->getClasse()->getNom(),
                'nomProfesseur' => $nomProfesseur
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
                '$dateActiviteScolaire' => $activiteScolaire->getDateActivite()->format('d-m-Y'),
                'classeActiviteScolaire' => $activiteScolaire->getClasse()->getId()
            );
        }
        return $listeActiviteScolaires;

    }

    /********************************************** Ajouter un Activité Scolaire***************************************/
    public function addActiviteScolaire($descriptionActiviteScolaire, $dateActiviteScolaire, $idClasseActiviteScolaire, $idEcole)
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

        $em = $this->getDoctrine()->getManager();
        $activiteScolaire = new ActivitesScolaires();

        $activiteScolaire->setDescription($descriptionActiviteScolaire);

        $dateTimeInput = new DateTime($dateActiviteScolaire);
        $activiteScolaire->setDateActivite($dateTimeInput);

        $activiteScolaire->setClasse($classeActiviteScolaire);
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



    /**************************************** Contrôleurs de la classe Admins******************************************/
    /******************************************************************************************************************/
    public function showAllAdminsAction()
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Admin:mainAdmins.html.twig', array('allAdmins' => $this->showAllAdmins($idEcole), 'logoEcole'=> $logoEcole));
    }

    //Gestion du cas Super Admin
    public function deleteAdminAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idAdmin = $request->get('idAdminDelete');
            
            $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Admin')
            ;
            
            $admin = $repository->findOneBy(array('id' => $idAdmin));

            if($admin->getProfil() !== 'superadmin')
            {
                try
                {
                    $this->deleteAdmin($idAdmin);

                }
                catch(\Doctrine\DBAL\DBALException $e)
                {
                    $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                    return $this->redirectToRoute('showAllAdmins', [
                        'request' => $request
                    ], 307);
                }
            }
            else{

                $this->get('session')->getFlashBag()->add('Exception', '* Attention : pour des raisons de sécurité, il est interdit de supprimer le SUPERADMIN (ID SUPERADMIN = '.$idAdmin.')');
                return $this->redirectToRoute('showAllAdmins', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllAdmins', [
                    'request' => $request
                ], 307);

        }
    }

    public function addAdminAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $loginAdmin = $request->get('loginAdmin');
            $passwordAdmin = $request->get('passwordAdmin');
            $profil = "admin";
            
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');

            try
            {
                $this->addAdmin($loginAdmin,$passwordAdmin, $profil, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Attention, l\'identifiant << '.$loginAdmin.' >> est dèja réservé. Veuillez choisir un autre.');
                return $this->redirectToRoute('showAllAdmins', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllAdmins', [
                    'request' => $request
                ], 307);


        }
    }

    public function updateAdminAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idAdmin = $request->get('idAdmin');
            $loginAdmin = $request->get('loginAdmin');
            $passwordAdmin = $request->get('passwordAdmin');

            try
            {
                $this->updateAdmin($idAdmin,$loginAdmin,$passwordAdmin);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllAdmins', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllAdmins', [
                    'request' => $request
                ], 307);

        }
    }

    /********************************************* CRUD de la classe Admins*******************************************/
    /******************************************************************************************************************/
    private function findAllAdmins($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Admin')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('profil' => 'DESC'));
    }
    /********************************************** Afficher Tous les Admins******************************************/
    public function showAllAdmins($idEcole)
    {
        
        $listeFinaleAdmins = array();
        
        $admins = $this->findAllAdmins($idEcole);

        foreach ($admins as $admin)
        {
            $listeAdmins = Array(
                'idAdmin' => $admin->getId(),
                'loginAdmin' => $admin->getLogin(),
                'passwordAdmin' => $admin->getPassword(),
                'profil' => $admin->getProfil()
            );

            $listeFinaleAdmins[] = $listeAdmins;
        }
        return $listeFinaleAdmins;

    }

    /********************************************** Afficher Un Seul Admin Par ID*************************************/
    public function showOneAdmin($idAdmin)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Admin')
        ;

        $admins = $repository->findOneBy(
            array('id' => $idAdmin)
        );

        foreach ($admins as $admin)
        {
            $listeAdmins = Array(
                'idAdmin' => $admin->getId(),
                'loginAdmin' => $admin->getLogin(),
                'passwordAdmin' => $admin->getPassword(),
                'profil' => $admin->getProfil()
            );
        }
        return $listeAdmins;

    }

    /********************************************** Ajouter un Admin**************************************************/
    public function addAdmin($loginAdmin, $passwordAdmin, $profil, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));

        $em = $this->getDoctrine()->getManager();
        $admin = new Admin();

        $admin->setLogin($loginAdmin);
        $admin->setPassword($passwordAdmin);
        //Profil ADMIN supprimable : "admin"
        $admin->setProfil($profil);
        $admin->setEcole($ecole);

        $em->persist($admin);
        $em->flush();

    }

    /********************************************** Modifier un Admin*************************************************/
    public function updateAdmin($idAdmin, $loginAdmin, $passwordAdmin)
    {
        $em = $this->getDoctrine()->getManager();
        $admin = $em->getRepository('PronoteBundle:Admin')->find($idAdmin);


        if (!$admin) {
            throw $this->createNotFoundException(
                'Ce Admin est introuvable, ID = '.$idAdmin
            );
        }

        if(!empty($loginAdmin)){
            $admin->setLogin($loginAdmin);
        }

        if(!empty($passwordAdmin)){
            $admin->setPassword($passwordAdmin);
        }

        $em->flush();


    }

    /********************************************** Supprimer un Admin par ID*****************************************/
    public function deleteAdmin($idAdmin)
    {

        $em = $this->getDoctrine()->getManager();
        $admin = $em->getRepository('PronoteBundle:Admin')->find($idAdmin);

        if (!$admin) {
            throw $this->createNotFoundException(
                'Cet Admin est introuvable, ID = '.$idAdmin
            );
        }
        $em->remove($admin);
        $em->flush();
    }

    /********************************************** FIN CRUD de la classe Admins**************************************/


    /**************************************** Contrôleurs de la classe Matieres****************************************/
    /******************************************************************************************************************/
    public function showAllMatieresAction()
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Admin:mainMatieres.html.twig', array('allMatieres' => $this->showAllMatieres($idEcole) ,'logoEcole'=> $logoEcole));
    }

    public function deleteMatiereAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idMatiere = $request->get('idMatiereDelete');

            try
            {
                $this->deleteMatiere($idMatiere);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllMatieres', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllMatieres', [
                    'request' => $request
                ], 307);

        }
    }

    public function addMatiereAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $nomMatiere = $request->get('nomMatiere');
            
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');

            try
            {
                $this->addMatiere($nomMatiere, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllMatieres', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllMatieres', [
                    'request' => $request
                ], 307);


        }
    }

    public function updateMatiereAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idMatiere = $request->get('idMatiere');
            $nomMatiere = $request->get('nomMatiere');

            try
            {
                $this->updateMatiere($idMatiere,$nomMatiere);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllMatieres', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllMatieres', [
                    'request' => $request
                ], 307);

        }
    }

    /********************************************* CRUD de la classe Matieres******************************************/
    /******************************************************************************************************************/
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

    /********************************************** Afficher Un Seul Matiere Par ID************************************/
    public function showOneMatiere($idMatiere)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Matieres')
        ;

        $matieres = $repository->findOneBy(
            array('id' => $idMatiere)
        );

        foreach ($matieres as $matiere)
        {
            $listeMatieres = Array(
                'idMatiere' => $matiere->getId(),
                'nomMatiere' => ucfirst($matiere->getNom())
            );
        }
        return $listeMatieres;

    }

    /********************************************** Ajouter un Matiere*************************************************/
    public function addMatiere($nomMatiere, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        

        $em = $this->getDoctrine()->getManager();
        $matiere = new Matieres();

        $matiere->setNom($nomMatiere);
        $matiere->setEcole($ecole);

        $em->persist($matiere);
        $em->flush();
    }

    /********************************************** Modifier une Matiere***********************************************/
    public function updateMatiere($idMatiere, $nomMatiere)
    {
        $em = $this->getDoctrine()->getManager();
        $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere);


        if (!$matiere) {
            throw $this->createNotFoundException(
                'Cette Matiere est introuvable, ID = '.$idMatiere
            );
        }

        if(!empty($nomMatiere)){
            $matiere->setNom($nomMatiere);
        }

        $em->flush();


    }

    /********************************************** Supprimer une Matiere par ID***************************************/
    public function deleteMatiere($idMatiere)
    {

        $em = $this->getDoctrine()->getManager();
        $matiere = $em->getRepository('PronoteBundle:Matieres')->find($idMatiere);
        
        //Delete des séances qui ont cette matière
        $this->findAndResetToNullSeancesByMatiere($idMatiere);

        if (!$matiere) {
            throw $this->createNotFoundException(
                'Cette Matiere est introuvable, ID = '.$idMatiere
            );
        }
        $em->remove($matiere);
        $em->flush();
    }

    /********************************************** FIN CRUD de la classe Matieres*************************************/


    /**************************************** Contrôleurs de la classe Salles *****************************************/
    /******************************************************************************************************************/
    public function showAllSallesAction()
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Admin:mainSalles.html.twig', array('allSalles' => $this->showAllSalles($idEcole) ,'logoEcole'=> $logoEcole));
        
    }

    public function deleteSalleAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idSalle = $request->get('idSalleDelete');

            try
            {
                $this->deleteSalle($idSalle);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllSalles', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllSalles', [
                    'request' => $request
                ], 307);

        }
    }

    public function addSalleAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $nomSalle = $request->get('nomSalle');
            
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');

            try
            {
                $this->addSalle($nomSalle, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllSalles', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllSalles', [
                    'request' => $request
                ], 307);
        }
    }

    public function updateSalleAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idSalle = $request->get('idSalle');
            $nomSalle = $request->get('nomSalle');

            try
            {
                $this->updateSalle($idSalle,$nomSalle);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllSalles', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllSalles', [
                    'request' => $request
                ], 307);

        }
    }

    /********************************************* CRUD de la classe Salles *******************************************/
    /******************************************************************************************************************/
    private function findAllSalles($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Salles')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('identificateur' => 'ASC'));
    }
    /******************************************** Afficher Toutes les Salles******************************************/
    public function showAllSalles($idEcole)
    {
        $listeFinaleSalles = array();
        
        $salles = $this->findAllSalles($idEcole);

        foreach ($salles as $salle)
        {
            $listeSalles = Array(
                'idSalle' => $salle->getId(),
                'nomSalle' => $salle->getIdentificateur()
            );

            $listeFinaleSalles[] = $listeSalles;
        }
        return $listeFinaleSalles;

    }

    /******************************************** Afficher Une Seule Salle Par ID*************************************/
    public function showOneSalle($idSalle)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Salles')
        ;

        $salles = $repository->findOneBy(
            array('id' => $idSalle)
        );

        foreach ($salles as $salle)
        {
            $listeSalles = Array(
                'idSalle' => $salle->getId(),
                'nomSalle' => ucfirst($salle->getIdentificateur())
            );
        }
        return $listeSalles;

    }

    /********************************************** Ajouter une Salle**************************************************/
    public function addSalle($nomSalle, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));

        $em = $this->getDoctrine()->getManager();
        $salle = new Salles();

        $salle->setIdentificateur($nomSalle);
        $salle->setEcole($ecole);

        $em->persist($salle);
        $em->flush();
    }

    /********************************************** Modifier une Salle*************************************************/
    public function updateSalle($idSalle, $nomSalle)
    {
        $em = $this->getDoctrine()->getManager();
        $salle = $em->getRepository('PronoteBundle:Salles')->find($idSalle);


        if (!$salle) {
            throw $this->createNotFoundException(
                'Cette Salle est introuvable, ID = '.$idSalle
            );
        }

        if(!empty($nomSalle)){
            $salle->setIdentificateur($nomSalle);
        }

        $em->flush();


    }

    /********************************************** Supprimer une Salle par ID*****************************************/
    public function deleteSalle($idSalle)
    {

        $em = $this->getDoctrine()->getManager();
        $salle = $em->getRepository('PronoteBundle:Salles')->find($idSalle);
        
        $this->findAndResetToNullEmploisBySalle($idSalle);
        $this->findAndResetToNullSeancesBySalle($idSalle);

        if (!$salle) {
            throw $this->createNotFoundException(
                'Cette Salle est introuvable, ID = '.$idSalle
            );
        }
        $em->remove($salle);
        $em->flush();
    }

    /********************************************** FIN CRUD de la classe Salles **************************************/

    /**************************************** Contrôleurs de la classe CLASSE *****************************************/
    /******************************************************************************************************************/
    public function showAllClassesAction()
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Admin:mainClasses.html.twig', array('allClasses' => $this->showAllClasses($idEcole) ,'logoEcole'=> $logoEcole));
    }

    public function deleteClasseAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idClasse = $request->get('idClasseDelete');

            try
            {
                $this->deleteClasse($idClasse);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllClasses', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllClasses', [
                    'request' => $request
                ], 307);

        }
    }

    public function addClasseAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $nomClasse = $request->get('nomClasse');
            
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');

            try
            {
                $this->addClasse($nomClasse, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllClasses', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllClasses', [
                    'request' => $request
                ], 307);
        }
    }

    public function updateClasseAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idClasse = $request->get('idClasse');
            $nomClasse = $request->get('nomClasse');

            try
            {
                $this->updateClasse($idClasse,$nomClasse);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllClasses', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllClasses', [
                    'request' => $request
                ], 307);

        }
    }

    /********************************************* CRUD de la classe CLASSE *******************************************/
    /******************************************************************************************************************/
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

    /******************************************** Afficher Une Seule Classe Par ID*************************************/
    public function showOneClasse($idClasse)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Classe')
        ;

        $classes = $repository->findOneBy(
            array('id' => $idClasse)
        );

        foreach ($classes as $classe)
        {
            $listeClasses = Array(
                'idClasse' => $classe->getId(),
                'nomClasse' => ucfirst($classe->getNom())
            );
        }
        return $listeClasses;

    }

    /********************************************** Ajouter une Classe**************************************************/
    public function addClasse($nomClasse, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));

        $em = $this->getDoctrine()->getManager();
        $classe = new Classe();

        $classe->setNom($nomClasse);
        $classe->setEcole($ecole);

        $em->persist($classe);
        $em->flush();
    }

    /********************************************** Modifier une Classe*************************************************/
    public function updateClasse($idClasse, $nomClasse)
    {
        $em = $this->getDoctrine()->getManager();
        $classe = $em->getRepository('PronoteBundle:Classe')->find($idClasse);


        if (!$classe) {
            throw $this->createNotFoundException(
                'Cette Classe est introuvable, ID = '.$idClasse
            );
        }

        if(!empty($nomClasse)){
            $classe->setNom($nomClasse);
        }

        $em->flush();


    }

    /********************************************** Supprimer une Classe par ID*****************************************/
    public function deleteClasse($idClasse)
    {

        $em = $this->getDoctrine()->getManager();
        $classe = $em->getRepository('PronoteBundle:Classe')->find($idClasse);
        
        $this->findAndResetToNullEmploisByClasse($idClasse);
        $this->findAndResetToNullElevesByClasse($idClasse);
        $this->findAndResetToNullBulletinsByClasse($idClasse);

        if (!$classe) {
            throw $this->createNotFoundException(
                'Cette Classe est introuvable, ID = '.$idClasse
            );
        }
        $em->remove($classe);
        $em->flush();
    }

    /********************************************** FIN CRUD de la classe Classes**************************************/

    /**************************************** Contrôleurs de la classe Professeurs*****************************************/
    /******************************************************************************************************************/
    public function showAllProfesseursAction()
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Admin:mainProfesseurs.html.twig', array('allProfesseurs' => $this->showAllProfesseurs($idEcole) ,'logoEcole'=> $logoEcole));
    }

    public function deleteProfesseurAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idProfesseur = $request->get('idProfesseurDelete');

            try
            {
                $this->deleteProfesseur($idProfesseur);


            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllProfesseurs', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllProfesseurs', [
                    'request' => $request
                ], 307);

        }
    }

    public function addProfesseurAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $nomProfesseur = $request->get('nomProfesseur');
            $loginProfesseur = $request->get('loginProfesseur');
            $passwordProfesseur = $request->get('passwordProfesseur');
            
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');

            try
            {
                $this->addProfesseur($nomProfesseur,$loginProfesseur,$passwordProfesseur, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Attention, l\'identifiant << '.$loginProfesseur.' >> est dèja réservé. Veuillez choisir un autre.');
                return $this->redirectToRoute('showAllProfesseurs', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllProfesseurs', [
                    'request' => $request
                ], 307);


        }
    }

    public function updateProfesseurAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idProfesseur = $request->get('idProfesseur');
            $nomProfesseur = $request->get('nomProfesseur');
            $loginProfesseur = $request->get('loginProfesseur');
            $passwordProfesseur = $request->get('passwordProfesseur');

            try
            {
                $this->updateProfesseur($idProfesseur,$nomProfesseur,$loginProfesseur,$passwordProfesseur);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllProfesseurs', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllProfesseurs', [
                    'request' => $request
                ], 307);

        }
    }

    /********************************************* CRUD de la classe Professeurs*******************************************/
    /******************************************************************************************************************/
    private function findAllProfesseurs($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Professeurs')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('nom' => 'ASC'));
    }
    /********************************************** Afficher Tous les Professeurs******************************************/
    public function showAllProfesseurs($idEcole)
    {
        $listeFinaleProfesseurs = array();

        $professeurs = $this->findAllProfesseurs($idEcole);

        foreach ($professeurs as $professeur)
        {
            $listeProfesseurs = Array(
                'idProfesseur' => $professeur->getId(),
                'nomProfesseur' => ucfirst($professeur->getNom()),
                'loginProfesseur' => $professeur->getLogin(),
                'passwordProfesseur' => $professeur->getPassword()
            );

            $listeFinaleProfesseurs[] = $listeProfesseurs;
        }
        return $listeFinaleProfesseurs;

    }

    /********************************************** Afficher Un Seul Professeur Par ID*************************************/
    public function showOneProfesseur($idProfesseur)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Professeurs')
        ;

        $professeurs = $repository->findOneBy(
            array('id' => $idProfesseur)
        );

        foreach ($professeurs as $professeur)
        {
            $listeProfesseurs = Array(
                'idProfesseur' => $professeur->getId(),
                'nomProfesseur' => ucfirst($professeur->getNom()),
                'loginProfesseur' => $professeur->getLogin(),
                'passwordProfesseur' => $professeur->getPassword()
            );
        }
        return $listeProfesseurs;

    }

    /********************************************** Ajouter un Professeur**************************************************/
    public function addProfesseur($nomProfesseur, $loginProfesseur, $passwordProfesseur, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));

        $em = $this->getDoctrine()->getManager();
        $professeur = new Professeurs();

        $professeur->setNom($nomProfesseur);
        $professeur->setLogin($loginProfesseur);
        $professeur->setPassword($passwordProfesseur);
        $professeur->setEcole($ecole);

        $em->persist($professeur);
        $em->flush();
        
    }

    /********************************************** Modifier un Professeur*************************************************/
    public function updateProfesseur($idProfesseur, $nomProfesseur, $loginProfesseur, $passwordProfesseur)
    {
        $em = $this->getDoctrine()->getManager();
        $professeur = $em->getRepository('PronoteBundle:Professeurs')->find($idProfesseur);


        if (!$professeur) {
            throw $this->createNotFoundException(
                'Ce Professeur est introuvable, ID = '.$idProfesseur
            );
        }

        if(!empty($nomProfesseur)){
            $professeur->setNom($nomProfesseur);
        }

        if(!empty($loginProfesseur)){
            $professeur->setLogin($loginProfesseur);
        }

        if(!empty($passwordProfesseur)){
            $professeur->setPassword($passwordProfesseur);
        }

        $em->flush();


    }

    /********************************************** Supprimer un Professeur par ID*****************************************/
    public function deleteProfesseur($idProfesseur)
    {

        $em = $this->getDoctrine()->getManager();
        
        $Professeur = $em->getRepository('PronoteBundle:Professeurs')->find($idProfesseur);
        

        if (!$Professeur) {
            throw $this->createNotFoundException(
                'Cet Professeur est introuvable, ID = '.$idProfesseur
            );
        }
        $em->remove($Professeur);
        $em->flush();
    }

    /********************************************** FIN CRUD de la classe Professeurs**************************************/

    /**************************************** Contrôleurs de la classe Élèves*****************************************/
    /******************************************************************************************************************/
    public function showAllElevesAction()
    {
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(!empty($ecole->getLogo()))
        {
            $logoEcole = $ecole->getLogo();
        }

        return $this->render('PronoteBundle:Admin:mainEleves.html.twig', array('allEleves' => $this->showAllEleves($idEcole), 'allParents' => $this->showAllParents($idEcole), 'allClasses' => $this->showAllClasses($idEcole) ,'logoEcole'=> $logoEcole));
    }

    public function deleteEleveAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idEleve = $request->get('idEleveDelete');

            try
            {
                $this->deleteEleve($idEleve);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllEleves', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllEleves', [
                    'request' => $request
                ], 307);

        }
    }

    public function addEleveAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $prenom = $request->get('prenomEleve');
            $nom = $request->get('nomEleve');
            $idParentEleve = $request->get('parentEleve');
            $idClasseEleve = $request->get('classeEleve');
            
            //Je dois faire un test si Session expirée, throw message erreur + redirection page login
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');

            try
            {
                $this->addEleve($prenom,$nom,$idParentEleve,$idClasseEleve, $idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout');
                return $this->redirectToRoute('showAllEleves', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllEleves', [
                    'request' => $request
                ], 307);


        }
    }

    public function updateEleveAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idEleve = $request->get('idEleve');
            $prenomEleve = $request->get('prenomEleve');
            $nomEleve = $request->get('nomEleve');
            $idParentEleve = $request->get('parentEleve');
            $idClasseEleve = $request->get('classeEleve');

            try
            {
                $this->updateEleve($idEleve,$prenomEleve,$nomEleve,$idParentEleve,$idClasseEleve);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllEleves', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllEleves', [
                    'request' => $request
                ], 307);

        }
    }

    /********************************************* CRUD de la classe Élèves*******************************************/
    /******************************************************************************************************************/
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
            //Si Élève sans Classe
            if(null ==$eleve->getClasse())
            {
                $idClasseEleve = "0";
                $nomClasseEleve = "Aucune Classe";
            }
            else
            {
                $idClasseEleve = $eleve->getClasse()->getId();
                $nomClasseEleve = $eleve->getClasse()->getNom();
            }
            
            $listeEleves = Array(
                'idEleve' => $eleve->getId(),
                'prenomEleve' => ucfirst($eleve->getPrenom()),
                'nomEleve' => ucfirst($eleve->getNom()),
                'idParentEleve' => $eleve->getParent()->getId(),
                'idClasseEleve' => $idClasseEleve,
                'prenomParentEleve' => ucfirst($eleve->getParent()->getPrenom()),
                'nomParentEleve' => ucfirst($eleve->getParent()->getNom()),
                'nomClasseEleve' => $nomClasseEleve,
            );

            $listeFinaleEleves[] = $listeEleves;
        }
        return $listeFinaleEleves;

    }

    /********************************************** Afficher Un Seul Élève Par ID*************************************/
    public function showOneEleve($idEleve)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Eleves')
        ;

        $eleves = $repository->findOneBy(
            array('id' => $idEleve)
        );

        foreach ($eleves as $eleve)
        {
            //Si Élève sans Classe
            if(null ==$eleve->getClasse())
            {
                $idClasseEleve = "0";
                $nomClasseEleve = "Aucune Classe";
            }
            else
            {
                $idClasseEleve = $eleve->getClasse()->getId();
                $nomClasseEleve = $eleve->getClasse()->getNom();
            }
            
            $listeEleves = Array(
                'idEleve' => $eleve->getId(),
                'prenomEleve' => $eleve->getPrenom(),
                'nomEleve' => $eleve->getNom(),
                'idParentEleve' => $eleve->getParent()->getId(),
                'idClasseEleve' => $idClasseEleve
            );
        }
        return $listeEleves;

    }

    /********************************************** Ajouter un Élève**************************************************/
    public function addEleve($prenom, $nom, $idParentEleve, $idClasseEleve, $idEcole)
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
            ->getRepository('PronoteBundle:Parents')
        ;
        $parentEleve = $repository->findOneBy(
            array('id' => $idParentEleve)
        );

        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Classe')
        ;

        $classeEleve = $repository->findOneBy(
            array('id' => $idClasseEleve)
        );

        $em = $this->getDoctrine()->getManager();
        $eleve = new Eleves();

        $eleve->setPrenom($prenom);
        $eleve->setNom($nom);
        $eleve->setParent($parentEleve);
        $eleve->setClasse($classeEleve);
        $eleve->setEcole($ecole);

        $em->persist($eleve);
        $em->flush();
    }

    /********************************************** Modifier un Élève*************************************************/
    public function updateEleve($idEleve, $prenom, $nom, $idParentEleve, $idClasseEleve)
    {
        $em = $this->getDoctrine()->getManager();
        $eleve = $em->getRepository('PronoteBundle:Eleves')->find($idEleve);

        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Parents')
        ;
        $parentEleve = $repository->findOneBy(
            array('id' => $idParentEleve)
        );

        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Classe')
        ;

        $classeEleve = $repository->findOneBy(
            array('id' => $idClasseEleve)
        );

        if (!$eleve) {
            throw $this->createNotFoundException(
                'Cet Élève est introuvable, ID = '.$eleve
            );
        }

        if(!empty($prenom)){
            $eleve->setPrenom($prenom);
        }

        if(!empty($nom)){
            $eleve->setNom($nom);
        }

        if(!empty($idParentEleve)){
            $eleve->setParent($parentEleve);
        }

        if(isset($idClasseEleve)&& !empty($idClasseEleve)){
            $eleve->setClasse($classeEleve);
        }
        

        $em->flush();


    }

    /********************************************** Supprimer un Élève par ID*****************************************/
    public function deleteEleve($idEleve)
    {

        $em = $this->getDoctrine()->getManager();
        $eleve = $em->getRepository('PronoteBundle:Eleves')->find($idEleve);

        if (!$eleve) {
            throw $this->createNotFoundException(
                'Cet Élève est introuvable, ID = '.$idEleve
            );
        }
        
        //Il faut penser à le suppression du bulletin (PJ) du disque dur


        //On supprime l'élève
        $em->remove($eleve);
        $em->flush();
    }

    /********************************************** FIN CRUD de la classe Élèves**************************************/

    /**************************************** Contrôleurs de la classe Parents*****************************************/
    /******************************************************************************************************************/
    public function showAllParentsAction(Request $request)
    {
        //Afficher uniquement les Parents d'une École passée en paramètre via un POST
        
        $session = $this->get('session');
        $idEcole = $session->get('idEcole');
        
        //Chemain du logo École
        $repository = $this->getDoctrine()->getManager()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        if(null !== $ecole->getLogo())
        {
            $logoEcole = $ecole->getLogo();
        }
        
        return $this->render('PronoteBundle:Admin:mainParents.html.twig', array('allParents' => $this->showAllParents($idEcole) ,'logoEcole'=> $logoEcole));
       
    }

    public function deleteParentAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idParent = $request->get('idParentDelete');
           
            try
            {
                $this->deleteParent($idParent);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression');
                return $this->redirectToRoute('showAllParents', [
                    'request' => $request
                ], 307);
            }
                
                return $this->redirectToRoute('showAllParents', [
                    'request' => $request
                ], 307);

        }
    }

    public function addParentAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $prenom = $request->get('prenom');
            $nom = $request->get('nom');
            $login = $request->get('login');
            $password = $request->get('password');
            $tel = $request->get('tel');
            $adresse = $request->get('adresse');
            $email = $request->get('email');
                     
            $session = $this->get('session');
            $idEcole = $session->get('idEcole');
            
            
            try
            {
                $this->addParent($prenom,$nom,$login,$password,$tel,$adresse,$email,$idEcole);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Attention : L\'identifiant ou Email doivent être uniques dans le système');
                return $this->redirectToRoute('showAllParents', [
                    'request' => $request
                ], 307);
            }

                return $this->redirectToRoute('showAllParents', [
                    'request' => $request
                ], 307);

        }
    }

    public function updateParentAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $id = $request->get('id');
            $prenom = $request->get('prenom');
            $nom = $request->get('nom');
            $login = $request->get('login');
            $password = $request->get('password');
            $tel = $request->get('tel');
            $adresse = $request->get('adresse');
            $email = $request->get('email');
            

            try
            {
                $this->updateParent($id,$prenom,$nom,$login,$password,$tel,$adresse,$email);

            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la modification');
                return $this->redirectToRoute('showAllParents', [
                    'request' => $request
                ], 307);
            }

           
                return $this->redirectToRoute('showAllParents', [
                    'request' => $request
                ], 307);

        }
    }

    /********************************************* CRUD de la classe Parents*******************************************/
    /******************************************************************************************************************/
    private function findAllParents($idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Parents')
        ;
        
        return $repository->findBy(array('ecole' => $idEcole), array('prenom' => 'ASC'));
    }
    /********************************************** Afficher Tous les Parents******************************************/
    public function showAllParents($idEcole)
    {
        $listeFinaleParents= array();
        
        $parents = $this->findAllParents($idEcole);

            foreach ($parents as $parent)
            {
                $listeParents = Array(
                    'idParent' => $parent->getId(),
                    'prenomParent' => ucfirst($parent->getPrenom()),
                    'nomParent' => ucfirst($parent->getNom()),
                    'loginParent' => $parent->getLogin(),
                    'passwordParent' => $parent->getPassword(),
                    'telParent' => $parent->getTel(),
                    'adresseParent' => ucfirst($parent->getAdresse()),
                    'emailParent' => $parent->getEmail()
                );

                $listeFinaleParents[] = $listeParents;
            }
            return $listeFinaleParents;

    }

    /********************************************** Afficher Un Seul Parent Par ID*************************************/
    public function showOneParent($idParent)
    {
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Parents')
        ;

        $parents = $repository->findOneBy(
            array('id' => $idParent)
        );

        foreach ($parents as $parent)
        {
            $listeParents = Array(
                'idParent' => $parent->getId(),
                'prenomParent' => $parent->getPrenom(),
                'nomParent' => $parent->getNom(),
                'loginParent' => $parent->getLogin(),
                'passwordParent' => $parent->getPassword(),
                'telParent' => $parent->getTel(),
                'adresseParent' => $parent->getAdresse(),
                'emailParent' => $parent->getEmail()
            );
        }
        return $listeParents;

    }

    /********************************************** Ajouter un Parent**************************************************/
    public function addParent($prenom, $nom, $login, $password, $tel, $adresse, $email, $idEcole)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Ecole')
        ;
        $ecole = $repository->findOneBy(array('id' => $idEcole));
        
        $em = $this->getDoctrine()->getManager();
        $parent = new Parents();
        
        if(empty($email))
        {
            $email = $login.$password."@gmail.com";
        }

        $parent->setPrenom($prenom);
        $parent->setNom($nom);
        $parent->setLogin($login);
        $parent->setPassword($password);
        $parent->setTel($tel);
        $parent->setAdresse($adresse);
        $parent->setEmail($email);
        $parent->setEcole($ecole);

        $em->persist($parent);
        $em->flush();

    }

    /********************************************** Modifier un Parent*************************************************/
    public function updateParent($idParent, $prenom, $nom, $login, $password, $tel, $adresse, $email)
    {
        $em = $this->getDoctrine()->getManager();
        $parent = $em->getRepository('PronoteBundle:Parents')->find($idParent);

        if (!$parent) {
            throw $this->createNotFoundException(
                'Ce Parent est introuvable, ID = '.$idParent
            );
        }

        if(!empty($prenom)){
            $parent->setPrenom($prenom);
        }

        if(!empty($nom)){
            $parent->setNom($nom);
        }

        if(!empty($login)){
            $parent->setLogin($login);
        }

        if(!empty($password)){
            $parent->setPassword($password);
        }

        if(!empty($tel)){
            $parent->setTel($tel);
        }

        if(!empty($adresse)){
            $parent->setAdresse($adresse);
        }

        if(!empty($email)){
            $parent->setEmail($email);
        }

        $em->flush();


    }

    /********************************************** Supprimer un Parent par ID*****************************************/
    public function deleteParent($idParent)
    {

        $em = $this->getDoctrine()->getManager();
        $parent = $em->getRepository('PronoteBundle:Parents')->find($idParent);

        if (!$parent) {
            throw $this->createNotFoundException(
                'Ce Parent est introuvable, ID = '.$idParent
            );
        }
        $em->remove($parent);
        $em->flush();
    }

    /********************************************** FIN CRUD de la classe Parents**************************************/

    /*********************************************** Fonctions HELPERS   ************************************************/
    /******************************************** Afficher Toutes les Classes******************************************/
    public function showAllClassesWithoutEmploi($idEcole)
    {
        
        $listeFinaleClasses = array();
        
        $query = $this->
                getDoctrine()->
                getManager()->
                createQuery(
                'SELECT C
                FROM PronoteBundle:Classe C
                WHERE C.ecole = :idEcole and C.id not in (Select DISTINCT IDENTITY(E.classe) FROM PronoteBundle:Emploi E where E.classe IS NOT NULL)')
                ->setParameter('idEcole', $idEcole);

        
        $classes = $query->getResult();

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
    
    //Notification Classe
    private function addNotificationClasse($idClasse, $descriptionNotification, $lienNotification, $idEcole)
    {
        if(null!=$idClasse and '0'!=$idClasse)
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
    }
    
    public function informationsPratiques($idEcole)
    {

        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Eleves');
        $eleves = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Parents');
        $parents = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Professeurs');
        $professeurs = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Emploi');
        $emplois = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Classe');
        $classes = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Salles');
        $salles = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        //Logo École
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $logoEcole = 'bootstrap/img/logo.png';
        
        if(!empty($ecole->getLogo()))
        {
            $logoEcole = $ecole->getLogo();
        }

        //Construire les valeurs vers Twig
        $listeInformationsPratiques = array('eleves'=> $eleves, 'parents'=> $parents, 'professeurs'=> $professeurs, 'emplois'=> $emplois, 'classes'=> $classes, 'salles'=> $salles,'idEcole'=> $idEcole, 'session'=> $this->get('session')->get('idEcole'), 'logoEcole'=> $logoEcole);       
      
        return $listeInformationsPratiques;
    }
    
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
    
    //Reset lié à la Matière
    private function findAndResetToNullSeancesByMatiere($idMatiere)
    {
        $em = $this->getDoctrine()->getManager();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Seance')
        ;
        
        //Je récupère les Séances qui utilisent cette matière
        $seances = $repository->findBy(array('matiere' => $idMatiere), array());

        
        //Je fais l'update
        $qb = $em->createQueryBuilder();
        
        $qb->update('PronoteBundle:Seance','Seance');
        
        $qb->set('Seance.matiere',':sansMatiere');
        
        $qb->setParameter('sansMatiere',null);
        
        $qb->where('Seance.id IN (:seances)');
        
        $qb->setParameter('seances',$seances);
        
        $qb->getQuery()->execute();
        
        $em->flush();
    }
    
    //Resets liés à la Salle
    private function findAndResetToNullSeancesBySalle($idSalle)
    {
        $em = $this->getDoctrine()->getManager();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Seance')
        ;
        
        //Je récupère les Séances qui utilisent cette matière
        $seances = $repository->findBy(array('salle' => $idSalle), array());
        
        
        //Je fais l'update
        $qb = $em->createQueryBuilder();
        
        $qb->update('PronoteBundle:Seance','Seance');
        
        $qb->set('Seance.salle',':sansSalle');
        
        $qb->setParameter('sansSalle',null);
        
        $qb->where('Seance.id IN (:seances)');
        
        $qb->setParameter('seances',$seances);
        
        $qb->getQuery()->execute();
        
        $em->flush();
    }
    
    private function findAndResetToNullEmploisBySalle($idSalle)
    {
        $em = $this->getDoctrine()->getManager();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Emploi')
        ;
        
        //Je récupère les Séances qui utilisent cette matière
        $emplois = $repository->findBy(array('salle' => $idSalle), array());
        
        
        //Je fais l'update
        $qb = $em->createQueryBuilder();
        
        $qb->update('PronoteBundle:Emploi','Emploi');
        
        $qb->set('Emploi.salle',':sansSalle');
        
        $qb->setParameter('sansSalle',null);
        
        $qb->where('Emploi.id IN (:emplois)');
        
        $qb->setParameter('emplois',$emplois);
        
        $qb->getQuery()->execute();
        
        $em->flush();
    }
    
    //Reset lié à la Classe
    private function findAndResetToNullEmploisByClasse($idClasse)
    {
        $em = $this->getDoctrine()->getManager();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Emploi')
        ;
        
        //Je récupère les Séances qui utilisent cette matière
        $emplois = $repository->findBy(array('classe' => $idClasse), array());
        
        
        //Je fais l'update
        $qb = $em->createQueryBuilder();
        
        $qb->update('PronoteBundle:Emploi','Emploi');
        
        $qb->set('Emploi.classe',':sansClasse');
        
        $qb->setParameter('sansClasse',null);
        
        $qb->where('Emploi.id IN (:emplois)');
        
        $qb->setParameter('emplois',$emplois);
        
        $qb->getQuery()->execute();
        
        $em->flush();
    }
    
    private function findAndResetToNullElevesByClasse($idClasse)
    {
        $em = $this->getDoctrine()->getManager();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        //Je récupère les élèves liés à cette classe
        $eleves = $repository->findBy(array('classe' => $idClasse), array());
        
        
        //Je fais l'update
        $qb = $em->createQueryBuilder();
        
        $qb->update('PronoteBundle:Eleves','Eleves');
        
        $qb->set('Eleves.classe',':sansClasse');
        
        $qb->setParameter('sansClasse',0);
        
        $qb->where('Eleves.id IN (:Eleves)');
        
        $qb->setParameter('Eleves',$eleves);
        
        $qb->getQuery()->execute();
        
        $em->flush();
    }
    
    private function findAndResetToNullBulletinsByClasse($idClasse)
    {
        $em = $this->getDoctrine()->getManager();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Bulletin')
        ;
        
        //Je récupère les bulletins liés à cette classe
        $bulletins = $repository->findBy(array('classe' => $idClasse), array());
        
        
        //Je fais l'update
        $qb = $em->createQueryBuilder();
        
        $qb->update('PronoteBundle:Bulletin','Bulletin');
        
        $qb->set('Bulletin.classe',':sansClasse');
        
        $qb->setParameter('sansClasse',0);
        
        $qb->where('Bulletin.id IN (:Bulletin)');
        
        $qb->setParameter('Bulletin',$bulletins);
        
        $qb->getQuery()->execute();
        
        $em->flush();
    }
    

    
}
