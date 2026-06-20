const config = window.zadoraLms || {};

export async function api(path, options = {}) {
  const response = await fetch(`${config.apiRoot}${path}`, {
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': config.nonce,
      ...(options.headers || {})
    },
    ...options
  });

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    throw new Error(data?.message || 'Request failed.');
  }

  return data;
}
