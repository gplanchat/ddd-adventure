// ✅ API Client Gyroscops Cloud (Projet Gyroscops Cloud)
export class HiveApiClient {
  private baseURL: string;
  private token: string | null = null;
  private refreshToken: string | null = null;
  private refreshPromise: Promise<string> | null = null;
  private requestQueue: Array<() => Promise<any>> = [];
  private isRefreshing = false;

  constructor(baseURL: string) {
    this.baseURL = baseURL;
    this.loadTokensFromStorage();
    this.setupTokenRefreshInterceptor();
  }

  private loadTokensFromStorage(): void {
    this.token = localStorage.getItem('hive_token');
    this.refreshToken = localStorage.getItem('hive_refresh_token');
  }

  private saveTokensToStorage(): void {
    if (this.token) {
      localStorage.setItem('hive_token', this.token);
    }
    if (this.refreshToken) {
      localStorage.setItem('hive_refresh_token', this.refreshToken);
    }
  }

  private setupTokenRefreshInterceptor(): void {
    // Intercepteur pour gérer le refresh automatique des tokens
    const originalRequest = this.request.bind(this);
    
    this.request = async <T>(endpoint: string, options: RequestInit = {}): Promise<T> => {
      // Vérifier si le token est expiré
      if (this.token && this.isTokenExpired(this.token)) {
        await this.refreshAccessToken();
      }

      try {
        return await originalRequest<T>(endpoint, options);
      } catch (error) {
        if (error instanceof HiveApiError && error.status === 401) {
          // Token expiré, essayer de le rafraîchir
          await this.refreshAccessToken();
          
          // Retry la requête avec le nouveau token
          return await originalRequest<T>(endpoint, options);
        }
        throw error;
      }
    };
  }

  async request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    const url = `${this.baseURL}${endpoint}`;
    
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(this.token && { Authorization: `Bearer ${this.token}` }),
      ...options.headers,
    };

    try {
      const response = await fetch(url, {
        ...options,
        headers,
      });

      if (!response.ok) {
        throw new HiveApiError(`API Error: ${response.status}`, response.status);
      }

      return response.json();
    } catch (error) {
      if (error instanceof HiveApiError) {
        throw error;
      }
      throw new HiveApiError('Network error', 0);
    }
  }

  private isTokenExpired(token: string): boolean {
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      return payload.exp * 1000 < Date.now();
    } catch {
      return true;
    }
  }

  private async refreshAccessToken(): Promise<void> {
    if (this.isRefreshing) {
      // Si un refresh est déjà en cours, attendre qu'il se termine
      return new Promise((resolve, reject) => {
        this.requestQueue.push(() => {
          if (this.token) {
            resolve();
          } else {
            reject(new Error('Token refresh failed'));
          }
        });
      });
    }

    this.isRefreshing = true;

    try {
      const newToken = await this.performTokenRefresh();
      this.token = newToken;
      this.saveTokensToStorage();
      
      // Exécuter toutes les requêtes en attente
      this.requestQueue.forEach(callback => callback());
      this.requestQueue = [];
    } catch (error) {
      // En cas d'erreur, rejeter toutes les requêtes en attente
      this.requestQueue.forEach(callback => callback());
      this.requestQueue = [];
      throw error;
    } finally {
      this.isRefreshing = false;
    }
  }

  private async performTokenRefresh(): Promise<string> {
    if (!this.refreshToken) {
      throw new HiveApiError('No refresh token available', 401);
    }

    const response = await fetch(`${this.baseURL}/api/auth/refresh`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        refresh_token: this.refreshToken,
      }),
    });

    if (!response.ok) {
      throw new HiveApiError('Token refresh failed', response.status);
    }

    const data = await response.json();
    this.refreshToken = data.refresh_token;
    
    return data.access_token;
  }

  setTokens(token: string, refreshToken: string): void {
    this.token = token;
    this.refreshToken = refreshToken;
    this.saveTokensToStorage();
  }

  clearTokens(): void {
    this.token = null;
    this.refreshToken = null;
    localStorage.removeItem('hive_token');
    localStorage.removeItem('hive_refresh_token');
  }

  isAuthenticated(): boolean {
    return this.token !== null && !this.isTokenExpired(this.token);
  }
}

// ✅ Classe d'erreur personnalisée
export class HiveApiError extends Error {
  constructor(message: string, public status: number) {
    super(message);
    this.name = 'HiveApiError';
  }
}

// ✅ Types TypeScript pour l'API
export interface AuthResponse {
  access_token: string;
  refresh_token: string;
  expires_in: number;
  token_type: string;
}

export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  roles: string[];
  organizationId: string;
}

export interface Payment {
  id: string;
  organizationId: string;
  customerName: string;
  customerEmail: string;
  amount: number;
  currency: string;
  status: string;
  createdAt: string;
  updatedAt: string;
}

export interface PaginationRequest {
  page: number;
  pageSize: number;
}

export interface PaginationResponse {
  data: any[];
  pagination: {
    page: number;
    pageSize: number;
    total: number;
    totalPages: number;
  };
}
