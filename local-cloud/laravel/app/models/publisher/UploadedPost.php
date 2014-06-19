<?php namespace Publisher;

use
    DomainException,
    InvalidArgumentException,
    PDODatabaseModel,
    URL;

/**
 * Class UploadedPin
 *
 * @package Publisher
 */
class UploadedPost extends PDODatabaseModel
{
    const STATUS_CREATED  = 'C';
    const STATUS_SENT     = 'S';
    const STATUS_QUEUED   = 'Q';
    const STATUS_ARCHIVED = 'A';
    const STATUS_DELETED  = 'D';
    const TYPE_LOCAL      = 'local';
    const TYPE_RACKSPACE  = 'rackspace';
    public
        $table = 'publisher_uploaded_posts',
        $columns = array(
        'id',
        'account_id',
        'type',
        'location',
        'status',
        'added_at',
        'updated_at'
    ),
        $primary_keys = array('id');
    public
        /**
         * @var int Auto-Incrementing id
         */
        $id,
        /**
         * @var int UserAccount id
         */
        $account_id,
        /**
         * Where this is stored (rackspace?)
         *
         * @var string
         */
        $type,
        /**
         * The location in rackspace or on our server where the file lives
         *
         * @var string
         */
        $location,
        /**
         * The status of the image if it has been sent / queued or ready to
         * be deleted from our servers
         *
         * @var string
         */
        $status,
        /**
         * The epoch time when we added this image
         *
         * @var int
         */
        $added_at,
        /**
         * The last epoch time this row was modified
         *
         * @var int
         */
        $updated_at;

    /**
     * @author  Will
     */
    public static function create(
        \UserAccount $user_account,
        $location,
        $type = self::TYPE_LOCAL)
    {
        if ($type != self::TYPE_LOCAL AND $type != self::TYPE_RACKSPACE) {
            throw new InvalidArgumentException();
        }

        $post             = new self;
        $post->account_id = $user_account->account_id;
        $post->location   = $location;
        $post->type       = $type;
        $post->status     = self::STATUS_CREATED;
        $post->added_at   = time();
        $post->updated_at = time();

        $post->saveAsNew();

        return $post;
    }

    /**
     * Save the last insert ID when saving as new
     *
     * @author  Will
     *
     * @return $this
     */
    public function saveAsNew()
    {
        parent::saveAsNew();
        $this->id = $this->DBH->lastInsertId();

        return $this;
    }

    /**
     * @author  Will
     */
    public function getUrl()
    {

        switch ($this->type) {
            default:
                throw new DomainException(
                    'The type ' . $this->type . ' is not supported'
                );

                break;
            case self::TYPE_LOCAL:

                return URL::route(
                          'publisher-public-uploaded-post',
                          array('uploaded_pin_id' => $this->id)
                );
                break;

            case self::TYPE_RACKSPACE:
                return $this->location;
                break;
        }

    }

}
