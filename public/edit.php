<?php
require_once __DIR__ . '/../src/bootstrap.php';

use CT275\Labs\Contact;

$contact = new Contact($PDO);

$id = isset($_REQUEST['id']) ?
  filter_var($_REQUEST['id'], FILTER_VALIDATE_INT) : false;

if (!$id || !($contact->find($id))) {
  redirect('/');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $contactData = [
    'name' => $_POST['name'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'notes' => $_POST['notes'] ?? '',
  ];

  $errors = $contact->validate($contactData, $_FILES);
  if (empty($errors)) {
    $contact->fill($contactData);
    $contact->handleUpload($_FILES);
    $contact->save() && redirect('/');
  }
}

include_once __DIR__ . '/../src/partials/header.php';
?>

<body>
  <?php include_once __DIR__ . '/../src/partials/navbar.php' ?>

  <!-- Main Page Content -->
  <div class="container">

    <?php
    $subtitle = 'Update your contacts here.';
    include_once __DIR__ . '/../src/partials/heading.php';
    ?>

    <div class="row">
      <div class="col-12">

        <form method="post" enctype="multipart/form-data" class="col-md-6 offset-md-3">

          <input type="hidden" name="id" value="<?= $contact->id ?>">

          <!-- Avatar Field -->
          <div class="mb-3">
            <label for="avatar" class="form-label">Avatar</label>
            <input type="file" name="avatar" id="avatar" class="form-control<?= isset($errors['avatar']) ? ' is-invalid' : '' ?>" accept="image/*" />
            <div class="mt-2">
              <?php if (!empty($contact->avatar)) : ?>
                <img id="avatar-preview" src="/<?= html_escape($contact->avatar) ?>" alt="Avatar Preview" style="max-width: 120px;" class="img-thumbnail" />
              <?php else : ?>
                <img id="avatar-preview" src="#" alt="Avatar Preview" style="max-width: 120px; display: none;" class="img-thumbnail" />
              <?php endif ?>
            </div>
            <?php if (isset($errors['avatar'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['avatar'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Name -->
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" maxlen="255" id="name" placeholder="Enter Name" value="<?= html_escape($contact->name) ?>" />

            <?php if (isset($errors['name'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['name'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Phone -->
          <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>" maxlen="255" id="phone" placeholder="Enter Phone" value="<?= html_escape($contact->phone) ?>" />

            <?php if (isset($errors['phone'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['phone'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Notes -->
          <div class="mb-3">
            <label for="notes" class="form-label">Notes </label>
            <textarea name="notes" id="notes" class="form-control<?= isset($errors['notes']) ? ' is-invalid' : '' ?>" placeholder="Enter notes (maximum character limit: 255)"><?= html_escape($contact->notes) ?></textarea>

            <?php if (isset($errors['notes'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['notes'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Submit -->
          <button type="submit" name="submit" class="btn btn-primary">Update Contact</button>
        </form>

      </div>
    </div>

  </div>

  <?php include_once __DIR__ . '/../src/partials/footer.php' ?>

  <!-- JS Preview Avatar -->
  <script>
    document.getElementById('avatar').addEventListener('change', function(e) {
      const preview = document.getElementById('avatar-preview');
      const file = e.target.files[0];
      if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
      }
    });
  </script>
</body>

</html>