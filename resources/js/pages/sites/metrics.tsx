import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Site } from '@/types/site';
import { Server } from '@/types/server';
import { MetricsFilter } from '@/types/metric';
import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import Filter from '@/pages/monitoring/components/filter';
import { StatsChart } from '@/pages/sites/components/stats-chart';
import { useSiteMetrics } from '@/pages/sites/components/use-site-metrics';

type Page = {
  server: Server;
  site: Site;
};

function formatNumber(value: unknown): string {
  return Number(value).toLocaleString();
}

function formatMs(value: unknown): string {
  return `${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })} ms`;
}

function formatPercent(value: unknown): string {
  return `${Number(value).toFixed(2)}%`;
}

function formatTime(value: string): string {
  const date = new Date(value.replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return date.toLocaleString('en-US', { hour: '2-digit', minute: '2-digit', month: 'short', day: 'numeric' });
}

export default function SiteMetrics() {
  const page = usePage<Page>();
  const { server, site } = page.props;

  return (
    <ServerLayout>
      <Head title={`Metrics - ${site.domain} - ${server.name}`} />
      <Container className="max-w-5xl">
        <MetricsView server={server} site={site} />
      </Container>
    </ServerLayout>
  );
}

function MetricsView({ server, site }: { server: Server; site: Site }) {
  const [filter, setFilter] = useState<MetricsFilter>();
  const { data, isLoading, isError } = useSiteMetrics(server, site, filter);

  const history = data?.history ?? [];
  const current = data?.current ?? null;
  const chartData = history as unknown as Array<Record<string, string | number>>;
  const deploys = data?.deploys ?? [];

  return (
    <>
      <HeaderContainer>
        <Heading title="Metrics" description="Request metrics from this site's nginx access log" />
        <div className="flex items-center gap-2">
          <Filter onValueChange={setFilter} />
        </div>
      </HeaderContainer>

      <SiteBanners site={site} />

      {current && (
        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
          <MetricCard title="Requests" value={formatNumber(current.requests)} />
          <MetricCard title="Avg response" value={current.avg_response_ms != null ? formatMs(current.avg_response_ms) : 'N/A'} />
          <MetricCard title="p95 response" value={current.p95_response_ms != null ? formatMs(current.p95_response_ms) : 'N/A'} />
          <MetricCard title="Error rate" value={current.error_rate != null ? formatPercent(current.error_rate) : 'N/A'} />
        </div>
      )}

      {isLoading && (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <Skeleton className="h-[200px]" />
          <Skeleton className="h-[200px]" />
          <Skeleton className="h-[200px]" />
          <Skeleton className="h-[200px]" />
        </div>
      )}

      {isError && (
        <Card>
          <CardContent className="text-muted-foreground p-6 text-sm">Failed to load metrics. Try a different range.</CardContent>
        </Card>
      )}

      {!isLoading && !isError && history.length === 0 && (
        <Card>
          <CardContent className="text-muted-foreground p-6 text-sm">
            No metrics yet. Request metrics are collected every minute from this site's nginx access log.
          </CardContent>
        </Card>
      )}

      {history.length > 0 && (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <StatsChart title="Requests" color="var(--color-chart-1)" dataKey="requests" labelKey="date" data={chartData} formatLabel={formatTime} valueFormatter={formatNumber} markers={deploys} />
          <StatsChart title="Avg response (ms)" color="var(--color-chart-2)" dataKey="avg_response_ms" labelKey="date" data={chartData} formatLabel={formatTime} valueFormatter={formatMs} markers={deploys} />
          <StatsChart title="p95 response (ms)" color="var(--color-chart-3)" dataKey="p95_response_ms" labelKey="date" data={chartData} formatLabel={formatTime} valueFormatter={formatMs} markers={deploys} />
          <StatsChart title="Error rate (%)" color="var(--color-chart-4)" dataKey="error_rate" labelKey="date" data={chartData} formatLabel={formatTime} valueFormatter={formatPercent} markers={deploys} />
          <StatsChart title="4xx responses" color="var(--color-chart-4)" dataKey="status_4xx" labelKey="date" data={chartData} formatLabel={formatTime} valueFormatter={formatNumber} markers={deploys} />
          <StatsChart title="5xx responses" color="var(--color-chart-5)" dataKey="status_5xx" labelKey="date" data={chartData} formatLabel={formatTime} valueFormatter={formatNumber} markers={deploys} />
        </div>
      )}
    </>
  );
}

function MetricCard({ title, value }: { title: string; value: string }) {
  return (
    <Card>
      <CardContent className="space-y-2 p-4">
        <h2 className="text-muted-foreground text-sm">{title}</h2>
        <span className="text-2xl font-bold">{value}</span>
      </CardContent>
    </Card>
  );
}
