<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ecole
 *
 * @ORM\Table(name="ecole")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\EcoleRepository")
 */
class Ecole
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nomEcole", type="string", length=255)
     */
    private $nomEcole;

    /**
     * @var string
     *
     * @ORM\Column(name="responsableEcole", type="string", length=255)
     */
    private $responsableEcole;
    
    /**
     * @var string
     *
     * @ORM\Column(name="loginAdminEcole", type="string", length=255)
     */
    private $loginAdminEcole;
    
    /**
     * @var string
     *
     * @ORM\Column(name="passwordAdminEcole", type="string", length=255)
     */
    private $passwordAdminEcole;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse", type="string", length=255)
     */
    private $adresse;

    /**
     * @var string
     *
     * @ORM\Column(name="site", type="string", length=255)
     */
    private $site;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="fixe", type="string", length=255)
     */
    private $fixe;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255)
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=512)
     */
    private $description;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(message="Merci de choisir votre Logo (jpeg, gif, png)")
     * @Assert\File(mimeTypes={ "application/pdf"  , "application/msword" , "application/jpeg", "application/jpg", "application/gif", "application/png"})
     */
    private $logo;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     *
     */
    private $pathArchivedLogo;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(message="Merci de choisir votre CIN (PDF, Word, jpeg, gif, png)")
     * @Assert\File(mimeTypes={ "application/pdf"  , "application/msword" , "application/jpeg", "application/jpg", "application/gif", "application/png"})
     */
    private $cin;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(message="Merci de choisir votre RC (PDF, Word, jpeg, gif, png)")
     * @Assert\File(mimeTypes={ "application/pdf"  , "application/msword" , "application/jpeg", "application/jpg", "application/gif", "application/png"})
     */
    private $rc;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set nomEcole
     *
     * @param string $nomEcole
     *
     * @return Ecole
     */
    public function setNomEcole($nomEcole)
    {
        $this->nomEcole = $nomEcole;

        return $this;
    }

    /**
     * Get nomEcole
     *
     * @return string
     */
    public function getNomEcole()
    {
        return $this->nomEcole;
    }

    /**
     * Set responsableEcole
     *
     * @param string $responsableEcole
     *
     * @return Ecole
     */
    public function setResponsableEcole($responsableEcole)
    {
        $this->responsableEcole = $responsableEcole;

        return $this;
    }

    /**
     * Get responsableEcole
     *
     * @return string
     */
    public function getResponsableEcole()
    {
        return $this->responsableEcole;
    }
    
    /**
     * Set loginAdminEcole
     *
     * @param string $loginAdminEcole
     *
     * @return Ecole
     */
    public function setloginAdminEcole($loginAdminEcole)
    {
        $this->loginAdminEcole = $loginAdminEcole;
        
        return $this;
    }
    
    /**
     * Get loginAdminEcole
     *
     * @return string
     */
    public function getloginAdminEcole()
    {
        return $this->loginAdminEcole;
    }
    
    /**
     * Set passwordAdminEcole
     *
     * @param string $passwordAdminEcole
     *
     * @return Ecole
     */
    public function setpasswordAdminEcole($passwordAdminEcole)
    {
        $this->passwordAdminEcole = $passwordAdminEcole;
        
        return $this;
    }
    
    /**
     * Get passwordAdminEcole
     *
     * @return string
     */
    public function getpasswordAdminEcole()
    {
        return $this->passwordAdminEcole;
    }

    /**
     * Set adresse
     *
     * @param string $adresse
     *
     * @return Ecole
     */
    public function setAdresse($adresse)
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * Get adresse
     *
     * @return string
     */
    public function getAdresse()
    {
        return $this->adresse;
    }

    /**
     * Set site
     *
     * @param string $site
     *
     * @return Ecole
     */
    public function setSite($site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Get site
     *
     * @return string
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Ecole
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set fixe
     *
     * @param string $fixe
     *
     * @return Ecole
     */
    public function setFixe($fixe)
    {
        $this->fixe = $fixe;

        return $this;
    }

    /**
     * Get fixe
     *
     * @return string
     */
    public function getFixe()
    {
        return $this->fixe;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return Ecole
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Ecole
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    
    public function setLogo($logo)
    {
        $this->logo = $logo;
        
        return $this;
    }
    
    
    public function getLogo()
    {
        return $this->logo;
    }
    
    
    public function setCin($cin)
    {
        $this->cin = $cin;
        
        return $this;
    }
    
    
    public function getCin()
    {
        return $this->cin;
    }
    
    public function setRc($rc)
    {
        $this->rc = $rc;
        
        return $this;
    }
    
    
    public function getRc()
    {
        return $this->rc;
    }
    
    //Backup du Logo 
    public function setPathArchivedLogo($pathArchivedLogo)
    {
        $this->pathArchivedLogo = $pathArchivedLogo;
        
        return $this;
    }
       
    public function getPathArchivedLogo()
    {
        return $this->pathArchivedLogo;
    }
}

