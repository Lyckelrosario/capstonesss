export class ApiError extends Error {
  status: number;
  details?: Record<string, string[]>;

  constructor(message: string, status: number, details?: Record<string, string[]>) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.details = details;
  }
}

function csrfToken(): string {
  return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

export async function api<T>(url: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');
  headers.set('X-CSRF-TOKEN', csrfToken());
  headers.set('X-Requested-With', 'XMLHttpRequest');

  if (options.body && !(options.body instanceof FormData) && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json');
  }

  const response = await fetch(url, {
    ...options,
    headers,
    credentials: 'same-origin',
  });

  const payload = await response.json().catch(() => ({})) as {
    message?: string;
    errors?: Record<string, string[]>;
  };

  if (!response.ok) {
    const firstValidationError = payload.errors
      ? Object.values(payload.errors).flat()[0]
      : undefined;
    throw new ApiError(firstValidationError ?? payload.message ?? 'The request could not be completed.', response.status, payload.errors);
  }

  return payload as T;
}
