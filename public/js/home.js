document.addEventListener('DOMContentLoaded', async () => {
  await loadPublicSettings();
  await showHomeProducts();
});

async function showHomeProducts() {
  const box = document.getElementById('homeProducts');
  if (!box) return;

  try {
    const data = await api('/foods');
    const foods = (data.foods || []).slice(0, 3);
    if (!foods.length) {
      box.innerHTML = '<div class="empty-menu">به زودی غذاهای ویژه اینجا نمایش داده می‌شوند</div>';
      return;
    }

    box.innerHTML = foods.map((product) => `
      <article class="food-card" data-id="${product.id}">
        <div class="food-image-hover">
          <img
            src="${escapeHtml(foodImage(product.image))}"
            alt="${escapeHtml(product.name)}"
            loading="lazy"
            decoding="async"
            onerror="this.src='images/default-food.jpg'"
          >
          <div class="food-overlay">
            <h2>${escapeHtml(product.name)}</h2>
            <p>${formatPrice(startingPrice(product))} تومان</p>
          </div>
        </div>
      </article>
    `).join('');

    box.querySelectorAll('.food-card').forEach((card) => {
      card.addEventListener('click', () => {
        location.href = 'food.html?id=' + card.dataset.id;
      });
    });
  } catch (_error) {
    box.innerHTML = '<div class="empty-menu">بارگذاری پیشنهادها با مشکل مواجه شد</div>';
  }
}
