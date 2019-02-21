<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use \Datetime;

/**
 * Bulletin
 *
 * @ORM\Table(name="bulletin")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\BulletinRepository")
 */
class Bulletin
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
     * @ORM\Column(name="description", type="string", length=512)
     */
    private $description;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank(message="Merci de choisir un fichier (PDF, Word, jpeg, gif, png)")
     * @Assert\File(mimeTypes={ "application/pdf"  , "application/msword" , "application/jpeg", "application/jpg", "application/gif", "application/png"})
     */
    private $file;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateBulletin", type="datetime")
     */
    private $dateBulletin;

    /**
     * @ORM\ManyToOne(targetEntity="Eleves", inversedBy="listeBulletins")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $eleve;
    
    /**
     * @ORM\ManyToOne(targetEntity="Professeurs", inversedBy="listeBulletins")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $professeur;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Ecole")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $ecole;
    
    /**************Getters et Setters Ecole (ManyToOne)*****************/
    /**
     * @return Ecole
     */
    public function getEcole()
    {
        return $this->ecole;
    }
    
    /**
     * @param Ecole $ecole
     */
    public function setEcole(Ecole $ecole)
    {
        $this->ecole = $ecole;
        
    }

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
     * Set description
     *
     * @param string $description
     *
     * @return MenuCantine
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


    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }


    public function getFile()
    {
        return $this->file;
    }
    
    /**
     * @return datetime
     */
    public function getDateBulletin()
    {
        return $this->dateBulletin;
    }
    
    /**
     * @param datetime $dateBulletin
     *
     * @return Bulletin
     */
    public function setDateBulletin($dateBulletin)
    {
        $this->dateBulletin = $dateBulletin;
        
        return $this;
    }

    /**
     * @return Eleves
     */
    public function getEleve()
    {
        return $this->eleve;
    }

    /**
     * @param Eleves $eleve
     */
    public function setEleve($eleve)
    {
        $this->eleve = $eleve;
    }
    
    /**
     * @return Professeurs
     */
    public function getProfesseur()
    {
        return $this->professeur;
    }
    
    /**
     * @param Professeurs $idProfesseur
     */
    public function setProfesseur($idProfesseur)
    {
        $this->professeur = $idProfesseur;
    }
    
    
}

