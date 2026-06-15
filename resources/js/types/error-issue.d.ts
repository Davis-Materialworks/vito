export interface ErrorIssue {
  id: number;
  server_id: number;
  site_id: number;
  fingerprint: string;
  exception_class: string;
  normalized_message: string;
  top_frame: string | null;
  status: string;
  status_color: 'danger' | 'success' | 'gray';
  count: number;
  affected_users: number;
  first_seen_at: string;
  last_seen_at: string;
  resolved_at: string | null;
  events_count: number;
  site?: { id: number; domain: string };
  latest_event?: ErrorEvent;
  created_at: string;
}

export interface ErrorEvent {
  id: number;
  error_issue_id: number;
  server_id: number;
  site_id: number;
  environment: string | null;
  release: string | null;
  exception_class: string;
  message: string;
  stack_trace: string;
  url: string | null;
  request_method: string | null;
  user_id: number | null;
  user_email: string | null;
  context: Record<string, unknown> | null;
  occurred_at: string;
  created_at: string;
}
