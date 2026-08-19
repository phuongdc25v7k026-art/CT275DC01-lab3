<?php

require_once __DIR__ . '/../src/bootstrap.php';

use CT275\Labs\Contact;

$contact = new Contact($PDO);

if (
  $_SERVER['REQUEST_METHOD'] === 'POST'
  && isset($_POST['id'])
  && ($contact->find($_POST['id'])) !== null
) {
  $contact->delete();
}

redirect('/');