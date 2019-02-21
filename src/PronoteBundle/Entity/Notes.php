<?php

namespace PronoteBundle\Entity;
use \Datetime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Notes
 *
 * @ORM\Table(name="notes")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\NotesRepository")
 */
class Notes
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
     * @var float
     *
     * @ORM\Column(name="note", type="float")
     */
    private $note;

    /**
     * @ORM\ManyToOne(targetEntity="Eleves", inversedBy="listeNotes")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $eleve;

    /**
     * @ORM\ManyToOne(targetEntity="Matieres", inversedBy="listeNotes")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $matiere;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateNote", type="datetime")
     */
    private $dateNote;
    
    
    /**
     * @ORM\ManyToOne(targetEntity="Professeurs", inversedBy="listeNotes")
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
     * Set note
     *
     * @param float $note
     *
     * @return Notes
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return float
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @return Eleves
     */
    public function getEleve()
    {
        return $this->eleve;
    }

    /**
     * @param Eleves $idEleve
     */
    public function setEleve($idEleve)
    {
        $this->eleve = $idEleve;
    }

    /**
     * @return Matieres
     */
    public function getMatiere()
    {
        return $this->matiere;
    }

    /**
     * @param Matieres $idMatiere
     */
    public function setIdMatiere($idMatiere)
    {
        $this->matiere = $idMatiere;
    }

    /**
     * @return datetime
     */
    public function getDateNote()
    {
        return $this->dateNote;
    }

    /**
     * @param datetime $dateNote
     *
     * @return Notes
     */
    public function setDateNote($dateNote)
    {
        $this->dateNote = $dateNote;

        return $this;
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

