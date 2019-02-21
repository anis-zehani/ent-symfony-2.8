<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Telechargements
 *
 * @ORM\Table(name="telechargements")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\TelechargementsRepository")
 */
class Telechargements
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
     * @ORM\ManyToOne(targetEntity="Classe", inversedBy="listeTelechargements")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $classe;
    
    /**
     * @ORM\ManyToOne(targetEntity="Professeurs", inversedBy="listeTelechargements")
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
     * @return Telechargements
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
     * @return Classe
     */
    public function getClasse()
    {
        return $this->classe;
    }

    /**
     * @param Classe $classe
     */
    public function setClasse($classe)
    {
        $this->classe = $classe;
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

