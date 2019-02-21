<?php

namespace PronoteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use PronoteBundle\Entity\Ecole;
use PronoteBundle\Entity\Admin;
use PronoteBundle\Entity\Analytics;
use Symfony\Component\HttpFoundation\JsonResponse;

class SmartgrapheController extends Controller
{
    public function indexAction()
    {
        return $this->render('PronoteBundle:Smartgraphe:index.html.twig');

    }
    
    public function accueilAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $login = $request->get('login');
            $password = $request->get('password');
            
            //Bon paramètres je renvoi vers le Dashboard
            if ($login =='ss135931' && $password=='fsdf+34jkk558-gjl57o')
            {
                return $this->render('PronoteBundle:Smartgraphe:accueil.html.twig',
                    array(
                        'allEcoles' => $this->showAllEcoles())
                    );
            }
            
        }

        return $this->redirect('PronoteBundle:Smartgraphe:index.html.twig');
        
    }
    
    public function showAllEcolesAction(Request $request)
    {
        
        return $this->render('PronoteBundle:Smartgraphe:accueil.html.twig',array('allEcoles' => $this->showAllEcoles()));
        
    }
    
    public function showEcoleAction(Request $request)
    {
        $idEcole = $request->get('idEcoleAfficher');
        return $this->render('PronoteBundle:Smartgraphe:mainEcole.html.twig', $this->informationsPratiques($idEcole));
        //return new JsonResponse($this->informationsPratiques($idEcole));
        
        

    }
    
    public function archiveUnarchiveLogoAction(Request $request)
    {
        $idEcole = $request->get('idEcoleAfficher');
        
        $em = $this->getDoctrine()->getManager();
        
        $repository = $em->getRepository('PronoteBundle:Ecole');
        
        //Je récupère École à modifier
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        if(null == $ecole->getLogo())
        {
            $ecole->setLogo($ecole->getPathArchivedLogo());
            $em->flush();
        }
        else
        {
            //Je fais l'update
            $qb = $em->createQueryBuilder();
            
            $qb->update('PronoteBundle:Ecole','Ecole');
            
            $qb->set('Ecole.logo',':sansLogo');
            
            $qb->setParameter('sansLogo',null);
            
            $qb->where('Ecole.id IN (:ecole)');
            
            $qb->setParameter('ecole',$ecole);
            
            $qb->getQuery()->execute();
            
            $em->flush();
        }
        
        //return $this->render('PronoteBundle:Smartgraphe:mainEcole.html.twig', $this->informationsPratiques($idEcole));
        return $this->redirectToRoute('smartgraphe_showEcole', [
            'request' => $request
        ], 307);
    }
    
    /*************************************************************************************************************************/
    private function findAllEcoles()
    {
        $repository = $this
         ->getDoctrine()
         ->getManager()
         ->getRepository('PronoteBundle:Ecole')
         ;
         
         return $repository->findBy(array(), array('nomEcole' => 'ASC'));
    }
    
    /********************************************** Afficher les écoles ******************************************************/
    public function showAllEcoles()
    {
        //Ce Repository permet de calculer le nombre des élèves et des parents
        $repository1 = $this->getDoctrine()->getRepository('PronoteBundle:Eleves');
        $repository2 = $this->getDoctrine()->getRepository('PronoteBundle:Parents');
        
        $listeFinaleEcoles = array();
        
        $ecoles = $this->findAllEcoles();
        
        foreach ($ecoles as $ecole)
        {
            
            $listeEcoles = Array(
                'idEcole' => $ecole->getId(),
                'nomEcole' => $ecole->getNomEcole(),
                'responsableEcole' => $ecole->getResponsableEcole(),
                'loginAdmin' => $ecole->getloginAdminEcole(),
                'passwordAdmin' => $ecole->getpasswordAdminEcole(),
                'adresse' => $ecole->getAdresse(),
                'emailEcole' => $ecole->getEmail(),
                'sitewebEcole' => $ecole->getSite(),
                'fixeEcole' => $ecole->getFixe(),
                'mobileEcole' => $ecole->getMobile(),
                'descriptionEcole' => $ecole->getDescription(),
                'logo' => $ecole->getLogo(),
                
                'nombreEleves' => count($repository1->findBy(array('ecole' => $ecole->getId()), array())),
                'nombreParents' => count($repository2->findBy(array('ecole' => $ecole->getId()), array())),
                'totalAnalytics' => $this->totalAnalytics($ecole->getId())
                );
            
            $listeFinaleEcoles[] = $listeEcoles;
        }
        return $listeFinaleEcoles;
        
    }
    
    /********************************************** Ajouter une école ********************************************************/
    public function addEcoleAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $nomEcole = $request->get('nomecole');
            $responsableEcole = $request->get('responsableecole');
            $loginAdmin = $request->get('loginadmin');
            $passwordAdmin = $request->get('passwordadmin');
            $adresse = $request->get('adresse');
            $emailEcole = $request->get('emailecole');
            $sitewebEcole = $request->get('sitewebecole');
            $fixeEcole = $request->get('fixeecole');
            $mobileEcole = $request->get('mobileecole');
            $descriptionEcole = $request->get('descriptionecole');
            
            try
            {
                $this->addEcole
                (
                    $nomEcole, 
                    $responsableEcole, 
                    $loginAdmin, 
                    $passwordAdmin, 
                    $adresse, 
                    $emailEcole, 
                    $sitewebEcole,
                    $fixeEcole,
                    $mobileEcole,
                    $descriptionEcole
                );
                
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout'.$e);
                
                return $this->redirectToRoute('smartgraphe_showAllEcoles');
            }
                return $this->redirectToRoute('smartgraphe_showAllEcoles');   
        }
    }
    

    public function addEcole($nomEcole, $responsableEcole, $loginAdmin, $passwordAdmin, $adresse, $emailEcole, $sitewebEcole,$fixeEcole,$mobileEcole,$descriptionEcole)
    {
        
        //Ajout École
        $em = $this->getDoctrine()->getManager();
        $ecole = new Ecole();
        
        $ecole->setNomEcole($nomEcole);
        $ecole->setResponsableEcole($responsableEcole);
        $ecole->setloginAdminEcole($loginAdmin);
        $ecole->setpasswordAdminEcole($passwordAdmin);
        $ecole->setAdresse($adresse);
        $ecole->setEmail($emailEcole);
        $ecole->setSite($sitewebEcole);
        $ecole->setFixe($fixeEcole);
        $ecole->setMobile($mobileEcole);
        $ecole->setDescription($descriptionEcole);

        
        $em->persist($ecole);
        $em->flush();
        
        //Ajout Admin
        $em1 = $this->getDoctrine()->getManager();
        $admin = new Admin();
        $profil = "superadmin";
        
        $admin->setLogin($loginAdmin);
        $admin->setPassword($passwordAdmin);
        //Profil SUPERADMIN non supprimable : "superadmin"
        $admin->setProfil($profil);
        $admin->setEcole($ecole);
        
        $em1->persist($admin);
        $em1->flush();
        
        /////////////////////////////////////////////////////////////////////////////////
        //Ajout Analytics : superadmin
        $em2 = $this->getDoctrine()->getManager();
        $analytics2 = new Analytics();
        
        $profil = "superadmin";
        $analytics2->setCompteur(0);
        $analytics2->setProfil($profil);
        $analytics2->setEcole($ecole);
        
        $em2->persist($analytics2);
        $em2->flush();
        
        ////////////////////////////////////////////////////////////////////////////////
        //Ajout Analytics : admin
        $em3 = $this->getDoctrine()->getManager();
        $analytics3 = new Analytics();
        
        $profil = "admin";
        $analytics3->setCompteur(0);
        $analytics3->setProfil($profil);
        $analytics3->setEcole($ecole);
        
        $em3->persist($analytics3);
        $em3->flush();
        
        ////////////////////////////////////////////////////////////////////////////////
        //Ajout Analytics : parent
        $em4 = $this->getDoctrine()->getManager();
        $analytics4 = new Analytics();
        
        $profil = "parent";
        $analytics4->setCompteur(0);
        $analytics4->setProfil($profil);
        $analytics4->setEcole($ecole);
        
        $em4->persist($analytics4);
        $em4->flush();
        
        ////////////////////////////////////////////////////////////////////////////////
        //Ajout Analytics : professeur
        $em5 = $this->getDoctrine()->getManager();
        $analytics5 = new Analytics();
        
        $profil = "professeur";
        $analytics5->setCompteur(0);
        $analytics5->setProfil($profil);
        $analytics5->setEcole($ecole);
        
        $em5->persist($analytics5);
        $em5->flush();
        
        mkdir('uploads/'.$ecole->getId(), 0755, true);
        
        mkdir('uploads/'.$ecole->getId().'/bulletins', 0755, true);
        mkdir('uploads/'.$ecole->getId().'/cantine', 0755, true);
        mkdir('uploads/'.$ecole->getId().'/ressourcesAdmin', 0755, true);
        mkdir('uploads/'.$ecole->getId().'/ressourcesProf', 0755, true);
        mkdir('uploads/'.$ecole->getId().'/ecole', 0755, true);
        
        
    }
    
    /********************************************** Supprimer une école *******************************************************/
    public function deleteEcoleAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            
            $idEcole = $request->get('idEcoleDelete');
            
            try
            {
                $this->deleteEcole($idEcole);
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de la suppression'.$e);
                
                return $this->redirectToRoute('smartgraphe_showAllEcoles');
            }
                return $this->redirectToRoute('smartgraphe_showAllEcoles');
            
        }
    }
    
    
    public function deleteEcole($idEcole)
    {
        try 
        {
            
            //Delete SUPERADMIN
            $em = $this->getDoctrine()->getManager();
            $superAdmin =  $this->findSuperAdmin($idEcole, "superadmin");
            $em->remove($superAdmin);
            $em->flush();
            
            
            //Delete ÉCOLE
            $em1 = $this->getDoctrine()->getManager();
            $ecole = $em->getRepository('PronoteBundle:Ecole')->find($idEcole);
            $em1->remove($ecole);
            $em1->flush();
            
            /*array_map('unlink', glob("uploads/".$ecole->getId()."/*.*"));
            rmdir("uploads/".$ecole->getId());*/
        }
        
        catch(Exception $e) 
        {
            echo 'Message: ' .$e->getMessage();
        }
        
    }
    
    /********************************************** Modifier une école ********************************************************/
    public function updateEcoleAction(Request $request)
    {
        if ('POST' === $request->getMethod())
        {
            $idEcole = $request->get('idEcole');
            $nomEcole = $request->get('nomecole');
            $responsableEcole = $request->get('responsableecole');
            $loginAdmin = $request->get('loginadmin');
            $passwordAdmin = $request->get('passwordadmin');
            $adresse = $request->get('adresse');
            $emailEcole = $request->get('emailecole');
            $sitewebEcole = $request->get('sitewebecole');
            $fixeEcole = $request->get('fixeecole');
            $mobileEcole = $request->get('mobileecole');
            $descriptionEcole = $request->get('descriptionecole');
            
            //Upload de : CIN, Registre du Commerce, Logo
            $newNameCin = $this->uploadFile($_FILES["cinecole"],$idEcole);
            $newNameRc = $this->uploadFile($_FILES["rcecole"],$idEcole);
            $newNameLogo = $this->uploadFile($_FILES["logoecole"],$idEcole);
            
            try
            {
                $this->updateEcole($idEcole, $nomEcole, $responsableEcole, $loginAdmin, $passwordAdmin, $adresse, $emailEcole, $sitewebEcole,$fixeEcole,$mobileEcole,$descriptionEcole,$newNameCin,$newNameRc,$newNameLogo);
                
            }
            catch(\Doctrine\DBAL\DBALException $e)
            {
                $this->get('session')->getFlashBag()->add('Exception', 'Problème lors de l\'ajout'.$e);
                
                return $this->redirectToRoute('smartgraphe_showAllEcoles');
            }
            
                return $this->redirectToRoute('smartgraphe_showAllEcoles');
        }
    }
    
    public function updateEcole($idEcole, $nomEcole, $responsableEcole, $loginAdmin, $passwordAdmin, $adresse, $emailEcole, $sitewebEcole,$fixeEcole,$mobileEcole,$descriptionEcole,$newNameCin,$newNameRc,$newNameLogo)
    {
        $em = $this->getDoctrine()->getManager();
        $ecole = $em->getRepository('PronoteBundle:Ecole')->find($idEcole);
        
        if (!$ecole) {
            throw $this->createNotFoundException(
                'Cette école est introuvable, ID = '.$idEcole
                );
        }
        
        $superAdmin =  $this->findSuperAdmin($idEcole, "superadmin");
        
        if(isset($nomEcole)){
            $ecole->setNomEcole($nomEcole);
        }
        
        if(isset($responsableEcole)){
            $ecole->setResponsableEcole($responsableEcole);
        }
        
        if(isset($loginAdmin)){
            $ecole->setloginAdminEcole($loginAdmin);
            $superAdmin->setLogin($loginAdmin);
        }
        
        if(isset($passwordAdmin)){
            $ecole->setpasswordAdminEcole($passwordAdmin);
            $superAdmin->setPassword($passwordAdmin);
        }
        
        if(isset($adresse)){
            $ecole->setAdresse($adresse);
        }
        
        if(isset($emailEcole)){
            $ecole->setEmail($emailEcole);
        }
        
        if(isset($sitewebEcole)){
            $ecole->setSite($sitewebEcole);
        }
        
        if(isset($fixeEcole)){
            $ecole->setFixe($fixeEcole);
        }
        
        if(isset($mobileEcole)){
            $ecole->setMobile($mobileEcole);
        }
        
        if(isset($descriptionEcole)){
            $ecole->setDescription($descriptionEcole);
        }
        
        if(!empty($newNameCin)){
            $ecole->setCin("uploads/".$idEcole."/ecole/".$newNameCin);
        }
        
        if(!empty($newNameRc)){
            $ecole->setRc("uploads/".$idEcole."/ecole/".$newNameRc);
        }
        
        if(!empty($newNameLogo)){
            $ecole->setLogo("uploads/".$idEcole."/ecole/".$newNameLogo);
            $ecole->setpathArchivedLogo("uploads/".$idEcole."/ecole/".$newNameLogo);
        }

        $em->persist($ecole);
        $em->persist($superAdmin);
        $em->flush();
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
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Admin');
        $admins = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Notes');
        $notes = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Telechargements');
        $telechargements = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Devoirs');
        $devoirs = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Assiduite');
        $assiduites = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:MenuCantine');
        $menuCantine = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Notifications');
        $notifications = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:NotificationsClasse');
        $notificationsClasse = count($repository->findBy(array('ecole' => $idEcole), array()));
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Ecole');
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        

        $listeInformationsPratiques = array('eleves'=> $eleves, 'parents'=> $parents, 'professeurs'=> $professeurs, 'emplois'=> $emplois, 'classes'=> $classes, 'admins'=> $admins, 'notes'=> $notes, 'telechargements'=> $telechargements, 'devoirs'=> $devoirs, 'assiduites'=> $assiduites, 'menuCantine'=> $menuCantine, 'notifications'=> $notifications+$notificationsClasse, 'analytics'=> $this->lireAnalytics($idEcole),'paths'=> $this->pathtoCinRcLogo($idEcole), 'logoEcole'=> $ecole->getLogo(), 'idEcole'=> $ecole->getId());
        
        return $listeInformationsPratiques;
    }
    
    
    
    public function lireAnalytics($idEcole)
    {
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Analytics');
        
        $analytics = $repository->findBy(array('ecole' => $idEcole), array());
        
        foreach ($analytics as $data)
        {
            if($data->getProfil()=='superadmin')
            {
                $superadmin = $data->getCompteur();
            }
            
            if($data->getProfil()=='admin')
            {
                $admin = $data->getCompteur();
            }
            
            if($data->getProfil()=='professeur')
            {
                $professeur = $data->getCompteur();
            }
            
            if($data->getProfil()=='parent')
            {
                $parent = $data->getCompteur();
            }
        }
        
        
        $listeFinaleDatas = Array(
            'superadmin' => $superadmin,
            'admin' => $admin,
            'professeur' => $professeur,
            'parent' => $parent
            );
        
        return $listeFinaleDatas;
        
    }
    
    public function totalAnalytics($idEcole)
    {
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Analytics');
        
        $analytics = $repository->findBy(array('ecole' => $idEcole), array());
        $total =0;$superadmin =0;$admin =0;$professeur =0;$parent =0;
        
        foreach ($analytics as $data)
        {
            if($data->getProfil()=='superadmin')
            {
                $superadmin = $data->getCompteur();
            }
            
            if($data->getProfil()=='admin')
            {
                $admin = $data->getCompteur();
            }
            
            if($data->getProfil()=='professeur')
            {
                $professeur = $data->getCompteur();
            }
            
            if($data->getProfil()=='parent')
            {
                $parent = $data->getCompteur();
            }
        }
        
        
        $total = $superadmin+$admin+$professeur+$parent;
        
        return $total;
        
    }
    
    public function pathtoCinRcLogo($idEcole)
    {
        
        $repository = $this->getDoctrine()->getRepository('PronoteBundle:Ecole');
        
        $ecole = $repository->findOneBy(array('id' => $idEcole), array());
        
        $listePaths = Array(
            'pathCin' => $ecole->getCin(),
            'pathRc' => $ecole->getRc(),
            'pathLogo' => $ecole->getLogo()
            );
        
        return $listePaths;
        
    }
    
    public function findSuperAdmin($idEcole, $profil)
    {
        $query = $this
        ->getDoctrine()
        ->getManager()
        ->createQuery
        ("  SELECT a.id as superAdmin
            FROM
            PronoteBundle:Admin a
            WHERE
            a.ecole =:idEcole
            AND
            a.profil =:profil
         ")
         ->setParameters(['idEcole' => $idEcole, 'profil' => $profil]);
         
         $resultat = $query->getResult();
         $idSuperAdmin = $resultat[0]["superAdmin"];
         
         //Retrieve le superAdmin
         $em = $this->getDoctrine()->getManager();
         $superAdmin = $em->getRepository('PronoteBundle:Admin')->findOneBy(array('id' => $idSuperAdmin));
         
         return $superAdmin;
    }
    
    //Ajout des CIN, RC, Logo 
    public function uploadFile($fileToAdd,$idEcole)
    {
        
        if(!empty($fileToAdd))
        {
            $filename = $fileToAdd["name"];
            $file_basename = substr($filename, 0, strripos($filename, '.')); // get file extention
            $file_ext = substr($filename, strripos($filename, '.')); // get file name
            $filesize = $fileToAdd["size"];
            $allowed_file_types = array('.doc','.docx','.jpg','.jpeg','.gif','.png','.pdf','.DOC','.DOCX','.JPG','.JPEG','.GIF','.PNG','.PDF');
            
            if (in_array($file_ext,$allowed_file_types) && ($filesize < 8388608 ))
            {
                // Rename file
                $newfilename = md5(uniqid()).$file_ext;
                
                if (file_exists("uploads/".$idEcole."/ecole/".$newfilename))
                {
                    // file already exists error
                    echo "You have already uploaded this file.";
                    
                }
                else
                {
                    move_uploaded_file($fileToAdd["tmp_name"], "uploads/".$idEcole."/ecole/".$newfilename);
                    echo "File uploaded successfully.";
                    //Si tout est bon, il retourne le nouveau nom du fichier
                    return $newfilename;
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
                unlink($fileToAdd["tmp_name"]);
                
            }
        }
    }
    
    
    
   
}
