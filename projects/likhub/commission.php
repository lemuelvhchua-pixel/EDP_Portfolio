<?php
$page_title = 'commission -';
$active_nav = 'commission';
require_once __DIR__ . '/php/db.php';

$medium  = $_GET['medium'] ?? '';
$search  = $_GET['search'] ?? '';
$mediums = ['Digital','Traditional','Painting'];

$currentUser = current_user();
$uid         = $currentUser ? $currentUser['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // If this is a bare browser POST (not a fetch/XHR), refuse to serve JSON as a page.
    // fetch() sends Content-Type: multipart/form-data but no X-Requested-With header by default,
    // so we use a custom marker field instead — the JS always appends _ajax=1.
    $isAjax = !empty($_POST['_ajax']);
    if (!$isAjax) {
        header('Location: /likhub/commission.php');
        exit;
    }

    header('Content-Type: application/json');

    if (!verify_csrf($_POST['csrf'] ?? '')) {
        echo json_encode(['error' => 'Invalid token']); exit;
    }

    
    if (!$uid) {
        echo json_encode(['error' => 'login_required']); exit;
    }
    if (($currentUser['role'] ?? '') === 'admin') {
        echo json_encode(['error' => 'Admins cannot place or interact with commissions.']); exit;
    }

    init_commission_requests_table();

    if ($action === 'cart_add') {
        $size    = in_array($_POST['size']  ?? '', ['Small','Medium','Large'])           ? $_POST['size']  : 'Small';
        $type    = in_array($_POST['type']  ?? '', ['Digital','Traditional','Painting'])  ? $_POST['type']  : 'Digital';
        $desc    = substr(trim($_POST['desc']    ?? ''), 0, 1000);
        $payment = htmlspecialchars($_POST['payment']   ?? '', ENT_QUOTES);
        $price   = intval($_POST['price'] ?? 0);
        $aId     = htmlspecialchars($_POST['artistId']  ?? '', ENT_QUOTES);
        $aName   = htmlspecialchars($_POST['artistName']?? '', ENT_QUOTES);
        $shippingFee = intval($_POST['shippingFee'] ?? 0);

        
        $artistDbId = preg_replace('/^db_/', '', $aId);
        if ((string)$uid === (string)$artistDbId) {
            echo json_encode(['error' => 'You cannot commission yourself.']); exit;
        }

        $ref     = 'LH-' . strtoupper(bin2hex(random_bytes(5)));

        // Handle reference image uploads
        $refImages = [];
        if (!empty($_FILES['ref_images'])) {
            $files = $_FILES['ref_images'];
            $uploadDir = __DIR__ . '/images/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $count = is_array($files['name']) ? count($files['name']) : 1;
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            for ($fi = 0; $fi < min($count, 5); $fi++) {
                $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$fi] : $files['tmp_name'];
                $origName = is_array($files['name']) ? $files['name'][$fi] : $files['name'];
                $err = is_array($files['error']) ? $files['error'][$fi] : $files['error'];
                if ($err !== UPLOAD_ERR_OK) continue;
                $mime = $finfo->file($tmpName);
                if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'])) continue;
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $fname = 'ref_' . preg_replace('/[^a-z0-9]/', '', strtolower($ref)) . '_' . $fi . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($tmpName, $uploadDir . $fname);
                $refImages[] = '/likhub/images/uploads/' . $fname;
            }
        }
        $refImagesJson = $refImages ? json_encode($refImages) : null;

        $stmt = db()->prepare('INSERT INTO commission_requests (ref, user_id, artist_id, artist_name, size, art_type, description, payment, price, shipping_fee, ref_images) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$ref, $uid, $aId, $aName, $size, $type, $desc, $payment, $price, $shippingFee, $refImagesJson]);

        echo json_encode(['ok' => true, 'cart' => get_user_cart($uid)]); exit;
    }

    if ($action === 'cart_remove') {
        $rmRef = $_POST['itemId'] ?? '';
        db()->prepare('DELETE FROM commission_requests WHERE ref = ? AND user_id = ?')->execute([$rmRef, $uid]);
        echo json_encode(['ok' => true, 'cart' => get_user_cart($uid)]); exit;
    }

    if ($action === 'cart_get') {
        echo json_encode(['ok' => true, 'cart' => get_user_cart($uid)]); exit;
    }

    if ($action === 'cart_checkout') {
        $cart = get_user_cart($uid);
        echo json_encode(['ok' => true, 'refs' => array_column($cart, 'ref'), 'cart' => $cart]); exit;
    }

    if ($action === 'client_accept') {
        $ref = $_POST['ref'] ?? '';
        db()->prepare("UPDATE commission_requests SET status = 'In Progress' WHERE ref = ? AND user_id = ? AND status = 'Approved'")
            ->execute([$ref, $uid]);
        echo json_encode(['ok' => true, 'cart' => get_user_cart($uid)]); exit;
    }

    if ($action === 'client_pay_confirm') {
        $ref = $_POST['ref'] ?? '';
        db()->prepare("UPDATE commission_requests SET status = 'Completed' WHERE ref = ? AND user_id = ? AND status = 'Payment Pending'")
            ->execute([$ref, $uid]);
        echo json_encode(['ok' => true, 'cart' => get_user_cart($uid)]); exit;
    }


    if ($action === 'set_payment_note') {
        $ref  = $_POST['ref'] ?? '';
        $note = substr(trim($_POST['note'] ?? ''), 0, 500);
        db()->prepare('UPDATE commission_requests SET payment_note = ? WHERE ref = ? AND user_id = ?')
            ->execute([$note, $ref, $uid]);
        echo json_encode(['ok' => true, 'cart' => get_user_cart($uid)]); exit;
    }

    if ($action === 'client_decline') {
        $ref = $_POST['ref'] ?? '';
        db()->prepare("UPDATE commission_requests SET status = 'Declined' WHERE ref = ? AND user_id = ? AND status = 'Approved'")
            ->execute([$ref, $uid]);
        echo json_encode(['ok' => true, 'cart' => get_user_cart($uid)]); exit;
    }

    if ($action === 'leave_review') {
        $ref     = $_POST['ref'] ?? '';
        $rating  = intval($_POST['rating'] ?? 0);
        $review  = substr(trim($_POST['review'] ?? ''), 0, 1000);
        if ($rating < 1 || $rating > 5) { echo json_encode(['error' => 'Invalid rating']); exit; }
        $stmt = db()->prepare('SELECT id, artist_name, status, rating FROM commission_requests WHERE ref = ? AND user_id = ?');
        $stmt->execute([$ref, $uid]);
        $row = $stmt->fetch();
        if (!$row)                      { echo json_encode(['error' => 'Commission not found']); exit; }
        if ($row['status'] !== 'Completed') { echo json_encode(['error' => 'Commission not completed yet']); exit; }
        if ($row['rating'])             { echo json_encode(['error' => 'Already reviewed']); exit; }
        db()->prepare('UPDATE commission_requests SET rating = ?, review_text = ? WHERE ref = ? AND user_id = ?')
            ->execute([$rating, $review, $ref, $uid]);
        $artistRow = db()->prepare('SELECT id FROM users WHERE name = ? LIMIT 1');
        $artistRow->execute([$row['artist_name']]);
        $ar = $artistRow->fetch();
        if ($ar) update_artist_stats((int)$ar['id']);
        echo json_encode(['ok' => true, 'cart' => get_user_cart($uid)]); exit;
    }

    echo json_encode(['error' => 'Unknown action']); exit;
}

$artists = get_all_db_artists($medium ?: null, $search ?: null);

$savedCart = $uid ? get_user_cart($uid) : [];

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <h1>Commission an Artist</h1>
    <p>Browse student artists by medium and price, then send a commission request.</p>
    <?php if (!$currentUser): ?>
    <div class="alert alert--info" style="margin-top: 32px;">
      <i class="fa-solid fa-circle-info" class="u-flex-shrink-0"></i>
      <span>
        <a class="u-fw-700" href="/likhub/login.php">Log in</a> or
        <a class="u-fw-700" href="/likhub/register.php">create a free account</a>
        to submit commission requests and track your orders.
      </span>
    </div>
    <?php endif; ?>
  </div>
</header>

<section class="section">
  <div class="container">

    <form class="filter-bar" method="GET" action="/likhub/commission.php" aria-label="Filter artists">
      <span class="filter-bar__label">Medium:</span>
      <button type="submit" name="medium" value="" class="filter-chip<?= !$medium?' active':''?>">All</button>
      <?php foreach ($mediums as $m): ?>
        <button type="submit" name="medium" value="<?= $m ?>" class="filter-chip<?= $medium===$m?' active':''?>"><?= $m ?></button>
      <?php endforeach; ?>
      <div class="search-input-wrap">
        <input type="text" name="search" placeholder="Search artist name…" value="<?= e($search) ?>">
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      </div>
    </form>

    <?php if ($artists): ?>
    <div class="grid-3">
      <?php foreach ($artists as $a):
        $init = strtoupper(substr(implode('', array_map(fn($w)=>$w[0], explode(' ', trim($a['name'])))),0,2));
      ?>
      <article class="artist-card fade-up">
        <div class="artist-card__cover">
          <?php if ($a['artworks']): ?>
            <?php foreach ($a['artworks'] as $i => $img): ?>
            <div class="slide<?= $i===0?' slide--active':''?>">
              <div class="slide__blur" style="background-image:url('<?= e($img) ?>')"></div>
              <img src="<?= e($img) ?>" alt="<?= e($a['name']) ?> artwork" loading="lazy">
            </div>
            <?php endforeach; ?>
            <?php if (count($a['artworks'])>1): ?>
            <div class="slide-dots">
              <?php foreach ($a['artworks'] as $i=>$img): ?>
                <span class="slide-dot<?= $i===0?' slide-dot--active':''?>"></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="artist-card__cover-placeholder"><i class="fa-solid fa-palette"></i><span>Portfolio Loading</span></div>
          <?php endif; ?>
        </div>
        <div class="artist-card__body">
          <div class="artist-card__row">
            <div class="artist-card__avatar">
              <?php if ($a['pfp']): ?><img src="<?= e($a['pfp']) ?>" alt=""><?php else: ?><?= e($init) ?><?php endif; ?>
            </div>
            <div class="status-badge status-badge--<?= $a['status']==='open'?'open':'closed'?>">
              <?= $a['status']==='open'?'Open':'Closed'?>
            </div>
          </div>
          <div class="artist-card__name"><?= e($a['name']) ?></div>
          <?php if (!empty($a['avg_rating'])): ?>
          <div class="artist-card__rating" title="Average rating: <?= number_format($a['avg_rating'],1) ?>/5">
            <?= render_stars((int)round($a['avg_rating'])) ?>
            <span class="artist-card__rating-val"><?= number_format($a['avg_rating'],1) ?></span>
          </div>
          <?php endif; ?>
          <div class="artist-card__tags">
            <?php foreach ($a['mediums'] as $md): ?><span class="tag"><?= e($md) ?></span><?php endforeach; ?>
          </div>
          <div class="artist-card__price">Starting from <strong>₱<?= number_format($a['price_min']) ?> – ₱<?= number_format($a['price_max']) ?></strong></div>
            <div class="artist-card-actions">
            <?php if ($a['status']==='open'): ?>
              <?php if ($currentUser && $currentUser['id'] === $a['db_id']): ?>
                <button class="btn btn-sm btn-full" disabled title="You cannot commission yourself">Your Profile</button>
              <?php elseif ($currentUser && ($currentUser['role'] ?? '') === 'admin'): ?>
                <button class="btn btn-sm btn-full" disabled title="Admins cannot place commissions">View Only</button>
              <?php elseif ($currentUser): ?>
                <button class="btn btn-primary btn-sm btn-full js-open-commission"
                  data-id="<?= e($a['id']) ?>"
                  data-name="<?= e($a['name']) ?>"
                  data-pfp="<?= e($a['pfp'] ?? '') ?>"
                  data-pmin="<?= $a['price_min'] ?>"
                  data-pmax="<?= $a['price_max'] ?>"
                  data-psmall="<?= $a['price_small'] ?>"
                  data-pmedium="<?= $a['price_medium'] ?>"
                  data-plarge="<?= $a['price_large'] ?>"
                  data-shipping="<?= $a['shipping_fee'] ?>">
                  <i class="fa-solid fa-handshake"></i> Inquire
                </button>
              <?php else: ?>
                <a href="/likhub/login.php?redirect=<?= urlencode('/likhub/commission.php') ?>" class="btn btn-outline btn-sm btn-full">
                  <i class="fa-solid fa-lock"></i> Log in to Inquire
                </a>
              <?php endif; ?>
            <?php else: ?>
              <button class="btn btn-sm btn-full" disabled>Closed</button>
            <?php endif; ?>
            <a href="/likhub/artist-profile.php?id=<?= $a['db_id'] ?>" class="btn btn-outline btn-sm btn-full" data-util-class="u-mt-6">
              View Profile
            </a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="no-results text-center">
      <i class="fa-solid fa-users"></i>
      <h3>No artists found</h3>
      <p><a href="/likhub/commission.php">Clear filters</a> to see all artists.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($currentUser && ($currentUser['role'] ?? '') !== 'admin'): ?>
<div class="modal-overlay" id="commissionModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="u-max-w-580 modal">
    <button class="modal__close" id="commModalClose">&times;</button>
    <h3 id="modalTitle">Request a Commission</h3>
    <p class="u-m-6-0-16 body-text">You are commissioning: <strong id="modalArtistName"></strong></p>

    
    <div class="price-estimate-box" id="priceEstimateBox">
      <div class="price-estimate-label">Estimated Price</div>
      <div class="price-estimate-amount" id="priceEstimateAmt">₱—</div>
      <div class="price-estimate-range" id="priceEstimateNote">Pick a size and type to see estimate</div>
    </div>

    <form id="commissionForm">
      <input type="hidden" id="modalArtistId">
      <input type="hidden" id="modalPriceMin">
      <input type="hidden" id="modalPriceMax">
      <input type="hidden" id="modalArtistPfp">
      <input type="hidden" id="currentEstimate" value="0">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="comm_size">Size *</label>
          <select id="comm_size" class="form-control" required>
            <option value="">— Select —</option>
            <option value="Small">Small · ~500px</option>
            <option value="Medium">Medium · ~1200px</option>
            <option value="Large">Large · ~2400px</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="comm_category">Art Type *</label>
          <select id="comm_category" class="form-control" required>
            <option value="">— Select —</option>
            <option value="Digital">Digital</option>
            <option value="Traditional">Traditional</option>
            <option value="Painting">Painting</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="comm_desc">Describe Your Commission *</label>
        <textarea id="comm_desc" class="form-control" rows="4"
          placeholder="Characters, style, colors, mood, deadline, references…" required minlength="20"></textarea>
        <div class="form-hint">Minimum 20 characters. More detail = better result.</div>
      </div>

      <div class="form-group">
        <label class="form-label">Reference Images <span class="u-fw-400 u-color-muted">(optional)</span></label>
        <div class="file-upload-area">
          <input type="file" id="comm_refs" accept="image/*" multiple>
          <i class="fa-solid fa-image"></i>
          <div class="upload-label"><strong>Upload references</strong> — PNG, JPG, up to 5 files</div>
        </div>
        <div class="file-preview-row" id="commRefPreview"></div>
      </div>

      <div class="form-group">
        <label class="form-label" for="comm_payment">Preferred Payment</label>
        <select id="comm_payment" class="form-control">
          <option value="">— Select —</option>
          <option value="GCash">GCash</option>
          <option value="PayMaya">PayMaya</option>
          <option value="Bank Transfer">Bank Transfer</option>
          <option value="Cash">Cash on Delivery</option>
        </select>
      </div>

      <div class="terms-notice">
        By submitting, you agree to LikHub's <a href="/likhub/terms.php" target="_blank">Terms of Service</a>.
        The artist must <strong>approve</strong> your request before work begins.
        Payment is arranged directly with the artist.
        <a href="/likhub/size-guide.php" target="_blank" class="u-ml-6"><i class="fa-solid fa-ruler-combined"></i> Size Guide</a>
      </div>

      <div class="modal-btn-row">
        <button type="button" class="btn btn-cart" id="btnAddToCart">
          <i class="fa-solid fa-basket-shopping"></i> Add to Cart
        </button>
        <button type="button" class="btn btn-primary" id="btnSubmitComm">
          <i class="fa-solid fa-paper-plane"></i> Request Now
        </button>
      </div>
    </form>

    <div id="commSuccess" class="u-d-none u-text-center">
      <i class="fa-solid fa-circle-check" style="color:#22c55e;font-size:48px;margin-bottom:12px;"></i>
      <h4>Commission Requested!</h4>
      <p class="u-mt-8 u-color-muted" id="commSuccessMsg"></p>
      <p class="u-mt-6 u-color-muted-sm">The artist will review and approve your request. You can track it in your cart.</p>
      <button class="btn btn-outline btn-sm" class="u-mt-16" id="btnViewCart">View Cart</button>
    </div>
  </div>
</div>

<button class="cart-fab" id="cartFab" aria-label="View cart">
  <i class="fa-solid fa-basket-shopping"></i>
  <span class="cart-badge" id="cartBadge"></span>
</button>
<div class="cart-drawer-backdrop" id="cartBackdrop"></div>
<div class="cart-drawer" id="cartDrawer" role="dialog" aria-label="Commission cart">
  <div class="cart-drawer__header">
    <div class="cart-drawer__title">My Commission Cart</div>
    <button class="cart-drawer__close" id="cartClose">&times;</button>
  </div>
  <div class="cart-drawer__body" id="cartBody">
    <div class="cart-empty" id="cartEmpty">
      <i class="fa-solid fa-basket-shopping"></i>
      <p>No commissions yet.<br>Inquire an artist to get started.</p>
    </div>
  </div>
  <div class="cart-drawer__footer">
    <div class="cart-total">
      <span class="cart-total__label">Estimated Total</span>
      <span class="cart-total__amount" id="cartTotal">₱0</span>
    </div>
    <button class="btn btn-primary btn-full" id="btnCheckout" disabled>
      <i class="fa-solid fa-rectangle-list"></i> View in Dashboard
    </button>
    <p class="u-text-fine">
      Artists review & approve each request. By checking out you agree to our
      <a class="u-color-navy" href="/likhub/terms.php" target="_blank">Terms of Service</a>.
    </p>
  </div>
</div>

<!-- Review Modal -->
<div class="modal-overlay" id="reviewModal" role="dialog" aria-modal="true" aria-labelledby="reviewModalTitle" style="display:none;">
  <div class="u-max-w-480 modal">
    <button class="modal__close" id="reviewModalClose">&times;</button>
    <h3 id="reviewModalTitle"><i class="fa-solid fa-star" style="color:#f59e0b;margin-right:6px;"></i> Leave a Review</h3>
    <p class="u-m-6-0-16 body-text">How was your experience with <strong id="reviewArtistName"></strong>?</p>
    <input type="hidden" id="reviewRef" value="">
    <div class="form-group" style="margin-bottom:16px;">
      <label class="form-label">Your Rating *</label>
      <div class="star-picker" id="starPicker" style="display:flex;gap:8px;margin-top:8px;">
        <?php for($i=1;$i<=5;$i++): ?>
        <button type="button" class="star-pick-btn" data-val="<?= $i ?>" style="font-size:28px;background:none;border:none;cursor:pointer;color:#d1d5db;transition:color .15s;" aria-label="<?= $i ?> star">★</button>
        <?php endfor; ?>
      </div>
      <input type="hidden" id="reviewRating" value="0">
    </div>
    <div class="form-group">
      <label class="form-label" for="reviewText">Comments <span class="u-fw-400 u-color-muted">(optional)</span></label>
      <textarea id="reviewText" class="form-control" rows="3" maxlength="1000" placeholder="Share your experience…"></textarea>
    </div>
    <div class="modal-btn-row" style="margin-top:16px;">
      <button type="button" class="btn btn-outline" id="reviewModalCancel">Cancel</button>
      <button type="button" class="btn btn-primary" id="reviewSubmitBtn"><i class="fa-solid fa-paper-plane"></i> Submit Review</button>
    </div>
  </div>
</div>

<div class="cart-added-toast" id="cartToast"></div>
<?php endif; ?>

<?php if ($currentUser && ($currentUser['role'] ?? '') !== 'admin'): ?>
<script>
(function(){
'use strict';
const CSRF = <?= json_encode(csrf_token()) ?>;
const ENDPOINT = '/likhub/commission.php';

const TYPE_MULT = {Digital:1, Traditional:1.1, Painting:1.3};

const STATUS_CLASS = {
  'Submitted':        'status-badge--pending',
  'Approved':         'status-badge--open',
  'In Progress':      'status-badge--inprogress',
  'Completed':        'status-badge--completed',
  'Declined':         'status-badge--closed',
  'Rejected':         'status-badge--closed',
  'Cancelled':        'status-badge--closed',
  'Payment Pending':  'status-badge--open',
};

let cart       = [];
let currentArtist = null;
let estimatedPrice = 0;

async function api(payload, files) {
  payload.csrf = CSRF;
  payload._ajax = 1;
  const fd = new FormData();
  Object.entries(payload).forEach(([k,v]) => fd.append(k,v));
  if (files && files.length) {
    Array.from(files).slice(0,5).forEach(f => fd.append('ref_images[]', f));
  }
  try {
    const r = await fetch(ENDPOINT, {method:'POST', body:fd, credentials:'same-origin'});
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
  } catch(err) {
    console.error('API error:', err);
    return {error: String(err)};
  }
}

function showToast(msg, ok=true) {
  const t = document.getElementById('cartToast');
  t.textContent = msg;
  t.style.background = ok ? 'var(--navy-dark)' : '#c0392b';
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'), 2600);
}

async function loadCart() {
  const res = await api({action:'cart_get'});
  if (res.ok) { cart = res.cart || []; renderCartWithPending(); }
}
loadCart();

document.querySelectorAll('.js-open-commission').forEach(btn => {
  btn.addEventListener('click', () => {
    currentArtist = {
      id:       btn.dataset.id,
      name:     btn.dataset.name,
      pfp:      btn.dataset.pfp || '',
      pmin:     parseInt(btn.dataset.pmin,10),
      pmax:     parseInt(btn.dataset.pmax,10),
      psmall:   parseInt(btn.dataset.psmall,10)  || 0,
      pmedium:  parseInt(btn.dataset.pmedium,10) || 0,
      plarge:   parseInt(btn.dataset.plarge,10)  || 0,
      shipping: parseInt(btn.dataset.shipping,10)|| 0,
    };
    document.getElementById('modalArtistId').value   = currentArtist.id;
    document.getElementById('modalPriceMin').value   = currentArtist.pmin;
    document.getElementById('modalPriceMax').value   = currentArtist.pmax;
    document.getElementById('modalArtistPfp').value  = currentArtist.pfp;
    document.getElementById('modalArtistName').textContent = currentArtist.name;
    document.getElementById('commissionForm').style.display = '';
    document.getElementById('commSuccess').style.display = 'none';
    document.getElementById('priceEstimateBox').classList.remove('visible');
    document.getElementById('comm_size').value = '';
    document.getElementById('comm_category').value = '';
    document.getElementById('comm_desc').value = '';
    document.getElementById('comm_payment').value = '';
    document.getElementById('commRefPreview').innerHTML = '';
    openModal();
  });
});

function openModal()  { document.getElementById('commissionModal').classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal() { document.getElementById('commissionModal').classList.remove('open'); document.body.style.overflow=''; }
document.getElementById('commModalClose').addEventListener('click', closeModal);
document.getElementById('commissionModal').addEventListener('click', e => { if(e.target===document.getElementById('commissionModal')) closeModal(); });

function updatePrice() {
  if (!currentArtist) return 0;
  const sz  = document.getElementById('comm_size').value;
  const cat = document.getElementById('comm_category').value;
  if (!sz || !cat) { document.getElementById('priceEstimateBox').classList.remove('visible'); return 0; }

  const sizeMap = {Small: currentArtist.psmall, Medium: currentArtist.pmedium, Large: currentArtist.plarge};
  const basePrice = sizeMap[sz] || 0;
  const tmul = TYPE_MULT[cat] || 1;
  const est  = Math.round(basePrice * tmul);

  const isPhysical = (cat === 'Traditional' || cat === 'Painting');
  const shipping   = isPhysical ? currentArtist.shipping : 0;
  const total      = est + shipping;

  let noteText = basePrice > 0
    ? 'Base ₱' + est.toLocaleString() + (shipping > 0 ? ' + ₱' + shipping.toLocaleString() + ' shipping' : '') + ' · Final price set by artist'
    : 'Artist has not set a price for this size yet — final price set by artist';

  document.getElementById('priceEstimateAmt').textContent  = total > 0 ? '₱' + total.toLocaleString() : '₱—';
  document.getElementById('priceEstimateNote').textContent = noteText;
  document.getElementById('priceEstimateBox').classList.add('visible');
  document.getElementById('currentEstimate').value = total;
  estimatedPrice = total;

  
  let shippingEl = document.getElementById('shippingNotice');
  if (!shippingEl) {
    shippingEl = document.createElement('div');
    shippingEl.id = 'shippingNotice';
    shippingEl.style.cssText = 'margin-top:10px;padding:10px 14px;background:var(--navy-light,#e8f4ff);border-radius:8px;font-size:13px;';
    document.getElementById('priceEstimateBox').after(shippingEl);
  }
  if (isPhysical) {
    shippingEl.style.display = '';
    shippingEl.innerHTML = '<i class="fa-solid fa-truck" style="margin-right:6px;"></i><strong>Physical artwork</strong> — the artist will send you the original piece. ' +
      (shipping > 0 ? 'Shipping fee: <strong>₱' + shipping.toLocaleString() + '</strong> (included above).' : 'Shipping fee to be arranged with the artist.');
  } else {
    shippingEl.style.display = 'none';
    shippingEl.innerHTML = '';
  }

  return total;
}
document.getElementById('comm_size').addEventListener('change', updatePrice);
document.getElementById('comm_category').addEventListener('change', updatePrice);

document.getElementById('comm_refs').addEventListener('change', function(){
  const prev = document.getElementById('commRefPreview');
  prev.innerHTML = '';
  Array.from(this.files).slice(0,5).forEach(f=>{
    const r = new FileReader();
    r.onload = ev => { const i=document.createElement('img'); i.src=ev.target.result; i.className='file-preview-thumb'; prev.appendChild(i); };
    r.readAsDataURL(f);
  });
});

function validateForm() {
  const sz   = document.getElementById('comm_size').value;
  const cat  = document.getElementById('comm_category').value;
  const desc = document.getElementById('comm_desc').value.trim();
  if (!sz)          { showToast('Please select a commission size.',false); return false; }
  if (!cat)         { showToast('Please select an art type.',false); return false; }
  if (desc.length<20){ showToast('Description too short (min 20 chars).',false); return false; }
  return true;
}

function collectPayload() {
  if (!estimatedPrice) updatePrice();
  const cat = document.getElementById('comm_category').value;
  const isPhysical = (cat === 'Traditional' || cat === 'Painting');
  const shipping = isPhysical ? (currentArtist.shipping || 0) : 0;
  return {
    action:      'cart_add',
    artistId:    currentArtist.id,
    artistName:  currentArtist.name,
    size:        document.getElementById('comm_size').value,
    type:        cat,
    desc:        document.getElementById('comm_desc').value.trim(),
    payment:     document.getElementById('comm_payment').value,
    price:       estimatedPrice || currentArtist.pmin,
    shippingFee: shipping,
  };
}

// ---- Local pending cart (not yet submitted to server) ----
const PENDING_KEY = 'likhub_pending_cart';
let pendingCart = [];
try { pendingCart = JSON.parse(localStorage.getItem(PENDING_KEY) || '[]'); } catch(e){}

function savePending() { try { localStorage.setItem(PENDING_KEY, JSON.stringify(pendingCart)); } catch(e){} }
function getPendingCount() { return pendingCart.length; }

function renderPendingBadge() {
  const badge = document.getElementById('cartBadge');
  const total = cart.length + pendingCart.length;
  if (total > 0) { badge.textContent = total; badge.classList.add('visible'); }
  else { badge.classList.remove('visible'); }
}
renderPendingBadge();

document.getElementById('btnAddToCart').addEventListener('click', () => {
  if (!validateForm()) return;
  const cat = document.getElementById('comm_category').value;
  const isPhysical = (cat === 'Traditional' || cat === 'Painting');
  const shipping = isPhysical ? (currentArtist.shipping || 0) : 0;
  const files = document.getElementById('comm_refs').files;
  const fileNames = Array.from(files).map(f=>f.name).join(', ');
  const item = {
    _pending: true,
    localId: Date.now() + '_' + Math.random().toString(36).slice(2),
    artistId:   currentArtist.id,
    artistName: currentArtist.name,
    pfp:        currentArtist.pfp,
    size:       document.getElementById('comm_size').value,
    type:       cat,
    desc:       document.getElementById('comm_desc').value.trim(),
    payment:    document.getElementById('comm_payment').value,
    price:      estimatedPrice || currentArtist.pmin,
    shippingFee: shipping,
    fileNames:  fileNames,
    addedAt:    new Date().toISOString(),
  };
  pendingCart.push(item);
  savePending();
  renderCartWithPending();
  showToast('Added to cart — review before submitting!');
  closeModal();
});

async function submitCommissionNow() {
  if (!validateForm()) return;
  const files = document.getElementById("comm_refs").files;
  const res = await api(collectPayload(), files);
  if (res.error) { showToast("Error: " + res.error, false); return; }
  cart = res.cart;
  renderCartWithPending();
  const ref = res.cart[res.cart.length-1]?.ref || "";
  document.getElementById("commissionForm").style.display = "none";
  document.getElementById("commSuccessMsg").textContent = "Reference: " + ref + " — awaiting artist approval.";
  document.getElementById("commSuccess").style.display = "block";
}

document.getElementById("btnSubmitComm").addEventListener("click", submitCommissionNow);

document.getElementById("commissionForm").addEventListener("submit", async e => {
  e.preventDefault();
  await submitCommissionNow();
});

document.getElementById('btnViewCart').addEventListener('click', ()=>{ closeModal(); openCart(); });

function renderCartWithPending() {
  const body    = document.getElementById('cartBody');
  const empty   = document.getElementById('cartEmpty');
  const totalEl = document.getElementById('cartTotal');
  const badge   = document.getElementById('cartBadge');
  const checkout= document.getElementById('btnCheckout');

  body.querySelectorAll('.cart-item, .cart-pending-item, .cart-pending-header, .cart-submit-all-row').forEach(el=>el.remove());

  const allEmpty = !cart.length && !pendingCart.length;
  empty.style.display = allEmpty ? '' : 'none';
  const total_count = cart.length + pendingCart.length;
  if (total_count > 0) { badge.textContent = total_count; badge.classList.add('visible'); }
  else badge.classList.remove('visible');
  checkout.disabled = !cart.some(i=>['Submitted','Approved','In Progress','Payment Pending'].includes(i.status));

  // ---- Pending (local, not yet submitted) ----
  if (pendingCart.length > 0) {
    const hdr = document.createElement('div');
    hdr.className = 'cart-pending-header';
    hdr.innerHTML = '<i class="fa-solid fa-clock"></i> Pending — not submitted yet';
    body.appendChild(hdr);

    pendingCart.forEach(item => {
      const init = item.artistName.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
      const el = document.createElement('div');
      el.className = 'cart-item cart-pending-item';
      el.dataset.localId = item.localId;
      el.innerHTML = `
        <div class="cart-item__avatar">${init}</div>
        <div class="cart-item__info">
          <div class="cart-item__artist">${item.artistName}</div>
          <div class="cart-item__detail">${item.size} · ${item.type} · ${item.payment||'Payment TBD'}</div>
          ${item.fileNames ? `<div style="font-size:11px;color:#64748b;margin-top:2px;"><i class="fa-solid fa-paperclip"></i> ${item.fileNames}</div>` : ''}
          <details class="u-mt-4 cart-item__notes">
            <summary class="u-summary-link"><i class="fa-solid fa-file-lines"></i> View Notes</summary>
            <p style="font-size:12px;color:#64748b;margin:6px 0 0;">${item.desc}</p>
          </details>
          <div style="margin-top:6px;"><span class="status-badge" style="background:#fef3c7;color:#92400e;">Pending</span></div>
        </div>
        <div class="u-cart-item-actions">
          <span class="cart-item__price">₱${item.price.toLocaleString()}</span>
          <div class="cart-item__btns">
            <button class="cart-item__remove" data-pending-remove="${item.localId}" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button>
          </div>
        </div>`;
      body.appendChild(el);
    });

    // Submit pending row
    const submitRow = document.createElement('div');
    submitRow.className = 'cart-submit-all-row';
    submitRow.innerHTML = `<button class="btn btn-primary btn-full" id="btnSubmitPending"><i class="fa-solid fa-paper-plane"></i> Submit ${pendingCart.length} Request${pendingCart.length>1?'s':''}</button><p class="u-text-fine" style="margin-top:6px;">Submitting sends requests to artists for approval.</p>`;
    body.appendChild(submitRow);
  }

  // ---- Submitted / active commissions ----
  let total = 0;
  cart.forEach(item => {
    total += item.price;
    const init = item.artistName.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
    const statusCls = STATUS_CLASS[item.status] || 'status-badge--pending';
    const el = document.createElement('div');
    el.className='cart-item';
    const isPhysical = item.type === 'Traditional' || item.type === 'Painting';
    const shippingFeeVal = item.shipping_fee || 0;
    const shippingLine = (shippingFeeVal > 0 && isPhysical)
      ? `<span style="font-size:11px;color:var(--text-muted);"><i class="fa-solid fa-truck"></i> +₱${shippingFeeVal.toLocaleString()} shipping</span>` : '';
    const deliveryBadge = isPhysical
      ? `<span style="font-size:10px;background:#fff7e6;color:#d97706;padding:2px 6px;border-radius:4px;font-weight:600;">🖼 Physical</span>`
      : `<span style="font-size:10px;background:#f0fdf4;color:#16a34a;padding:2px 6px;border-radius:4px;font-weight:600;">💻 Digital</span>`;
    el.innerHTML=`
      <div class="cart-item__avatar">${init}</div>
      <div class="cart-item__info">
        <div class="cart-item__artist">${item.artistName}</div>
        <div class="cart-item__detail">${item.size} · ${item.type} · ${item.payment||'Payment TBD'}</div>
        <div class="u-mt-4 u-d-flex u-gap-6 u-flex-wrap u-align-center">${deliveryBadge}${shippingLine}</div>
        <details class="u-mt-4 cart-item__notes">
          <summary class="u-summary-link"><i class="fa-solid fa-file-lines"></i> View Notes</summary>
          <p style="font-size:12px;color:#64748b;margin:6px 0 0;">${item.desc}</p>
        </details>
        <div class="u-mt-6">
          <span class="status-badge ${statusCls}">${item.status}</span>
          <span class="u-ref-label">${item.ref}</span>
        </div>
      </div>
      ${item.status==='Approved' ? `
      <div class="cart-payment-prompt">
        <div class="cart-payment-prompt__icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="cart-payment-prompt__body">
          <div class="cart-payment-prompt__title">Artist approved your request!</div>
          <div class="cart-payment-prompt__note">Accept to let the artist begin work. Payment is due once they mark it complete.</div>
        </div>
      </div>` : ''}
      ${item.status==='Payment Pending' ? `
      <div class="cart-payment-prompt">
        <div class="cart-payment-prompt__icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="cart-payment-prompt__body">
          <div class="cart-payment-prompt__title">Work is done — time to pay!</div>
          <div class="cart-payment-prompt__amount">₱${item.price.toLocaleString()}</div>
          <div class="cart-payment-prompt__method">
            Pay via <strong>${item.payment || 'method TBD'}</strong> directly to <strong>${item.artistName}</strong>
          </div>
          <div class="cart-payment-prompt__note">Once you've sent the payment, hit <em>Confirm Payment</em> to complete the commission.</div>
        </div>
      </div>` : ''}
      ${item.status==='Completed' && !item.rating ? `
      <div class="cart-review-prompt">
        <div class="cart-review-prompt__title"><i class="fa-solid fa-star"></i> How was your experience?</div>
        <div class="cart-review-stars" id="inlineStars_${item.ref}">
          ${[1,2,3,4,5].map(n=>`<button class="inline-star-btn" data-ref="${item.ref}" data-val="${n}" title="${n} star">★</button>`).join('')}
        </div>
        <div class="cart-review-prompt__or">or <button class="cart-review-prompt__link" data-action="review" data-ref="${item.ref}" data-artist="${item.artistName}">write a full review</button></div>
      </div>` : ''}
      <div class="u-cart-item-actions">
        <span class="cart-item__price">₱${item.price.toLocaleString()}</span>
        <div class="cart-item__btns">
          ${item.status==='Submitted' ?
            `<button class="cart-item__remove" data-action="remove" data-id="${item.id}" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button>` : ''}
          ${item.status==='Approved' ? `
            <button class="cart-item__accept" data-action="accept" data-ref="${item.ref}" data-artist="${item.artistName}" title="Accept — work begins, pay when done"><i class="fa-solid fa-check"></i> Accept</button>
            <button class="cart-item__decline" data-action="decline" data-ref="${item.ref}" title="Decline offer"><i class="fa-solid fa-xmark"></i> Decline</button>
          ` : ''}
          ${item.status==='Payment Pending' ? `
            <button class="cart-item__accept" data-action="pay_confirm" data-ref="${item.ref}" data-price="${item.price}" data-payment="${item.payment||''}" data-artist="${item.artistName}" title="Confirm you have paid"><i class="fa-solid fa-money-bill"></i> Confirm Payment</button>
          ` : ''}
          ${item.status==='Completed' && !item.rating ? `
            <button class="cart-item__review" data-action="review" data-ref="${item.ref}" data-artist="${item.artistName}" title="Leave a review"><i class="fa-solid fa-star"></i> Review</button>
          ` : ''}
          ${item.status==='Completed' && item.rating ? `
            <span class="cart-item__rated" title="Your rating">${'★'.repeat(item.rating)}${'☆'.repeat(5-item.rating)}</span>
          ` : ''}
          ${item.status==='Completed' && item.download_path ? `
            <a class="cart-item__chat" href="${item.download_path}" download title="Download finished file"><i class="fa-solid fa-download"></i></a>
          ` : ''}
          ${'In Progress,Approved,Payment Pending'.includes(item.status) || item.status==='Submitted' ? `
            <a class="cart-item__chat" href="/likhub/dashboard.php?tab=commissions&thread=${encodeURIComponent(item.ref)}" title="Open chat"><i class="fa-solid fa-comments"></i></a>
          ` : ''}
        </div>
      </div>`;
    body.appendChild(el);
  });

  pendingCart.forEach(i => total += i.price);
  totalEl.textContent='₱'+total.toLocaleString();

  // Event: remove pending
  body.querySelectorAll('[data-pending-remove]').forEach(btn => {
    btn.addEventListener('click', function(){
      const lid = this.dataset.pendingRemove;
      pendingCart = pendingCart.filter(i => i.localId !== lid);
      savePending();
      renderCartWithPending();
      showToast('Removed from cart');
    });
  });

  // Event: submit pending
  const sbtn = document.getElementById('btnSubmitPending');
  if (sbtn) {
    sbtn.addEventListener('click', async () => {
      sbtn.disabled = true;
      sbtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';
      const toSubmit = [...pendingCart];
      let errors = 0;
      for (const item of toSubmit) {
        const payload = {
          action: 'cart_add',
          artistId: item.artistId,
          artistName: item.artistName,
          size: item.size,
          type: item.type,
          desc: item.desc,
          payment: item.payment,
          price: item.price,
          shippingFee: item.shippingFee,
        };
        const res = await api(payload);
        if (res.ok) {
          cart = res.cart;
          pendingCart = pendingCart.filter(i => i.localId !== item.localId);
          savePending();
        } else {
          errors++;
        }
      }
      renderCartWithPending();
      if (errors) showToast(errors + ' item(s) failed to submit.', false);
      else showToast('All requests submitted to artists!');
    });
  }

  // Event: remove submitted
  body.querySelectorAll('[data-action="remove"]').forEach(btn=>{
    btn.addEventListener('click', async function(){
      const res = await api({action:'cart_remove', itemId:this.dataset.id});
      if(res.ok){ cart=res.cart; renderCartWithPending(); showToast('Removed from cart'); }
    });
  });
  body.querySelectorAll('[data-action="accept"]').forEach(btn=>{
    btn.addEventListener('click', async function(){
      const artist = this.dataset.artist || 'the artist';
      if(!confirm(`Accept this commission from ${artist}?\n\nWork will begin — you pay once they mark it complete.`)) return;
      const res = await api({action:'client_accept', ref:this.dataset.ref});
      if(res.ok){ cart=res.cart; renderCartWithPending(); showToast('Commission accepted — the artist will get started! 🎨'); }
      else showToast('Error: '+(res.error||'Unknown'),false);
    });
  });

  body.querySelectorAll('[data-action="pay_confirm"]').forEach(btn=>{
    btn.addEventListener('click', async function(){
      const price   = this.dataset.price   || '?';
      const payment = this.dataset.payment || 'your agreed method';
      const artist  = this.dataset.artist  || 'the artist';
      const msg = `Confirm you have sent ₱${parseInt(price).toLocaleString()} to ${artist} via ${payment}?\n\nOnly confirm once payment has been sent.`;
      if(!confirm(msg)) return;
      const res = await api({action:'client_pay_confirm', ref:this.dataset.ref});
      if(res.ok){ cart=res.cart; renderCartWithPending(); showToast('Payment confirmed — commission complete! 🎉'); }
      else showToast('Error: '+(res.error||'Unknown'),false);
    });
  });

  // Inline star quick-review
  body.querySelectorAll('.inline-star-btn').forEach(btn => {
    btn.addEventListener('mouseenter', function(){
      const ref = this.dataset.ref;
      const val = parseInt(this.dataset.val);
      body.querySelectorAll(`.inline-star-btn[data-ref="${ref}"]`).forEach((b,i)=>{
        b.style.color = i < val ? '#f59e0b' : '#d1d5db';
      });
    });
    btn.addEventListener('mouseleave', function(){
      const ref = this.dataset.ref;
      // Reset to current selection (stored on container)
      const container = document.getElementById('inlineStars_' + ref);
      const cur = container ? parseInt(container.dataset.selected||'0') : 0;
      body.querySelectorAll(`.inline-star-btn[data-ref="${ref}"]`).forEach((b,i)=>{
        b.style.color = i < cur ? '#f59e0b' : '#d1d5db';
      });
    });
    btn.addEventListener('click', async function(){
      const ref = this.dataset.ref;
      const val = parseInt(this.dataset.val);
      const container = document.getElementById('inlineStars_' + ref);
      if(container) container.dataset.selected = val;
      body.querySelectorAll(`.inline-star-btn[data-ref="${ref}"]`).forEach((b,i)=>{
        b.style.color = i < val ? '#f59e0b' : '#d1d5db';
      });
      const res = await api({action:'leave_review', ref, rating:val, review:''});
      if(res.ok){
        cart = res.cart;
        renderCartWithPending();
        showToast('Thanks for the review! ⭐'.repeat(val>3?1:1));
      } else showToast('Error: '+(res.error||'Unknown'),false);
    });
  });

  // "write a full review" link inside quick review
  body.querySelectorAll('.cart-review-prompt__link').forEach(btn => {
    btn.addEventListener('click', function(){
      openReviewModal(this.dataset.ref);
    });
  });
  body.querySelectorAll('[data-action="decline"]').forEach(btn=>{
    btn.addEventListener('click', async function(){
      if(!confirm('Decline this offer?')) return;
      const res = await api({action:'client_decline', ref:this.dataset.ref});
      if(res.ok){ cart=res.cart; renderCartWithPending(); showToast('Offer declined.'); }
      else showToast('Error: '+(res.error||'Unknown'),false);
    });
  });
  body.querySelectorAll('[data-action="review"]').forEach(btn=>{
    btn.addEventListener('click', function(){
      openReviewModal(this.dataset.ref);
    });
  });
}
const cartDrawer   = document.getElementById('cartDrawer');
const cartBackdrop = document.getElementById('cartBackdrop');
function openCart()  { cartDrawer.classList.add('open'); cartBackdrop.classList.add('open'); document.body.style.overflow='hidden'; }
function closeCart() { cartDrawer.classList.remove('open'); cartBackdrop.classList.remove('open'); document.body.style.overflow=''; }
document.getElementById('cartFab').addEventListener('click', openCart);
document.getElementById('cartClose').addEventListener('click', closeCart);
cartBackdrop.addEventListener('click', closeCart);

document.getElementById('btnCheckout').addEventListener('click', ()=>{
  window.location.href = '/likhub/dashboard.php?tab=commissions';
});


// ---- Review Modal ----
const reviewModal = document.getElementById('reviewModal');
let reviewRef = '';
let selectedReviewRating = 0;

function openReviewModal(ref) {
  reviewRef = ref;
  selectedReviewRating = 0;
  document.getElementById('reviewRef').value = ref;
  document.getElementById('reviewRating').value = 0;
  document.getElementById('reviewText').value = '';
  document.querySelectorAll('.star-pick-btn').forEach(b => b.style.color = '#d1d5db');
  // Set artist name
  const cartItem = cart.find(i => i.ref === ref);
  document.getElementById('reviewArtistName').textContent = cartItem ? cartItem.artistName : 'the artist';
  reviewModal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeReviewModal() {
  reviewModal.style.display = 'none';
  document.body.style.overflow = '';
}
document.getElementById('reviewModalClose').addEventListener('click', closeReviewModal);
document.getElementById('reviewModalCancel').addEventListener('click', closeReviewModal);
reviewModal.addEventListener('click', e => { if(e.target === reviewModal) closeReviewModal(); });

document.querySelectorAll('.star-pick-btn').forEach((btn, i) => {
  btn.addEventListener('mouseenter', () => {
    document.querySelectorAll('.star-pick-btn').forEach((b,j) => b.style.color = j <= i ? '#f59e0b' : '#d1d5db');
  });
  btn.addEventListener('mouseleave', () => {
    document.querySelectorAll('.star-pick-btn').forEach((b,j) => b.style.color = j < selectedReviewRating ? '#f59e0b' : '#d1d5db');
  });
  btn.addEventListener('click', () => {
    selectedReviewRating = i + 1;
    document.getElementById('reviewRating').value = selectedReviewRating;
    document.querySelectorAll('.star-pick-btn').forEach((b,j) => b.style.color = j < selectedReviewRating ? '#f59e0b' : '#d1d5db');
  });
});

document.getElementById('reviewSubmitBtn').addEventListener('click', async () => {
  const rating = parseInt(document.getElementById('reviewRating').value, 10);
  if (rating < 1) { showToast('Please select a star rating.', false); return; }
  const review = document.getElementById('reviewText').value.trim();
  const res = await api({action:'leave_review', ref:reviewRef, rating, review});
  if (res.error) { showToast('Error: '+res.error, false); return; }
  cart = res.cart;
  renderCartWithPending();
  closeReviewModal();
  showToast('Review submitted! Thank you ⭐');
});

document.querySelectorAll('.artist-card__cover').forEach(cover=>{
  const slides=cover.querySelectorAll('.slide'), dots=cover.querySelectorAll('.slide-dot');
  if(slides.length<=1) return;
  let cur=0, timer;
  function goTo(n){ slides[cur].classList.remove('slide--active'); dots[cur]?.classList.remove('slide-dot--active'); cur=(n+slides.length)%slides.length; slides[cur].classList.add('slide--active'); dots[cur]?.classList.add('slide-dot--active'); }
  function start(){ timer=setInterval(()=>goTo(cur+1),2800); }
  function stop(){ clearInterval(timer); }
  start();
  cover.addEventListener('mouseenter',stop);
  cover.addEventListener('mouseleave',start);
});
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>