import { useQuery } from '@tanstack/react-query';
import { MetricsFilter } from '@/types/metric';
import { Server } from '@/types/server';
import { Site } from '@/types/site';

export type SiteMetricPoint = {
  date: string;
  requests: number;
  avg_response_ms: number | null;
  p95_response_ms: number | null;
  error_rate: number | null;
  bytes: number;
  status_2xx: number;
  status_3xx: number;
  status_4xx: number;
  status_5xx: number;
};

export type SiteMetricsResponse = {
  current: SiteMetricPoint | null;
  history: SiteMetricPoint[];
};

const REFETCH_INTERVALS: Record<string, number> = {
  '10m': 60_000,
  '30m': 60_000,
  '1h': 300_000,
  '12h': 300_000,
  '1d': 600_000,
  '7d': 600_000,
  custom: 600_000,
};

export function useSiteMetrics(server: Server, site: Site, filter?: MetricsFilter) {
  const resolved: MetricsFilter = filter ?? { period: '10m' };
  const refetchInterval = REFETCH_INTERVALS[resolved.period] ?? 60_000;

  return useQuery<SiteMetricsResponse>({
    queryKey: ['site-metrics', site.id, resolved.period, resolved.from, resolved.to],
    queryFn: async () => {
      const response = await fetch(route('site-metrics.json', { server: server.id, site: site.id, ...resolved }));
      if (!response.ok) {
        throw new Error('Failed to fetch site metrics');
      }
      return response.json();
    },
    refetchInterval,
    retry: false,
  });
}
