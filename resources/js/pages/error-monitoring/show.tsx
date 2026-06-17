import { Head, usePage, router } from '@inertiajs/react';
import { Server } from '@/types/server';
import { ErrorIssue, ErrorEvent } from '@/types/error-issue';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { PaginatedData } from '@/types';
import { format } from 'date-fns';
import {
  CheckCircleIcon,
  EyeOffIcon,
  AlertTriangleIcon,
  UserIcon,
  GlobeIcon,
  CalendarIcon,
  GitCommitIcon,
} from 'lucide-react';

type Page = {
  server: Server;
  issue: ErrorIssue;
  events: PaginatedData<ErrorEvent>;
};

export default function ShowError() {
  const page = usePage<Page>();
  const { issue, events, server } = page.props;

  const handleResolve = () => {
    router.post(route('errors.resolve', { server: server.id, errorIssue: issue.id }));
  };

  const handleIgnore = () => {
    router.post(route('errors.ignore', { server: server.id, errorIssue: issue.id }));
  };

  const badgeVariant =
    issue.status_color === 'danger' ? 'destructive' : issue.status_color === 'success' ? 'success' : 'gray';

  return (
    <ServerLayout>
      <Head title={`${issue.exception_class} - Errors - ${server.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <div>
            <Heading
              title={issue.exception_class.split('\\').pop() || issue.exception_class}
              description={issue.normalized_message}
            />
            <div className="mt-2 flex items-center gap-3">
              <Badge variant={badgeVariant}>{issue.status}</Badge>
              <span className="text-muted-foreground text-sm">
                {issue.count} {issue.count === 1 ? 'event' : 'events'}
              </span>
              {issue.affected_users > 0 && (
                <span className="text-muted-foreground flex items-center gap-1 text-sm">
                  <UserIcon className="h-3 w-3" />
                  {issue.affected_users} {issue.affected_users === 1 ? 'user' : 'users'} affected
                </span>
              )}
              {issue.site && (
                <span className="text-muted-foreground flex items-center gap-1 text-sm">
                  <GlobeIcon className="h-3 w-3" />
                  {issue.site.domain}
                </span>
              )}
              {issue.first_release && (
                <span className="text-muted-foreground flex items-center gap-1 text-sm">
                  <GitCommitIcon className="h-3 w-3" />
                  First seen in {issue.first_release.substring(0, 7)}
                </span>
              )}
            </div>
          </div>
          <div className="flex items-center gap-2">
            {issue.status === 'unresolved' && (
              <>
                <Button onClick={handleResolve} variant="outline" size="sm">
                  <CheckCircleIcon className="h-4 w-4" />
                  Resolve
                </Button>
                <Button onClick={handleIgnore} variant="outline" size="sm">
                  <EyeOffIcon className="h-4 w-4" />
                  Ignore
                </Button>
              </>
            )}
          </div>
        </HeaderContainer>

        <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
          <Card>
            <CardContent className="pt-4">
              <div className="text-muted-foreground text-xs">First Seen</div>
              <div className="flex items-center gap-1 text-sm font-medium">
                <CalendarIcon className="h-3 w-3" />
                {format(new Date(issue.first_seen_at), 'MMM d, HH:mm')}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-4">
              <div className="text-muted-foreground text-xs">Last Seen</div>
              <div className="flex items-center gap-1 text-sm font-medium">
                <CalendarIcon className="h-3 w-3" />
                {format(new Date(issue.last_seen_at), 'MMM d, HH:mm')}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-4">
              <div className="text-muted-foreground text-xs">Total Events</div>
              <div className="text-sm font-medium">{issue.count}</div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-4">
              <div className="text-muted-foreground text-xs">Top Frame</div>
              <div className="truncate font-mono text-xs" title={issue.top_frame || ''}>
                {issue.top_frame || '—'}
              </div>
            </CardContent>
          </Card>
        </div>

        <div className="space-y-4">
          <h2 className="text-lg font-semibold">Events</h2>
          {events.data.length === 0 ? (
            <p className="text-muted-foreground text-sm">No events recorded.</p>
          ) : (
            events.data.map((event) => (
              <Card key={event.id} className="border-l-4 border-l-destructive">
                <CardHeader className="pb-2">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <AlertTriangleIcon className="text-destructive h-4 w-4" />
                      <span className="text-muted-foreground text-xs">
                        {format(new Date(event.occurred_at), 'MMM d, yyyy HH:mm:ss')}
                      </span>
                    </div>
                    <div className="text-muted-foreground flex items-center gap-2 text-xs">
                      {event.environment && (
                        <Badge variant="outline" className="text-xs">
                          {event.environment}
                        </Badge>
                      )}
                      {event.release && (
                        <span className="flex items-center gap-1">
                          <GitCommitIcon className="h-3 w-3" />
                          {event.release.substring(0, 7)}
                        </span>
                      )}
                      {event.user_email && (
                        <span className="flex items-center gap-1">
                          <UserIcon className="h-3 w-3" />
                          {event.user_email}
                        </span>
                      )}
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <p className="mb-2 text-sm font-medium">{event.message}</p>
                  {event.url && (
                    <div className="text-muted-foreground mb-2 flex items-center gap-1 text-xs">
                      {event.request_method && (
                        <Badge variant="gray" className="mr-1 text-xs">
                          {event.request_method}
                        </Badge>
                      )}
                      <span className="truncate">{event.url}</span>
                    </div>
                  )}
                  <details className="mt-2">
                    <summary className="text-muted-foreground hover:text-foreground cursor-pointer text-xs">
                      Stack Trace
                    </summary>
                    <pre className="bg-muted mt-2 max-h-96 overflow-x-auto whitespace-pre-wrap rounded-md p-3 font-mono text-xs">
                      {event.stack_trace}
                    </pre>
                  </details>
                  {event.context && Object.keys(event.context).length > 0 && (
                    <details className="mt-2">
                      <summary className="text-muted-foreground hover:text-foreground cursor-pointer text-xs">
                        Context
                      </summary>
                      <pre className="bg-muted mt-2 max-h-48 overflow-x-auto whitespace-pre-wrap rounded-md p-3 font-mono text-xs">
                        {JSON.stringify(event.context, null, 2)}
                      </pre>
                    </details>
                  )}
                </CardContent>
              </Card>
            ))
          )}
        </div>
      </Container>
    </ServerLayout>
  );
}
