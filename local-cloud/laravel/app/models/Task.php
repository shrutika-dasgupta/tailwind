<?php

/**
 * Class Task
 *
 * An action that needs to be done
 */
class Task extends Model
{
    /**
     * @const type - What kind of task is this related to?
     */
    const
        TYPE_PROFILE           = 'profile',
        TYPE_USER              = 'user',
        TYPE_USER_ACCOUNT      = 'user_account',
        TYPE_BOARD_PINS        = 'board_pins',
        TYPE_BOARD_CATEGORIES  = 'board_categories',
        TYPE_BOARD_DESCRIPTION = 'board_descriptions',
        TYPE_ORGANIZATION      = 'organization';
    /**
     * The name of the task
     *
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $type;
    /**
     * Depending on the type, this is how we know which account it relates to
     *
     * @var string | int
     */
    protected $identifier;
    /**
     * The weight of each task in terms of importances
     *
     * @var float
     */
    protected $weight = 1;
    /**
     * If a task has multiple things to do before its complete, it'd be nice to
     * know it's percentage complete
     *
     * @var int
     */
    protected $percent_complete = 0;
    /**
     * The order or priority these tasks should be done
     *
     * @var int
     */
    protected $priority = 1;

    /**
     * @author  Will
     *
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setType($type)
    {
        switch ($type) {
            default:
                throw new InvalidArgumentException(
                    "Can't set task type to $type, sorry."
                );
                break;

            case self::TYPE_ORGANIZATION:
            case self::TYPE_PROFILE:
            case self::TYPE_USER:
            case self::TYPE_USER_ACCOUNT:
            case self::TYPE_BOARD_PINS:
            case self::TYPE_BOARD_CATEGORIES:
            case self::TYPE_BOARD_DESCRIPTION:
                $this->type = $type;
                break;
        }

        return $this;
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function setIdentifier($value)
    {
        $this->identifier = $value;

        return $this;
    }

    /**
     * @return int|string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return $this
     */
    public function setComplete()
    {
        $this->percent_complete = 100;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCompleted()
    {
        if ($this->percent_complete === 100) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return !$this->isCompleted();
    }

    /**
     * @param $priority
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setPriority($priority)
    {
        if (!is_numeric($priority)) {
            throw new InvalidArgumentException("
            Priority needs to be numeric
            ");
        }

        $this->priority = $priority;

        return $this;
    }

    /**
     * @author  Will
     * @return string
     */
    public function getKey()
    {
        $identifier = $this->getIdentifier();

        if (!is_null($identifier)) {

            if ($identifier instanceof Profile) {
                return $this->name . '-' . $identifier->user_id;
            }
            if ($identifier instanceof UserAccount) {
                return $this->name . '-' . $identifier->account_id;
            }
            if ($identifier instanceof Board) {
                return $this->name . '-' . $identifier->board_id;
            }

        }

        return $this->name;
    }
}

/**
 * Class TaskException
 */
class TaskException extends Exception {}
