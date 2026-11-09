<?php
$page_title = 'Edit My Profile';
$active_nav = '';
require_once __DIR__ . '/php/db.php';
require_login('/likhub/login.php');

$user = current_user();
if ($user['role'] !== 'artist') {
    header('Location: /likhub/dashboard.php'); exit;
}

init_artist_profiles_table();

db()->prepare('INSERT IGNORE INTO artist_profiles (user_id) VALUES (?)')->execute([$user['id']]);

$stmt = db()->prepare('SELECT * FROM artist_profiles WHERE user_id = ?');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid form token.';
    } else {
        $action = $_POST['action'] ?? 'save_profile';

        if ($action === 'upload_avatar') {
            $file = $_FILES['avatar_file'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Please select a photo to upload.';
            } else {
                $allowed = ['image/jpeg','image/png','image/webp'];
                $finfo   = new finfo(FILEINFO_MIME_TYPE);
                $mime    = $finfo->file($file['tmp_name']);
                if (!in_array($mime, $allowed))        $errors[] = 'Only JPG, PNG, or WEBP images allowed.';
                if ($file['size'] > 4 * 1024 * 1024)  $errors[] = 'Photo must be under 4 MB.';

                if (!$errors) {
                    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'pfp_' . $user['id'] . '_' . uniqid() . '.' . $ext;
                    $dir      = __DIR__ . '/images/uploads/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);

                    if ($user['avatar'] && strpos($user['avatar'], '/images/uploads/pfp_') !== false) {
                        @unlink(__DIR__ . parse_url($user['avatar'], PHP_URL_PATH));
                    }

                    move_uploaded_file($file['tmp_name'], $dir . $filename);
                    $avatarPath = '/likhub/images/uploads/' . $filename;
                    db()->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$avatarPath, $user['id']]);
                    set_flash('success', 'Profile photo updated!');
                    header('Location: /likhub/edit-profile.php'); exit;
                }
            }
        }

        if ($action === 'save_profile') {
            $bio      = substr(trim($_POST['bio'] ?? ''), 0, 1000);
            $status   = in_array($_POST['commission_status'] ?? '', ['open','closed']) ? $_POST['commission_status'] : 'open';
            $price_min    = max(0, intval($_POST['price_min'] ?? 0));
            $price_max    = max(0, intval($_POST['price_max'] ?? 0));
            $price_small  = max(0, intval($_POST['price_small'] ?? 0));
            $price_medium = max(0, intval($_POST['price_medium'] ?? 0));
            $price_large  = max(0, intval($_POST['price_large'] ?? 0));
            $shipping_fee = max(0, intval($_POST['shipping_fee'] ?? 0));

            $valid_mediums = ['Digital','Traditional','Painting'];
            $chosen_meds   = array_filter($_POST['mediums'] ?? [], fn($m) => in_array($m, $valid_mediums));
            $mediums_str   = implode(',', $chosen_meds);

            $styles_raw = trim($_POST['styles'] ?? '');
            $styles_arr = array_filter(array_map('trim', explode(',', $styles_raw)));
            $styles_str = implode(',', array_slice($styles_arr, 0, 10));

            $display_name = trim($_POST['display_name'] ?? '');
            if (strlen($display_name) < 2) $errors[] = 'Display name must be at least 2 characters.';
            if ($price_max > 0 && $price_min > $price_max) $errors[] = 'Min price cannot exceed max price.';

            if (!$errors) {
                db()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$display_name, $user['id']]);
                db()->prepare(
                    'UPDATE artist_profiles SET bio=?, commission_status=?, price_min=?, price_max=?, price_small=?, price_medium=?, price_large=?, shipping_fee=?, mediums=?, styles=? WHERE user_id=?'
                )->execute([$bio, $status, $price_min, $price_max, $price_small, $price_medium, $price_large, $shipping_fee, $mediums_str, $styles_str, $user['id']]);

                set_flash('success', 'Profile saved! Changes are live on the commission page.');
                header('Location: /likhub/edit-profile.php'); exit;
            }
        }

        if ($action === 'delete_account') {
            $confirm = trim($_POST['delete_confirm'] ?? '');
            if ($confirm !== 'DELETE') {
                $errors[] = 'Please type DELETE to confirm account deletion.';
            } else {
                $uid = $user['id'];
                // Deactivate instead of hard delete to preserve commission history
                db()->prepare('UPDATE users SET is_active = 0, email = CONCAT("deleted_", id, "_", email) WHERE id = ?')->execute([$uid]);
                db()->prepare('UPDATE artist_profiles SET commission_status = "closed" WHERE user_id = ?')->execute([$uid]);
                // Destroy session
                session_destroy();
                header('Location: /likhub/index.php?deleted=1'); exit;
            }
        }
    }
}

$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

$chosen_meds = $profile['mediums'] ? array_map('trim', explode(',', $profile['mediums'])) : [];
$styles_text = $profile['styles'] ?? '';

include __DIR__ . '/partials/header.php';

$initials = '';
foreach (explode(' ', trim($user['name'])) as $p) $initials .= strtoupper($p[0]);
$initials = substr($initials, 0, 2);
?>

<header class="page-header">
  <div class="container">
    <h1>Edit Your Artist Profile</h1>
    <p>This information shows on your public profile and the commission page.</p>
  </div>
</header>

<section class="section">
  <div class="u-max-w-760 container">

    <?php if ($errors): ?>
      <div class="u-mb-20 alert alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= implode(' ', array_map('e', $errors)) ?></div>
    <?php endif; ?>

    <div class="u-mb-24 form-section">
      <div class="form-section__title">Profile Photo</div>
      <div class="u-avatar-row">
        <div class="profile-edit-avatar">
          <?php if ($user['avatar']): ?>
            <img src="<?= e($user['avatar']) ?>" alt="<?= e($user['name']) ?>" id="avatarPreview">
          <?php else: ?>
            <div class="profile-edit-avatar__initials" id="avatarInitials"><?= e($initials) ?></div>
          <?php endif; ?>
        </div>
        <form class="u-avatar-form" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="upload_avatar">
          <label class="form-label" for="avatar_file">Upload a new photo (JPG, PNG, WEBP · max 4 MB)</label>
          <div class="u-avatar-btns">
            <input type="file" name="avatar_file" id="avatar_file" accept="image/jpeg,image/png,image/webp"
              class="u-flex-1 form-control" onchange="previewAvatar(this)">
            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
          </div>
        </form>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section__title">Profile Details</div>
      <form method="POST">
        <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save_profile">

        <div class="form-group">
          <label class="form-label" for="display_name">Display Name *</label>
          <input type="text" name="display_name" id="display_name" class="form-control"
            value="<?= e($user['name']) ?>" maxlength="100" required
            placeholder="The name shown on commission listings">
          <div class="form-hint">This is your public artist name — clients will see this.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="bio">Bio / About</label>
          <textarea name="bio" id="bio" class="form-control" rows="4" maxlength="1000"
            placeholder="Tell clients about your style, experience, or what you love to create…"><?= e($profile['bio'] ?? '') ?></textarea>
          <div class="form-hint" id="bioCount"><?= strlen($profile['bio'] ?? '') ?>/1000</div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Commission Status</label>
            <div class="role-picker">
              <label class="role-label">
                <input type="radio" name="commission_status" value="open" class="role-radio"
                  <?= ($profile['commission_status'] ?? 'open') === 'open' ? 'checked' : '' ?>>
                🟢 Open
              </label>
              <label class="role-label">
                <input type="radio" name="commission_status" value="closed" class="role-radio"
                  <?= ($profile['commission_status'] ?? 'open') === 'closed' ? 'checked' : '' ?>>
                🔴 Closed
              </label>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Mediums</label>
          <div class="checkbox-group">
            <?php foreach (['Digital','Traditional','Painting'] as $med): ?>
            <label class="checkbox-label">
              <input type="checkbox" name="mediums[]" value="<?= $med ?>"
                <?= in_array($med, $chosen_meds) ? 'checked' : '' ?>>
              <?= $med ?>
            </label>
            <?php endforeach; ?>
          </div>
          <div class="form-hint">Check all mediums you offer for commissions.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="styles">Styles <span class="u-fw-400 u-color-muted">(optional)</span></label>
          <input type="text" name="styles" id="styles" class="form-control"
            value="<?= e($styles_text) ?>"
            placeholder="e.g. Chibi, Anime, Realism, Watercolour" maxlength="255">
          <div class="form-hint">Comma-separated. Up to 10 styles shown on your profile.</div>
        </div>

        <?php
          $no_pricing = empty($profile['price_small']) && empty($profile['price_medium']) && empty($profile['price_large']);
          if ($no_pricing): ?>
          <div class="u-mb-16 alert alert--error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><strong>You haven't set your prices yet!</strong> Clients will see standard default rates (₱300 / ₱900 / ₱3,000) on your profile until you fill these in. Set your own prices below so clients know what to expect.</span>
          </div>
          <?php endif; ?>

        <div class="form-group">
          <label class="form-label">Prices per Size</label>
          <div class="u-mb-10 form-hint">Set your price for each size. Leave 0 if you don't offer that size.</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="price_small">Small (₱)</label>
              <input type="number" name="price_small" id="price_small" class="form-control"
                value="<?= (int)($profile['price_small'] ?? 0) ?>" min="0" max="99999" step="50"
                placeholder="e.g. 300">
              <div class="form-hint">~500px</div>
            </div>
            <div class="form-group">
              <label class="form-label" for="price_medium">Medium (₱)</label>
              <input type="number" name="price_medium" id="price_medium" class="form-control"
                value="<?= (int)($profile['price_medium'] ?? 0) ?>" min="0" max="99999" step="50"
                placeholder="e.g. 900">
              <div class="form-hint">~1200px</div>
            </div>
            <div class="form-group">
              <label class="form-label" for="price_large">Large (₱)</label>
              <input type="number" name="price_large" id="price_large" class="form-control"
                value="<?= (int)($profile['price_large'] ?? 0) ?>" min="0" max="99999" step="50"
                placeholder="e.g. 3000">
              <div class="form-hint">~2400px</div>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="price_min">Display: Starting Price (₱)</label>
            <input type="number" name="price_min" id="price_min" class="form-control"
              value="<?= (int)($profile['price_min'] ?? 0) ?>" min="0" max="99999" step="50"
              placeholder="e.g. 300">
          </div>
          <div class="form-group">
            <label class="form-label" for="price_max">Display: Max Price (₱)</label>
            <input type="number" name="price_max" id="price_max" class="form-control"
              value="<?= (int)($profile['price_max'] ?? 0) ?>" min="0" max="99999" step="50"
              placeholder="e.g. 3000">
          </div>
        </div>
        <div class="form-hint" style="margin-bottom:16px;">These show as the "Starting from ₱X – ₱Y" range on the listing card. Usually set to your smallest and largest per-size prices.</div>

        <div class="form-group">
          <label class="form-label" for="shipping_fee">Shipping Fee for Physical Orders (₱)</label>
          <input type="number" name="shipping_fee" id="shipping_fee" class="form-control"
            value="<?= (int)($profile['shipping_fee'] ?? 0) ?>" min="0" max="9999" step="10"
            placeholder="e.g. 150">
          <div class="form-hint">Added to the total for Traditional and Painting commissions. Set 0 if you handle shipping separately.</div>
        </div>

        <div class="u-d-flex u-gap-10 u-flex-wrap u-mt-8">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Profile</button>
          <a href="/likhub/artist-profile.php?id=<?= $user['id'] ?>" class="btn btn-outline" target="_blank">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> View Public Profile
          </a>
          <a href="/likhub/dashboard.php" class="btn btn-outline">← Dashboard</a>
        </div>
      </form>
    </div>

    <!-- Danger Zone -->
    <div class="form-section" style="margin-top: 40px; border: 1px solid #e53e3e; border-radius: 8px; padding: 1.5rem;">
      <div class="form-section__title" style="color: #e53e3e;">Danger Zone</div>
      <p style="color: var(--text-muted, #666); font-size: 14px; margin-bottom: 16px;">
        Deleting your account is permanent. Your profile will be hidden, and you will be logged out immediately. Commission history is preserved for existing clients.
      </p>
      <button type="button" class="btn" id="btnShowDelete"
        style="background: #fff0f0; color: #e53e3e; border: 1px solid #e53e3e;">
        <i class="fa-solid fa-trash"></i> Delete My Account
      </button>

      <div id="deletePanel" style="display:none; margin-top: 20px;">
        <form method="POST" onsubmit="return confirmDelete()">
          <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="delete_account">
          <div class="form-group">
            <label class="form-label" for="delete_confirm">
              Type <strong>DELETE</strong> to confirm
            </label>
            <input type="text" name="delete_confirm" id="delete_confirm" class="form-control"
              placeholder="DELETE" autocomplete="off"
              style="border-color: #e53e3e; max-width: 240px;">
          </div>
          <button type="submit" class="btn"
            style="background: #e53e3e; color: #fff; border: none;">
            <i class="fa-solid fa-triangle-exclamation"></i> Yes, permanently delete my account
          </button>
          <button type="button" class="btn btn-outline" style="margin-left: 8px;" onclick="document.getElementById('deletePanel').style.display='none'">
            Cancel
          </button>
        </form>
      </div>
    </div>

  </div>
</section>

<script>
function previewAvatar(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById('avatarPreview');
    const init = document.getElementById('avatarInitials');
    if (prev) { prev.src = e.target.result; }
    else if (init) {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.id  = 'avatarPreview';
      init.replaceWith(img);
    }
  };
  reader.readAsDataURL(input.files[0]);
}

const bioArea  = document.getElementById('bio');
const bioCount = document.getElementById('bioCount');
if (bioArea) {
  bioArea.addEventListener('input', () => {
    bioCount.textContent = bioArea.value.length + '/1000';
  });
}

document.getElementById('btnShowDelete').addEventListener('click', () => {
  document.getElementById('deletePanel').style.display = 'block';
  document.getElementById('delete_confirm').focus();
});

function confirmDelete() {
  const val = document.getElementById('delete_confirm').value;
  if (val !== 'DELETE') {
    alert('Please type DELETE exactly to confirm.');
    return false;
  }
  return confirm('Are you absolutely sure? This cannot be undone.');
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>