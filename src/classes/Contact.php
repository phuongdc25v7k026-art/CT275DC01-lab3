<?php

namespace CT275\Labs;

use PDO;

class Contact
{
  private ?PDO $db;

  public int $id = -1;
  public $name;
  public $phone;
  public $notes;
  public $created_at;
  public $updated_at;

  public function __construct(?PDO $pdo)
  {
    $this->db = $pdo;
  }

  public function fill(array $data): Contact
  {
    $this->name = $data['name'] ?? '';
    $this->phone = $data['phone'] ?? '';
    $this->notes = $data['notes'] ?? '';
    return $this;
  }

  public function validate(array $data): array
  {
    $errors = [];

    $name = trim($data['name'] ?? '');
    if (!$name) {
      $errors['name'] = 'Invalid name.';
    }

    $validPhone = preg_match(
      '/^(03|05|07|08|09|01[2|6|8|9])+([0-9]{8})\b$/',
      $data['phone'] ?? ''
    );
    if (!$validPhone) {
      $errors['phone'] = 'Invalid phone number.';
    }

    $notes = trim($data['notes'] ?? '');
    if (strlen($notes) > 255) {
      $errors['notes'] = 'Notes must be at most 255 characters.';
    }

    return $errors;
  }
}
