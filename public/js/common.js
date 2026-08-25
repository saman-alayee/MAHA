function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatPrice(price) {
  return Number(price || 0).toLocaleString();
}

function foodImage(src) {
  return src || 'images/default-food.jpg';
}

function startingPrice(product) {
  if (product.sizes && product.sizes.length) {
    return Number(product.sizes[0][1] || 0);
  }
  return Number(product.price || 0);
}

function applySettings(settings) {
  if (!settings) return;

  document.querySelectorAll('[data-setting]').forEach((el) => {
    const key = el.getAttribute('data-setting');
    if (settings[key]) el.textContent = settings[key];
  });

  const phone = settings.phone || '-';
  const address = settings.address || '-';
  const hours = settings.hours || '-';

  const contactPhone = document.getElementById('contactPhone');
  const contactAddress = document.getElementById('contactAddress');
  const contactHours = document.getElementById('contactHours');
  if (contactPhone) contactPhone.textContent = '📞  ' + phone;
  if (contactHours) contactHours.textContent = '⏰ ' + hours;
  if (contactAddress) contactAddress.textContent = '📍 ' + address;

  const footerPhone = document.getElementById('footerPhone');
  const footerAddress = document.getElementById('footerAddress');
  if (footerPhone) footerPhone.textContent = ' ' + phone;
  if (footerAddress) footerAddress.textContent = address;

  const weekly = {
    hoursSaturday: settings.hours_saturday,
    hoursSunday: settings.hours_sunday,
    hoursMonday: settings.hours_monday,
    hoursTuesday: settings.hours_tuesday,
    hoursWednesday: settings.hours_wednesday,
    hoursThursday: settings.hours_thursday,
    hoursFriday: settings.hours_friday
  };
  Object.entries(weekly).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el && value) el.textContent = value;
  });

  const callBtn = document.getElementById('callBtn');
  if (callBtn) {
    const digits = toLatinDigits(phone).replace(/\D/g, '');
    if (digits) callBtn.href = 'tel:' + digits;
  }

  const map = document.getElementById('contactMap');
  if (map && settings.address) {
    map.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(settings.address) + '&z=16&output=embed';
  }

  if (settings.instagram_url) {
    document.querySelectorAll('.follow').forEach((el) => {
      el.innerHTML = '<a href="' + escapeHtml(settings.instagram_url) + '" target="_blank" rel="noopener">ما را در اینستاگرام دنبال کنید</a>';
    });
  }
}

function toLatinDigits(value) {
  const fa = '۰۱۲۳۴۵۶۷۸۹';
  const ar = '٠١٢٣٤٥٦٧٨٩';
  return String(value ?? '').replace(/[۰-۹٠-٩]/g, (ch) => {
    const faIndex = fa.indexOf(ch);
    if (faIndex >= 0) return String(faIndex);
    const arIndex = ar.indexOf(ch);
    return arIndex >= 0 ? String(arIndex) : ch;
  });
}

async function loadPublicSettings() {
  try {
    const data = await api('/settings');
    applySettings(data.settings);
  } catch (_error) {
    // متن‌های موجود در HTML به‌عنوان پیش‌فرض باقی می‌مانند
  }
}

window.escapeHtml = escapeHtml;
window.formatPrice = formatPrice;
window.foodImage = foodImage;
window.startingPrice = startingPrice;
window.applySettings = applySettings;
window.loadPublicSettings = loadPublicSettings;
