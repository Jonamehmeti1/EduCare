<?php
require_once 'User.php';

class Teacher extends User {
    private $subject;
    private $status;

    public function __construct($id, $name, $email, $role, $subject, $status) {
        parent::__construct($id, $name, $email, $role); // Calling parent constructor
        $this->subject = $subject;
        $this->status = $status;
    }

    public function getSubject() { return $this->subject; }
    public function getStatus() { return $this->status; }
}
?>