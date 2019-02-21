<?php

namespace PronoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Emploi
 *
 * @ORM\Table(name="emploi")
 * @ORM\Entity(repositoryClass="PronoteBundle\Repository\EmploiRepository")
 */
class Emploi
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
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_1_1;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_1_2;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_1_3;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_1_4;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_1_5;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_1_6;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_1_7;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_1_8;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_1_9;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_1_10;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_2_1;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_2_2;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_2_3;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_2_4;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_2_5;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_2_6;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_2_7;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_2_8;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_2_9;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_2_10;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_3_1;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_3_2;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_3_3;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_3_4;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_3_5;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_3_6;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_3_7;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_3_8;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_3_9;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_3_10;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_4_1;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_4_2;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_4_3;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_4_4;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_4_5;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_4_6;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_4_7;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_4_8;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_4_9;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_4_10;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_5_1;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_5_2;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_5_3;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_5_4;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_5_5;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_5_6;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_5_7;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_5_8;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_5_9;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $seance_5_10;

    /**
     * @ORM\ManyToOne(targetEntity="Classe")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     */
    private $classe;

    /**
     * @ORM\ManyToOne(targetEntity="Salles")
     * @ORM\JoinColumn(nullable=true)
     */
    private $salle;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $designation;
    
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
     * @return string
     */
    public function getDesignation()
    {
        return $this->designation;
    }

    /**
     * @param string $designation
     */
    public function setDesignation(string $designation)
    {
        $this->designation = $designation;
    }

    /**
     * @return Salles
     */
    public function getSalle()
    {
        return $this->salle;
    }

    /**
     * @param Salles $salle
     * @return Salles;
     */
    public function setSalle(Salles $salle)
    {
        $this->salle = $salle;
        return $this;
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
     * @return Classe
     */
    public function setClasse(Classe $classe)
    {
        $this->classe = $classe;
        return $this;
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
     * @return integer
     */
    public function getSeance_1_1()
    {
        return $this->seance_1_1;
    }

    /**
     * @param int $seance_1_1
     */
    public function setSeance_1_1(int $seance_1_1)
    {
        $this->seance_1_1 = $seance_1_1;
    }

    /**
     * @return integer
     */
    public function getSeance_1_2()
    {
        return $this->seance_1_2;
    }

    /**
     * @param int $seance_1_2
     */
    public function setSeance_1_2(int $seance_1_2)
    {
        $this->seance_1_2 = $seance_1_2;
    }

    /**
     * @return integer
     */
    public function getSeance_1_3()
    {
        return $this->seance_1_3;
    }

    /**
     * @param int $seance_1_3
     */
    public function setSeance_1_3(int $seance_1_3)
    {
        $this->seance_1_3 = $seance_1_3;
    }

    /**
     * @return integer
     */
    public function getSeance_1_4()
    {
        return $this->seance_1_4;
    }

    /**
     * @param int $seance_1_4
     */
    public function setSeance_1_4(int $seance_1_4)
    {
        $this->seance_1_4 = $seance_1_4;
    }

    /**
     * @return integer
     */
    public function getSeance_1_5()
    {
        return $this->seance_1_5;
    }

    /**
     * @param int $seance_1_5
     */
    public function setSeance_1_5(int $seance_1_5)
    {
        $this->seance_1_5 = $seance_1_5;
    }

    /**
     * @return integer
     */
    public function getSeance_1_6()
    {
        return $this->seance_1_6;
    }

    /**
     * @param int $seance_1_6
     */
    public function setSeance_1_6(int $seance_1_6)
    {
        $this->seance_1_6 = $seance_1_6;
    }

    /**
     * @return integer
     */
    public function getSeance_1_7()
    {
        return $this->seance_1_7;
    }

    /**
     * @param int $seance_1_7
     */
    public function setSeance_1_7(int $seance_1_7)
    {
        $this->seance_1_7 = $seance_1_7;
    }

    /**
     * @return integer
     */
    public function getSeance_1_8()
    {
        return $this->seance_1_8;
    }

    /**
     * @param int $seance_1_8
     */
    public function setSeance_1_8(int $seance_1_8)
    {
        $this->seance_1_8 = $seance_1_8;
    }

    /**
     * @return integer
     */
    public function getSeance_1_9()
    {
        return $this->seance_1_9;
    }

    /**
     * @param int $seance_1_9
     */
    public function setSeance_1_9(int $seance_1_9)
    {
        $this->seance_1_9 = $seance_1_9;
    }

    /**
     * @return integer
     */
    public function getSeance_1_10()
    {
        return $this->seance_1_10;
    }

    /**
     * @param int $seance_1_10
     */
    public function setSeance_1_10(int $seance_1_10)
    {
        $this->seance_1_10 = $seance_1_10;
    }

    /**
     * @return integer
     */
    public function getSeance_2_1()
    {
        return $this->seance_2_1;
    }

    /**
     * @param int $seance_2_1
     */
    public function setSeance_2_1(int $seance_2_1)
    {
        $this->seance_2_1 = $seance_2_1;
    }

    /**
     * @return integer
     */
    public function getSeance_2_2()
    {
        return $this->seance_2_2;
    }

    /**
     * @param int $seance_2_2
     */
    public function setSeance_2_2(int $seance_2_2)
    {
        $this->seance_2_2 = $seance_2_2;
    }

    /**
     * @return integer
     */
    public function getSeance_2_3()
    {
        return $this->seance_2_3;
    }

    /**
     * @param int $seance_2_3
     */
    public function setSeance_2_3(int $seance_2_3)
    {
        $this->seance_2_3 = $seance_2_3;
    }

    /**
     * @return integer
     */
    public function getSeance_2_4()
    {
        return $this->seance_2_4;
    }

    /**
     * @param int $seance_2_4
     */
    public function setSeance_2_4(int $seance_2_4)
    {
        $this->seance_2_4 = $seance_2_4;
    }

    /**
     * @return integer
     */
    public function getSeance_2_5()
    {
        return $this->seance_2_5;
    }

    /**
     * @param int $seance_2_5
     */
    public function setSeance_2_5(int $seance_2_5)
    {
        $this->seance_2_5 = $seance_2_5;
    }

    /**
     * @return integer
     */
    public function getSeance_2_6()
    {
        return $this->seance_2_6;
    }

    /**
     * @param int $seance_2_6
     */
    public function setSeance_2_6(int $seance_2_6)
    {
        $this->seance_2_6 = $seance_2_6;
    }

    /**
     * @return integer
     */
    public function getSeance_2_7()
    {
        return $this->seance_2_7;
    }

    /**
     * @param int $seance_2_7
     */
    public function setSeance_2_7(int $seance_2_7)
    {
        $this->seance_2_7 = $seance_2_7;
    }

    /**
     * @return integer
     */
    public function getSeance_2_8()
    {
        return $this->seance_2_8;
    }

    /**
     * @param int $seance_2_8
     */
    public function setSeance_2_8(int $seance_2_8)
    {
        $this->seance_2_8 = $seance_2_8;
    }

    /**
     * @return integer
     */
    public function getSeance_2_9()
    {
        return $this->seance_2_9;
    }

    /**
     * @param int $seance_2_9
     */
    public function setSeance_2_9(int $seance_2_9)
    {
        $this->seance_2_9 = $seance_2_9;
    }

    /**
     * @return integer
     */
    public function getSeance_2_10()
    {
        return $this->seance_2_10;
    }

    /**
     * @param int $seance_2_10
     */
    public function setSeance_2_10(int $seance_2_10)
    {
        $this->seance_2_10 = $seance_2_10;
    }

    /**
     * @return integer
     */
    public function getSeance_3_1()
    {
        return $this->seance_3_1;
    }

    /**
     * @param int $seance_3_1
     */
    public function setSeance_3_1(int $seance_3_1)
    {
        $this->seance_3_1 = $seance_3_1;
    }

    /**
     * @return integer
     */
    public function getSeance_3_2()
    {
        return $this->seance_3_2;
    }

    /**
     * @param int $seance_3_2
     */
    public function setSeance_3_2(int $seance_3_2)
    {
        $this->seance_3_2 = $seance_3_2;
    }

    /**
     * @return integer
     */
    public function getSeance_3_3()
    {
        return $this->seance_3_3;
    }

    /**
     * @param int $seance_3_3
     */
    public function setSeance_3_3(int $seance_3_3)
    {
        $this->seance_3_3 = $seance_3_3;
    }

    /**
     * @return integer
     */
    public function getSeance_3_4()
    {
        return $this->seance_3_4;
    }

    /**
     * @param int $seance_3_4
     */
    public function setSeance_3_4(int $seance_3_4)
    {
        $this->seance_3_4 = $seance_3_4;
    }

    /**
     * @return integer
     */
    public function getSeance_3_5()
    {
        return $this->seance_3_5;
    }

    /**
     * @param int $seance_3_5
     */
    public function setSeance_3_5(int $seance_3_5)
    {
        $this->seance_3_5 = $seance_3_5;
    }

    /**
     * @return integer
     */
    public function getSeance_3_6()
    {
        return $this->seance_3_6;
    }

    /**
     * @param int $seance_3_6
     */
    public function setSeance_3_6(int $seance_3_6)
    {
        $this->seance_3_6 = $seance_3_6;
    }

    /**
     * @return integer
     */
    public function getSeance_3_7()
    {
        return $this->seance_3_7;
    }

    /**
     * @param int $seance_3_7
     */
    public function setSeance_3_7(int $seance_3_7)
    {
        $this->seance_3_7 = $seance_3_7;
    }

    /**
     * @return integer
     */
    public function getSeance_3_8()
    {
        return $this->seance_3_8;
    }

    /**
     * @param int $seance_3_8
     */
    public function setSeance_3_8(int $seance_3_8)
    {
        $this->seance_3_8 = $seance_3_8;
    }

    /**
     * @return integer
     */
    public function getSeance_3_9()
    {
        return $this->seance_3_9;
    }

    /**
     * @param int $seance_3_9
     */
    public function setSeance_3_9(int $seance_3_9)
    {
        $this->seance_3_9 = $seance_3_9;
    }

    /**
     * @return integer
     */
    public function getSeance_3_10()
    {
        return $this->seance_3_10;
    }

    /**
     * @param int $seance_3_10
     */
    public function setSeance_3_10(int $seance_3_10)
    {
        $this->seance_3_10 = $seance_3_10;
    }

    /**
     * @return integer
     */
    public function getSeance_4_1()
    {
        return $this->seance_4_1;
    }

    /**
     * @param int $seance_4_1
     */
    public function setSeance_4_1(int $seance_4_1)
    {
        $this->seance_4_1 = $seance_4_1;
    }

    /**
     * @return integer
     */
    public function getSeance_4_2()
    {
        return $this->seance_4_2;
    }

    /**
     * @param int $seance_4_2
     */
    public function setSeance_4_2(int $seance_4_2)
    {
        $this->seance_4_2 = $seance_4_2;
    }

    /**
     * @return integer
     */
    public function getSeance_4_3()
    {
        return $this->seance_4_3;
    }

    /**
     * @param int $seance_4_3
     */
    public function setSeance_4_3(int $seance_4_3)
    {
        $this->seance_4_3 = $seance_4_3;
    }

    /**
     * @return integer
     */
    public function getSeance_4_4()
    {
        return $this->seance_4_4;
    }

    /**
     * @param int $seance_4_4
     */
    public function setSeance_4_4(int $seance_4_4)
    {
        $this->seance_4_4 = $seance_4_4;
    }

    /**
     * @return integer
     */
    public function getSeance_4_5()
    {
        return $this->seance_4_5;
    }

    /**
     * @param int $seance_4_5
     */
    public function setSeance_4_5(int $seance_4_5)
    {
        $this->seance_4_5 = $seance_4_5;
    }

    /**
     * @return integer
     */
    public function getSeance_4_6()
    {
        return $this->seance_4_6;
    }

    /**
     * @param int $seance_4_6
     */
    public function setSeance_4_6(int $seance_4_6)
    {
        $this->seance_4_6 = $seance_4_6;
    }

    /**
     * @return integer
     */
    public function getSeance_4_7()
    {
        return $this->seance_4_7;
    }

    /**
     * @param int $seance_4_7
     */
    public function setSeance_4_7(int $seance_4_7)
    {
        $this->seance_4_7 = $seance_4_7;
    }

    /**
     * @return integer
     */
    public function getSeance_4_8()
    {
        return $this->seance_4_8;
    }

    /**
     * @param int $seance_4_8
     */
    public function setSeance_4_8(int $seance_4_8)
    {
        $this->seance_4_8 = $seance_4_8;
    }

    /**
     * @return integer
     */
    public function getSeance_4_9()
    {
        return $this->seance_4_9;
    }

    /**
     * @param int $seance_4_9
     */
    public function setSeance_4_9(int $seance_4_9)
    {
        $this->seance_4_9 = $seance_4_9;
    }

    /**
     * @return integer
     */
    public function getSeance_4_10()
    {
        return $this->seance_4_10;
    }

    /**
     * @param int $seance_4_10
     */
    public function setSeance_4_10(int $seance_4_10)
    {
        $this->seance_4_10 = $seance_4_10;
    }

    /**
     * @return integer
     */
    public function getSeance_5_1()
    {
        return $this->seance_5_1;
    }

    /**
     * @param int $seance_5_1
     */
    public function setSeance_5_1(int $seance_5_1)
    {
        $this->seance_5_1 = $seance_5_1;
    }

    /**
     * @return integer
     */
    public function getSeance_5_2()
    {
        return $this->seance_5_2;
    }

    /**
     * @param int $seance_5_2
     */
    public function setSeance_5_2(int $seance_5_2)
    {
        $this->seance_5_2 = $seance_5_2;
    }

    /**
     * @return integer
     */
    public function getSeance_5_3()
    {
        return $this->seance_5_3;
    }

    /**
     * @param int $seance_5_3
     */
    public function setSeance_5_3(int $seance_5_3)
    {
        $this->seance_5_3 = $seance_5_3;
    }

    /**
     * @return integer
     */
    public function getSeance_5_4()
    {
        return $this->seance_5_4;
    }

    /**
     * @param int $seance_5_4
     */
    public function setSeance_5_4(int $seance_5_4)
    {
        $this->seance_5_4 = $seance_5_4;
    }

    /**
     * @return integer
     */
    public function getSeance_5_5()
    {
        return $this->seance_5_5;
    }

    /**
     * @param int $seance_5_5
     */
    public function setSeance_5_5(int $seance_5_5)
    {
        $this->seance_5_5 = $seance_5_5;
    }

    /**
     * @return integer
     */
    public function getSeance_5_6()
    {
        return $this->seance_5_6;
    }

    /**
     * @param int $seance_5_6
     */
    public function setSeance_5_6(int $seance_5_6)
    {
        $this->seance_5_6 = $seance_5_6;
    }

    /**
     * @return integer
     */
    public function getSeance_5_7()
    {
        return $this->seance_5_7;
    }

    /**
     * @param int $seance_5_7
     */
    public function setSeance_5_7(int $seance_5_7)
    {
        $this->seance_5_7 = $seance_5_7;
    }

    /**
     * @return integer
     */
    public function getSeance_5_8()
    {
        return $this->seance_5_8;
    }

    /**
     * @param int $seance_5_8
     */
    public function setSeance_5_8(int $seance_5_8)
    {
        $this->seance_5_8 = $seance_5_8;
    }

    /**
     * @return integer
     */
    public function getSeance_5_9()
    {
        return $this->seance_5_9;
    }

    /**
     * @param int $seance_5_9
     */
    public function setSeance_5_9(int $seance_5_9)
    {
        $this->seance_5_9 = $seance_5_9;
    }

    /**
     * @return integer
     */
    public function getSeance_5_10()
    {
        return $this->seance_5_10;
    }

    /**
     * @param int $seance_5_10
     */
    public function setSeance_5_10(int $seance_5_10)
    {
        $this->seance_5_10 = $seance_5_10;
    }


}

