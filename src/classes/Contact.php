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
      '/^(03|05|07|08|09)+([0-9]{8})\b$/',
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

  public function find(int $id): ?Contact
  {
    $statement = $this->db->prepare('select * from contacts where id = :id');
    $statement->execute(['id' => $id]);

    if ($row = $statement->fetch()) {
      $this->fillFromDbRow($row);
      return $this;
    }

    return null;
  }

  public function update(array $data): bool
  {
    $this->fill($data);
    return $this->save();
  }

  public function save(): bool
  {
    $result = false;

    if ($this->id >= 0) {
      $statement = $this->db->prepare(
        'update contacts set name = :name,
          phone = :phone, notes = :notes, updated_at = now()
          where id = :id'
      );
      $result = $statement->execute([
        'name' => $this->name,
        'phone' => $this->phone,
        'notes' => $this->notes,
        'id' => $this->id
      ]);
    } else {
      $statement = $this->db->prepare(
        'insert into contacts (name, phone, notes, created_at, updated_at)
          values (:name, :phone, :notes, now(), now())'
      );
      $result = $statement->execute([
        'name' => $this->name,
        'phone' => $this->phone,
        'notes' => $this->notes
      ]);
      if ($result) {
        $this->id = (int)$this->db->lastInsertId();
      }
    }

    return $result;
  }
  public function delete(): bool
  {
    $statement = $this->db->prepare('delete from contacts where id = :id');
    return $statement->execute(['id' => $this->id]);
  }
  public function count(): int
  {
    $statement = $this->db->prepare('select count(*) from contacts');
    $statement->execute();
    return (int) $statement->fetchColumn();
  }

  public function paginate(int $offset = 0, int $limit = 10): array
  {
    $contacts = [];
    $statement = $this->db->prepare('select * from contacts limit :limit offset :offset');
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();

    while ($row = $statement->fetch()) {
      $contact = new Contact($this->db);
      $contact->fillFromDbRow($row);
      $contacts[] = $contact;
    }

    return $contacts;
  }

  public function all(): array
  {
    $contacts = [];
    $statement = $this->db->prepare('select * from contacts');
    $statement->execute();
    while ($row = $statement->fetch()) {
      $contact = new Contact($this->db);
      $contact->fillFromDbRow($row);
      $contacts[] = $contact;
    }
    return $contacts;
  }

  protected function fillFromDbRow(array $row): Contact
  {
    $this->id = $row['id'];
    $this->name = $row['name'];
    $this->phone = $row['phone'];
    $this->notes = $row['notes'];
    $this->created_at = $row['created_at'];
    $this->updated_at = $row['updated_at'];
    return $this;
  }
}