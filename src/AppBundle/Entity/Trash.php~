<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trash
 *
 * @ORM\Table(name="trash")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TrashRepository")
 */
class Trash
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
     * @ORM\Column(name="todo_name", type="string", length=255)
     */
    private $todoName;

    /**
     * @var string
     *
     * @ORM\Column(name="todo_description", type="string", length=255)
     */
    private $todoDescription;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="todo_due_date", type="datetime")
     */
    private $todoDueDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="todo_create_date", type="datetime")
     */
    private $todoCreateDate;

    /**
     * @var string
     *
     * @ORM\Column(name="todo_priority", type="string", length=255)
     */
    private $todoPriority;

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
     * Set todoName
     *
     * @param string $todoName
     *
     * @return Trash
     */
    public function setTodoName($todoName)
    {
        $this->todoName = $todoName;

        return $this;
    }

    /**
     * Get todoName
     *
     * @return string
     */
    public function getTodoName()
    {
        return $this->todoName;
    }

    /**
     * Set todoDescription
     *
     * @param string $todoDescription
     *
     * @return Trash
     */
    public function setTodoDescription($todoDescription)
    {
        $this->todoDescription = $todoDescription;

        return $this;
    }

    /**
     * Get todoDescription
     *
     * @return string
     */
    public function getTodoDescription()
    {
        return $this->todoDescription;
    }

    /**
     * Set todoDueDate
     *
     * @param \DateTime $todoDueDate
     *
     * @return Trash
     */
    public function setTodoDueDate($todoDueDate)
    {
        $this->todoDueDate = $todoDueDate;

        return $this;
    }

    /**
     * Get todoDueDate
     *
     * @return \DateTime
     */
    public function getTodoDueDate()
    {
        return $this->todoDueDate;
    }

    /**
     * Set todoCreateDate
     *
     * @param \DateTime $todoCreateDate
     *
     * @return Trash
     */
    public function setTodoCreateDate($todoCreateDate)
    {
        $this->todoCreateDate = $todoCreateDate;

        return $this;
    }

    /**
     * Get todoCreateDate
     *
     * @return \DateTime
     */
    public function getTodoCreateDate()
    {
        return $this->todoCreateDate;
    }

    /**
     * Set todoPriority
     *
     * @param string $todoPriority
     *
     * @return Trash
     */
    public function setTodoPriority($todoPriority)
    {
        $this->todoPriority = $todoPriority;

        return $this;
    }

    /**
     * Get todoPriority
     *
     * @return string
     */
    public function getTodoPriority()
    {
        return $this->todoPriority;
    }
}
