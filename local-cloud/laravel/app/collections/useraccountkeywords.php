<?php

class UserAccountKeywords extends DBCollection
{
    public $columns = array(
        'account_id',
        'keyword'
    ), $table = 'user_accounts_keywords';
}

class UserAccountKeywordsException extends CollectionException {}
