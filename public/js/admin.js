document.addEventListener('DOMContentLoaded', async () => {
  const loginBox = document.getElementById('loginBox');
  const adminPanel = document.getElementById('adminPanel');
  const messageEl = document.getElementById('adminMessage');
  const foodForm = document.getElementById('foodForm');
  const categorySelect = document.getElementById('category');
  const sizeBox = document.getElementById('pizzaSizes');
  const sizeList = document.getElementById('sizeList');
  const priceInput = document.getElementById('price');
  const imagePreview = document.getElementById('imagePreview');
  const imageInput = document.getElementById('image');
  const foodList = document.getElementById('foodList');
  const categoryList = document.getElementById('categoryList');
  const saveEditBtn = document.getElementById('saveEditBtn');
  const addFoodBtn = document.getElementById('addFoodBtn');
  const cancelEditBtn = document.getElementById('cancelEditBtn');

  let foods = [];
  let categories = [];
  let editId = null;

  const defaultSizes = [
    { label: 'مینی', price: '' },
    { label: 'یک نفره', price: '' },
    { label: 'دو نفره', price: '' },
    { label: 'خانواده', price: '' }
  ];

  function showMessage(text, type = 'ok') {
    if (messageEl) {
      messageEl.hidden = false;
      messageEl.textContent = text;
      messageEl.className = 'admin-message ' + type;
    }
    alert(text);
  }

  function selectedCategory() {
    return categories.find((item) => String(item.id) === String(categorySelect.value));
  }

  function renderSizeRows(sizes) {
    sizeList.innerHTML = (sizes.length ? sizes : defaultSizes).map((size) => `
      <div class="size-row">
        <input class="size-label" value="${escapeHtml(size.label || '')}" placeholder="نام سایز">
        <input class="size-price" type="number" min="0" value="${size.price ?? ''}" placeholder="قیمت">
        <button type="button" class="ghost-btn remove-size">حذف</button>
      </div>
    `).join('');

    sizeList.querySelectorAll('.remove-size').forEach((btn) => {
      btn.addEventListener('click', () => btn.parentElement.remove());
    });
  }

  function readSizes() {
    return Array.from(sizeList.querySelectorAll('.size-row')).map((row) => ({
      label: row.querySelector('.size-label').value.trim(),
      price: Number(row.querySelector('.size-price').value || 0)
    })).filter((item) => item.label);
  }

  function togglePriceMode() {
    const category = selectedCategory();
    const hasSizes = Boolean(category?.hasSizes);
    sizeBox.style.display = hasSizes ? 'block' : 'none';
    priceInput.style.display = hasSizes ? 'none' : 'block';
    if (hasSizes && !sizeList.children.length) renderSizeRows(defaultSizes);
  }

  function fillCategories(selectedId) {
    categorySelect.innerHTML = categories.map((item) => `
      <option value="${item.id}" ${String(item.id) === String(selectedId) ? 'selected' : ''}>
        ${escapeHtml(item.name)}
      </option>
    `).join('');
    togglePriceMode();
  }

  function resetFoodForm() {
    editId = null;
    foodForm.reset();
    document.getElementById('isActive').checked = true;
    imagePreview.hidden = true;
    imagePreview.removeAttribute('src');
    renderSizeRows(defaultSizes);
    saveEditBtn.style.display = 'none';
    cancelEditBtn.style.display = 'none';
    addFoodBtn.style.display = 'block';
    fillCategories();
  }

  function renderFoods() {
    if (!foods.length) {
      foodList.innerHTML = '<div class="empty-menu">غذایی ثبت نشده است</div>';
      return;
    }

    foodList.innerHTML = foods.map((food, index) => {
      const price = food.sizes
        ? food.sizes.map((size) => `<div>${escapeHtml(size[0])} : ${formatPrice(size[1])} تومان</div>`).join('')
        : `<div>${formatPrice(food.price)} تومان</div>`;

      return `
        <div class="admin-food-card ${food.isActive ? '' : 'is-hidden'}">
          <img src="${escapeHtml(foodImage(food.image))}" alt="" onerror="this.src='images/default-food.jpg'">
          <h3>${escapeHtml(food.name)}</h3>
          <p>${escapeHtml(food.category)}</p>
          <p>${escapeHtml(food.description || '')}</p>
          <div>${price}</div>
          <p class="status-pill">${food.isActive ? 'نمایش در سایت' : 'مخفی'}</p>
          <div class="admin-actions">
            <button type="button" data-edit="${food.id}">ویرایش</button>
            <button type="button" data-toggle="${food.id}">${food.isActive ? 'مخفی کردن' : 'نمایش'}</button>
            <button type="button" data-up="${food.id}" ${index === 0 ? 'disabled' : ''}>بالا</button>
            <button type="button" data-down="${food.id}" ${index === foods.length - 1 ? 'disabled' : ''}>پایین</button>
            <button type="button" data-delete="${food.id}">حذف</button>
          </div>
        </div>
      `;
    }).join('');
  }

  function renderCategories() {
    if (!categories.length) {
      categoryList.innerHTML = '<div class="empty-menu">دسته‌بندی‌ای ثبت نشده است</div>';
      return;
    }

    categoryList.innerHTML = categories.map((item, index) => `
      <div class="admin-food-card">
        <h3>${escapeHtml(item.icon || '')} ${escapeHtml(item.name)}</h3>
        <p>${item.hasSizes ? 'دارای سایز و قیمت‌های جدا' : 'یک قیمت'}</p>
        <p class="status-pill">${item.isActive ? 'فعال' : 'غیرفعال'}</p>
        <div class="admin-actions">
          <button type="button" data-cat-edit="${item.id}">ویرایش</button>
          <button type="button" data-cat-up="${item.id}" ${index === 0 ? 'disabled' : ''}>بالا</button>
          <button type="button" data-cat-down="${item.id}" ${index === categories.length - 1 ? 'disabled' : ''}>پایین</button>
          <button type="button" data-cat-delete="${item.id}">حذف</button>
        </div>
      </div>
    `).join('');
  }

  function fillSettings(settings) {
    document.querySelectorAll('[data-settings-input]').forEach((input) => {
      const key = input.getAttribute('data-settings-input');
      input.value = settings[key] || '';
    });
  }

  async function loadAdminData() {
    const [foodData, categoryData, settingData] = await Promise.all([
      api('/admin/foods'),
      api('/admin/categories'),
      api('/admin/settings')
    ]);
    foods = foodData.foods || [];
    categories = categoryData.categories || [];
    fillCategories();
    renderFoods();
    renderCategories();
    fillSettings(settingData.settings || {});
  }

  function showPanel() {
    loginBox.style.display = 'none';
    adminPanel.style.display = 'block';
    const hash = (location.hash || '').replace('#', '');
    if (hash) switchTab(hash);
  }

  function switchTab(name) {
    document.querySelectorAll('.admin-tab').forEach((tab) => {
      tab.classList.toggle('active', tab.dataset.tab === name);
    });
    document.querySelectorAll('.admin-section').forEach((section) => {
      section.classList.toggle('active', section.id === 'tab-' + name);
    });
  }

  document.querySelectorAll('.admin-tab').forEach((tab) => {
    tab.addEventListener('click', () => switchTab(tab.dataset.tab));
  });

  document.getElementById('loginForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      await api('/auth/login', {
        method: 'POST',
        body: {
          username: document.getElementById('username').value.trim(),
          password: document.getElementById('password').value
        }
      });
      showPanel();
      await loadAdminData();
      alert('ورود با موفقیت انجام شد');
    } catch (error) {
      alert(error.message || 'رمز اشتباه است');
    }
  });

  document.getElementById('logoutBtn').addEventListener('click', async () => {
    try {
      await api('/auth/logout', { method: 'POST' });
    } catch (_error) {
      // reload anyway
    }
    alert('از پنل خارج شدید');
    location.reload();
  });

  categorySelect.addEventListener('change', togglePriceMode);

  document.getElementById('addSizeBtn').addEventListener('click', () => {
    const current = readSizes();
    current.push({ label: '', price: '' });
    renderSizeRows(current);
  });

  imageInput.addEventListener('change', () => {
    const file = imageInput.files[0];
    if (!file) {
      imagePreview.hidden = true;
      return;
    }
    imagePreview.src = URL.createObjectURL(file);
    imagePreview.hidden = false;
  });

  function buildFoodFormData(isUpdate) {
    const formData = new FormData();
    formData.append('name', document.getElementById('name').value.trim());
    formData.append('categoryId', categorySelect.value);
    formData.append('description', document.getElementById('description').value.trim());
    formData.append('isActive', document.getElementById('isActive').checked ? 'true' : 'false');

    const category = selectedCategory();
    if (category?.hasSizes) {
      formData.append('sizes', JSON.stringify(readSizes()));
    } else {
      formData.append('price', document.getElementById('price').value || '0');
      formData.append('sizes', JSON.stringify([]));
    }

    if (imageInput.files[0]) formData.append('image', imageInput.files[0]);
    if (isUpdate) formData.append('_method', 'PUT');
    return formData;
  }

  addFoodBtn.addEventListener('click', async () => {
    if (!document.getElementById('name').value.trim()) {
      alert('نام غذا را وارد کنید');
      return;
    }
    try {
      await api('/admin/foods', { method: 'POST', body: buildFoodFormData() });
      await loadAdminData();
      resetFoodForm();
      showMessage('غذا با موفقیت اضافه شد');
    } catch (error) {
      alert(error.message);
    }
  });

  saveEditBtn.addEventListener('click', async () => {
    if (!editId) return;
    try {
      await api('/admin/foods/' + editId, { method: 'POST', body: buildFoodFormData(true) });
      await loadAdminData();
      resetFoodForm();
      showMessage('تغییرات غذا با موفقیت ذخیره شد');
    } catch (error) {
      alert(error.message);
    }
  });

  cancelEditBtn.addEventListener('click', () => {
    resetFoodForm();
    alert('ویرایش لغو شد');
  });

  foodList.addEventListener('click', async (event) => {
    const target = event.target;
    try {
      if (target.dataset.edit) {
        const food = foods.find((item) => String(item.id) === String(target.dataset.edit));
        if (!food) return;
        editId = food.id;
        document.getElementById('name').value = food.name;
        fillCategories(food.categoryId);
        document.getElementById('description').value = food.description || '';
        document.getElementById('price').value = food.price || '';
        document.getElementById('isActive').checked = food.isActive;
        if (food.image) {
          imagePreview.src = foodImage(food.image);
          imagePreview.hidden = false;
        }
        if (food.sizeList && food.sizeList.length) {
          renderSizeRows(food.sizeList.map((size) => ({ label: size.label, price: size.price })));
        } else {
          renderSizeRows(defaultSizes);
        }
        togglePriceMode();
        saveEditBtn.style.display = 'block';
        cancelEditBtn.style.display = 'block';
        addFoodBtn.style.display = 'none';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        alert('غذا برای ویرایش آماده است. تغییرات را بدهید و «ذخیره تغییرات» را بزنید.');
      }

      if (target.dataset.delete) {
        if (!confirm('این غذا حذف شود؟')) return;
        await api('/admin/foods/' + target.dataset.delete, { method: 'DELETE' });
        await loadAdminData();
        showMessage('غذا با موفقیت حذف شد');
      }

      if (target.dataset.toggle) {
        const food = foods.find((item) => String(item.id) === String(target.dataset.toggle));
        await api('/admin/foods/' + target.dataset.toggle, {
          method: 'PATCH',
          body: { isActive: !food.isActive }
        });
        await loadAdminData();
        showMessage(food.isActive ? 'غذا از منو مخفی شد' : 'غذا در سایت نمایش داده می‌شود');
      }

      if (target.dataset.up || target.dataset.down) {
        const id = Number(target.dataset.up || target.dataset.down);
        const index = foods.findIndex((item) => item.id === id);
        const swapWith = target.dataset.up ? index - 1 : index + 1;
        if (swapWith < 0 || swapWith >= foods.length) return;
        const ids = foods.map((item) => item.id);
        const current = ids[index];
        ids[index] = ids[swapWith];
        ids[swapWith] = current;
        await api('/admin/foods/reorder', { method: 'PUT', body: { ids } });
        await loadAdminData();
        showMessage('ترتیب نمایش غذا عوض شد');
      }
    } catch (error) {
      alert(error.message);
    }
  });

  document.getElementById('categoryForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const id = document.getElementById('categoryId').value;
    const payload = {
      name: document.getElementById('categoryName').value.trim(),
      icon: document.getElementById('categoryIcon').value.trim(),
      hasSizes: document.getElementById('categoryHasSizes').checked,
      isActive: document.getElementById('categoryActive').checked
    };
    try {
      if (id) {
        await api('/admin/categories/' + id, { method: 'PUT', body: payload });
      } else {
        await api('/admin/categories', { method: 'POST', body: payload });
      }
      document.getElementById('categoryForm').reset();
      document.getElementById('categoryId').value = '';
      document.getElementById('categoryActive').checked = true;
      document.getElementById('categorySubmit').textContent = 'افزودن دسته‌بندی';
      await loadAdminData();
      showMessage(id ? 'دسته‌بندی با موفقیت ویرایش شد' : 'دسته‌بندی با موفقیت اضافه شد');
    } catch (error) {
      alert(error.message);
    }
  });

  categoryList.addEventListener('click', async (event) => {
    const target = event.target;
    try {
      if (target.dataset.catEdit) {
        const item = categories.find((cat) => String(cat.id) === String(target.dataset.catEdit));
        if (!item) return;
        document.getElementById('categoryId').value = item.id;
        document.getElementById('categoryName').value = item.name;
        document.getElementById('categoryIcon').value = item.icon || '';
        document.getElementById('categoryHasSizes').checked = item.hasSizes;
        document.getElementById('categoryActive').checked = item.isActive;
        document.getElementById('categorySubmit').textContent = 'ذخیره دسته‌بندی';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        alert('دسته برای ویرایش آماده است. بعد از تغییر، «ذخیره دسته‌بندی» را بزنید.');
      }

      if (target.dataset.catDelete) {
        if (!confirm('این دسته‌بندی حذف شود؟')) return;
        await api('/admin/categories/' + target.dataset.catDelete, { method: 'DELETE' });
        await loadAdminData();
        showMessage('دسته‌بندی با موفقیت حذف شد');
      }

      if (target.dataset.catUp || target.dataset.catDown) {
        const id = Number(target.dataset.catUp || target.dataset.catDown);
        const index = categories.findIndex((item) => item.id === id);
        const swapWith = target.dataset.catUp ? index - 1 : index + 1;
        if (swapWith < 0 || swapWith >= categories.length) return;
        const ids = categories.map((item) => item.id);
        const current = ids[index];
        ids[index] = ids[swapWith];
        ids[swapWith] = current;
        await api('/admin/categories/reorder', { method: 'PUT', body: { ids } });
        await loadAdminData();
        showMessage('ترتیب دسته‌بندی عوض شد');
      }
    } catch (error) {
      alert(error.message);
    }
  });

  document.getElementById('settingsForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const settings = {};
    document.querySelectorAll('[data-settings-input]').forEach((input) => {
      settings[input.getAttribute('data-settings-input')] = input.value.trim();
    });
    try {
      await api('/admin/settings', { method: 'PUT', body: { settings } });
      showMessage('اطلاعات و متن‌های سایت با موفقیت ذخیره شد');
    } catch (error) {
      alert(error.message);
    }
  });

  document.getElementById('passwordForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      await api('/admin/password', {
        method: 'PUT',
        body: {
          currentPassword: document.getElementById('currentPassword').value,
          newPassword: document.getElementById('newPassword').value
        }
      });
      event.target.reset();
      showMessage('رمز عبور با موفقیت تغییر کرد');
    } catch (error) {
      alert(error.message);
    }
  });

  try {
    await api('/auth/me');
    showPanel();
    await loadAdminData();
    renderSizeRows(defaultSizes);
  } catch (_error) {
    loginBox.style.display = 'block';
    adminPanel.style.display = 'none';
    renderSizeRows(defaultSizes);
  }
});
