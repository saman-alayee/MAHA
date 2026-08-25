function renderFoodCard(product) {
  return `
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
  `;
}

function showProducts(list) {
  const container = document.getElementById('products');
  if (!container) return;

  if (!list.length) {
    container.innerHTML = `
      <div class="empty-menu">
        <h1>غذایی برای نمایش وجود ندارد</h1>
      </div>
    `;
    return;
  }

  container.innerHTML = list.map(renderFoodCard).join('');
  container.querySelectorAll('.food-card').forEach((card) => {
    card.addEventListener('click', () => {
      location.href = 'food.html?id=' + card.dataset.id;
    });
  });
}

function setActiveCategoryButton(button) {
  document.querySelectorAll('.menu-btn').forEach((btn) => btn.classList.remove('active'));
  if (button) button.classList.add('active');
}

async function filterMenu(category, button) {
  setActiveCategoryButton(button);
  const container = document.getElementById('products');
  if (container) {
    container.innerHTML = '<div class="empty-menu">در حال بارگذاری منو...</div>';
  }

  try {
    const query = category && category !== 'همه' ? `?category=${encodeURIComponent(category)}` : '';
    const data = await api('/foods' + query);
    showProducts(data.foods || []);
  } catch (_error) {
    if (container) {
      container.innerHTML = '<div class="empty-menu">بارگذاری منو با مشکل مواجه شد</div>';
    }
  }
}

function bindCategoryButtons() {
  document.querySelectorAll('.menu-btn').forEach((button) => {
    button.addEventListener('click', () => filterMenu(button.dataset.category, button));
  });
}

function renderCategoryButtons(categories) {
  const wrap = document.querySelector('.category-menu');
  if (!wrap) return;

  const buttons = [
    `<button type="button" class="menu-btn active" data-category="همه">همه</button>`
  ];

  categories.forEach((category) => {
    const label = `${category.icon ? category.icon + ' ' : ''}${category.name}`;
    buttons.push(
      `<button type="button" class="menu-btn" data-category="${escapeHtml(category.name)}">${escapeHtml(label)}</button>`
    );
  });

  wrap.innerHTML = buttons.join('');
  bindCategoryButtons();
}

document.addEventListener('DOMContentLoaded', async () => {
  loadPublicSettings();
  const container = document.getElementById('products');
  if (!container) return;

  container.innerHTML = '<div class="empty-menu">در حال بارگذاری منو...</div>';

  try {
    const categoryData = await api('/categories');
    renderCategoryButtons(categoryData.categories || []);
    await filterMenu('همه', document.querySelector('.menu-btn'));
  } catch (_error) {
    bindCategoryButtons();
    try {
      await filterMenu('همه', document.querySelector('.menu-btn'));
    } catch (_foodError) {
      container.innerHTML = '<div class="empty-menu">بارگذاری منو با مشکل مواجه شد</div>';
    }
  }
});
