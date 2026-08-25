async function api(path, { method = 'GET', body, headers } = {}) {
  const isForm = typeof FormData !== 'undefined' && body instanceof FormData;
  const response = await fetch(`/api${path}`, {
    method,
    credentials: 'include',
    headers: isForm ? { ...headers } : { 'Content-Type': 'application/json', ...headers },
    body: body == null ? undefined : (isForm ? body : JSON.stringify(body))
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const error = new Error(data.message || 'خطای سرور');
    error.status = response.status;
    throw error;
  }
  return data;
}

window.api = api;
