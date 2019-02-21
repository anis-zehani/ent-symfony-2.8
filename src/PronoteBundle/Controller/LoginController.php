<?php
/**
 * Created by PhpStorm.
 * User: Anis
 * Date: 05/02/2018
 * Time: 13:33
 */

namespace PronoteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use PronoteBundle\Entity\Analytics;
use PronoteBundle\Entity\Classe;
use PronoteBundle\Entity\Eleves;
use PronoteBundle\Entity\Matieres;
use PronoteBundle\Entity\Parents;
use PronoteBundle\Entity\Horaires;
use PronoteBundle\Entity\Professeurs;
use PronoteBundle\Entity\Salles;
use PronoteBundle\Entity\Seance;
use PronoteBundle\Entity\Notifications;

use \Datetime;


class LoginController extends Controller
{
    //variable qui vérifie que la session est encore active
    //Par la vérification qu'une variabl n'est pas vide
    public function sessionAction(Request $request)
    {
        $session = $this->get('session');
        return new JsonResponse($session->get('parentSession'));
    }
    
    public function exitParentSessionAction(Request $request)
    {
        $session = $this->get('session');
        //$session->invalidate();
        $session->remove('login');
        $session->remove('password');
        $session->remove('parentSession');
        
        return $this->redirectToRoute('index_page');
    }
    
    /*******************************************Service Écran Principal************************************************/
    public function indexAction(Request $request)
    {
        $eleveListeDeroulante = null;
        $liste3 = null;$liste4 = null;$liste5 = null;$liste6 = null;$liste7 = null;$liste8 = null;
        $liste9 = null;$liste10 = null;$liste11 = null;$liste12 = null;$liste13 = null;
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Parents')
        ;

        if (null !== $request->get('login') and null !== $request->get('password'))
        {
            $login = $request->get('login');
            $password = $request->get('password');
            $parent = $repository->findOneBy(array('login' => $login, 'password' => $password));
            
            if (!empty($parent))
            {
                //Mise à jour compteur "parent"
                $analytics = $this->updateAnalytics($parent->getEcole()->getId(),"parent");
                
            }
            
            //On ajoute Login et Password à la session
            $session = $this->get('session');
            $session->start();
            $session->set('login', $login);
            $session->set('password', $password);
            $session->set('parentSession', "SessionParentIsOn");
        }
        
        elseif (null !== $request->get('idParent') and null !== $request->get('idEleve'))
        {
            $idParent = $request->get('idParent');
            $idEleve = $request->get('idEleve');
            $parent = $repository->findOneBy(array('id' => $idParent));
            
        }

        if (empty($parent))
            {
            return $this->render('PronoteBundle:Default:index.html.twig', array('message_erreur' => 'Votre Identifiant ou votre mot de passe est incorrect'));
            }
        else
            {
            $liste1 = Parents::parentToArray($parent);

            if(isset($idEleve))
            {
                $eleveListeDeroulante = $idEleve;
                $liste2 = $this->findEleveFromParent($parent->getId(), $eleveListeDeroulante);

            }
            else
            {
                $liste2 = $this->findEleveFromParent($parent->getId(),0);
                //Si le Parent a un enfant au moins
                if(sizeof($liste2)> 0)
                {
                    //Liste Déroulante : remplacer ID dans le GET par autre chose
                    $eleveListeDeroulante = $liste2[0]["idEnfant"];
                }

            }
            if(null !== $eleveListeDeroulante)
            {
            $liste3 = $this->findEmploiFromClasse($this->findClasseFromEleve($eleveListeDeroulante));
            //Notes Home Page et écran Notes
            $liste4 = $this->notesFromEleve($eleveListeDeroulante, date("Y-m")."-01");
           
            $classeEleveListeDeroulante = $this->findClasseFromEleve($eleveListeDeroulante);
            
            $liste5 = $this->devoirsForClasse($classeEleveListeDeroulante, date("Y-m-d"),$eleveListeDeroulante);
            $liste6 = $this->assiduiteForEleve($eleveListeDeroulante, date("Y-m")."-01");
            $liste7 = $this->activitesScolairesForClasse($classeEleveListeDeroulante, $eleveListeDeroulante, date("Y-m")."-01");
            $liste8 = $this->observationsGeneralesForClasse($classeEleveListeDeroulante, $eleveListeDeroulante);
            $liste9 = $this->telechargementsForClasse($classeEleveListeDeroulante, $eleveListeDeroulante);
            $liste10 = $this->menuCantineForClasse($classeEleveListeDeroulante, $eleveListeDeroulante);
            $liste11 = $this->listerNotificationsForEleve($eleveListeDeroulante);
            $liste12 = $this->listerNotificationsForClasse($classeEleveListeDeroulante);
            $liste13 = $this->bulletinsForEleve($eleveListeDeroulante);
            }
            
            //return new JsonResponse($analytics);
            
            //Chemain du logo École
            $logoEcole = 'bootstrap/img/logo.png';
            
            if(null !== $parent->getEcole()->getLogo())
            {
                $logoEcole = $parent->getEcole()->getLogo();
            }
    
            $total = array('pageData1' => $liste1, 'pageData2' => $liste2, 'pageData3' => $liste3, 'pageData4' => $liste4, 'pageData5' => $liste5, 'pageData6' => $liste6, 'pageData7' => $liste7, 'pageData8' => $liste8, 'pageData9' => $liste9, 'pageData10' => $liste10, 'pageData11' => $liste11, 'pageData12' => $liste12, 'pageData13' => $liste13, 'logoEcole' => $logoEcole);

            return $this->render('PronoteBundle:Default:main.html.twig', $total);


            }
            //return new JsonResponse($total);
    }
   
    /******************************************************************************************************************/
    /******************************************************************************************************************/
    /******************************************************************************************************************/
    /*                                               **************                                                   */


    /***********************************************  ACTIONS  ********************************************************/
    /******************************************************************************************************************/
    /******************************************************************************************************************/
    public function emploiAction(Request $request)
    {

            $idParent = $request->get('idParent');
            $idClasse = $request->get('idClasse');
            $idEleve  = $request->get('idEleve');

            $repository = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('PronoteBundle:Parents')
            ;
            $parent = $repository->findOneBy(array('id' => $idParent));
            
            $liste1 = Parents::parentToArray($parent);
            $liste2 = $this->findEleveFromParent($parent->getId(), $idEleve);


            if(isset($idClasse))
            {
                $liste3 = $this->findEmploiFromClasse($idClasse);
            }
            else
            {
                $liste3 = $this->findEmploiFromClasse($this->findClasseFromEleve($idEleve));
            }
            $liste4 = $this->notesFromEleve($idEleve, null);
            $liste11 = $this->listerNotificationsForEleve($idEleve);
            $liste12 = $this->listerNotificationsForClasse($idClasse);
            
            //Chemain du logo École
            $logoEcole = 'bootstrap/img/logo.png';
            
            if(null !== $parent->getEcole()->getLogo())
            {
                $logoEcole = $parent->getEcole()->getLogo();
            }
            
            $total = array('pageData1' => $liste1, 'pageData2' => $liste2, 'pageData3' => $liste3, 'pageData4' => $liste4, 'pageData11' => $liste11, 'pageData12' => $liste12, 'logoEcole' => $logoEcole);

            return $this->render('PronoteBundle:Default:emploi.html.twig', $total);

        //return new JsonResponse($total);
    }

    public function notesAction(Request $request)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Parents')
        ;
        
        $dateNote = null;
        
        $idParent = $request->get('idParent');
        $idEleve  = $request->get('idEleve');
        
        if (null !== $request->get('dateNote'))
        {
            $dateNote = $request->get('dateNote');
        }    

        $parent = $repository->findOneBy(array('id' => $idParent));
        $liste1 = Parents::parentToArray($parent);
        $liste2 = $this->findEleveFromParent($parent->getId(),$idEleve);
        $liste4 = $this->notesFromEleve($idEleve, $dateNote);
        $liste11 = $this->listerNotificationsForEleve($idEleve);
        $liste12 = $this->listerNotificationsForClasse($this->findClasseFromEleve($idEleve));
        
        //Chemain du logo École
        $logoEcole = 'bootstrap/img/logo.png';
        
        if(null !== $parent->getEcole()->getLogo())
        {
            $logoEcole = $parent->getEcole()->getLogo();
        }
        
        $total = array('pageData1' => $liste1, 'pageData2' => $liste2, 'pageData4' => $liste4, 'pageData11' => $liste11, 'pageData12' => $liste12, 'logoEcole' => $logoEcole);

        return $this->render('PronoteBundle:Default:notes.html.twig', $total);
    }

    public function devoirsAction(Request $request)
    {
        $liste1 = null;$liste2 = null;$liste3 = null;$liste4 = null;
        $liste11 = null;$liste12 = null;
        
        $idParent = $request->get('idParent');
        $idClasse = $request->get('idClasse');
        $idEleve = $request->get('idEleve');

        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Parents')
        ;
        $parent = $repository->findOneBy(array('id' => $idParent));
        $liste1 = Parents::parentToArray($parent);
        $liste2 = $this->findEleveFromParent($parent->getId(),$idEleve);
        $liste3 = $this->devoirsForClasse($idClasse, null, null);

        //liste4 contient les idEleves : à faire passer au header pour gérer les liens du menu
        foreach ($liste2 as $var){
            $listeVar = Array(
                'idEleve' => $var["idEnfant"],
            );

            $liste4[] = $listeVar;
        }
        $liste11 = $this->listerNotificationsForEleve($idEleve);
        $liste12 = $this->listerNotificationsForClasse($this->findClasseFromEleve($idEleve));
        
        //Chemain du logo École
        $logoEcole = 'bootstrap/img/logo.png';
        
        if(null !== $parent->getEcole()->getLogo())
        {
            $logoEcole = $parent->getEcole()->getLogo();
        }
        
        $total = array('pageData1' => $liste1, 'pageData2' => $liste2, 'pageData3' => $liste3 ,'pageData4' => $liste4, 'pageData11' => $liste11, 'pageData12' => $liste12, 'logoEcole' => $logoEcole); 
        
        return $this->render('PronoteBundle:Default:travailafaire.html.twig', $total);
        //return new JsonResponse($total);
    }

    public function assiduiteAction(Request $request)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Parents')
        ;
        
        $dateFaute = null;
        
        $idParent = $request->get('idParent');
        $idEleve = $request->get('idEleve');
        
        if (null !== $request->get('dateFaute'))
        {
            $dateFaute = $request->get('dateFaute');
        }  

        $parent = $repository->findOneBy(array('id' => $idParent));
        
        $liste1 = Parents::parentToArray($parent);
        $liste2 = $this->findEleveFromParent($parent->getId(), $idEleve);
        $liste4 = $this->assiduiteForEleve($idEleve, $dateFaute);

        $liste11 = $this->listerNotificationsForEleve($idEleve);
        $liste12 = $this->listerNotificationsForClasse($this->findClasseFromEleve($idEleve));
        
        //Chemain du logo École
        $logoEcole = 'bootstrap/img/logo.png';
        
        if(null !== $parent->getEcole()->getLogo())
        {
            $logoEcole = $parent->getEcole()->getLogo();
        }
        
        $total = array('pageData1' => $liste1, 'pageData2' => $liste2, 'pageData4' => $liste4, 'pageData11' => $liste11, 'pageData12' => $liste12, 'logoEcole' => $logoEcole);
        
        return $this->render('PronoteBundle:Default:assiduite.html.twig', $total);
        //return new JsonResponse($total);
    }
    /******************************************************************************************************************/
    /******************************************************************************************************************/
    /******************************************************************************************************************/

    //Retourne un Array qui contient les données de l'élève
    public function findEleveFromParent($parentID, $eleveID){
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Eleves')
        ;

        $eleve = $repository->findBy(
            array('parent' => $parentID),
            array('prenom' => 'ASC')
        );


        $listeDeroulante = Array();
        $eleveEnQuestion = Array();

        //Si eleveID n'a pas été fourni dans le GET donc == 0
        if($eleveID == 0)
        {
            foreach ($eleve as $ev)
            {
                $listeEleves = Array(
                    'idEnfant' => $ev->getId(),
                    'prenomEnfant' => $ev->getPrenom(),
                    'nomEnfant' => $ev->getNom(),
                    'idClasse' => $ev->getClasse()->getId(),
                    'nomClasse' => $ev->getClasse()->getNom()
                );

                $listeDeroulante [] = $listeEleves;
            }
        }
        //Si eleveID est fourni à partir de la liste déroulante
        elseif($eleveID != 0)
        {

            foreach ($eleve as $ev)
            {
                $listeEleves = Array(
                    'idEnfant' => $ev->getId(),
                    'prenomEnfant' => $ev->getPrenom(),
                    'nomEnfant' => $ev->getNom(),
                    'idClasse' => $ev->getClasse()->getId(),
                    'nomClasse' => $ev->getClasse()->getNom()
                );

                if($eleveID == $ev->getId() )
                {
                    $eleveEnQuestion [] = $listeEleves;
                    continue;
                }
                else
                {
                    $listeDeroulante [] = $listeEleves;
                }

            }
        }

        return array_merge($eleveEnQuestion,$listeDeroulante);

    }

    public function findClasseFromEleve($eleveID)
    {
        $IdclasseEleve = null;
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Eleves')
        ;

        $eleve = $repository->findOneBy(
            array('id' => $eleveID)
        );
        
        if(null !== $eleve) 
        {
            $IdclasseEleve = $eleve->getClasse()->getId();
        }
        
        return $IdclasseEleve;
    }

    public function seanceFromIdToColonnes(int $SeanceId)
    {
        $em = $this
            ->getDoctrine()
            ->getManager()
        ;

        $query = $em->createQuery("SELECT IDENTITY(s.matiere) as matiere , IDENTITY(s.professeur) as professeur , IDENTITY(s.salle) as salle
            FROM PronoteBundle:Seance s WHERE s.id =:idSeance")
            ->setParameter('idSeance' , $SeanceId);

        $resultat = $query->getResult();
        if(!empty($resultat)){
            $ArrayGlobal = array($resultat[0]["matiere"], $resultat[0]["professeur"], $resultat[0]["salle"]);
        }
        else{
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
        $chaineResultat = '';
        
        if(null!=($params[2]))
        {
            $query = $em->createQuery("SELECT m.nom as Matiere, p.nom as Professeur, s.identificateur as Salle
            FROM
            PronoteBundle:Matieres m, PronoteBundle:Professeurs p, PronoteBundle:Salles s
            WHERE
            m.id =:idMatiere
            AND
            s.id =:idSalle ")
            ->setParameters(['idMatiere' => $params[0],'idSalle' => $params[2]]);
            
            $resultat = $query->getResult();
            if(!empty($resultat)) {
                $chaineResultat = $resultat[0]["Matiere"] . "@@" . $resultat[0]["Salle"]. "@@" . $resultat[0]["Professeur"] ;
            }
            else{
                $chaineResultat='';
            }
        }
        else 
        {
            $query = $em->createQuery("SELECT m.nom as Matiere, p.nom as Professeur
            FROM
            PronoteBundle:Matieres m, PronoteBundle:Professeurs p
            WHERE
            m.id =:idMatiere")
            ->setParameters(['idMatiere' => $params[0]]);
            
            $resultat = $query->getResult();
            if(!empty($resultat)) {
                $chaineResultat = $resultat[0]["Matiere"] . "@@" . 'Aucune salle'. "@@" . $resultat[0]["Professeur"] ;
            }
            else{
                $chaineResultat='';
            }
        }

        return $chaineResultat;
    }

    //Retourne une ligne Emploi à partir d'un ID de classe (ex: 4ème A (ID=1))
    public function findEmploiFromClasse($classeID)
    {
        $Emploi = Array();
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Emploi')
        ;

        $ligneEmploi = $repository->findOneBy(
            array('classe' => $classeID)
        );

        if(!empty($ligneEmploi)) 
        {
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
            'designationEmploi' => $ligneEmploi->getDesignation()

        );
        }

        return $Emploi;

    }

    public function notesFromEleve($idEleve, $dateNote)
    {
        
        $listeFinaleNotes = Array();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        $eleve = $repository->findOneBy(array('id' => $idEleve));
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Notes')
        ;
        
        //Écran principal (2 sur 4) : tri avec Date
        if(isset($dateNote))
        {
            $dateTimeInput = new DateTime($dateNote);
            
            $query = $repository->createQueryBuilder('p')
            ->where('p.eleve = :eleve')
            ->andWhere('p.dateNote >= :dateTimeInput')
            ->setParameter('eleve', $idEleve)
            ->setParameter('dateTimeInput', $dateTimeInput)
            ->orderBy('p.dateNote', 'DESC')
            ->getQuery();
            
            $notes = $query->getResult();
            
            //test dans le cas ou un parent n'a pas d'élèves sur le système
            if(null==$eleve)
            {
                $prenomEleve = "Élève non défini";
            }
            else
            {
                $prenomEleve = $eleve->getPrenom();
            }
            
            if(Empty($notes))
            {
                $listeNotes = Array(
                    'idEleve' => $idEleve,
                    'messagePasDeNotes' => $prenomEleve." n'a pas encore eu des notes durant le mois en cours"
                    );
                
                $listeFinaleNotes[] = $listeNotes;
            }
            else
            {
                foreach ($notes as $note)
                {
                    $listeNotes = Array(
                        'idNote' => $note->getId(),
                        'idEleve' => $note->getEleve()->getId(),
                        'nomMatiere' => $note->getMatiere()->getNom(),
                        'note' => $note->getNote(),
                        'dateNote' => $note->getDateNote()->format('d-m-Y')
                        );
                    
                    $listeFinaleNotes[] = $listeNotes;
                }
            }
    
        }
        //Écran All notes (clic sur le menu) : tri sans Date
        else
        {
            $notes = $repository->findBy(
                array('eleve' => $idEleve),
                array('dateNote' => 'DESC')
                );
            
            //test dans le cas ou un parent n'a pas d'élèves sur le système
            if(null==$eleve)
            {
                $prenomEleve = "Élève non défini";
            }
            else
            {
                $prenomEleve = $eleve->getPrenom();
            }
            
            if(Empty($notes))
            {
                $listeNotes = Array(
                    'idEleve' => $idEleve,
                    'messagePasDeNotes' => $prenomEleve." n'a pas encore eu des notes"
                    );
                
                $listeFinaleNotes[] = $listeNotes;
            }
            else
            {
                foreach ($notes as $note)
                {
                    $listeNotes = Array(
                        'idNote' => $note->getId(),
                        'idEleve' => $note->getEleve()->getId(),
                        'nomMatiere' => $note->getMatiere()->getNom(),
                        'note' => $note->getNote(),
                        'dateNote' => $note->getDateNote()->format('d-m-Y')
                        );
                    
                    $listeFinaleNotes[] = $listeNotes;
                }
            }
        }
        
       
        return $listeFinaleNotes;

    }

    //Retourne la liste des devoirs à faire pour une Classe donnée
    public function devoirsForClasse($idClasse, $dateDevoir, $idEleve)
    {
        
        $listeFinaleDevoirs = Array();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        $eleve = $repository->findOneBy(array('id' => $idEleve));
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Devoirs')
        ;

        //Écran principal (3 sur 4) : tri avec Date
        if(isset($dateDevoir))
        {
            $dateTimeInput = new DateTime($dateDevoir);
            
            $query = $repository->createQueryBuilder('p')
            ->where('p.classe = :classe')
            ->andWhere('p.donnePour >= :dateTimeInput')
            ->setParameter('classe', $idClasse)
            ->setParameter('dateTimeInput', $dateTimeInput)
            ->orderBy('p.donnePour', 'ASC')
            ->getQuery();
            
            $devoirs = $query->getResult();
            
            //test dans le cas ou un parent n'a pas d'élèves sur le système
            if(null==$eleve)
            {
                $prenomEleve = "Élève non défini";
            }
            else
            {
                $prenomEleve = $eleve->getPrenom();
            }
            
            if(Empty($devoirs))
            {
                $listeDevoirs = Array(
                    'idDevoir' => '',
                    'nomMatiere' => '',
                    'detailsDevoir' => '',
                    'classe' => '',
                    'donneLe' => '',
                    'donnePour' => '',
                    'messagePasDeDevoirs' => $prenomEleve." n'a pas de travail à faire pour le moment, soyez rassurés, tous les travaux du mois en cours s'afficheront au fur et à mesure"
                    );
                
                $listeFinaleDevoirs[] = $listeDevoirs;
            }
            else
            {
                foreach ($devoirs as $devoir)
                {
                    $listeDevoirs = Array(
                        'idDevoir' => $devoir->getId(),
                        'nomMatiere' => $devoir->getMatiere()->getNom(),
                        'detailsDevoir' => $devoir->getDetailsDevoir(),
                        'classe' => $devoir->getClasse()->getNom(),
                        'donneLe' => $devoir->getDonneLe()->format('d-m-Y'),
                        'donnePour' => $devoir->getDonnePour()->format('d-m-Y')
                        );
                    
                    $listeFinaleDevoirs[] = $listeDevoirs;
                }
            }
            
        }
        //Écran All Devoirs (clic sur le menu) : tri sans Date
        else
        {
            $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Devoirs')
            ;
            
            $devoirs = $repository->findBy(
                array('classe' => $idClasse),
                array('donnePour' => 'ASC')
                );
            
            if(Empty($devoirs))
            {
                
                $listeDevoirs = Array(
                    'idDevoir' => '',
                    'nomMatiere' => '',
                    'detailsDevoir' => '',
                    'classe' => '',
                    'donneLe' => '',
                    'donnePour' => ''
                    );
                
                $listeFinaleDevoirs[] = $listeDevoirs;
                
            }
            else
            {
                foreach ($devoirs as $devoir)
                {
                        $listeDevoirs = Array(
                            'idDevoir' => $devoir->getId(),
                            'nomMatiere' => $devoir->getMatiere()->getNom(),
                            'detailsDevoir' => $devoir->getDetailsDevoir(),
                            'classe' => $devoir->getClasse()->getNom(),
                            'donneLe' => $devoir->getDonneLe()->format('d-m-Y'),
                            'donnePour' => $devoir->getDonnePour()->format('d-m-Y')
                        );
            
                        $listeFinaleDevoirs[] = $listeDevoirs;
                }
            }
        }
        
        return $listeFinaleDevoirs;

    }

    //Retourne toutes les assiduité d'un élève donné
    public function assiduiteForEleve($idEleve, $dateFaute)
    {
        
        $listeFinaleAssiduites = array();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        $eleve = $repository->findOneBy(array('id' => $idEleve));
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Assiduite')
        ;

        if(isset ($dateFaute))
        {
            $dateTimeInput = new DateTime($dateFaute);
                
            $query = $repository->createQueryBuilder('p')
                ->where('p.eleve = :eleve')
                ->andWhere('p.dateFaute >= :dateTimeInput')
                ->setParameter('eleve', $idEleve)
                ->setParameter('dateTimeInput', $dateTimeInput)
                ->orderBy('p.dateFaute', 'DESC')
                ->getQuery();
                
            $assiduites = $query->getResult();
        }
        else
        {
            $assiduites = $repository->findBy(
            array('eleve' => $idEleve),
            array('dateFaute' => 'DESC')
            );
        
        }
        
        //test dans le cas ou un parent n'a pas d'élèves sur le système
        if(null==$eleve)
        {
            $prenomEleve = "Élève non défini";
        }
        else 
        {
            $prenomEleve = $eleve->getPrenom();
        }
        
        if(Empty($assiduites))
        {
            $listeAssiduites = Array(
                'idAssiduite' => "",
                'idEleve' => $idEleve,
                'nomEleve' => $prenomEleve,
                'detailsAssiduite' => "",
                'messagePasDeAssiduites' => "Félicitaitions : ".$prenomEleve." a une assiduité Parfaite. C'est grâce à vous certainement",
                'typeFaute' => "",
                'dateAssiduite' => date('d-m-Y')
            );

            $listeFinaleAssiduites[] = $listeAssiduites;
        }
        else
        {
        foreach ($assiduites as $assiduite){
            $listeAssiduites = Array(
                'idAssiduite' => $assiduite->getId(),
                'idEleve' => $assiduite->getEleve()->getId(),
                'nomEleve' => $eleve->getPrenom(),
                'detailsAssiduite' => $assiduite->getDetailsFautes(),
                'typeFaute' => $assiduite->getTypeFaute()->getTypeFaute(),
                'dateAssiduite' => $assiduite->getDateFaute()->format('d-m-Y')
            );

            $listeFinaleAssiduites[] = $listeAssiduites;
        }
        }
        return $listeFinaleAssiduites;

    }

    //Retourne toutes les activités scolaires d'une classe donnée
    public function activitesScolairesForClasse($idClasse, $idEleve, $dateActivite)
    {
        $listeFinaleActivites = array();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        $eleve = $repository->findOneBy(array('id' => $idEleve));
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:ActivitesScolaires')
        ;
        
        
        $dateTimeInput = new DateTime($dateActivite);
            
            $query = $repository->createQueryBuilder('p')
            ->where('p.classe = :classe')
            ->andWhere('p.dateActivite >= :dateTimeInput')
            ->setParameter('classe', $idClasse)
            ->setParameter('dateTimeInput', $dateTimeInput)
            ->orderBy('p.dateActivite', 'ASC')
            ->getQuery();
            
        $activites = $query->getResult();
            
        /*$activites = $repository->findBy(
            array('classe' => $idClasse),
            array('dateActivite' => 'ASC')
        );*/

        //test dans le cas ou un parent n'a pas d'élèves sur le système
        if(null==$eleve)
        {
            $prenomEleve = "Élève non défini";
        }
        else
        {
            $prenomEleve = $eleve->getPrenom();
        }
        
        if(Empty($activites))
        {
            $listeActivites = Array(
                'idActivite' => "",
                'descriptionActivite' => $prenomEleve." n'a aucune activité scolaire mentionnée pour le moment",
                'dateActivite' => ''
            );

            $listeFinaleActivites[] = $listeActivites;
        }
        else
        {
        foreach ($activites as $activite){
            $listeActivites = Array(
                'idActivite' => $activite->getId(),
                'descriptionActivite' => $activite->getDescription(),
                'dateActivite' => $activite->getDateActivite()->format('d-m-Y')
            );

            $listeFinaleActivites[] = $listeActivites;
        }
        }
        return $listeFinaleActivites;


    }

    //Retourne toutes les observations générales d'une classe donnée
    public function observationsGeneralesForClasse($idClasse, $idEleve)
    {
        $listeFinaleObservations = array();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        $eleve = $repository->findOneBy(array('id' => $idEleve));
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:ObservationsGenerales')
        ;

        $observations = $repository->findBy(
            array('classe' => $idClasse)
        );

        //test dans le cas ou un parent n'a pas d'élèves sur le système
        if(null==$eleve)
        {
            $prenomEleve = "Élève non défini";
        }
        else
        {
            $prenomEleve = $eleve->getPrenom();
        }

        if(Empty($observations))
        {
            $listeObservations = Array(
                'idObservation' => "",
                'descriptionObservation' => $prenomEleve." n'a aucun évènement dans l'agenda pour le moment"
            );

            $listeFinaleObservations[] = $listeObservations;
        }
        else
        {
        foreach ($observations as $observation){
            $listeObservations = Array(
                'idObservation' => $observation->getId(),
                'descriptionObservation' => $observation->getDescription()
            );

            $listeFinaleObservations[] = $listeObservations;
        }
        }
        return $listeFinaleObservations;

    }

    //Retourne touts les téléchargements disponibles pour une classe
    public function telechargementsForClasse($idClasse, $idEleve)
    {
        $listeFinaleTelechargements = array();
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        $eleve = $repository->findOneBy(array('id' => $idEleve));
        
        $repository = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PronoteBundle:Telechargements')
        ;

        $telechargements = $repository->findBy(
            array('classe' => $idClasse)
        );

        //test dans le cas ou un parent n'a pas d'élèves sur le système
        if(null==$eleve)
        {
            $prenomEleve = "Élève non défini";
        }
        else
        {
            $prenomEleve = $eleve->getPrenom();
        }
        
        if(Empty($telechargements))
        {
            $listeTelechargements = Array(
                'idTelechargement' => "",
                'descriptionTelechargement' => $prenomEleve." n'a aucun téléchargement disponible pour le moment",
                'pathTelechargement' => ""
            );

            $listeFinaleTelechargements[] = $listeTelechargements;
        }
        else
        {
            foreach ($telechargements as $telechargement){
                $listeTelechargements = Array(
                    'idTelechargement' => $telechargement->getId(),
                    'descriptionTelechargement' => $telechargement->getDescription(),
                    'pathTelechargement' => "uploads/".$telechargement->getFile()
                );

                $listeFinaleTelechargements[] = $listeTelechargements;
            }
        }
        return $listeFinaleTelechargements;

    }
    
    //Retourne touts les Menu Cantine disponibles pour une classe
    public function menuCantineForClasse($idClasse, $idEleve)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        $eleve = $repository->findOneBy(array('id' => $idEleve));
        
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:MenuCantine')
        ;
        
        $MenuCantines = $repository->findBy(
            array('classe' => $idClasse)
            );
        
        
        
        //test dans le cas ou un parent n'a pas d'élèves sur le système
        if(null==$eleve)
        {
            $prenomEleve = "Élève non défini";
        }
        else
        {
            $prenomEleve = $eleve->getPrenom();
        }
        
        $listeFinaleMenuCantine = array();
        
        if(Empty($MenuCantines))
        {
            $listeMenuCantine = Array(
                'idMenuCantine' => "",
                'descriptionMenuCantine' => $prenomEleve." n'a aucun Menu Cantine disponible pour le moment",
                'pathMenuCantine' => ""
                );
            
            $listeFinaleMenuCantine[] = $listeMenuCantine;
        }
        else
        {
            foreach ($MenuCantines as $MenuCantine){
                $listeMenuCantine = Array(
                    'idMenuCantine' => $MenuCantine->getId(),
                    'descriptionMenuCantine' => $MenuCantine->getDescription(),
                    'pathMenuCantine' => "uploads/".$MenuCantine->getFile()
                    );
                
                $listeFinaleMenuCantine[] = $listeMenuCantine;
            }
        }
        return $listeFinaleMenuCantine;
        
    }
    
    //Retourne les bulletins disponibles pour un élève
    public function bulletinsForEleve($idEleve)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Eleves')
        ;
        
        $eleve = $repository->findOneBy(array('id' => $idEleve));
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Bulletin')
        ;
        
        $Bulletins = $repository->findBy(
            array('eleve' => $idEleve)
            );
        
        //test dans le cas ou un parent n'a pas d'élèves sur le système
        if(null==$eleve)
        {
            $prenomEleve = "Élève non défini";
        }
        else
        {
            $prenomEleve = $eleve->getPrenom();
        }
        
        $listeFinaleBulletins = array();
        
        if(Empty($Bulletins))
        {
            $listeBulletins = Array(
                'idBulletin' => "",
                'descriptionBulletin' => $prenomEleve." n'a aucun Bulletin scolaire disponible pour le moment",
                'pathBulletin' => ""
                );
            
            $listeFinaleBulletins[] = $listeBulletins;
        }
        else
        {
            foreach ($Bulletins as $Bulletin){
                $listeBulletins = Array(
                    'idBulletin' => $Bulletin->getId(),
                    'descriptionBulletin' => $Bulletin->getDescription(),
                    'pathBulletin' => $Bulletin->getFile()
                    );
                
                $listeFinaleBulletins[] = $listeBulletins;
            }
        }
        return $listeFinaleBulletins;
        
    }
    
    /******************************************************************************************************************/
            /***********************************GESTION DES NOTIFICATIONS***************************************/
    /******************************************************************************************************************/
    
    //Retourne la liste des Notifications pour un élève donnée
    public function listerNotificationsForEleve($idEleve)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Notifications')
        ;
        
        $notifications = $repository->findBy(
            array('eleve' => $idEleve, 'etatNotification' => "NVU"),
            array('id' => 'ASC')
            );
        $listeFinaleNotifications = array();
        
        foreach ($notifications as $notification){
            
            $listeNotifications = Array(
                'idNotification' => $notification->getId(),
                'etatNotification' => $notification->getEtatNotification(),
                'descriptionNotification' => $notification->getDescriptionNotification(),
                'lienNotification' => $notification->getLienNotification(),
                'eleveId' => $notification->getEleve()->getId()
                );
            
            $listeFinaleNotifications[] = $listeNotifications;
        }
        return $listeFinaleNotifications;
        
    }
    
    //Retourne la liste des Notifications pour un élève donnée
    public function listerNotificationsForClasse($idClasse)
    {
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:NotificationsClasse')
        ;
        
        $notificationsClasse = $repository->findBy(
            array('classe' => $idClasse, 'etatNotification' => "NVU"),
            array('id' => 'ASC')
            );
        $listeFinaleNotificationsClasse = array();
        
        foreach ($notificationsClasse as $notificationClasse){
            
            $listeNotificationsClasse = Array(
                'idNotification' => $notificationClasse->getId(),
                'etatNotification' => $notificationClasse->getEtatNotification(),
                'descriptionNotification' => $notificationClasse->getDescriptionNotification(),
                'lienNotification' => $notificationClasse->getLienNotification(),
                'classeId' => $notificationClasse->getClasse()->getId()
                );
            
            $listeFinaleNotificationsClasse[] = $listeNotificationsClasse;
        }
        return $listeFinaleNotificationsClasse;
        
    }
    
    //Fonction qui désactive une Notification Élève suite à un appel Ajax
    
    public function deactivateNotificationAction(Request $request)
    {
         
        $idNotification = $request->get('idNotification');
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:Notifications')
        ;
        
        $notification = $repository->findOneBy(array('id' => $idNotification));
        
        $em = $this->getDoctrine()->getManager();
        
        $notification->setEtatNotification("VU");$em->persist($notification);
        
        $response = array("code" => 200, "success" => true);
        
        //Penser si c'est bénéfique de supprimer les notifications aprés usage
        //$em->remove($notification);
        $em->flush();

        return new Response(json_encode($response)); 
        
    }
    
    //Fonction qui désactive une Notification Classe suite à un appel Ajax
    
    public function deactivateNotificationClasseAction(Request $request)
    {
        
        $idNotificationClasse = $request->get('idNotificationClasse');
        
        $repository = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('PronoteBundle:NotificationsClasse')
        ;
        
        $notificationClasse = $repository->findOneBy(array('id' => $idNotificationClasse));
        
        $em = $this->getDoctrine()->getManager();
        
        $notificationClasse->setEtatNotification("VU");$em->persist($notificationClasse);
        
        $response = array("code" => 200, "success" => true);
        
        //Penser si c'est bénéfique de supprimer les notifications aprés usage
        //$em->remove($notificationClasse);
        $em->flush();

        
        return new Response(json_encode($response));
        
    }
    
    //Fonction générique qui ajoute une ligne dans la table Notification
    
    /**************Prévoir l'envoi de mail aux parents********************/
    /*public function addNotification($idEleve, $descriptionNotification)
    {
        $notification = new Notifications();
        
        $notification->setEleve($idEleve);
        $notification->setDescriptionNotification($descriptionNotification);
        $notification->setEtatNotification("NVU");
        
        $this->getDoctrine()->getManager()->persist($notification);
        $this->getDoctrine()->getManager()->flush();
    }*/
    
    //Ajouter un CRON qui Delete une Notification VUe au bout de 15 jours
    //Fonction générique qui supprime une notification de la table Notification
    public function deleteNotification($idNotification)
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
