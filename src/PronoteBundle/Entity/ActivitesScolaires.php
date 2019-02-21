<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActivitesScolaires
 *
 * @ORM\Table(name="activites_scolaires")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\ActivitesScolairesRepository")
 */
class ActivitesScolaires
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
     * @var \DateTime
     *
     * @ORM\Column(name="dateActivite", type="datetime")
     */
    private $dateActivite;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=512)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="Classe", inversedBy="listeActivitesScolaires")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $classe;

    /**
     * @ORM\ManyToOne(targetEntity="Professeurs", inversedBy="listeActivitesScolaires")
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
     * Set dateActivite
     *
     * @param \DateTime $dateActivite
     *
     * @return ActivitesScolaires
     */
    public function setDateActivite($dateActivite)
    {
        $this->dateActivite = $dateActivite;

        return $this;
    }

    /**
     * Get dateActivite
     *
     * @return \DateTime
     */
    public function getDateActivite()
    {
        return $this->dateActivite;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return ActivitesScolaires
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

