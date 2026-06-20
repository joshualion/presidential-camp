const bootstrap = () => window.CampaignManager || {};

export const cmRoute = (key, params = {}) => {
  let url = bootstrap().routes?.[key] || '';

  Object.entries(params || {}).forEach(([name, value]) => {
    url = url.replace(`__${name.toUpperCase()}__`, encodeURIComponent(value ?? ''));
  });

  return url;
};

export const cmMedia = (key, filename = null) => {
  const base = bootstrap().media?.[key] || bootstrap().mediaBaseUrl || bootstrap().baseUrl || '';
  const normalizedBase = String(base).replace(/\/+$/, '');

  if (!filename) {
    return normalizedBase;
  }

  return `${normalizedBase}/${String(filename).replace(/^\/+/, '')}`;
};

export const cmUrl = (path = '') => {
  const base = String(bootstrap().baseUrl || '').replace(/\/+$/, '');
  const cleanPath = String(path || '').replace(/^\/+/, '');

  return cleanPath ? `${base}/${cleanPath}` : base;
};

export const cmIntervals = () => ({
  notifications: 30000,
  feed: 45000,
  message_thread: 7000,
  conversation_list: 20000,
  message_badge: 30000,
  ...(bootstrap().community?.intervals || {}),
});

export const cmRealtime = () => ({
  mode: bootstrap().community?.mode || 'polling',
  pollingMode: bootstrap().community?.pollingMode !== false,
  realtimeAvailable: !!bootstrap().community?.realtimeAvailable,
});
