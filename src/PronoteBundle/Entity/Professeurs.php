<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Professeurs
 *
 * @ORM\Table(name="professeurs")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\ProfesseursRepository")
 */
class Professeurs
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
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="login", type="string", length=255, unique = true)
     */
    private $login;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     */
    private $password;
    
    /**
     * @ORM\OneToMany(targetEntity="Assiduite" , mappedBy="professeur")
     */
    private $listeAssiduites;
    
    /**
     * @ORM\OneToMany(targetEntity="Notes" , mappedBy="professeur")
     */
    private $listeNotes;
    
    /**
     * @ORM\OneToMany(targetEntity="Devoirs" , mappedBy="professeur")
     */
    private $listeDevoirs;
    
    /**
     * @ORM\OneToMany(targetEntity="Telechargements" , mappedBy="professeur")
     */
    private $listeTelechargements;
    
    /**
     * @ORM\OneToMany(targetEntity="Bulletin" , mappedBy="professeur")
     */
    private $listeBulletins;
    
    /**
     * @ORM\OneToMany(targetEntity="ActivitesScolaires" , mappedBy="professeur")
     */
    private $listeActivitesScolaires;
    
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
     * Set nom
     *
     * @param string $nom
     *
     * @return Professeurs
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set login
     *
     * @param string $login
     *
     * @return Professeurs
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Professeurs
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
    

    
}

