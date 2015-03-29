<?php
class Message {
    const UNSENT = 0;
    const SENT = 1;
    const READ = 2;
    
    public $id;
    public $from_user_id;
    public $to_user_id;
    public $from;
    public $from_email;
    public $to;
    public $to_email;
    public $date;
    public $title;
    public $message;
    public $status;
    public $regid;
    public $from_avatar;
    public $to_avatar;
    public $inbox;
    public $outbox;
}