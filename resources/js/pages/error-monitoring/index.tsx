import { Head, Link, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { ErrorIssue } from '@/types/error-issue';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { VitoTable } from '@/components/vito-table';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { EyeIcon } from 'lucide-react';

type Page = {
  server: Server;
  issues: InertiaTableData;
};

export default function ErrorMonitoring() {
  const page = usePage<Page>();

  return (
    <ServerLayout>
      <Head title={`Errors - ${page.props.server.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Error Monitoring" description="Track application errors grouped by issue" />
        </HeaderContainer>

        <VitoTable
          tableData={page.props.issues}
          actions={(row: Row) => {
            const issue = row as unknown as ErrorIssue;
            return (
              <div className="flex items-center gap-2">
                <Badge
                  variant={
                    issue.status_color === 'danger' ? 'destructive' : issue.status_color === 'success' ? 'success' : 'gray'
                  }
                >
                  {issue.status}
                </Badge>
                <Link href={route('errors.show', { server: page.props.server.id, errorIssue: issue.id })} prefetch>
                  <Button variant="outline" size="sm">
                    <EyeIcon />
                  </Button>
                </Link>
              </div>
            );
          }}
        />
      </Container>
    </ServerLayout>
  );
}
