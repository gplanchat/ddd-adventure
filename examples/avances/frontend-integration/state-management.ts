// ✅ State Management Hive (Projet Hive)
import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import { HiveApiClient, User, Payment, PaginationRequest, PaginationResponse } from './api-client';

// Types d'état
export interface HiveState {
  auth: AuthState;
  payments: PaymentState;
  organizations: OrganizationState;
  ui: UIState;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  loading: boolean;
  error: string | null;
}

export interface PaymentState {
  payments: Payment[];
  currentPayment: Payment | null;
  loading: boolean;
  error: string | null;
  pagination: PaginationState;
}

export interface OrganizationState {
  organizations: Organization[];
  currentOrganization: Organization | null;
  loading: boolean;
  error: string | null;
}

export interface UIState {
  theme: 'light' | 'dark';
  sidebarOpen: boolean;
  notifications: Notification[];
  loading: boolean;
}

export interface PaginationState {
  page: number;
  pageSize: number;
  total: number;
  totalPages: number;
}

export interface Organization {
  id: string;
  name: string;
  description: string;
  createdAt: string;
  updatedAt: string;
}

export interface Notification {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  message: string;
  timestamp: string;
  read: boolean;
}

// Actions asynchrones pour l'authentification
export const loginUser = createAsyncThunk(
  'auth/loginUser',
  async (credentials: { email: string; password: string }, { rejectWithValue }) => {
    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(credentials),
      });

      if (!response.ok) {
        throw new Error('Login failed');
      }

      const data = await response.json();
      return data;
    } catch (error) {
      return rejectWithValue(error instanceof Error ? error.message : 'Login failed');
    }
  }
);

export const logoutUser = createAsyncThunk(
  'auth/logoutUser',
  async (_, { rejectWithValue }) => {
    try {
      await fetch('/api/auth/logout', {
        method: 'POST',
      });
    } catch (error) {
      return rejectWithValue(error instanceof Error ? error.message : 'Logout failed');
    }
  }
);

// Actions asynchrones pour les paiements
export const fetchPayments = createAsyncThunk(
  'payments/fetchPayments',
  async (params: { organizationId: string; pagination: PaginationRequest }, { rejectWithValue }) => {
    try {
      const { organizationId, pagination } = params;
      const queryParams = new URLSearchParams({
        organization: organizationId,
        page: pagination.page.toString(),
        pageSize: pagination.pageSize.toString(),
      });

      const response = await fetch(`/api/payments?${queryParams}`);
      
      if (!response.ok) {
        throw new Error('Failed to fetch payments');
      }

      const data = await response.json();
      return data;
    } catch (error) {
      return rejectWithValue(error instanceof Error ? error.message : 'Failed to fetch payments');
    }
  }
);

export const createPayment = createAsyncThunk(
  'payments/createPayment',
  async (paymentData: Partial<Payment>, { rejectWithValue }) => {
    try {
      const response = await fetch('/api/payments', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(paymentData),
      });

      if (!response.ok) {
        throw new Error('Failed to create payment');
      }

      const data = await response.json();
      return data;
    } catch (error) {
      return rejectWithValue(error instanceof Error ? error.message : 'Failed to create payment');
    }
  }
);

export const updatePayment = createAsyncThunk(
  'payments/updatePayment',
  async (params: { paymentId: string; paymentData: Partial<Payment> }, { rejectWithValue }) => {
    try {
      const { paymentId, paymentData } = params;
      const response = await fetch(`/api/payments/${paymentId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(paymentData),
      });

      if (!response.ok) {
        throw new Error('Failed to update payment');
      }

      const data = await response.json();
      return data;
    } catch (error) {
      return rejectWithValue(error instanceof Error ? error.message : 'Failed to update payment');
    }
  }
);

export const deletePayment = createAsyncThunk(
  'payments/deletePayment',
  async (paymentId: string, { rejectWithValue }) => {
    try {
      const response = await fetch(`/api/payments/${paymentId}`, {
        method: 'DELETE',
      });

      if (!response.ok) {
        throw new Error('Failed to delete payment');
      }

      return paymentId;
    } catch (error) {
      return rejectWithValue(error instanceof Error ? error.message : 'Failed to delete payment');
    }
  }
);

// Slice pour l'authentification
export const authSlice = createSlice({
  name: 'auth',
  initialState: {
    user: null,
    token: null,
    isAuthenticated: false,
    loading: false,
    error: null,
  } as AuthState,
  reducers: {
    clearError: (state) => {
      state.error = null;
    },
    setUser: (state, action: PayloadAction<User>) => {
      state.user = action.payload;
      state.isAuthenticated = true;
    },
    clearUser: (state) => {
      state.user = null;
      state.isAuthenticated = false;
      state.token = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(loginUser.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(loginUser.fulfilled, (state, action) => {
        state.loading = false;
        state.user = action.payload.user;
        state.token = action.payload.access_token;
        state.isAuthenticated = true;
        state.error = null;
      })
      .addCase(loginUser.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload as string;
        state.isAuthenticated = false;
      })
      .addCase(logoutUser.fulfilled, (state) => {
        state.user = null;
        state.token = null;
        state.isAuthenticated = false;
        state.loading = false;
        state.error = null;
      });
  },
});

// Slice pour les paiements
export const paymentSlice = createSlice({
  name: 'payments',
  initialState: {
    payments: [],
    currentPayment: null,
    loading: false,
    error: null,
    pagination: {
      page: 1,
      pageSize: 20,
      total: 0,
      totalPages: 0,
    },
  } as PaymentState,
  reducers: {
    setCurrentPayment: (state, action: PayloadAction<Payment | null>) => {
      state.currentPayment = action.payload;
    },
    clearError: (state) => {
      state.error = null;
    },
    updatePagination: (state, action: PayloadAction<Partial<PaginationState>>) => {
      state.pagination = { ...state.pagination, ...action.payload };
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchPayments.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchPayments.fulfilled, (state, action) => {
        state.loading = false;
        state.payments = action.payload.data;
        state.pagination = action.payload.pagination;
        state.error = null;
      })
      .addCase(fetchPayments.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload as string;
      })
      .addCase(createPayment.fulfilled, (state, action) => {
        state.payments.unshift(action.payload);
        state.pagination.total += 1;
      })
      .addCase(updatePayment.fulfilled, (state, action) => {
        const index = state.payments.findIndex(p => p.id === action.payload.id);
        if (index !== -1) {
          state.payments[index] = action.payload;
        }
        if (state.currentPayment?.id === action.payload.id) {
          state.currentPayment = action.payload;
        }
      })
      .addCase(deletePayment.fulfilled, (state, action) => {
        state.payments = state.payments.filter(p => p.id !== action.payload);
        state.pagination.total -= 1;
        if (state.currentPayment?.id === action.payload) {
          state.currentPayment = null;
        }
      });
  },
});

// Slice pour les organisations
export const organizationSlice = createSlice({
  name: 'organizations',
  initialState: {
    organizations: [],
    currentOrganization: null,
    loading: false,
    error: null,
  } as OrganizationState,
  reducers: {
    setCurrentOrganization: (state, action: PayloadAction<Organization | null>) => {
      state.currentOrganization = action.payload;
    },
    clearError: (state) => {
      state.error = null;
    },
  },
});

// Slice pour l'interface utilisateur
export const uiSlice = createSlice({
  name: 'ui',
  initialState: {
    theme: 'light' as const,
    sidebarOpen: false,
    notifications: [],
    loading: false,
  } as UIState,
  reducers: {
    toggleTheme: (state) => {
      state.theme = state.theme === 'light' ? 'dark' : 'light';
    },
    toggleSidebar: (state) => {
      state.sidebarOpen = !state.sidebarOpen;
    },
    addNotification: (state, action: PayloadAction<Omit<Notification, 'id' | 'timestamp'>>) => {
      const notification: Notification = {
        ...action.payload,
        id: Date.now().toString(),
        timestamp: new Date().toISOString(),
        read: false,
      };
      state.notifications.unshift(notification);
    },
    markNotificationAsRead: (state, action: PayloadAction<string>) => {
      const notification = state.notifications.find(n => n.id === action.payload);
      if (notification) {
        notification.read = true;
      }
    },
    removeNotification: (state, action: PayloadAction<string>) => {
      state.notifications = state.notifications.filter(n => n.id !== action.payload);
    },
    setLoading: (state, action: PayloadAction<boolean>) => {
      state.loading = action.payload;
    },
  },
});

// Export des actions
export const {
  clearError: clearAuthError,
  setUser,
  clearUser,
} = authSlice.actions;

export const {
  setCurrentPayment,
  clearError: clearPaymentError,
  updatePagination,
} = paymentSlice.actions;

export const {
  setCurrentOrganization,
  clearError: clearOrganizationError,
} = organizationSlice.actions;

export const {
  toggleTheme,
  toggleSidebar,
  addNotification,
  markNotificationAsRead,
  removeNotification,
  setLoading,
} = uiSlice.actions;

// Export des reducers
export const authReducer = authSlice.reducer;
export const paymentReducer = paymentSlice.reducer;
export const organizationReducer = organizationSlice.reducer;
export const uiReducer = uiSlice.reducer;
