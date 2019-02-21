<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Assiduite
 *
 * @ORM\Table(name="assiduite")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\AssiduiteRepository")
 */
class Assiduite
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
     * @ORM\Column(name="dateFaute", type="datetime")
     */
    private $dateFaute;

    /**
     * @var string
     *
     * @ORM\Column(name="detailsFautes", type="string", length=512)
     */
    private $detailsFautes;

    /**
     * @ORM\ManyToOne(targetEntity="Eleves", inversedBy="listeAssiduites")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $eleve;

    /**
     * @ORM\ManyToOne(targetEntity="Fautes")
     */
    private $typeFaute;

    /**
     * @ORM\ManyToOne(targetEntity="Professeurs", inversedBy="listeAssiduites")
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
     * Set dateFaute
     *
     * @param \DateTime $dateFaute
     *
     * @return Assiduite
     */
    public function setDateFaute($dateFaute)
    {
        $this->dateFaute = $dateFaute;

        return $this;
    }

    /**
     * Get dateFaute
     *
     * @return \DateTime
     */
    public function getDateFaute()
    {
        return $this->dateFaute;
    }

    /**
     * Set detailsFautes
     *
     * @param string $detailsFautes
     *
     * @return Assiduite
     */
    public function setDetailsFautes($detailsFautes)
    {
        $this->detailsFautes = $detailsFautes;

        return $this;
    }

    /**
     * Get detailsFautes
     *
     * @return string
     */
    public function getDetailsFautes()
    {
        return $this->detailsFautes;
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
     * @return Fautes
     */
    public function getTypeFaute()
    {
        return $this->typeFaute;
    }

    /**
     * @param Fautes $typeFaute
     */
    public function setTypeFaute($typeFaute)
    {
        $this->typeFaute = $typeFaute;
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

