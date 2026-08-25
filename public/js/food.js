function sizeHtml(product) {
  if (product.sizes && product.sizes.length) {
    return `
      <div class="detail-sizes">
        <h3>انتخاب اندازه</h3>
        ${product.sizes.map((size) => `
          <div class="detail-size">
            <span>${escapeHtml(size[0])}</span>
            <strong>${formatPrice(size[1])} تومان</strong>
          </div>
        `).join('')}
      </div>
    `;
  }

  return `
    <div class="detail-price">
      ${formatPrice(product.price)}
      <span>تومان</span>
    </div>
  `;
}

async function showFoodDetails() {
  const box = document.getElementById('foodDetails');
  if (!box) return;

  const id = new URLSearchParams(location.search).get('id');
  if (!id) {
    box.innerHTML = '<div class="empty-menu"><h1>غذا پیدا نشد</h1></div>';
    return;
  }

  try {
    const data = await api('/foods/' + encodeURIComponent(id));
    const product = data.food;
    box.innerHTML = `
      <div class="food-detail-card">
        <div class="food-detail-image">
          <img
            src="${escapeHtml(foodImage(product.image))}"
            alt="${escapeHtml(product.name)}"
            onerror="this.src='images/default-food.jpg'"
          >
        </div>
        <div class="food-detail-info">
          <span class="food-category">${escapeHtml(product.category)}</span>
          <h1>${escapeHtml(product.name)}</h1>
          <p>${escapeHtml(product.description || '')}</p>
          ${sizeHtml(product)}
          <a href="menu.html" class="food-details">بازگشت به منو</a>
        </div>
      </div>
    `;
  } catch (_error) {
    box.innerHTML = '<div class="empty-menu"><h1>غذا پیدا نشد</h1></div>';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  loadPublicSettings();
  showFoodDetails();
});
