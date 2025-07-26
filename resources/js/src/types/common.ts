// Common API response types
export interface ApiResponse<T = any> {
  code: number;
  data: T;
  message: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

// User types
export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  friend_code?: string;
}

// Member types
export interface Member {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

export interface MemberListResponse {
  data: {
    data: Member[];
    total?: number;
    current_page?: number;
  };
}

// Invitation types
export interface Invitation {
  id: number;
  sender_id: number;
  receiver_id: number;
  status: "pending" | "accepted" | "declined" | "cancelled";
  sender?: User;
  receiver?: User;
  created_at: string;
  updated_at: string;
}

export interface InvitationListResponse {
  data: {
    data: Invitation[];
  };
}

// Project types
export interface Project {
  id: number;
  name: string;
  startDate: string;
  endDate: string;
  slug: string;
  task_progress: {
    id: number;
    projectId: number;
    progress: string;
    created_at: string;
    updated_at: string;
  };
  creator?: User | null;
  users?: User[];
}

export interface ProjectListResponse {
  data: {
    data: Project[];
    total?: number;
    current_page?: number;
  };
}

// Event types
export interface MemberEventPayload {
  byUser: number;
  action: string;
  payload: any;
}

// Loading states
export interface LoadingState {
  isLoading: boolean;
  error: string | null;
}
