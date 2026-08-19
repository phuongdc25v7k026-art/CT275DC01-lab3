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
  public $avatar;
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
    if (isset($data['avatar'])) {
      $this->avatar = $data['avatar'];
    }
    return $this;
  }

  public function validate(array $data, array $file = []): array
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

    // Validate Avatar Upload
    if (isset($file['avatar']) && $file['avatar']['error'] === UPLOAD_ERR_OK) {
      $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
      if (!in_array($file['avatar']['type'], $allowedTypes)) {
        $errors['avatar'] = 'Chỉ chấp nhận tập tin ảnh (jpg, png, gif, webp).';
      }
    }

    return $errors;
  }

  // Xử lý upload ảnh vào public/uploads/
  public function handleUpload(array $file): bool
  {
    if (isset($file['avatar']) && $file['avatar']['error'] === UPLOAD_ERR_OK) {
      $uploadDir = __DIR__ . '/../../public/uploads/';
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
      }

      $extension = pathinfo($file['avatar']['name'], PATHINFO_EXTENSION);
      $filename = uniqid('avatar_') . '.' . $extension;
      $targetPath = $uploadDir . $filename;

      if (move_uploaded_file($file['avatar']['tmp_name'], $targetPath)) {
        // Xóa avatar cũ nếu có
        if (!empty($this->avatar) && file_exists(__DIR__ . '/../../public/' . $this->avatar)) {
          unlink(__DIR__ . '/../../public/' . $this->avatar);
        }
        $this->avatar = 'uploads/' . $filename;
        return true;
      }
    }
    return false;
  }

  public function find(int $id): ?Contact
  {
    $statement = $this->db->prepare('SELECT * FROM contacts WHERE id = :id');
    $statement->execute(['id' => $id]);

    if ($row = $statement->fetch()) {
      $this->fillFromDbRow($row);
      return $this;
    }

    return null;
  }

  public function save(): bool
  {
    if ($this->id >= 0) {
      // Cập nhật record đã có
      $statement = $this->db->prepare(
        'UPDATE contacts SET name = :name,
          phone = :phone, notes = :notes, avatar = :avatar, updated_at = NOW()
          WHERE id = :id'
      );
      return $statement->execute([
        'name' => $this->name,
        'phone' => $this->phone,
        'notes' => $this->notes,
        'avatar' => $this->avatar,
        'id' => $this->id
      ]);
    } else {
      // PostgreSQL hỗ trợ RETURNING id để lấy ID vừa tạo với chuỗi SERIAL
      $statement = $this->db->prepare(
        'INSERT INTO contacts (name, phone, notes, avatar, created_at, updated_at)
          VALUES (:name, :phone, :notes, :avatar, NOW(), NOW()) RETURNING id'
      );
      $result = $statement->execute([
        'name' => $this->name,
        'phone' => $this->phone,
        'notes' => $this->notes,
        'avatar' => $this->avatar
      ]);

      if ($result) {
        $this->id = (int) $statement->fetchColumn();
        return true;
      }
    }

    return false;
  }

  public function delete(): bool
  {
    if (!empty($this->avatar) && file_exists(__DIR__ . '/../../public/' . $this->avatar)) {
      unlink(__DIR__ . '/../../public/' . $this->avatar);
    }
    $statement = $this->db->prepare('DELETE FROM contacts WHERE id = :id');
    return $statement->execute(['id' => $this->id]);
  }

  public function count(): int
  {
    $statement = $this->db->prepare('SELECT COUNT(*) FROM contacts');
    $statement->execute();
    return (int) $statement->fetchColumn();
  }

  public function paginate(int $offset = 0, int $limit = 10): array
  {
    $contacts = [];
    $statement = $this->db->prepare('SELECT * FROM contacts ORDER BY id ASC LIMIT :limit OFFSET :offset');
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
  protected function fillFromDbRow(array $row): Contact
  {
    $this->id = $row['id'];
    $this->name = $row['name'];
    $this->phone = $row['phone'];
    $this->notes = $row['notes'];
    $this->avatar = $row['avatar'];
    $this->created_at = $row['created_at'];
    $this->updated_at = $row['updated_at'];
    return $this;
  }
}